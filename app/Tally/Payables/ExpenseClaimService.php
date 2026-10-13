<?php

namespace Tally\Payables;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\ExpenseClaim;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;

class ExpenseClaimService
{
    public function __construct(
        private readonly VoucherEngine $vouchers,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, Branch $branch, FinancialYear $year, User $user, array $input, ?ExpenseClaim $claim = null): ExpenseClaim
    {
        return DB::transaction(function () use ($company, $branch, $year, $user, $input, $claim) {
            $amount = 0.0;
            $lineRows = [];
            $lineNumber = 1;

            foreach ($input['lines'] ?? [] as $line) {
                if (! is_array($line)) {
                    continue;
                }
                $lineAmount = (float) ($line['amount'] ?? 0);
                $amount += $lineAmount;
                $lineRows[] = [
                    'line_number' => $lineNumber++,
                    'expense_date' => ($line['expense_date'] ?? null) ?: null,
                    'category' => ($line['category'] ?? null) ?: null,
                    'expense_ledger_id' => ($line['expense_ledger_id'] ?? null) ?: null,
                    'description' => ($line['description'] ?? null) ?: null,
                    'amount' => round($lineAmount, 2),
                    'bill_attached' => (bool) ($line['bill_attached'] ?? false),
                ];
            }

            if ($amount <= 0 && isset($input['amount'])) {
                $amount = (float) $input['amount'];
            }

            $payload = [
                'claim_date' => (string) ($input['claim_date'] ?? now()->toDateString()),
                'employee_id' => ($input['employee_id'] ?? null) ?: null,
                'employee_ledger_id' => ($input['employee_ledger_id'] ?? null) ?: null,
                'category' => ($input['category'] ?? null) ?: null,
                'amount' => round($amount, 2),
                'status' => ($input['status'] ?? null) ?: ($claim?->status ?? 'draft'),
                'is_reimbursement' => array_key_exists('is_reimbursement', $input)
                    ? (bool) $input['is_reimbursement']
                    : ($claim?->is_reimbursement ?? true),
                'policy_flag' => ($input['policy_flag'] ?? null) ?: null,
                'narration' => ($input['narration'] ?? null) ?: null,
            ];

            if ($claim) {
                abort_unless($claim->company_id === $company->id, 404);
                if (! in_array($claim->status, ['draft', 'submitted'], true)) {
                    throw ValidationException::withMessages([
                        'status' => 'This expense claim cannot be changed.',
                    ]);
                }
                $claim->update($payload);
                $claim->lines()->delete();
            } else {
                $count = ExpenseClaim::query()
                    ->where('company_id', $company->id)
                    ->where('financial_year_id', $year->id)
                    ->count() + 1;
                $claim = ExpenseClaim::query()->create($payload + [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'financial_year_id' => $year->id,
                    'number' => 'EC-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT),
                    'created_by' => $user->id,
                ]);
            }

            if ($lineRows !== []) {
                $claim->lines()->createMany($lineRows);
            }

            return $claim->fresh(['lines', 'employeeLedger']);
        });
    }

    /**
     * Approve claim and post accrual journal (Dr expense lines, Cr employee ledger).
     */
    public function approve(Company $company, ExpenseClaim $claim, User $user): ExpenseClaim
    {
        abort_unless($claim->company_id === $company->id, 404);

        return DB::transaction(function () use ($company, $claim, $user) {
            $claim = ExpenseClaim::query()->whereKey($claim->id)->lockForUpdate()->firstOrFail();
            $claim->loadMissing(['lines', 'branch', 'financialYear', 'employeeLedger']);

            if ($claim->status === 'approved' && $claim->accrual_voucher_id) {
                return $claim->fresh(['lines', 'employeeLedger', 'accrualVoucher']);
            }

            if (! in_array($claim->status, ['draft', 'submitted'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft or submitted claims can be approved.',
                ]);
            }

            if (! $claim->employee_ledger_id) {
                throw ValidationException::withMessages([
                    'employee_ledger_id' => 'Select an employee ledger before approving.',
                ]);
            }

            $claim->loadMissing('lines');
            if ($claim->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'lines' => 'Add at least one expense line before approving.',
                ]);
            }

            $entries = [];
            $creditCents = 0;
            foreach ($claim->lines as $line) {
                $cents = Money::cents((string) $line->amount);
                if ($cents <= 0) {
                    continue;
                }
                if (! $line->expense_ledger_id) {
                    throw ValidationException::withMessages([
                        'lines' => 'Each expense line needs an expense ledger before approving.',
                    ]);
                }
                $entries[] = [
                    'ledger_id' => (int) $line->expense_ledger_id,
                    'debit' => Money::format($cents),
                    'credit' => '0.00',
                ];
                $creditCents += $cents;
            }

            if ($creditCents <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Claim amount must be greater than zero.',
                ]);
            }

            $entries[] = [
                'ledger_id' => (int) $claim->employee_ledger_id,
                'debit' => '0.00',
                'credit' => Money::format($creditCents),
            ];

            $branch = $claim->branch
                ?? Branch::query()->where('company_id', $company->id)->whereKey($claim->branch_id)->first()
                ?? $company->branches()->where('is_active', true)->orderBy('id')->first();
            $year = $claim->financialYear
                ?? FinancialYear::query()
                    ->where('company_id', $company->id)
                    ->where('is_active', true)
                    ->where('start_date', '<=', $claim->claim_date->toDateString())
                    ->where('end_date', '>=', $claim->claim_date->toDateString())
                    ->first();

            if (! $branch || ! $year) {
                throw ValidationException::withMessages([
                    'claim_date' => 'Active branch and financial year are required to post the accrual.',
                ]);
            }

            $voucher = $this->vouchers->save(
                $company,
                $branch,
                $year,
                $user,
                VoucherType::Journal,
                [
                    'voucher_date' => $claim->claim_date->toDateString(),
                    'reference_number' => $claim->number,
                    'narration' => trim('Expense claim accrual '.$claim->number.($claim->narration ? ' — '.$claim->narration : '')),
                    'entries' => $entries,
                ],
                true
            );

            $claim->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'accrual_voucher_id' => $voucher->id,
            ]);

            return $claim->fresh(['lines', 'employeeLedger', 'accrualVoucher', 'approver']);
        });
    }

    /**
     * Post payment voucher (Dr employee, Cr bank) and mark claim paid.
     */
    public function pay(Company $company, ExpenseClaim $claim, User $user, int $bankLedgerId): ExpenseClaim
    {
        abort_unless($claim->company_id === $company->id, 404);

        return DB::transaction(function () use ($company, $claim, $user, $bankLedgerId) {
            $claim = ExpenseClaim::query()->whereKey($claim->id)->lockForUpdate()->firstOrFail();
            $claim->loadMissing(['branch', 'financialYear', 'employeeLedger']);

            if ($claim->status === 'paid' && $claim->payment_voucher_id) {
                return $claim->fresh(['lines', 'employeeLedger', 'paymentVoucher', 'accrualVoucher']);
            }

            if ($claim->status !== 'approved') {
                throw ValidationException::withMessages([
                    'status' => 'Approve the claim before paying.',
                ]);
            }

            if (! $claim->employee_ledger_id) {
                throw ValidationException::withMessages([
                    'employee_ledger_id' => 'Employee ledger is required to pay the claim.',
                ]);
            }

            $bank = Ledger::query()
                ->where('company_id', $company->id)
                ->whereKey($bankLedgerId)
                ->first();

            if (! $bank || ! $bank->isCashOrBank()) {
                throw ValidationException::withMessages([
                    'bank_ledger_id' => 'Select a cash or bank ledger for payment.',
                ]);
            }

            $amount = Money::format(Money::cents((string) $claim->amount));
            if (Money::cents($amount) <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Claim amount must be greater than zero.',
                ]);
            }

            $branch = $claim->branch
                ?? $company->branches()->where('is_active', true)->orderBy('id')->first();
            $year = $claim->financialYear
                ?? FinancialYear::query()
                    ->where('company_id', $company->id)
                    ->where('is_active', true)
                    ->where('start_date', '<=', $claim->claim_date->toDateString())
                    ->where('end_date', '>=', $claim->claim_date->toDateString())
                    ->first();

            if (! $branch || ! $year) {
                throw ValidationException::withMessages([
                    'claim_date' => 'Active branch and financial year are required to post payment.',
                ]);
            }

            $voucher = $this->vouchers->save(
                $company,
                $branch,
                $year,
                $user,
                VoucherType::Payment,
                [
                    'voucher_date' => $claim->claim_date->toDateString(),
                    'reference_number' => $claim->number,
                    'narration' => trim('Expense claim payment '.$claim->number.($claim->narration ? ' — '.$claim->narration : '')),
                    'entries' => [
                        ['ledger_id' => (int) $claim->employee_ledger_id, 'debit' => $amount, 'credit' => '0.00'],
                        ['ledger_id' => $bank->id, 'debit' => '0.00', 'credit' => $amount],
                    ],
                ],
                true
            );

            $claim->update([
                'status' => 'paid',
                'payment_voucher_id' => $voucher->id,
            ]);

            return $claim->fresh(['lines', 'employeeLedger', 'paymentVoucher', 'accrualVoucher', 'approver']);
        });
    }
}
