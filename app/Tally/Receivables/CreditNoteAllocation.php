<?php

namespace Tally\Receivables;

use Tally\Accounting\Money;
use Tally\Models\Bill;
use Tally\Models\BillAllocation;
use Tally\Models\Invoice;

/**
 * Allocates a posted credit note against the source sales invoice bill.
 */
class CreditNoteAllocation
{
    public function apply(Invoice $creditNote): void
    {
        if ($creditNote->kind->value !== 'credit_note' || ! $creditNote->against_invoice_id || ! $creditNote->voucher_id) {
            return;
        }

        $source = Invoice::query()->with('voucher.entries')->find($creditNote->against_invoice_id);
        if (! $source?->voucher_id) {
            return;
        }

        $entryIds = $source->voucher->entries->pluck('id');
        $bill = Bill::query()
            ->where('company_id', $creditNote->company_id)
            ->where('ledger_id', $creditNote->party_ledger_id)
            ->whereIn('voucher_entry_id', $entryIds)
            ->first();

        if (! $bill) {
            $bill = Bill::query()
                ->where('company_id', $creditNote->company_id)
                ->where('ledger_id', $creditNote->party_ledger_id)
                ->where('bill_number', $source->invoice_number)
                ->first();
        }

        if (! $bill) {
            return;
        }

        $amount = Money::format(Money::cents((string) $creditNote->grand_total));

        BillAllocation::query()->updateOrCreate(
            [
                'voucher_id' => $creditNote->voucher_id,
                'bill_id' => $bill->id,
            ],
            [
                'company_id' => $creditNote->company_id,
                'ledger_id' => $creditNote->party_ledger_id,
                'amount' => $amount,
            ]
        );
    }
}
