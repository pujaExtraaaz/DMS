<?php

namespace Tally\Accounting;

use Tally\Models\Bill;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;
use Tally\Models\Voucher;
use Tally\Models\VoucherEntry;

/**
 * Opens a bill for each posted customer debit and supplier credit.
 * Receipts and payments do not open bills; they allocate against them.
 */
class BillService
{
    public function syncPostedVoucher(Voucher $voucher): void
    {
        if (! $voucher->isPosted()) {
            return;
        }

        $voucher->loadMissing(['entries.ledger.accountGroup', 'financialYear']);

        foreach ($voucher->entries as $entry) {
            $ledger = $entry->ledger;

            if (! $ledger) {
                continue;
            }

            $debit = Money::cents((string) $entry->debit);
            $credit = Money::cents((string) $entry->credit);
            $side = null;
            $amount = 0;

            if ($ledger->isCustomer() && $debit > 0) {
                $side = OpeningBalanceType::Debit;
                $amount = $debit;
            } elseif ($ledger->isSupplier() && $credit > 0) {
                $side = OpeningBalanceType::Credit;
                $amount = $credit;
            }

            if (! $side || $amount === 0) {
                continue;
            }

            $this->openFromEntry($voucher, $entry, $ledger, $side, $amount);
        }
    }

    public function ensureOpening(Ledger $ledger, ?FinancialYear $year = null): ?Bill
    {
        $ledger->loadMissing('accountGroup');
        $amount = Money::cents((string) $ledger->opening_balance);

        if ($amount === 0) {
            return null;
        }

        $side = $ledger->isCustomer() && $ledger->opening_balance_type === OpeningBalanceType::Debit
            ? OpeningBalanceType::Debit
            : ($ledger->isSupplier() && $ledger->opening_balance_type === OpeningBalanceType::Credit
                ? OpeningBalanceType::Credit
                : null);

        if (! $side) {
            return null;
        }

        $year ??= $ledger->company->financialYears()->orderBy('start_date')->first();
        $date = $year?->start_date->toDateString()
            ?? $ledger->company->financial_year_start->toDateString();

        $bill = Bill::query()->firstOrNew([
            'company_id' => $ledger->company_id,
            'ledger_id' => $ledger->id,
            'bill_number' => 'Opening',
        ]);

        $amountText = Money::format($amount);
        $due = $this->dueDate($date, (int) ($ledger->credit_days ?? 0));

        if ($bill->exists
            && (string) $bill->original_amount === $amountText
            && $bill->side === $side
            && $bill->bill_date?->toDateString() === $date
            && $bill->due_date?->toDateString() === $due) {
            return $bill;
        }

        $bill->fill([
            'branch_id' => null,
            'financial_year_id' => $year?->id,
            'voucher_entry_id' => null,
            'bill_date' => $date,
            'due_date' => $due,
            'original_amount' => $amountText,
            'side' => $side,
            'is_opening' => true,
        ]);
        $bill->save();

        return $bill;
    }

    private function openFromEntry(Voucher $voucher, VoucherEntry $entry, Ledger $ledger, OpeningBalanceType $side, int $amount): void
    {
        $existing = Bill::query()->where('voucher_entry_id', $entry->id)->first();

        if ($existing) {
            return;
        }

        $number = $voucher->voucher_number;
        $taken = Bill::query()
            ->where('company_id', $voucher->company_id)
            ->where('ledger_id', $ledger->id)
            ->where('bill_number', $number)
            ->exists();

        if ($taken) {
            $number .= '-'.$entry->line_number;
        }

        Bill::query()->create([
            'company_id' => $voucher->company_id,
            'branch_id' => $voucher->branch_id,
            'financial_year_id' => $voucher->financial_year_id,
            'ledger_id' => $ledger->id,
            'voucher_entry_id' => $entry->id,
            'bill_number' => $number,
            'bill_date' => $voucher->voucher_date->toDateString(),
            'due_date' => $this->dueDate($voucher->voucher_date->toDateString(), (int) ($ledger->credit_days ?? 0)),
            'original_amount' => Money::format($amount),
            'side' => $side,
            'is_opening' => false,
        ]);
    }

    private function dueDate(string $date, int $days): string
    {
        $days = max(0, $days);

        return date('Y-m-d', strtotime($date.' +'.$days.' days'));
    }
}
