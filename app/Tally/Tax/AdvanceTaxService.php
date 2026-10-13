<?php

namespace Tally\Tax;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Voucher;

class AdvanceTaxService
{
    public function __construct(private readonly VoucherEngine $vouchers) {}

    /**
     * Post advance tax payment: Dr Advance tax / prepaid tax, Cr Bank.
     *
     * @param  array<string, mixed>  $input
     */
    public function post(
        Company $company,
        Branch $branch,
        FinancialYear $year,
        User $user,
        array $input,
    ): Voucher {
        $amount = Money::format(Money::cents((string) ($input['amount'] ?? '0')));
        if (Money::cents($amount) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter an advance tax amount.']);
        }

        $advanceLedgerId = (int) ($input['advance_tax_ledger_id'] ?? 0);
        $bankLedgerId = (int) ($input['bank_ledger_id'] ?? 0);
        if ($advanceLedgerId <= 0 || $bankLedgerId <= 0) {
            throw ValidationException::withMessages([
                'advance_tax_ledger_id' => 'Select advance-tax and bank ledgers.',
            ]);
        }

        $date = (string) ($input['payment_date'] ?? now()->toDateString());
        if ($date < $year->start_date->toDateString() || $date > $year->end_date->toDateString()) {
            throw ValidationException::withMessages([
                'payment_date' => 'Date must fall in the active financial year.',
            ]);
        }

        return DB::transaction(function () use ($company, $branch, $year, $user, $input, $amount, $advanceLedgerId, $bankLedgerId, $date) {
            return $this->vouchers->save(
                $company,
                $branch,
                $year,
                $user,
                VoucherType::Payment,
                [
                    'voucher_date' => $date,
                    'reference_number' => ($input['challan_number'] ?? null) ?: 'ADV-TAX',
                    'narration' => ($input['narration'] ?? null) ?: 'Advance income-tax payment',
                    'entries' => [
                        ['ledger_id' => $advanceLedgerId, 'debit' => $amount, 'credit' => '0.00'],
                        ['ledger_id' => $bankLedgerId, 'debit' => '0.00', 'credit' => $amount],
                    ],
                ],
                true,
            );
        });
    }
}
