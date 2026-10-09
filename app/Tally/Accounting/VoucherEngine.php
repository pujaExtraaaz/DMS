<?php

namespace Tally\Accounting;

use Tally\Accounting\Events\VoucherWritten;
use Tally\Audit\AuditLogger;
use Tally\Models\Bill;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\CostCentre;
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

        return DB::transaction(function () use ($company, $branch, $financialYear, $user, $type, $input, $post, $voucher, $assignedNumber) {
            $this->assertContext($company, $branch, $financialYear);

            if ($voucher) {
                $this->assertDraft($voucher);
                $this->assertSameContext($voucher, $company, $branch, $financialYear);
                $type = $voucher->voucher_type;
            }

            $lines = $this->lines($company, $type, $input['entries'] ?? [], $post);
            $date = $this->date($financialYear, (string) ($input['voucher_date'] ?? ''));
            [$debitCents, $creditCents] = $this->totals($lines);

            if ($voucher) {
                $voucher->entries()->delete();
                $voucher->update([
                    'voucher_date' => $date,
                    'reference_number' => $this->blank($input['reference_number'] ?? null),
                    'narration' => $this->blank($input['narration'] ?? null),
                    'total_debit' => Money::format($debitCents),
                    'total_credit' => Money::format($creditCents),
                ] + $this->moneyMeta($input));
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
                    'total_debit' => Money::format($debitCents),
                    'total_credit' => Money::format($creditCents),
                    'created_by' => $user->id,
                ] + $this->moneyMeta($input));
            }

            $voucher->entries()->createMany($lines);
            $this->allocate($voucher, $type, $input['allocations'] ?? []);

            if ($post) {
                $this->markPosted($voucher->fresh('entries'));
                $this->bills->syncPostedVoucher($voucher->fresh(['entries.ledger.accountGroup', 'financialYear']));
            }

            $voucher = $voucher->fresh(['entries.ledger', 'creator']);
            VoucherWritten::dispatch($voucher);

            return $voucher;
        });
    }

    public function post(Voucher $voucher): Voucher
    {
        return DB::transaction(function () use ($voucher) {
            $voucher = Voucher::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();

            if ($voucher->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => 'A cancelled voucher cannot be posted again.',
                ]);
            }

            $this->assertDraft($voucher);
            $this->assertContext($voucher->company, $voucher->branch, $voucher->financialYear);
            $this->date($voucher->financialYear, $voucher->voucher_date->toDateString());

            $this->markPosted($voucher);
            $this->bills->syncPostedVoucher($voucher->fresh(['entries.ledger.accountGroup', 'financialYear']));
            $voucher = $voucher->fresh(['entries.ledger', 'creator']);
            VoucherWritten::dispatch($voucher);

            return $voucher;
        });
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
        $voucher->load('entries.ledger.accountGroup.parent');
        $stored = [];

        foreach ($voucher->entries as $entry) {
            $stored[] = [
                'ledger_id' => $entry->ledger_id,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'narration' => $entry->narration,
                'reference' => $entry->reference,
                'cost_centre_id' => $entry->cost_centre_id,
            ];
        }

        $this->lines($voucher->company, $voucher->voucher_type, $stored, true);

        $voucher->update([
            'status' => VoucherStatus::Posted,
            'posted_at' => now(),
        ]);
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
     * @param  list<array<string, mixed>>  $entries
     * @return list<array<string, mixed>>
     */
    private function lines(Company $company, VoucherType $type, array $entries, bool $post): array
    {
        $lines = [];
        $errors = [];
        $debitCents = 0;
        $creditCents = 0;
        $hasDebit = false;
        $hasCredit = false;

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

            $role = $this->roleError($type, $ledger, $debit, $credit);

            if ($role) {
                $errors["entries.$index.ledger_id"] = $role;

                continue;
            }

            $centreId = $entry['cost_centre_id'] ?? null;
            $centreId = $centreId === '' || $centreId === null ? null : (int) $centreId;

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

            $hasDebit = $hasDebit || $debit > 0;
            $hasCredit = $hasCredit || $credit > 0;
            $debitCents += $debit;
            $creditCents += $credit;

            $lines[] = [
                'ledger_id' => $ledger->id,
                'line_number' => count($lines) + 1,
                'debit' => Money::format($debit),
                'credit' => Money::format($credit),
                'narration' => $this->blank($entry['narration'] ?? null),
                'reference' => $this->blank($entry['reference'] ?? null),
                'cost_centre_id' => $centreId,
            ];
        }

        if ($lines === []) {
            $errors['entries'] = 'Add at least one ledger line.';
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

    private function roleError(VoucherType $type, Ledger $ledger, int $debit, int $credit): ?string
    {
        $cash = $ledger->isCashOrBank();

        return match ($type) {
            VoucherType::Payment => $credit > 0
                ? ($cash ? null : 'The payment source must be a Cash or Bank ledger.')
                : ($cash ? 'Paid-to ledgers cannot be Cash or Bank. Move cash and bank balances with a Contra voucher.' : null),
            VoucherType::Receipt => $debit > 0
                ? ($cash ? null : 'The receipt must be deposited into a Cash or Bank ledger.')
                : ($cash ? 'Received-from ledgers cannot be Cash or Bank. Move cash and bank balances with a Contra voucher.' : null),
            VoucherType::Contra => $cash ? null : 'Contra vouchers can only use Cash and Bank ledgers.',
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

        if ($financialYear->company_id !== $company->id || ! $financialYear->is_active) {
            throw ValidationException::withMessages([
                'financial_year_id' => 'Select an active financial year of the current company.',
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
     * @param  list<array<string, mixed>>  $rows
     */
    private function allocate(Voucher $voucher, VoucherType $type, array $rows): void
    {
        if (! in_array($type, [VoucherType::Receipt, VoucherType::Payment], true)) {
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
