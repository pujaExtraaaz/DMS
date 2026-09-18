<?php

namespace App\Domains\Tally\Services;

use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Tally\Models\TallySyncQueue;
use App\Jobs\ProcessTallySyncJob;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class TallyExportService
{
    public function enqueue(string $documentType, Model $document, bool $dispatch = true): TallySyncQueue
    {
        $payload = match ($documentType) {
            'invoice' => $this->buildInvoiceXml($document instanceof Invoice ? $document : throw new InvalidArgumentException('Expected Invoice')),
            'payment' => $this->buildPaymentXml($document instanceof Payment ? $document : throw new InvalidArgumentException('Expected Payment')),
            'credit_note' => $this->buildCreditNoteXml($document instanceof CreditNote ? $document : throw new InvalidArgumentException('Expected CreditNote')),
            'purchase_invoice' => $this->buildPurchaseInvoiceXml($document instanceof PurchaseInvoice ? $document : throw new InvalidArgumentException('Expected PurchaseInvoice')),
            default => throw new InvalidArgumentException("Unsupported Tally document type [{$documentType}]"),
        };

        $queue = TallySyncQueue::create([
            'document_type' => $documentType,
            'document_id' => $document->getKey(),
            'payload' => $payload,
            'status' => 'pending',
            'attempts' => 0,
        ]);

        if ($dispatch) {
            ProcessTallySyncJob::dispatch($queue->id);
        }

        return $queue;
    }

    public function enqueuePostedDocument(Model $document): ?TallySyncQueue
    {
        $map = [
            Invoice::class => 'invoice',
            Payment::class => 'payment',
            CreditNote::class => 'credit_note',
            PurchaseInvoice::class => 'purchase_invoice',
        ];

        $type = $map[$document::class] ?? null;
        if (! $type) {
            return null;
        }

        $status = (string) ($document->status ?? '');
        $postedStatuses = ['issued', 'posted', 'paid', 'partial', 'completed', 'approved'];

        if ($status !== '' && ! in_array($status, $postedStatuses, true)) {
            return null;
        }

        return $this->enqueue($type, $document);
    }

    public function retry(TallySyncQueue $queue): TallySyncQueue
    {
        $queue->update([
            'status' => 'pending',
            'last_error' => null,
        ]);

        ProcessTallySyncJob::dispatch($queue->id);

        return $queue->fresh();
    }

    public function buildInvoiceXml(Invoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'items.product']);

        $lines = '';
        foreach ($invoice->items as $item) {
            $name = htmlspecialchars($item->product?->name ?? 'Item', ENT_XML1);
            $lines .= "<ALLINVENTORYENTRIES.LIST><STOCKITEMNAME>{$name}</STOCKITEMNAME>"
                ."<ACTUALQTY>{$item->quantity}</ACTUALQTY><RATE>{$item->unit_price}</RATE>"
                ."<AMOUNT>{$item->line_total}</AMOUNT></ALLINVENTORYENTRIES.LIST>";
        }

        $party = htmlspecialchars($invoice->customer?->name ?? 'Customer', ENT_XML1);
        $voucher = htmlspecialchars($invoice->invoice_no, ENT_XML1);
        $date = $invoice->invoice_date?->format('Ymd') ?? now()->format('Ymd');

        return $this->wrap("INVOICE", <<<XML
<VOUCHER VCHTYPE="Sales" ACTION="Create">
 <DATE>{$date}</DATE>
 <VOUCHERNUMBER>{$voucher}</VOUCHERNUMBER>
 <PARTYLEDGERNAME>{$party}</PARTYLEDGERNAME>
 <AMOUNT>{$invoice->grand_total}</AMOUNT>
 {$lines}
</VOUCHER>
XML);
    }

    public function buildPaymentXml(Payment $payment): string
    {
        $payment->loadMissing('customer');
        $party = htmlspecialchars($payment->customer?->name ?? 'Customer', ENT_XML1);
        $voucher = htmlspecialchars($payment->payment_no ?? ('PAY-'.$payment->id), ENT_XML1);
        $date = optional($payment->paid_at)->format('Ymd') ?? now()->format('Ymd');
        $amount = $payment->amount;

        return $this->wrap('PAYMENT', <<<XML
<VOUCHER VCHTYPE="Receipt" ACTION="Create">
 <DATE>{$date}</DATE>
 <VOUCHERNUMBER>{$voucher}</VOUCHERNUMBER>
 <PARTYLEDGERNAME>{$party}</PARTYLEDGERNAME>
 <AMOUNT>{$amount}</AMOUNT>
</VOUCHER>
XML);
    }

    public function buildCreditNoteXml(CreditNote $creditNote): string
    {
        $creditNote->loadMissing('customer');
        $party = htmlspecialchars($creditNote->customer?->name ?? 'Customer', ENT_XML1);
        $voucher = htmlspecialchars($creditNote->credit_note_no, ENT_XML1);
        $date = $creditNote->credit_note_date?->format('Ymd') ?? now()->format('Ymd');

        return $this->wrap('CREDIT_NOTE', <<<XML
<VOUCHER VCHTYPE="Credit Note" ACTION="Create">
 <DATE>{$date}</DATE>
 <VOUCHERNUMBER>{$voucher}</VOUCHERNUMBER>
 <PARTYLEDGERNAME>{$party}</PARTYLEDGERNAME>
 <AMOUNT>{$creditNote->grand_total}</AMOUNT>
</VOUCHER>
XML);
    }

    public function buildPurchaseInvoiceXml(PurchaseInvoice $invoice): string
    {
        $invoice->loadMissing('supplier');
        $party = htmlspecialchars($invoice->supplier?->name ?? 'Supplier', ENT_XML1);
        $voucher = htmlspecialchars($invoice->invoice_no ?? ('PI-'.$invoice->id), ENT_XML1);
        $date = optional($invoice->invoice_date)->format('Ymd') ?? now()->format('Ymd');

        return $this->wrap('PURCHASE_INVOICE', <<<XML
<VOUCHER VCHTYPE="Purchase" ACTION="Create">
 <DATE>{$date}</DATE>
 <VOUCHERNUMBER>{$voucher}</VOUCHERNUMBER>
 <PARTYLEDGERNAME>{$party}</PARTYLEDGERNAME>
 <AMOUNT>{$invoice->grand_total}</AMOUNT>
</VOUCHER>
XML);
    }

    protected function wrap(string $type, string $inner): string
    {
        $inner = trim($inner);
        $company = htmlspecialchars((string) config('services.tally.company', ''), ENT_XML1);

        // Tally Prime / ERP 9 compatible Import Data envelope.
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ENVELOPE>
 <HEADER>
  <TALLYREQUEST>Import Data</TALLYREQUEST>
 </HEADER>
 <BODY>
  <IMPORTDATA>
   <REQUESTDESC>
    <REPORTNAME>Vouchers</REPORTNAME>
    <STATICVARIABLES>
     <SVCURRENTCOMPANY>{$company}</SVCURRENTCOMPANY>
    </STATICVARIABLES>
   </REQUESTDESC>
   <REQUESTDATA>
    <TALLYMESSAGE xmlns:UDF="TallyUDF">
     <!-- DMS document type: {$type} -->
     {$inner}
    </TALLYMESSAGE>
   </REQUESTDATA>
  </IMPORTDATA>
 </BODY>
</ENVELOPE>
XML;
    }
}
