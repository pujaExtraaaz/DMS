<?php

namespace Tally\Payables;

use Tally\Invoicing\InvoiceKind;
use Tally\Models\Company;
use Tally\Models\DuplicateBillCheck;
use Tally\Models\Invoice;

class DuplicateBillDetector
{
    /**
     * Matching purchase invoices with the same supplier + bill number + amount.
     *
     * @return \Illuminate\Support\Collection<int, Invoice>
     */
    public function matches(Company $company, int $supplierLedgerId, string $billNumber, string|float $amount, ?int $excludeInvoiceId = null)
    {
        $billNumber = trim($billNumber);
        $amount = number_format((float) $amount, 2, '.', '');

        if ($billNumber === '' || $supplierLedgerId <= 0) {
            return collect();
        }

        return Invoice::query()
            ->where('company_id', $company->id)
            ->where('kind', InvoiceKind::Purchase)
            ->where('party_ledger_id', $supplierLedgerId)
            ->where(function ($q) use ($billNumber) {
                $q->where('reference_number', $billNumber)
                    ->orWhere('invoice_number', $billNumber);
            })
            ->where('grand_total', $amount)
            ->when($excludeInvoiceId, fn ($q) => $q->whereKeyNot($excludeInvoiceId))
            ->get();
    }

    /**
     * Flag if the same supplier + bill number + amount already exists on purchase invoices.
     *
     * @return list<DuplicateBillCheck>
     */
    public function flag(Company $company, int $supplierLedgerId, string $billNumber, string|float $amount, ?string $billDate = null, ?int $excludeInvoiceId = null): array
    {
        $billNumber = trim($billNumber);
        $amount = number_format((float) $amount, 2, '.', '');
        $matches = $this->matches($company, $supplierLedgerId, $billNumber, $amount, $excludeInvoiceId);

        $flags = [];
        foreach ($matches as $invoice) {
            $flags[] = DuplicateBillCheck::query()->create([
                'company_id' => $company->id,
                'supplier_ledger_id' => $supplierLedgerId,
                'bill_number' => $billNumber,
                'bill_date' => $billDate ?: $invoice->invoice_date?->toDateString(),
                'amount' => $amount,
                'invoice_id' => $invoice->id,
                'match_reason' => 'Same supplier, bill number and amount',
                'status' => 'flagged',
            ]);
        }

        return $flags;
    }

    public function isDuplicate(Company $company, int $supplierLedgerId, string $billNumber, string|float $amount, ?int $excludeInvoiceId = null): bool
    {
        return $this->matches($company, $supplierLedgerId, $billNumber, $amount, $excludeInvoiceId)->isNotEmpty();
    }
}
