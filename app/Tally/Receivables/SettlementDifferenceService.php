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
use Tally\Models\Voucher;

/**
 * Posts a small journal to clear under/over settlement against a bill
 * (cash discount allowed / write-off of remainder).
 */
class SettlementDifferenceService
{
    public function __construct(
        private readonly VoucherEngine $vouchers,
        private readonly BillAllocationService $allocations,
    ) {}

    public function clear(
        Company $company,
        FinancialYear $year,
        User $user,
        Bill $bill,
        string $amount,
        int $differenceLedgerId,
        string $date,
        string $kind = 'discount',
    ): Voucher {
        $cents = Money::cents($amount);
        if ($cents <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter a positive settlement difference.']);
        }

        $outstanding = $this->allocations->outstandingCents($bill, $date);
        if ($cents > $outstanding) {
            throw ValidationException::withMessages(['amount' => 'Difference cannot exceed the bill outstanding.']);
        }

        $branch = $company->branches()->whereKey($bill->branch_id)->first()
            ?? $company->branches()->where('is_active', true)->orderBy('name')->first();
        abort_unless($branch, 404);

        $narration = ($kind === 'write_off' ? 'Settlement write-off ' : 'Settlement discount ').$bill->bill_number;

        return $this->vouchers->save(
            $company,
            $branch,
            $year,
            $user,
            VoucherType::Journal,
            [
                'voucher_date' => $date,
                'narration' => $narration,
                'entries' => [
                    ['ledger_id' => $differenceLedgerId, 'debit' => Money::format($cents), 'credit' => '0.00'],
                    ['ledger_id' => $bill->ledger_id, 'debit' => '0.00', 'credit' => Money::format($cents)],
                ],
                'allocations' => [
                    ['ledger_id' => $bill->ledger_id, 'bill_id' => $bill->id, 'amount' => Money::format($cents)],
                ],
            ],
            true
        );
    }
}
