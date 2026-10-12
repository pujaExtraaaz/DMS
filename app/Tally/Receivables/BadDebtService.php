<?php

namespace Tally\Receivables;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\BillAllocationService;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\Bill;
use Tally\Models\Company;
use Tally\Models\FinancialYear;

class BadDebtService
{
    public function __construct(
        private readonly VoucherEngine $vouchers,
        private readonly BillAllocationService $allocations,
    ) {}

    /**
     * @param  array{expense_percent_current?: float, b1_30?: float, b31_60?: float, b61_90?: float, b90_plus?: float}  $rates
     */
    public function provisionJournal(
        Company $company,
        FinancialYear $year,
        User $user,
        array $bucketTotalsCents,
        array $rates,
        string $date,
    ): void {
        $expenseId = $company->bad_debt_ledger_id;
        $provisionId = $company->doubtful_debt_ledger_id;

        if (! $expenseId || ! $provisionId) {
            throw ValidationException::withMessages([
                'company' => 'Set bad debt and doubtful debt ledgers on the company first.',
            ]);
        }

        $amount = 0;
        foreach (['current' => 'expense_percent_current', 'b1_30' => 'b1_30', 'b31_60' => 'b31_60', 'b61_90' => 'b61_90', 'b90_plus' => 'b90_plus'] as $bucket => $rateKey) {
            $rate = (float) ($rates[$rateKey] ?? 0);
            $amount += (int) round(($bucketTotalsCents[$bucket] ?? 0) * ($rate / 100));
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'rates' => 'Provision amount is zero.',
            ]);
        }

        $branch = $company->branches()->where('is_active', true)->orderBy('name')->first();
        abort_unless($branch, 404);

        $this->vouchers->save(
            $company,
            $branch,
            $year,
            $user,
            VoucherType::Journal,
            [
                'voucher_date' => $date,
                'narration' => 'Doubtful debt provision',
                'entries' => [
                    ['ledger_id' => $expenseId, 'debit' => Money::format($amount), 'credit' => '0.00'],
                    ['ledger_id' => $provisionId, 'debit' => '0.00', 'credit' => Money::format($amount)],
                ],
            ],
            true
        );
    }

    public function writeOff(Bill $bill, Company $company, FinancialYear $year, User $user, string $date): void
    {
        $expenseId = $company->bad_debt_ledger_id;
        if (! $expenseId) {
            throw ValidationException::withMessages([
                'company' => 'Set a bad debt ledger on the company first.',
            ]);
        }

        $outstanding = $this->allocations->outstandingCents($bill, $date);
        if ($outstanding <= 0) {
            throw ValidationException::withMessages([
                'bill' => 'Bill has no outstanding balance.',
            ]);
        }

        $branch = $company->branches()->whereKey($bill->branch_id)->first()
            ?? $company->branches()->where('is_active', true)->orderBy('name')->first();

        $voucher = $this->vouchers->save(
            $company,
            $branch,
            $year,
            $user,
            VoucherType::Journal,
            [
                'voucher_date' => $date,
                'narration' => 'Bad debt write-off '.$bill->bill_number,
                'entries' => [
                    ['ledger_id' => $expenseId, 'debit' => Money::format($outstanding), 'credit' => '0.00'],
                    ['ledger_id' => $bill->ledger_id, 'debit' => '0.00', 'credit' => Money::format($outstanding)],
                ],
                'allocations' => [
                    ['ledger_id' => $bill->ledger_id, 'bill_id' => $bill->id, 'amount' => Money::format($outstanding)],
                ],
            ],
            true
        );
    }
}
