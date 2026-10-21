<?php

namespace Tally\Accounting;

use Tally\Accounting\Events\VoucherWritten;
use Tally\Audit\AuditLogger;
use Tally\Models\ApprovalPolicy;
use Tally\Models\ApprovalRequest;
use Tally\Models\Bill;
use Tally\Models\Branch;
use Tally\Models\BusinessUnit;
use Tally\Models\Company;
use Tally\Models\Department;
use Tally\Models\CostCentre;
use Tally\Models\Currency;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;
use Tally\Models\ManufacturingOrder;
use App\Models\User;
use Tally\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherEngine
{
    public function __construct(
        private readonly VoucherNumberer $numbers,
        private readonly BillService $bills,
        private readonly BillAllocationService $allocations,
        private readonly PeriodLockService $periods,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(
        Company $company,
        Branch $branch,
        FinancialYear $financialYear,
        User $user,
        VoucherType $type,
        array $input,
        bool $post,
        ?Voucher $voucher = null,
        ?string $assignedNumber = null,
    ): Voucher {
        if ($voucher && ! $voucher->isDraft()) {
            app(AuditLogger::class)->record('change_blocked', 'accounting', $voucher, 'Posted and cancelled vouchers cannot be changed.');

            throw ValidationException::withMessages([
                'status' => 'Posted and cancelled vouchers cannot be changed.',
            ]);
        }

        $outcome = DB::transaction(function () use ($company, $branch, $financialYear, $user, $type, $input, $post, $voucher, $assignedNumber) {
            $this->assertContext($company, $branch, $financialYear);

            if ($voucher) {
                $this->assertDraft($voucher);
                $this->assertSameContext($voucher, $company, $branch, $financialYear);
                $type = $voucher->voucher_type;
            }

            $date = $this->date($financialYear, (string) ($input['voucher_date'] ?? ''));
            $this->periods->assertDateOpen($company, $date, $financialYear);
            $input = $this->assertFx($company, $input);
            $lines = $this->lines($company, $type, $input['entries'] ?? [], $post, $input);
            [$debitCents, $creditCents] = $this->totals($lines);

            if ($voucher) {
                $voucher->entries()->delete();
                $voucher->update([
                    'voucher_date' => $date,
                    'reference_number' => $this->blank($input['reference_number'] ?? null),
                    'narration' => $this->blank($input['narration'] ?? null),
                    'total_debit' => Money::format($debitCents),
                    'total_credit' => Money::format($creditCents),
                ] + $this->moneyMeta($input) + $this->linkMeta($input));
            } else {
                $voucher = Voucher::query()->create([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'financial_year_id' => $financialYear->id,
                    'voucher_type' => $type,
                    'voucher_number' => $this->assignedNumber($assignedNumber, $company, $branch, $financialYear, $type),
                    'voucher_date' => $date,
                    'reference_number' => $this->blank($input['reference_number'] ?? null),
                    'narration' => $this->blank($input['narration'] ?? null),
                    'status' => VoucherStatus::Draft,
                    'approval_status' => 'none',
                    'total_debit' => Money::format($debitCents),
                    'total_credit' => Money::format($creditCents),
                    'created_by' => $user->id,
                ] + $this->moneyMeta($input) + $this->linkMeta($input));
            }

            $voucher->entries()->createMany($lines);
            $this->allocate($voucher, $type, $input['allocations'] ?? []);

            $awaitingApproval = false;
            if ($post) {
                $fresh = $voucher->fresh('entries');
                $this->assertLinesReadyToPost($fresh);
                if ($this->queueForApprovalIfRequired($fresh, $user)) {
                    $awaitingApproval = true;
                } else {
                    $this->applyPostedStatus($fresh);
                    $this->bills->syncPostedVoucher($fresh->fresh(['entries.ledger.accountGroup', 'financialYear']));
                }
            }

            $voucher = $voucher->fresh(['entries.ledger', 'creator']);
            VoucherWritten::dispatch($voucher);

            return ['voucher' => $voucher, 'awaiting_approval' => $awaitingApproval];
        });

        if ($outcome['awaiting_approval']) {
            throw ValidationException::withMessages([
                'status' => 'Awaiting approval',
            ]);
        }

        return $outcome['voucher'];
    }

    public function post(Voucher $voucher): Voucher
    {
        $outcome = DB::transaction(function () use ($voucher) {
            $voucher = Voucher::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();

            if ($voucher->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => 'A cancelled voucher cannot be posted again.',
                ]);
            }

            $this->assertDraft($voucher);
            $this->assertContext($voucher->company, $voucher->branch, $voucher->financialYear);
            $date = $this->date($voucher->financialYear, $voucher->voucher_date->toDateString());
            $this->periods->assertDateOpen($voucher->company, $date, $voucher->financialYear);

            $awaitingApproval = false;
            $this->assertLinesReadyToPost($voucher);
            if ($this->queueForApprovalIfRequired($voucher, $voucher->creator)) {
                $awaitingApproval = true;
            } else {
                $this->applyPostedStatus($voucher);
                $this->bills->syncPostedVoucher($voucher->fresh(['entries.ledger.accountGroup', 'financialYear']));
            }

            $voucher = $voucher->fresh(['entries.ledger', 'creator']);
            VoucherWritten::dispatch($voucher);

            return ['voucher' => $voucher, 'awaiting_approval' => $awaitingApproval];
        });

        if ($outcome['awaiting_approval']) {
            throw ValidationException::withMessages([
                'status' => 'Awaiting approval',
            ]);
        }

        return $outcome['voucher'];
    }

    public function cancel(Voucher $voucher, bool $withDocument = false): Voucher
    {
        return DB::transaction(function () use ($voucher, $withDocument) {
            $voucher = Voucher::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();

            if (! $voucher->isPosted()) {
                throw ValidationException::withMessages([
                    'status' => 'Only a posted voucher can be cancelled.',
                ]);
            }

            if (! $withDocument) {
                $this->assertStandalone($voucher);
            }

            $this->periods->assertDateOpen(
                $voucher->company,
                $voucher->voucher_date->toDateString(),
                $voucher->financialYear,
            );

            $voucher->update([
                'status' => VoucherStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $voucher = $voucher->fresh(['entries.ledger', 'creator']);
            VoucherWritten::dispatch($voucher);

            return $voucher;
        });
    }

    /**
     * Reopen a posted standalone voucher, replace its lines, and post it again.
     * The voucher number stays. Invoice, stock, and manufacturing vouchers are refused.
     *
     * @param  array<string, mixed>  $input
     */
    public function alter(
        Company $company,
        Branch $branch,
        FinancialYear $financialYear,
        User $user,
        array $input,
        Voucher $voucher,
    ): Voucher {
        return DB::transaction(function () use ($company, $branch, $financialYear, $user, $input, $voucher) {
            $voucher = Voucher::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();

            if (! $voucher->isPosted()) {
                throw ValidationException::withMessages([
                    'status' => 'Only a posted voucher can be altered.',
                ]);
            }

            $this->assertStandalone($voucher);
            $this->assertSameContext($voucher, $company, $branch, $financialYear);
            $this->periods->assertDateOpen($company, $voucher->voucher_date->toDateString(), $financialYear);
            $entryIds = $voucher->entries()->pluck('id');

            if ($entryIds->isNotEmpty()) {
                Bill::query()->whereIn('voucher_entry_id', $entryIds)->whereDoesntHave('allocations')->delete();
            }

            $voucher->update([
                'status' => VoucherStatus::Draft,
                'posted_at' => null,
            ]);

            return $this->save($company, $branch, $financialYear, $user, $voucher->voucher_type, $input, true, $voucher->fresh());
        });
    }

    public function deleteDraft(Voucher $voucher): void
    {
        $current = Voucher::query()->whereKey($voucher->id)->firstOrFail();

        if (! $current->isDraft()) {
            app(AuditLogger::class)->blocked($current, 'Posted and cancelled vouchers cannot be deleted.');

            throw ValidationException::withMessages([
                'status' => 'Posted and cancelled vouchers cannot be changed.',
            ]);
        }

        DB::transaction(function () use ($voucher) {
            $voucher = Voucher::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($voucher);
            $voucher->delete();
        });
    }

    private function markPosted(Voucher $voucher): void
    {
        $this->assertLinesReadyToPost($voucher);

        if ($this->queueForApprovalIfRequired($voucher, $voucher->creator)) {
            throw ValidationException::withMessages([
                'status' => 'Awaiting approval',
            ]);
        }

        $this->applyPostedStatus($voucher);
    }

    private function assertLinesReadyToPost(Voucher $voucher): void
    {
        $voucher->loadMissing(['company', 'entries.ledger.accountGroup.parent']);
        $stored = [];

        foreach ($voucher->entries as $entry) {
            $stored[] = [
                'ledger_id' => $entry->ledger_id,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'narration' => $entry->narration,
                'reference' => $entry->reference,
                'cost_centre_id' => $entry->cost_centre_id,
                'business_unit_id' => $entry->business_unit_id,
                'department_id' => $entry->department_id,
            ];
        }

        $this->lines($voucher->company, $voucher->voucher_type, $stored, true, [
            'currency_id' => $voucher->currency_id,
            'exchange_rate' => $voucher->exchange_rate,
        ]);
    }

    private function applyPostedStatus(Voucher $voucher): void
    {
        $voucher->update([
            'status' => VoucherStatus::Posted,
            'posted_at' => now(),
        ]);
    }

    /**
     * When an active voucher approval policy matches the amount and the voucher
     * is not yet approved, keep it draft, mark pending, and queue a request.
     *
     * @return bool true when posting must wait for approval
     */
    private function queueForApprovalIfRequired(Voucher $voucher, ?User $user = null): bool
    {
        if (($voucher->approval_status ?? 'none') === 'approved') {
            return false;
        }

        $amount = (string) $voucher->total_debit;
        $policy = ApprovalPolicy::query()
            ->where('company_id', $voucher->company_id)
            ->where('document_type', 'voucher')
            ->where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->where(function ($q) use ($amount) {
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
            })
            ->orderByDesc('min_amount')
            ->first();

        if (! $policy) {
            return false;
        }

        $voucher->update([
            'status' => VoucherStatus::Draft,
            'approval_status' => 'pending',
            'posted_at' => null,
        ]);

        $exists = ApprovalRequest::query()
            ->where('company_id', $voucher->company_id)
            ->where('document_type', 'voucher')
            ->where('document_id', $voucher->id)
            ->where('status', 'pending')
            ->exists();

        if (! $exists) {
            ApprovalRequest::query()->create([
                'company_id' => $voucher->company_id,
                'approval_policy_id' => $policy->id,
                'document_type' => 'voucher',
                'document_id' => $voucher->id,
                'amount' => $amount,
                'current_level' => 1,
                'status' => 'pending',
                'requested_by' => $user?->id ?? $voucher->created_by,
                'remarks' => 'Auto-queued on post',
            ]);
        }

        return true;
    }

    /**
     * Reverse a posted standalone voucher by posting swapped debit/credit lines.
     */
    public function reverse(Voucher $voucher, User $user, ?string $date = null, ?string $narration = null): Voucher
    {
        if (! $voucher->isPosted()) {
            throw ValidationException::withMessages(['status' => 'Only a posted voucher can be reversed.']);
        }

        if ($voucher->reversed_voucher_id) {
            throw ValidationException::withMessages(['voucher' => 'This voucher is already reversed.']);
        }

        $this->assertStandalone($voucher);
        $voucher->load('entries');

        $reverseDate = $date ?: ($voucher->reverses_on?->toDateString() ?: now()->toDateString());

        $reverse = $this->save(
            $voucher->company,
            $voucher->branch,
            $voucher->financialYear,
            $user,
            $voucher->voucher_type,
            [
                'voucher_date' => $reverseDate,
                'reference_number' => $voucher->voucher_number,
                'narration' => $narration ?: 'Reversal of '.$voucher->voucher_number,
                'entries' => $voucher->entries->map(fn ($entry) => [
                    'ledger_id' => $entry->ledger_id,
                    'debit' => $entry->credit,
                    'credit' => $entry->debit,
                    'cost_centre_id' => $entry->cost_centre_id,
                    'business_unit_id' => $entry->business_unit_id,
                ])->all(),
            ],
            true,
        );

        $voucher->update(['reversed_voucher_id' => $reverse->id]);

        return $reverse;
    }

    /**
     * Post a correcting voucher linked to the original without deleting history.
     *
     * @param  array<string, mixed>  $input
     */
    public function correct(
        Company $company,
        Branch $branch,
        FinancialYear $financialYear,
        User $user,
        Voucher $original,
        array $input,
    ): Voucher {
        if (! $original->isPosted()) {
            throw ValidationException::withMessages(['status' => 'Only a posted voucher can be corrected.']);
        }

        $this->assertStandalone($original);

        $correction = $this->save(
            $company,
            $branch,
            $financialYear,
            $user,
            $original->voucher_type,
            $input + [
                'reference_number' => $input['reference_number'] ?? $original->voucher_number,
                'narration' => $input['narration'] ?? 'Correction of '.$original->voucher_number,
            ],
            true,
        );

        $correction->update(['corrected_voucher_id' => $original->id]);

        return $correction->fresh();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function moneyMeta(array $input): array
    {
        $blankId = fn (string $key): ?int => ($input[$key] ?? '') === '' || $input[$key] === null ? null : (int) $input[$key];

        return [
            'currency_id' => $blankId('currency_id'),
            'exchange_rate' => ($input['exchange_rate'] ?? '') === '' || $input['exchange_rate'] === null ? null : $input['exchange_rate'],
            'foreign_total' => $input['foreign_total'] ?? null,
            'payment_request_id' => $blankId('payment_request_id'),
            'merchant_profile_id' => $blankId('merchant_profile_id'),
            'name_on_receipt' => $this->blank($input['name_on_receipt'] ?? null),
            'is_post_dated' => filter_var($input['is_post_dated'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_optional' => filter_var($input['is_optional'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_memo' => filter_var($input['is_memo'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'voucher_class_id' => $blankId('voucher_class_id'),
            'nature_of_payment' => $this->blank($input['nature_of_payment'] ?? null),
            'reverses_on' => ($input['reverses_on'] ?? '') === '' ? null : $input['reverses_on'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function linkMeta(array $input): array
    {
        return [
            'intercompany_pair_id' => $this->blank($input['intercompany_pair_id'] ?? null),
            'counterparty_company_id' => ($input['counterparty_company_id'] ?? '') === '' || ($input['counterparty_company_id'] ?? null) === null
                ? null
                : (int) $input['counterparty_company_id'],
            'corrected_voucher_id' => ($input['corrected_voucher_id'] ?? '') === '' || ($input['corrected_voucher_id'] ?? null) === null
                ? null
                : (int) $input['corrected_voucher_id'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function assertFx(Company $company, array $input): array
    {
        $currencyId = $input['currency_id'] ?? null;
        if ($currencyId === '' || $currencyId === null) {
            return $input;
        }

        $currency = Currency::query()->where('company_id', $company->id)->whereKey($currencyId)->first();
        if (! $currency) {
            throw ValidationException::withMessages(['currency_id' => 'Select a currency of the current company.']);
        }

        if ($currency->is_base) {
            return $input;
        }

        $rate = $input['exchange_rate'] ?? null;
        if ($rate === null || $rate === '' || (float) $rate <= 0) {
            $date = (string) ($input['voucher_date'] ?? now()->toDateString());
            $lookedUp = app(FxRateBook::class)->rateOn($company, $currency, $date);
            if ($lookedUp > 0) {
                $input['exchange_rate'] = $lookedUp;

                return $input;
            }

            throw ValidationException::withMessages([
                'exchange_rate' => 'Enter an exchange rate when the voucher currency is not the base currency.',
            ]);
        }

        return $input;
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @param  array<string, mixed>  $input
     * @return list<array<string, mixed>>
     */
    private function lines(Company $company, VoucherType $type, array $entries, bool $post, array $input = []): array
    {
        $lines = [];
        $errors = [];
        $debitCents = 0;
        $creditCents = 0;
        $hasDebit = false;
        $hasCredit = false;
        $rate = (float) ($input['exchange_rate'] ?? 1);
        if ($rate <= 0) {
            $rate = 1.0;
        }

        foreach (array_values($entries) as $index => $entry) {
            $ledgerId = $entry['ledger_id'] ?? null;
            try {
                $debit = Money::cents($entry['debit'] ?? 0);
                $credit = Money::cents($entry['credit'] ?? 0);
            } catch (\InvalidArgumentException) {
                $errors["entries.$index.debit"] = 'Enter an amount with up to 2 decimal places.';

                continue;
            }

            if (! $ledgerId && $debit === 0 && $credit === 0) {
                continue;
            }

            $ledger = Ledger::query()
                ->with('accountGroup.parent')
                ->where('company_id', $company->id)
                ->whereKey($ledgerId)
                ->first();

            if (! $ledger || ! $ledger->is_active) {
                $errors["entries.$index.ledger_id"] = 'Select an active ledger from the current company.';

                continue;
            }

            if ($debit < 0 || $credit < 0 || ($debit > 0 && $credit > 0) || ($debit === 0 && $credit === 0)) {
                $errors["entries.$index.debit"] = 'Each line needs either a debit or a credit, not both and not zero.';

                continue;
            }

            $role = $this->roleError($company, $type, $ledger, $debit, $credit);

            if ($role) {
                $errors["entries.$index.ledger_id"] = $role;

                continue;
            }

            $centreId = $entry['cost_centre_id'] ?? null;
            $centreId = $centreId === '' || $centreId === null ? null : (int) $centreId;

            if (! $company->usesCostCentres()) {
                $centreId = null;
            }

            if ($centreId) {
                $centre = CostCentre::query()
                    ->where('company_id', $company->id)
                    ->whereKey($centreId)
                    ->where('is_active', true)
                    ->exists();

                if (! $centre) {
                    $errors["entries.$index.cost_centre_id"] = 'Select an active cost centre from the current company.';

                    continue;
                }
            }

            $unitId = $entry['business_unit_id'] ?? null;
            $unitId = $unitId === '' || $unitId === null ? null : (int) $unitId;

            if ($unitId) {
                $unitOk = BusinessUnit::query()
                    ->where('company_id', $company->id)
                    ->whereKey($unitId)
                    ->where('is_active', true)
                    ->exists();

                if (! $unitOk) {
                    $errors["entries.$index.business_unit_id"] = 'Select an active business unit from the current company.';

                    continue;
                }
            }

            $departmentId = $entry['department_id'] ?? null;
            $departmentId = $departmentId === '' || $departmentId === null ? null : (int) $departmentId;

            if ($departmentId) {
                $deptOk = Department::query()
                    ->where('company_id', $company->id)
                    ->whereKey($departmentId)
                    ->where('is_active', true)
                    ->exists();

                if (! $deptOk) {
                    $errors["entries.$index.department_id"] = 'Select an active department from the current company.';

                    continue;
                }
            }

            $hasDebit = $hasDebit || $debit > 0;
            $hasCredit = $hasCredit || $credit > 0;
            $debitCents += $debit;
            $creditCents += $credit;

            $lines[] = [
                'ledger_id' => $ledger->id,
                'line_number' => count($lines) + 1,
                'debit' => Money::format($debit),
                'credit' => Money::format($credit),
                'base_debit' => Money::format((int) round($debit * $rate)),
                'base_credit' => Money::format((int) round($credit * $rate)),
                'narration' => $this->blank($entry['narration'] ?? null),
                'reference' => $this->blank($entry['reference'] ?? null),
                'cost_centre_id' => $centreId,
                'business_unit_id' => $unitId,
                'department_id' => $departmentId,
            ];
        }

        if ($lines === []) {
            $errors['entries'] = 'Add at least one ledger line.';
        }

        if ($post && $errors === [] && $debitCents !== $creditCents) {
            $balanced = $this->balanceWithRoundOff($company, $lines, $debitCents, $creditCents, $hasDebit, $hasCredit, $rate);
            $lines = $balanced['lines'];
            $debitCents = $balanced['debit'];
            $creditCents = $balanced['credit'];
            $hasDebit = $balanced['hasDebit'];
            $hasCredit = $balanced['hasCredit'];
        }

        if ($post && $errors === [] && ($debitCents !== $creditCents || ! $hasDebit || ! $hasCredit)) {
            $errors['entries'] = 'Total debit must equal total credit before the voucher can be posted.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $lines;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{0: int, 1: int}
     */
    private function totals(array $lines): array
    {
        $debit = 0;
        $credit = 0;

        foreach ($lines as $line) {
            $debit += Money::cents($line['debit']);
            $credit += Money::cents($line['credit']);
        }

        return [$debit, $credit];
    }

    private function roleError(Company $company, VoucherType $type, Ledger $ledger, int $debit, int $credit): ?string
    {
        if ((int) ($company->round_off_ledger_id ?? 0) === (int) $ledger->id) {
            return null;
        }

        $cash = $ledger->isCashOrBank();

        return match ($type) {
            VoucherType::Payment => $credit > 0
                ? ($cash ? null : 'The payment source must be a Cash or Bank ledger.')
                : ($cash ? 'Paid-to ledgers cannot be Cash or Bank. Move cash and bank balances with a Contra voucher.' : null),
            VoucherType::Receipt => $debit > 0
                ? ($cash ? null : 'The receipt must be deposited into a Cash or Bank ledger.')
                : ($cash ? 'Received-from ledgers cannot be Cash or Bank. Move cash and bank balances with a Contra voucher.' : null),
            VoucherType::Contra => $cash ? null : 'Contra vouchers can only use Cash and Bank ledgers.',
            VoucherType::Transfer => null,
            VoucherType::Adjustment => null,
            VoucherType::Sales => $debit > 0
                ? ($ledger->isCustomer() ? null : 'The sales customer must be a ledger under Sundry Debtors.')
                : ($ledger->isSalesAccount() || $ledger->belongsToGroup('DUTIES') ? null : 'The sales credit must be a Sales Accounts or Duties & Taxes ledger.'),
            VoucherType::Purchase => $debit > 0
                ? ($ledger->isPurchaseAccount() || $ledger->belongsToGroup('DUTIES') ? null : 'The purchase debit must be a Purchase Accounts or Duties & Taxes ledger.')
                : ($ledger->isSupplier() ? null : 'The supplier must be a ledger under Sundry Creditors.'),
            VoucherType::CreditNote => $credit > 0
                ? ($ledger->isCustomer() ? null : 'The credit note customer must be a ledger under Sundry Debtors.')
                : ($ledger->isSalesAccount() || $ledger->belongsToGroup('DUTIES') ? null : 'The credit note debit must be a Sales Accounts or Duties & Taxes ledger.'),
            VoucherType::DebitNote => $debit > 0
                ? ($ledger->isSupplier() ? null : 'The debit note supplier must be a ledger under Sundry Creditors.')
                : ($ledger->isPurchaseAccount() || $ledger->belongsToGroup('DUTIES') ? null : 'The debit note credit must be a Purchase Accounts or Duties & Taxes ledger.'),
            default => null,
        };
    }

    private function date(FinancialYear $financialYear, string $value): string
    {
        if ($value === '' || ! strtotime($value)) {
            throw ValidationException::withMessages([
                'voucher_date' => 'Enter a voucher date.',
            ]);
        }

        $date = date('Y-m-d', strtotime($value));
        $start = $financialYear->start_date->toDateString();
        $end = $financialYear->end_date->toDateString();

        if ($date < $start || $date > $end) {
            throw ValidationException::withMessages([
                'voucher_date' => 'The voucher date must fall in '.$financialYear->name.' ('.$financialYear->rangeLabel().').',
            ]);
        }

        return $date;
    }

    private function assertContext(Company $company, Branch $branch, FinancialYear $financialYear): void
    {
        if (! $company->is_active || $branch->company_id !== $company->id || ! $branch->is_active) {
            throw ValidationException::withMessages([
                'branch_id' => 'Select an active branch of the current company.',
            ]);
        }

        if ($financialYear->company_id !== $company->id || ! $financialYear->is_active || ($financialYear->is_closed && $company->locksClosedYear())) {
            throw ValidationException::withMessages([
                'financial_year_id' => 'Select an open financial year of the current company.',
            ]);
        }
    }

    private function assertSameContext(Voucher $voucher, Company $company, Branch $branch, FinancialYear $financialYear): void
    {
        if ($voucher->company_id !== $company->id || $voucher->branch_id !== $branch->id || $voucher->financial_year_id !== $financialYear->id) {
            throw ValidationException::withMessages([
                'voucher' => 'This voucher belongs to a different company, branch, or financial year.',
            ]);
        }
    }

    /**
     * Invoice and manufacturing stock stay with their own documents.
     * Cancelling only the voucher would leave stock and the books apart.
     */
    private function assertStandalone(Voucher $voucher): void
    {
        if ($voucher->invoice()->exists()) {
            throw ValidationException::withMessages([
                'voucher' => 'Cancel the sales or purchase invoice so stock and the invoice stay in step with this voucher.',
            ]);
        }

        if (ManufacturingOrder::query()->where('voucher_id', $voucher->id)->exists()) {
            throw ValidationException::withMessages([
                'voucher' => 'This journal belongs to a manufacturing order and cannot be cancelled on its own.',
            ]);
        }

        if (str_starts_with((string) $voucher->reference_number, 'inv-')) {
            throw ValidationException::withMessages([
                'voucher' => 'This inventory journal belongs to a stock document and cannot be cancelled on its own.',
            ]);
        }
    }

    private function assertDraft(Voucher $voucher): void
    {
        if (! $voucher->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Posted and cancelled vouchers cannot be changed.',
            ]);
        }
    }

    /**
     * Invoices allocate their number first, then pass it here so the accounting
     * voucher reuses that number instead of taking a second one from the sequence.
     */
    private function assignedNumber(?string $assignedNumber, Company $company, Branch $branch, FinancialYear $financialYear, VoucherType $type): string
    {
        $assignedNumber = trim((string) $assignedNumber);

        if ($assignedNumber === '') {
            return $this->numbers->allocate($company, $branch, $financialYear, $type);
        }

        if (strlen($assignedNumber) > 30) {
            throw ValidationException::withMessages([
                'invoice_number' => 'The invoice number is too long.',
            ]);
        }

        return $assignedNumber;
    }

    /**
     * A difference of ₹1.00 or less posts to the company round-off ledger.
     *
     * @param  list<array<string, mixed>>  $lines
     * @return array{lines: list<array<string, mixed>>, debit: int, credit: int, hasDebit: bool, hasCredit: bool}
     */
    private function balanceWithRoundOff(Company $company, array $lines, int $debitCents, int $creditCents, bool $hasDebit, bool $hasCredit, float $rate): array
    {
        $difference = $debitCents - $creditCents;
        $ledgerId = (int) ($company->round_off_ledger_id ?? 0);

        if ($ledgerId === 0 || $difference === 0 || abs($difference) > 100) {
            return compact('lines') + [
                'debit' => $debitCents,
                'credit' => $creditCents,
                'hasDebit' => $hasDebit,
                'hasCredit' => $hasCredit,
            ];
        }

        $ledger = Ledger::query()
            ->where('company_id', $company->id)
            ->whereKey($ledgerId)
            ->where('is_active', true)
            ->first();

        if (! $ledger) {
            return compact('lines') + [
                'debit' => $debitCents,
                'credit' => $creditCents,
                'hasDebit' => $hasDebit,
                'hasCredit' => $hasCredit,
            ];
        }

        $debit = $difference < 0 ? -$difference : 0;
        $credit = $difference > 0 ? $difference : 0;
        $lines[] = [
            'ledger_id' => $ledger->id,
            'line_number' => count($lines) + 1,
            'debit' => Money::format($debit),
            'credit' => Money::format($credit),
            'base_debit' => Money::format((int) round($debit * $rate)),
            'base_credit' => Money::format((int) round($credit * $rate)),
            'narration' => 'Round off',
            'reference' => null,
            'cost_centre_id' => null,
            'business_unit_id' => null,
            'department_id' => null,
        ];

        return [
            'lines' => $lines,
            'debit' => $debitCents + $debit,
            'credit' => $creditCents + $credit,
            'hasDebit' => $hasDebit || $debit > 0,
            'hasCredit' => $hasCredit || $credit > 0,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function allocate(Voucher $voucher, VoucherType $type, array $rows): void
    {
        if (! $voucher->company?->usesBillWise()) {
            return;
        }

        if (! in_array($type, [VoucherType::Receipt, VoucherType::Payment, VoucherType::Journal, VoucherType::CreditNote], true)) {
            return;
        }

        if ($rows === [] && ! in_array($type, [VoucherType::Receipt, VoucherType::Payment], true)) {
            return;
        }

        $this->allocations->sync($voucher->fresh(['entries.ledger.accountGroup']), $rows);
    }

    private function blank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
