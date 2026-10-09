<?php

namespace Tally\Documents;

use Tally\Invoicing\InvoiceKind;
use Tally\Models\Invoice;
use Tally\Models\PurchaseOrder;
use Tally\Support\IndianCurrency;

/**
 * Print data for a sales invoice, purchase invoice, or purchase order.
 * Amounts are the figures already stored on the document.
 */
class TradingDocument
{
    /**
     * @return array<string, mixed>
     */
    public function invoice(Invoice $invoice): array
    {
        $invoice->loadMissing(['company', 'branch', 'party', 'lines.product', 'lines.hsnSac', 'financialYear']);
        $sales = $invoice->kind->usesCostOfGoods();

        return $this->pack(
            $invoice->company,
            $invoice->branch,
            $invoice->kind->documentLabel(),
            $invoice->invoice_number,
            $invoice->invoice_date->format('d M Y'),
            $invoice->kind->partyLabel(),
            $invoice->party,
            $invoice->reference_number,
            $invoice->narration,
            $invoice->place_of_supply,
            $sales ? $invoice->company->sales_terms : $invoice->company->purchase_terms,
            $invoice->lines,
            (string) $invoice->subtotal,
            (string) $invoice->discount_total,
            (string) $invoice->tax_total,
            (string) $invoice->grand_total,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function purchaseOrder(PurchaseOrder $order): array
    {
        $order->loadMissing(['company', 'branch', 'supplier', 'purchaseLedger', 'lines.product', 'lines.hsnSac', 'financialYear']);

        $document = $this->pack(
            $order->company,
            $order->branch,
            'Purchase order',
            $order->number,
            $order->order_date->format('d M Y'),
            'Supplier',
            $order->supplier,
            $order->reference_number,
            $order->narration,
            null,
            $order->company->purchase_terms,
            $order->lines,
            (string) $order->subtotal,
            (string) $order->discount_total,
            (string) $order->tax_total,
            (string) $order->grand_total,
        );
        $document['delivery'] = (string) ($order->delivery_charge ?? '0.00');
        $document['purchase_ledger'] = $order->purchaseLedger?->name;

        return $document;
    }

    /**
     * @param  iterable<int, mixed>  $lines
     * @return array<string, mixed>
     */
    private function pack(
        $company,
        $branch,
        string $title,
        string $number,
        string $date,
        string $partyLabel,
        $party,
        ?string $reference,
        ?string $narration,
        ?string $place,
        ?string $terms,
        iterable $lines,
        string $subtotal,
        string $discount,
        string $tax,
        string $grand,
    ): array {
        $rows = [];

        foreach ($lines as $line) {
            $rows[] = [
                'item' => $line->item_name,
                'hsn' => $line->hsnSac?->code,
                'quantity' => rtrim(rtrim((string) $line->quantity, '0'), '.'),
                'rate' => IndianCurrency::format($line->rate),
                'discount' => IndianCurrency::format($line->discount),
                'taxable' => IndianCurrency::format($line->taxable_amount),
                'cgst' => IndianCurrency::format($line->cgst_amount),
                'sgst' => IndianCurrency::format($line->sgst_amount),
                'igst' => IndianCurrency::format($line->igst_amount),
                'cess' => IndianCurrency::format($line->cess_amount),
                'tax' => IndianCurrency::format($line->tax_amount),
                'amount' => IndianCurrency::format($line->line_total),
            ];
        }

        return [
            'title' => $title,
            'number' => $number,
            'date' => $date,
            'company' => $company->legal_name ?: $company->name,
            'address' => array_values(array_filter([
                $company->address,
                trim(implode(', ', array_filter([$company->city, $company->state, $company->pincode]))),
                $company->phone,
                $company->email,
            ])),
            'gstin' => $company->gstin,
            'branch' => $branch?->name,
            'party_label' => $partyLabel,
            'party' => $party?->name,
            'party_gstin' => $party?->gstin,
            'party_state' => $party?->state,
            'reference' => $reference,
            'narration' => $narration,
            'place' => $place,
            'terms' => $terms,
            'lines' => $rows,
            'subtotal' => IndianCurrency::format($subtotal),
            'discount' => IndianCurrency::format($discount),
            'tax' => IndianCurrency::format($tax),
            'grand' => IndianCurrency::format($grand),
            'words' => IndianCurrency::words($grand),
        ];
    }
}
