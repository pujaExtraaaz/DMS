<?php

namespace Tally\Payables;

use App\Models\User;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\AccruedExpense;
use Tally\Models\Company;
use Tally\Models\FinancialYear;

class AccruedExpenseService
{
    public function __construct(
        private readonly VoucherEngine $vouchers,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, array $input, ?AccruedExpense $expense = null): AccruedExpense
    {
        $payload = [
            'branch_id' => ($input['branch_id'] ?? null) ?: null,
            'name' => (string) ($input['name'] ?? ''),
            'accrual_date' => (string) ($input['accrual_date'] ?? now()->toDateString()),
            'expense_ledger_id' => (int) ($input['expense_ledger_id'] ?? 0),
            'accrual_ledger_id' => (int) ($input['accrual_ledger_id'] ?? 0),
            'amount' => round((float) ($input['amount'] ?? 0), 2),
            'goods_receipt_id' => ($input['goods_receipt_id'] ?? null) ?: null,
            'is_uninvoiced_receipt' => (bool) ($input['is_uninvoiced_receipt'] ?? false),
            'status' => ($input['status'] ?? null) ?: ($expense?->status ?? 'open'),
        ];

        if ($expense) {
            abort_unless($expense->company_id === $company->id, 404);
            $expense->update($payload);

            return $expense->fresh(['expenseLedger', 'accrualLedger']);
        }

        return AccruedExpense::query()->create($payload + [
            'company_id' => $company->id,
        ])->fresh(['expenseLedger', 'accrualLedger']);
    }

    public function post(AccruedExpense $expense, ?User $user = null): AccruedExpense
    {
        $expense->loadMissing('company');

        if ($expense->status !== 'open' || $expense->accrual_voucher_id) {
            return $expense;
        }

        $cents = Money::cents((string) $expense->amount);
        if ($cents <= 0 || ! $expense->expense_ledger_id || ! $expense->accrual_ledger_id) {
            return $expense;
        }

        $company = $expense->company;
        $asOn = $expense->accrual_date?->toDateString() ?? now()->toDateString();
        $user ??= User::query()->first();

        $year = FinancialYear::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('start_date', '<=', $asOn)
            ->where('end_date', '>=', $asOn)
            ->first();

        $branch = $expense->branch_id
            ? $company->branches()->whereKey($expense->branch_id)->first()
            : null;
        $branch ??= $company->branches()->where('is_active', true)->orderBy('name')->first();

        if (! $year || ! $branch || ! $user) {
            return $expense;
        }

        $voucher = $this->vouchers->save(
            $company,
            $branch,
            $year,
            $user,
            VoucherType::Journal,
            [
                'voucher_date' => $asOn,
                'narration' => 'Accrual: '.$expense->name,
                'entries' => [
                    ['ledger_id' => $expense->expense_ledger_id, 'debit' => Money::format($cents), 'credit' => '0.00'],
                    ['ledger_id' => $expense->accrual_ledger_id, 'debit' => '0.00', 'credit' => Money::format($cents)],
                ],
            ],
            true
        );

        $expense->update(['accrual_voucher_id' => $voucher->id]);

        return $expense->fresh(['expenseLedger', 'accrualLedger', 'accrualVoucher']);
    }
}
