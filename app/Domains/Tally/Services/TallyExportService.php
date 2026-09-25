<?php

namespace App\Domains\Tally\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Tally\Models\TallySyncQueue;
use App\Jobs\ProcessTallySyncJob;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;


class TallyExportService
{
    public function __construct(
        protected TallySyncMappingService $mappingService
    ) {}

    public function enqueue(string $documentType, Model $document, bool $dispatch = true): TallySyncQueue
    {
        $payload = match ($documentType) {
            'invoice'          => $this->buildInvoiceXml($document instanceof Invoice ? $document : throw new InvalidArgumentException('Expected Invoice')),
            'payment'          => $this->buildPaymentXml($document instanceof Payment ? $document : throw new InvalidArgumentException('Expected Payment')),
            'credit_note'      => $this->buildCreditNoteXml($document instanceof CreditNote ? $document : throw new InvalidArgumentException('Expected CreditNote')),
            'purchase_invoice' => $this->buildPurchaseInvoiceXml($document instanceof PurchaseInvoice ? $document : throw new InvalidArgumentException('Expected PurchaseInvoice')),
            'purchase_order'   => $this->buildPurchaseOrderXml($document instanceof PurchaseOrder ? $document : throw new InvalidArgumentException('Expected PurchaseOrder')),
            'product'          => $this->buildStockItemXml($document instanceof Product ? $document : throw new InvalidArgumentException('Expected Product')),
            'customer'         => $this->buildLedgerXml($document instanceof Customer ? $document : throw new InvalidArgumentException('Expected Customer')),
            'uom'              => $this->buildUomXml($document instanceof Uom ? $document : throw new InvalidArgumentException('Expected Uom')),
            default            => throw new InvalidArgumentException("Unsupported Tally document type [{$documentType}]"),
        };

        $queue = TallySyncQueue::create([
            'document_type' => $documentType,
            'document_id'   => $document->getKey(),
            'payload'       => $payload,
            'status'        => 'pending',
            'attempts'      => 0,
        ]);

        if ($dispatch) {
            ProcessTallySyncJob::dispatch($queue->id);
        }

        return $queue;
    }

    /**
     * Enqueue a master record (Product / Customer / Uom) immediately.
     * Unlike vouchers these do NOT need a status gate — they sync as soon as they are saved.
     */
    public function enqueueMaster(Model $document): ?TallySyncQueue
    {
        $map = [
            Product::class  => 'product',
            Customer::class => 'customer',
            Uom::class      => 'uom',
        ];

        $type = $map[$document::class] ?? null;
        if (! $type) {
            return null;
        }

        try {
            return $this->enqueue($type, $document);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function enqueuePostedDocument(Model $document): ?TallySyncQueue
    {
        $map = [
            Invoice::class         => 'invoice',
            Payment::class         => 'payment',
            CreditNote::class      => 'credit_note',
            PurchaseInvoice::class => 'purchase_invoice',
            PurchaseOrder::class   => 'purchase_order',
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

    protected function getVoucherAction(Model $document): string
    {
        $mapping = $this->mappingService->findForDms($document::class, $document->getKey(), 'voucher');
        return $mapping ? 'Alter' : 'Create';
    }

    public function buildInvoiceXml(Invoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'items.product']);
        $action = $this->getVoucherAction($invoice);

        $lines = '';
        foreach ($invoice->items as $item) {
            $name = htmlspecialchars($item->product?->name ?? 'Item', ENT_XML1);
            $lines .= "<ALLINVENTORYENTRIES.LIST><STOCKITEMNAME>{$name}</STOCKITEMNAME>"
                ."<ACTUALQTY>{$item->quantity}</ACTUALQTY><RATE>{$item->unit_price}</RATE>"
                ."<AMOUNT>{$item->line_total}</AMOUNT></ALLINVENTORYENTRIES.LIST>";
        }

        $party = htmlspecialchars($invoice->customer?->name ?? 'Customer', ENT_XML1);
        
        // Use mapping for party ledger if it exists
        $partyMapping = $invoice->customer ? $this->mappingService->findForDms(\App\Domains\Master\Models\Customer::class, $invoice->customer->id, 'ledger') : null;
        if ($partyMapping && $partyMapping->tally_name) {
            $party = htmlspecialchars($partyMapping->tally_name, ENT_XML1);
        }

        $voucher = htmlspecialchars($invoice->invoice_no, ENT_XML1);
        $date = $invoice->invoice_date?->format('Ymd') ?? now()->format('Ymd');

        return $this->wrap("INVOICE", <<<XML
<VOUCHER VCHTYPE="Sales" ACTION="{$action}">
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
        $action = $this->getVoucherAction($payment);

        $party = htmlspecialchars($payment->customer?->name ?? 'Customer', ENT_XML1);
        $partyMapping = $payment->customer ? $this->mappingService->findForDms(\App\Domains\Master\Models\Customer::class, $payment->customer->id, 'ledger') : null;
        if ($partyMapping && $partyMapping->tally_name) {
            $party = htmlspecialchars($partyMapping->tally_name, ENT_XML1);
        }

        $voucher = htmlspecialchars($payment->payment_no ?? ('PAY-'.$payment->id), ENT_XML1);
        $date = optional($payment->paid_at)->format('Ymd') ?? now()->format('Ymd');
        $amount = $payment->amount;

        return $this->wrap('PAYMENT', <<<XML
<VOUCHER VCHTYPE="Receipt" ACTION="{$action}">
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
        $action = $this->getVoucherAction($creditNote);

        $party = htmlspecialchars($creditNote->customer?->name ?? 'Customer', ENT_XML1);
        $partyMapping = $creditNote->customer ? $this->mappingService->findForDms(\App\Domains\Master\Models\Customer::class, $creditNote->customer->id, 'ledger') : null;
        if ($partyMapping && $partyMapping->tally_name) {
            $party = htmlspecialchars($partyMapping->tally_name, ENT_XML1);
        }

        $voucher = htmlspecialchars($creditNote->credit_note_no, ENT_XML1);
        $date = $creditNote->credit_note_date?->format('Ymd') ?? now()->format('Ymd');

        return $this->wrap('CREDIT_NOTE', <<<XML
<VOUCHER VCHTYPE="Credit Note" ACTION="{$action}">
 <DATE>{$date}</DATE>
 <VOUCHERNUMBER>{$voucher}</VOUCHERNUMBER>
 <PARTYLEDGERNAME>{$party}</PARTYLEDGERNAME>
 <AMOUNT>{$creditNote->grand_total}</AMOUNT>
</VOUCHER>
XML);
    }

    public function buildPurchaseInvoiceXml(PurchaseInvoice $invoice): string
{
    $invoice->loadMissing([
        'supplier',
        'items.product',
        'items.uom',
    ]);
    $action = $this->getVoucherAction($invoice);

    $party = htmlspecialchars(
        $invoice->supplier?->name ?? 'Supplier',
        ENT_XML1
    );
    $partyMapping = $invoice->supplier ? $this->mappingService->findForDms(\App\Domains\Master\Models\Customer::class, $invoice->supplier->id, 'ledger') : null;
    if ($partyMapping && $partyMapping->tally_name) {
        $party = htmlspecialchars($partyMapping->tally_name, ENT_XML1);
    }

    $voucher = htmlspecialchars(
        $invoice->invoice_no ?? ('PI-'.$invoice->id),
        ENT_XML1
    );

    $date = optional($invoice->invoice_date)->format('Ymd')
        ?? now()->format('Ymd');

    $lines = '';

    foreach ($invoice->items as $item) {
        $stockItem = htmlspecialchars(
            $item->product?->name ?? 'Item',
            ENT_XML1
        );
        $itemMapping = $item->product ? $this->mappingService->findForDms(\App\Domains\Master\Models\Product::class, $item->product->id, 'stock_item') : null;
        if ($itemMapping && $itemMapping->tally_name) {
            $stockItem = htmlspecialchars($itemMapping->tally_name, ENT_XML1);
        }

        $uom = htmlspecialchars(
            $item->uom?->name ?? 'Nos',
            ENT_XML1
        );

        $quantity = (float) $item->quantity;
        $rate = (float) $item->unit_cost;
        $amount = $quantity * $rate;

        $amount = number_format($amount, 2, '.', '');

        $batchName = htmlspecialchars(
            $item->batch_no ?: 'Primary Batch',
            ENT_XML1
        );

        $lines .= <<<XML
    <ALLINVENTORYENTRIES.LIST>
    <STOCKITEMNAME>{$stockItem}</STOCKITEMNAME>
    <ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>
    <ACTUALQTY>{$quantity} {$uom}</ACTUALQTY>
    <BILLEDQTY>{$quantity} {$uom}</BILLEDQTY>
    <RATE>{$rate}</RATE>
    <AMOUNT>-{$amount}</AMOUNT>
    <BATCHALLOCATIONS.LIST>
    <BATCHNAME>{$batchName}</BATCHNAME>
    <AMOUNT>-{$amount}</AMOUNT>
    <ACTUALQTY>{$quantity} {$uom}</ACTUALQTY>
    <BILLEDQTY>{$quantity} {$uom}</BILLEDQTY>
    </BATCHALLOCATIONS.LIST>
    </ALLINVENTORYENTRIES.LIST>
XML;
    }

    $totalCgst = $invoice->items->sum('cgst_amount');
    $totalSgst = $invoice->items->sum('sgst_amount');

    if ($totalCgst > 0) {
        $cgstAmount = number_format($totalCgst, 2, '.', '');
        $lines .= <<<XML
    <LEDGERENTRIES.LIST>
     <LEDGERNAME>CGST</LEDGERNAME>
     <ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>
     <AMOUNT>-{$cgstAmount}</AMOUNT>
    </LEDGERENTRIES.LIST>
XML;
    }

    if ($totalSgst > 0) {
        $sgstAmount = number_format($totalSgst, 2, '.', '');
        $lines .= <<<XML
    <LEDGERENTRIES.LIST>
     <LEDGERNAME>SGST</LEDGERNAME>
     <ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>
     <AMOUNT>-{$sgstAmount}</AMOUNT>
    </LEDGERENTRIES.LIST>
XML;
    }

    return $this->wrap('PURCHASE_INVOICE', <<<XML
<VOUCHER VCHTYPE="Purchase" ACTION="{$action}">
 <DATE>{$date}</DATE>
 <VOUCHERNUMBER>{$voucher}</VOUCHERNUMBER>
 <VOUCHERTYPENAME>Purchase</VOUCHERTYPENAME>
 <PERSISTEDVIEW>Invoice Voucher View</PERSISTEDVIEW>
 <PARTYLEDGERNAME>{$party}</PARTYLEDGERNAME>
 <AMOUNT>{$invoice->grand_total}</AMOUNT>
 {$lines}
</VOUCHER>
XML);
}

    // ─────────────────────────────────────────────────────────────
    // Purchase Order
    // ─────────────────────────────────────────────────────────────
    public function buildPurchaseOrderXml(PurchaseOrder $order): string
    {
        $order->loadMissing(['supplier', 'items.product', 'items.uom']);
        $action = $this->getVoucherAction($order);

        $party = htmlspecialchars($order->supplier?->name ?? 'Supplier', ENT_XML1);
        $partyMapping = $order->supplier
            ? $this->mappingService->findForDms(Customer::class, $order->supplier->id, 'ledger')
            : null;
        if ($partyMapping?->tally_name) {
            $party = htmlspecialchars($partyMapping->tally_name, ENT_XML1);
        }

        $voucher = htmlspecialchars($order->order_no ?? ('PO-'.$order->id), ENT_XML1);
        $date    = optional($order->order_date)->format('Ymd') ?? now()->format('Ymd');
        $lines   = '';

        foreach ($order->items as $item) {
            $stockItem = htmlspecialchars($item->product?->name ?? 'Item', ENT_XML1);
            $itemMapping = $item->product
                ? $this->mappingService->findForDms(Product::class, $item->product->id, 'stock_item')
                : null;
            if ($itemMapping?->tally_name) {
                $stockItem = htmlspecialchars($itemMapping->tally_name, ENT_XML1);
            }

            $uom      = htmlspecialchars($item->uom?->name ?? 'Nos', ENT_XML1);
            $qty      = (float) $item->quantity;
            $rate     = (float) $item->unit_cost;
            $amt      = number_format($qty * $rate, 2, '.', '');

            $lines .= <<<XML
    <ALLINVENTORYENTRIES.LIST>
     <STOCKITEMNAME>{$stockItem}</STOCKITEMNAME>
     <ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>
     <ACTUALQTY>{$qty} {$uom}</ACTUALQTY>
     <BILLEDQTY>{$qty} {$uom}</BILLEDQTY>
     <RATE>{$rate}</RATE>
     <AMOUNT>-{$amt}</AMOUNT>
    </ALLINVENTORYENTRIES.LIST>
XML;
        }

        return $this->wrap('PURCHASE_ORDER', <<<XML
<VOUCHER VCHTYPE="Purchase Order" ACTION="{$action}">
 <DATE>{$date}</DATE>
 <VOUCHERNUMBER>{$voucher}</VOUCHERNUMBER>
 <VOUCHERTYPENAME>Purchase Order</VOUCHERTYPENAME>
 <PERSISTEDVIEW>Invoice Voucher View</PERSISTEDVIEW>
 <PARTYLEDGERNAME>{$party}</PARTYLEDGERNAME>
 {$lines}
</VOUCHER>
XML);
    }

    // ─────────────────────────────────────────────────────────────
    // Masters  (Stock Item / Ledger / UOM)
    // These use REPORTNAME=All Masters, not Vouchers.
    // ─────────────────────────────────────────────────────────────

    public function buildStockItemXml(Product $product): string
    {
        $product->loadMissing('baseUom');

        $mapping = $this->mappingService->findForDms(Product::class, $product->id, 'stock_item');
        $action  = $mapping ? 'Alter' : 'Create';

        $name    = htmlspecialchars($product->name, ENT_XML1);
        $uom     = htmlspecialchars($product->baseUom?->name ?? 'Nos', ENT_XML1);
        $hsn     = htmlspecialchars($product->hsn_code ?? '', ENT_XML1);
        $gst     = number_format((float) ($product->tax_rate ?? 0), 2, '.', '');

        return $this->wrapMaster('PRODUCT', <<<XML
<STOCKITEM NAME="{$name}" ACTION="{$action}">
 <NAME>{$name}</NAME>
 <BASEUNITS>{$uom}</BASEUNITS>
 <HSNDETAILS.LIST>
  <HSNCODE>{$hsn}</HSNCODE>
  <GSTRATE>{$gst}</GSTRATE>
 </HSNDETAILS.LIST>
</STOCKITEM>
XML);
    }

    public function buildLedgerXml(Customer $customer): string
    {
        $mapping = $this->mappingService->findForDms(Customer::class, $customer->id, 'ledger');
        $action  = $mapping ? 'Alter' : 'Create';

        $name    = htmlspecialchars($customer->name, ENT_XML1);
        $gstin   = htmlspecialchars($customer->gstin ?? '', ENT_XML1);
        $address = htmlspecialchars($customer->address ?? '', ENT_XML1);
        $state   = htmlspecialchars($customer->state ?? '', ENT_XML1);

        // Determine the Tally parent group based on the DMS party_type
        $parent = match (true) {
            $customer->isSupplier()      => 'Sundry Creditors',
            $customer->isCustomerParty() => 'Sundry Debtors',
            default                      => 'Sundry Debtors',
        };

        return $this->wrapMaster('CUSTOMER', <<<XML
<LEDGER NAME="{$name}" ACTION="{$action}">
 <NAME>{$name}</NAME>
 <PARENT>{$parent}</PARENT>
 <GSTREGISTRATIONTYPE>Regular</GSTREGISTRATIONTYPE>
 <PARTYGSTIN>{$gstin}</PARTYGSTIN>
 <ADDRESS.LIST TYPE="Address">
  <ADDRESS>{$address}</ADDRESS>
 </ADDRESS.LIST>
 <STATENAME>{$state}</STATENAME>
 <ISCOSTCENTREON>No</ISCOSTCENTREON>
</LEDGER>
XML);
    }

    public function buildUomXml(Uom $uom): string
    {
        $mapping = $this->mappingService->findForDms(Uom::class, $uom->id, 'uom');
        $action  = $mapping ? 'Alter' : 'Create';

        $name     = htmlspecialchars($uom->name, ENT_XML1);
        $symbol   = htmlspecialchars($uom->code ?? $uom->name, ENT_XML1);

        return $this->wrapMaster('UOM', <<<XML
<UNIT NAME="{$name}" ACTION="{$action}">
 <NAME>{$name}</NAME>
 <SYMBOL>{$symbol}</SYMBOL>
 <FORMALNAME>{$name}</FORMALNAME>
 <ISSIMPLEUNIT>Yes</ISSIMPLEUNIT>
</UNIT>
XML);
    }

    /**
     * Wraps master data (Stock Item / Ledger / UOM) in the correct
     * Tally "All Masters" import envelope instead of the Vouchers one.
     */
    protected function wrapMaster(string $type, string $inner): string
    {
        $inner   = trim($inner);
        $company = htmlspecialchars((string) config('services.tally.company', ''), ENT_XML1);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ENVELOPE>
 <HEADER>
  <TALLYREQUEST>Import Data</TALLYREQUEST>
 </HEADER>
 <BODY>
  <IMPORTDATA>
   <REQUESTDESC>
    <REPORTNAME>All Masters</REPORTNAME>
    <STATICVARIABLES>
     <SVCURRENTCOMPANY>{$company}</SVCURRENTCOMPANY>
    </STATICVARIABLES>
   </REQUESTDESC>
   <REQUESTDATA>
    <TALLYMESSAGE xmlns:UDF="TallyUDF">
     <!-- DMS master type: {$type} -->
     {$inner}
    </TALLYMESSAGE>
   </REQUESTDATA>
  </IMPORTDATA>
 </BODY>
</ENVELOPE>
XML;
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
