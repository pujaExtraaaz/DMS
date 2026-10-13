<?php

namespace Tally\Assets;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\InventoryImpairment;
use Tally\Models\Product;

class InventoryImpairmentService
{
    public function __construct(private readonly VoucherEngine $vouchers) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function post(
        Company $company,
        Branch $branch,
        FinancialYear $year,
        User $user,
        array $input,
    ): InventoryImpairment {
        $amount = Money::format(Money::cents((string) ($input['amount'] ?? '0')));
        if (Money::cents($amount) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter an impairment amount.']);
        }

        $expenseLedgerId = (int) ($input['expense_ledger_id'] ?? 0);
        $provisionLedgerId = (int) ($input['provision_ledger_id'] ?? 0);
        if ($expenseLedgerId <= 0 || $provisionLedgerId <= 0) {
            throw ValidationException::withMessages([
                'expense_ledger_id' => 'Select expense and provision ledgers.',
            ]);
        }

        $date = (string) ($input['entry_date'] ?? now()->toDateString());
        if ($date < $year->start_date->toDateString() || $date > $year->end_date->toDateString()) {
            throw ValidationException::withMessages([
                'entry_date' => 'Date must fall in the active financial year.',
            ]);
        }

        return DB::transaction(function () use ($company, $branch, $year, $user, $input, $amount, $expenseLedgerId, $provisionLedgerId, $date) {
            $productId = ($input['product_id'] ?? null) ?: null;
            if ($productId) {
                Product::query()->where('company_id', $company->id)->whereKey($productId)->firstOrFail();
            }

            $voucher = $this->vouchers->save(
                $company,
                $branch,
                $year,
                $user,
                VoucherType::Journal,
                [
                    'voucher_date' => $date,
                    'reference_number' => ($input['reference'] ?? null) ?: 'INV-IMP',
                    'narration' => ($input['narration'] ?? null) ?: 'Inventory impairment / obsolescence provision',
                    'entries' => [
                        ['ledger_id' => $expenseLedgerId, 'debit' => $amount, 'credit' => '0.00'],
                        ['ledger_id' => $provisionLedgerId, 'debit' => '0.00', 'credit' => $amount],
                    ],
                ],
                true,
            );

            return InventoryImpairment::query()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'financial_year_id' => $year->id,
                'product_id' => $productId,
                'entry_date' => $date,
                'amount' => $amount,
                'expense_ledger_id' => $expenseLedgerId,
                'provision_ledger_id' => $provisionLedgerId,
                'voucher_id' => $voucher->id,
                'narration' => ($input['narration'] ?? null) ?: null,
                'created_by' => $user->id,
            ])->fresh(['product', 'voucher']);
        });
    }
}
