<?php

namespace Tally\Receivables;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Voucher;

/**
 * Period-end accrual of unbilled / accrued income, with optional reverse next period.
 */
class UnbilledRevenueService
{
    public function __construct(private readonly VoucherEngine $vouchers) {}

    public function accrue(
        Company $company,
        FinancialYear $year,
        User $user,
        int $accruedIncomeLedgerId,
        int $revenueLedgerId,
        string $amount,
        string $date,
        bool $autoReverse = true,
    ): Voucher {
        $cents = Money::cents($amount);
        if ($cents <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter a positive unbilled amount.']);
        }

        $branch = $company->branches()->where('is_active', true)->orderBy('name')->first();
        abort_unless($branch, 404);

        $voucher = $this->vouchers->save(
            $company,
            $branch,
            $year,
            $user,
            VoucherType::Journal,
            [
                'voucher_date' => $date,
                'narration' => 'Unbilled revenue accrual',
                'entries' => [
                    ['ledger_id' => $accruedIncomeLedgerId, 'debit' => Money::format($cents), 'credit' => '0.00'],
                    ['ledger_id' => $revenueLedgerId, 'debit' => '0.00', 'credit' => Money::format($cents)],
                ],
            ],
            true
        );

        if ($autoReverse) {
            $reverseDate = date('Y-m-d', strtotime($date.' +1 day'));
            $this->vouchers->save(
                $company,
                $branch,
                $year,
                $user,
                VoucherType::Journal,
                [
                    'voucher_date' => $reverseDate,
                    'narration' => 'Reverse unbilled revenue accrual',
                    'entries' => [
                        ['ledger_id' => $revenueLedgerId, 'debit' => Money::format($cents), 'credit' => '0.00'],
                        ['ledger_id' => $accruedIncomeLedgerId, 'debit' => '0.00', 'credit' => Money::format($cents)],
                    ],
                ],
                true
            );
        }

        return $voucher;
    }
}
