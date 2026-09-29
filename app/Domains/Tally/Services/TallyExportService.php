<?php

namespace App\Domains\Tally\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Warehouse;
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
    /** Queue document_type => DMS model class. */
    public const DOCUMENT_CLASSES = [
        'invoice'          => Invoice::class,
        'payment'          => Payment::class,
        'credit_note'      => CreditNote::class,
        'purchase_invoice' => PurchaseInvoice::class,
        'purchase_order'   => PurchaseOrder::class,
        'product'          => Product::class,
        'customer'         => Customer::class,
        'uom'              => Uom::class,
        'godown'           => Warehouse::class,
    ];

    public const VOUCHER_TYPES = ['invoice', 'payment', 'credit_note', 'purchase_invoice', 'purchase_order'];

    /** Queue document_type => tally_sync_mappings.tally_type for masters. */
    public const MASTER_TALLY_TYPES = [
        'product'  => 'stock_item',
        'customer' => 'ledger',
        'uom'      => 'uom',
        'godown'   => 'godown',
    ];

    public function __construct(
        protected TallySyncMappingService $mappingService
    ) {}

    public function enqueue(string $documentType, Model $document, bool $dispatch = true): TallySyncQueue
    {
        if ($this->hasDependencies($documentType)) {
            $this->enqueueMissingMasters($document, $dispatch);
        }

        $queue = TallySyncQueue::create([
            'document_type' => $documentType,
            'document_id'   => $document->getKey(),
            'payload'       => $this->buildPayload($documentType, $document),
            'status'        => 'pending',
            'attempts'      => 0,
        ]);

        if ($dispatch) {
            ProcessTallySyncJob::dispatch($queue->id);
        }

        return $queue;
    }

    public function buildPayload(string $documentType, Model $document): string
    {
        $expected = self::DOCUMENT_CLASSES[$documentType]
            ?? throw new InvalidArgumentException("Unsupported Tally document type [{$documentType}]");

        if (! $document instanceof $expected) {
            throw new InvalidArgumentException("Expected {$expected} for Tally document type [{$documentType}]");
        }

        return match ($documentType) {
            'invoice'          => $this->buildInvoiceXml($document),
            'payment'          => $this->buildPaymentXml($document),
            'credit_note'      => $this->buildCreditNoteXml($document),
            'purchase_invoice' => $this->buildPurchaseInvoiceXml($document),
            'purchase_order'   => $this->buildPurchaseOrderXml($document),
            'product'          => $this->buildStockItemXml($document),
            'customer'         => $this->buildLedgerXml($document),
            'uom'              => $this->buildUomXml($document),
            'godown'           => $this->buildGodownXml($document),
        };
    }

    public function resolveDocument(TallySyncQueue $queue): ?Model
    {
        $class = self::DOCUMENT_CLASSES[$queue->document_type] ?? null;

        return $class ? $class::query()->find($queue->document_id) : null;
    }

    /**
     * Enqueue a master record (Product / Customer / Uom) immediately.
     * Unlike vouchers these do NOT need a status gate — they sync as soon as they are saved.
     */
    public function enqueueMaster(Model $document): ?TallySyncQueue
    {
        $map = [
            Product::class   => 'product',
            Customer::class  => 'customer',
            Uom::class       => 'uom',
            Warehouse::class => 'godown',
        ];

        $type = $map[$document::class] ?? null;
        if (! $type) {
            return null;
        }

        try {
            return $this->enqueue($type, $document);
        } catch (\Throwable $e) {
            \Log::error('Tally master enqueue failed', [
                'model' => $document::class,
                'id' => $document->getKey(),
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
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

    /**
     * Rebuilds the XML from the current DMS record so that fixed masters / mappings
     * are picked up, instead of resending the payload captured at enqueue time.
     */
    public function retry(TallySyncQueue $queue): TallySyncQueue
    {
        $payload = $queue->payload;
        $document = $this->resolveDocument($queue);

        if ($document) {
            if ($this->hasDependencies($queue->document_type)) {
                $this->enqueueMissingMasters($document);
            }
            $payload = $this->buildPayload($queue->document_type, $document);
        }

        $queue->update([
            'status' => 'pending',
            'payload' => $payload,
            'last_error' => null,
        ]);

        ProcessTallySyncJob::dispatch($queue->id);

        return $queue->fresh();
    }

    /**
     * Called when the connector reports a successful import so later syncs
     * send ACTION="Alter" and use the name Tally knows the record by.
     */
    public function recordSuccess(TallySyncQueue $queue, ?string $response): void
    {
        $document = $this->resolveDocument($queue);
        if (! $document) {
            return;
        }

        $type = $queue->document_type;

        if (in_array($type, self::VOUCHER_TYPES, true)) {
            $lastVchId = self::parseTallyResponse($response)['lastvchid'];
            $existing = $this->mappingService->findForDms($document::class, $document->getKey(), 'voucher');

            $this->mappingService->createOrUpdate(
                $document,
                $document::class,
                'voucher',
                $lastVchId > 0 ? (string) $lastVchId : $existing?->tally_guid,
                $this->remoteId($type, $document),
                'synced'
            );

            return;
        }

        $tallyType = self::MASTER_TALLY_TYPES[$type] ?? null;
        if (! $tallyType) {
            return;
        }

        $name = (string) $document->name;
        $existing = $this->mappingService->findForDms($document::class, $document->getKey(), $tallyType);
        $guid = $existing?->tally_guid
            ?? ($type === 'godown' ? 'godown:'.mb_strtolower($name) : null);

        $this->mappingService->createOrUpdate($document, $document::class, $tallyType, $guid, $name, 'synced');
    }

    /**
     * Handles both the TallyPrime <RESPONSE> shape and the ERP 9 ENVELOPE/BODY/DATA/IMPORTRESULT shape.
     *
     * @return array{created:int, altered:int, combined:int, ignored:int, errors:int, exceptions:int, lastvchid:int, line_errors:list<string>}
     */
    public static function parseTallyResponse(?string $response): array
    {
        $result = [
            'created' => 0, 'altered' => 0, 'combined' => 0, 'ignored' => 0,
            'errors' => 0, 'exceptions' => 0, 'lastvchid' => 0, 'line_errors' => [],
        ];

        if (! $response) {
            return $result;
        }

        foreach (['created', 'altered', 'combined', 'ignored', 'errors', 'exceptions', 'lastvchid'] as $tag) {
            if (preg_match('/<'.strtoupper($tag).'>\s*(-?\d+)\s*<\//i', $response, $m)) {
                $result[$tag] = (int) $m[1];
            }
        }

        if (preg_match_all('/<LINEERROR>(.*?)<\/LINEERROR>/is', $response, $m)) {
            $result['line_errors'] = array_map(
                fn ($e) => html_entity_decode(trim($e), ENT_QUOTES | ENT_XML1),
                $m[1]
            );
        }

        return $result;
    }

    protected function hasDependencies(string $documentType): bool
    {
        return $documentType === 'product' || in_array($documentType, self::VOUCHER_TYPES, true);
    }

    /**
     * Tally rejects a voucher if its party ledger, godown, units or stock items do not exist,
     * and a stock item if its unit does not exist. Queue any of those that were never synced
     * so they are imported first (connector processes in id order).
     */
    protected function enqueueMissingMasters(Model $document, bool $dispatch = true): void
    {
        $masters = [];

        if ($document instanceof Product) {
            $document->loadMissing('baseUom');
            if ($document->baseUom) {
                $masters[] = ['uom', $document->baseUom];
            }
        }

        $party = match (true) {
            $document instanceof PurchaseInvoice, $document instanceof PurchaseOrder => $document->supplier,
            $document instanceof Invoice, $document instanceof Payment, $document instanceof CreditNote => $document->customer,
            default => null,
        };
        if ($party) {
            $masters[] = ['customer', $party];
        }

        if (($document instanceof PurchaseInvoice || $document instanceof PurchaseOrder) && $document->warehouse) {
            $masters[] = ['godown', $document->warehouse];
        }

        if ($document instanceof PurchaseInvoice || $document instanceof PurchaseOrder || $document instanceof Invoice) {
            $document->loadMissing(['items.product.baseUom', 'items.uom']);

            $units = $document->items->pluck('uom')
                ->merge($document->items->pluck('product.baseUom'))
                ->filter()
                ->unique('id');
            foreach ($units as $uom) {
                $masters[] = ['uom', $uom];
            }

            foreach ($document->items->pluck('product')->filter()->unique('id') as $product) {
                $masters[] = ['product', $product];
            }
        }

        foreach ($masters as [$type, $master]) {
            $tallyType = self::MASTER_TALLY_TYPES[$type];

            if ($this->mappingService->findForDms($master::class, $master->getKey(), $tallyType)) {
                continue;
            }

            $alreadyQueued = TallySyncQueue::query()
                ->where('document_type', $type)
                ->where('document_id', $master->getKey())
                ->whereIn('status', ['pending', 'sent'])
                ->exists();

            if (! $alreadyQueued) {
                $this->enqueue($type, $master, $dispatch);
            }
        }
    }

    protected function getVoucherAction(Model $document): string
    {
        $mapping = $this->mappingService->findForDms($document::class, $document->getKey(), 'voucher');
        return $mapping ? 'Alter' : 'Create';
    }

    // ─────────────────────────────────────────────────────────────
    // Vouchers
    // Tally sign convention: negative AMOUNT = debit (ISDEEMEDPOSITIVE=Yes),
    // positive AMOUNT = credit (ISDEEMEDPOSITIVE=No). Every voucher must net to zero,
    // otherwise Tally rejects it with <EXCEPTIONS>1</EXCEPTIONS> and no line error.
    // ─────────────────────────────────────────────────────────────

    public function buildInvoiceXml(Invoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'items.product.baseUom', 'items.uom']);

        $party = $this->partyName($invoice->customer, 'Customer');
        $voucherNo = $invoice->invoice_no;
        $godown = $this->godownName(null);
        $salesLedger = (string) config('services.tally.sales_ledger');

        $entries = [];
        $sum = 0.0;

        foreach ($invoice->items as $item) {
            $qty = (float) $item->quantity;
            $rate = (float) $item->unit_price;
            $amount = round($qty * $rate - (float) $item->discount_amount, 2);
            $effectiveRate = $qty > 0 ? $amount / $qty : $rate;

            $entries[] = $this->inventoryEntry(
                $this->stockItemName($item->product),
                $qty,
                $this->unitName($item->uom, $item->product),
                $effectiveRate,
                $amount,
                $godown,
                $salesLedger
            );
            $sum += $amount;
        }

        $tax = round((float) $invoice->tax_amount, 2);
        $sum += $this->appendGstEntries($entries, round($tax / 2, 2), round($tax - round($tax / 2, 2), 2), 0.0, false, 'output');

        $grandTotal = round((float) $invoice->grand_total, 2);
        $partyEntry = $this->ledgerEntry($party, -$grandTotal, true, $this->billAllocation($voucherNo, 'New Ref', -$grandTotal));
        $sum -= $grandTotal;

        return $this->wrap('INVOICE', $this->voucherXml(
            'invoice',
            $invoice,
            'Sales',
            $invoice->invoice_date?->format('Ymd') ?? now()->format('Ymd'),
            $voucherNo,
            $party,
            $partyEntry.implode('', $entries).$this->roundOffEntry($sum),
            isInvoice: true,
            reference: $invoice->reference_no
        ));
    }

    public function buildPaymentXml(Payment $payment): string
    {
        $payment->loadMissing(['customer', 'invoice']);

        $party = $this->partyName($payment->customer, 'Customer');
        $voucherNo = $payment->payment_no ?? ('PAY-'.$payment->id);
        $amount = round((float) $payment->amount, 2);

        $method = strtolower((string) $payment->method);
        $cashOrBank = $method === 'cash'
            ? (string) config('services.tally.cash_ledger')
            : (string) config('services.tally.bank_ledger');

        $bill = $payment->invoice?->invoice_no
            ? $this->billAllocation($payment->invoice->invoice_no, 'Agst Ref', $amount)
            : $this->billAllocation($voucherNo, 'On Account', $amount);

        $body = $this->ledgerEntry($party, $amount, true, $bill, 'ALLLEDGERENTRIES.LIST')
            .$this->ledgerEntry($cashOrBank, -$amount, false, '', 'ALLLEDGERENTRIES.LIST');

        return $this->wrap('PAYMENT', $this->voucherXml(
            'payment',
            $payment,
            'Receipt',
            optional($payment->paid_at)->format('Ymd') ?? now()->format('Ymd'),
            $voucherNo,
            $party,
            $body,
            reference: $payment->reference_no
        ));
    }

    public function buildCreditNoteXml(CreditNote $creditNote): string
    {
        $creditNote->loadMissing(['customer', 'invoice']);

        $party = $this->partyName($creditNote->customer, 'Customer');
        $voucherNo = $creditNote->credit_note_no;
        $grandTotal = round((float) $creditNote->grand_total, 2);
        $subtotal = round((float) $creditNote->subtotal, 2);
        $tax = round((float) $creditNote->tax_amount, 2);
        $tag = 'ALLLEDGERENTRIES.LIST';

        $bill = $creditNote->invoice?->invoice_no
            ? $this->billAllocation($creditNote->invoice->invoice_no, 'Agst Ref', $grandTotal)
            : $this->billAllocation($voucherNo, 'New Ref', $grandTotal);

        $entries = [
            $this->ledgerEntry($party, $grandTotal, true, $bill, $tag),
            $this->ledgerEntry((string) config('services.tally.sales_ledger'), -$subtotal, false, '', $tag),
        ];
        $sum = $grandTotal - $subtotal;
        $sum += $this->appendGstEntries($entries, round($tax / 2, 2), round($tax - round($tax / 2, 2), 2), 0.0, true, 'output', $tag);

        return $this->wrap('CREDIT_NOTE', $this->voucherXml(
            'credit_note',
            $creditNote,
            'Credit Note',
            $creditNote->credit_note_date?->format('Ymd') ?? now()->format('Ymd'),
            $voucherNo,
            $party,
            implode('', $entries).$this->roundOffEntry($sum, $tag),
            reference: $creditNote->invoice?->invoice_no
        ));
    }

    public function buildPurchaseInvoiceXml(PurchaseInvoice $invoice): string
    {
        $invoice->loadMissing(['supplier', 'warehouse', 'items.product.baseUom', 'items.uom']);

        $party = $this->partyName($invoice->supplier, 'Supplier');
        $voucherNo = $invoice->invoice_no ?? ('PI-'.$invoice->id);
        $billRef = $invoice->supplier_invoice_no ?: $voucherNo;
        $godown = $this->godownName($invoice->warehouse);
        $cgst = round((float) $invoice->items->sum('cgst_amount'), 2);
        $sgst = round((float) $invoice->items->sum('sgst_amount'), 2);
        $igst = max(0.0, round((float) $invoice->tax_amount - $cgst - $sgst, 2));
        $purchaseLedger = $this->purchaseLedger($igst > 0);
        $useBatches = (bool) config('services.tally.use_batches');

        $entries = [];
        $sum = 0.0;

        foreach ($invoice->items as $item) {
            $qty = (float) $item->quantity;
            $rate = (float) $item->unit_cost;
            $amount = round($qty * $rate, 2);

            $entries[] = $this->inventoryEntry(
                $this->stockItemName($item->product),
                $qty,
                $this->unitName($item->uom, $item->product),
                $rate,
                -$amount,
                $godown,
                $purchaseLedger,
                $useBatches && $item->batch_no ? $item->batch_no : 'Primary Batch'
            );
            $sum -= $amount;
        }

        $sum += $this->appendGstEntries($entries, $cgst, $sgst, $igst, true, 'input');

        $grandTotal = round((float) $invoice->grand_total, 2);
        $partyEntry = $this->ledgerEntry($party, $grandTotal, true, $this->billAllocation($billRef, 'New Ref', $grandTotal));
        $sum += $grandTotal;

        return $this->wrap('PURCHASE_INVOICE', $this->voucherXml(
            'purchase_invoice',
            $invoice,
            'Purchase',
            optional($invoice->invoice_date)->format('Ymd') ?? now()->format('Ymd'),
            $voucherNo,
            $party,
            $partyEntry.implode('', $entries).$this->roundOffEntry($sum),
            isInvoice: true,
            reference: $invoice->supplier_invoice_no
        ));
    }

    public function buildPurchaseOrderXml(PurchaseOrder $order): string
    {
        $order->loadMissing(['supplier', 'warehouse', 'items.product.baseUom', 'items.uom']);

        $party = $this->partyName($order->supplier, 'Supplier');
        $voucherNo = $order->po_no ?? ('PO-'.$order->id);
        $godown = $this->godownName($order->warehouse);
        $cgst = round((float) $order->items->sum('cgst_amount'), 2);
        $sgst = round((float) $order->items->sum('sgst_amount'), 2);
        $igst = max(0.0, round((float) $order->tax_amount - $cgst - $sgst, 2));
        $purchaseLedger = $this->purchaseLedger($igst > 0);

        $entries = [];
        $sum = 0.0;

        foreach ($order->items as $item) {
            $qty = (float) $item->quantity;
            $rate = (float) $item->unit_cost;
            $amount = round($qty * $rate, 2);

            $entries[] = $this->inventoryEntry(
                $this->stockItemName($item->product),
                $qty,
                $this->unitName($item->uom, $item->product),
                $rate,
                -$amount,
                $godown,
                $purchaseLedger
            );
            $sum -= $amount;
        }

        $sum += $this->appendGstEntries($entries, $cgst, $sgst, $igst, true, 'input');

        $grandTotal = round((float) $order->grand_total, 2);
        $partyEntry = $this->ledgerEntry($party, $grandTotal, true);
        $sum += $grandTotal;

        return $this->wrap('PURCHASE_ORDER', $this->voucherXml(
            'purchase_order',
            $order,
            'Purchase Order',
            optional($order->po_date)->format('Ymd') ?? now()->format('Ymd'),
            $voucherNo,
            $party,
            $partyEntry.implode('', $entries).$this->roundOffEntry($sum)
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // Masters  (Stock Item / Ledger / UOM / Godown)
    // These use REPORTNAME=All Masters, not Vouchers.
    // The NAME attribute is the name Tally currently knows (for Alter after a rename);
    // the <NAME> tag is the new name.
    // ─────────────────────────────────────────────────────────────

    public function buildStockItemXml(Product $product): string
    {
        $product->loadMissing('baseUom');

        $mapping = $this->mappingService->findForDms(Product::class, $product->id, 'stock_item');
        $action  = $mapping ? 'Alter' : 'Create';

        $name    = $this->xml($product->name);
        $current = $this->xml($mapping?->tally_name ?: $product->name);
        $uom     = $this->xml($this->unitName($product->baseUom, null) ?: 'Nos');
        $hsn     = $this->xml($product->hsn_code ?? '');
        $gst     = number_format((float) ($product->tax_rate ?? 0), 2, '.', '');

        return $this->wrapMaster('PRODUCT', <<<XML
<STOCKITEM NAME="{$current}" ACTION="{$action}">
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

        $name    = $this->xml($customer->name);
        $current = $this->xml($mapping?->tally_name ?: $customer->name);
        $gstin   = $this->xml($customer->gstin ?? '');
        $address = $this->xml($customer->address ?? '');
        $state   = $this->xml($customer->state ?? '');

        // Determine the Tally parent group based on the DMS party_type
        $parent = match (true) {
            $customer->isSupplier()      => 'Sundry Creditors',
            $customer->isCustomerParty() => 'Sundry Debtors',
            default                      => 'Sundry Debtors',
        };

        return $this->wrapMaster('CUSTOMER', <<<XML
<LEDGER NAME="{$current}" ACTION="{$action}">
 <NAME>{$name}</NAME>
 <PARENT>{$parent}</PARENT>
 <ISBILLWISEON>Yes</ISBILLWISEON>
 <GSTREGISTRATIONTYPE>Regular</GSTREGISTRATIONTYPE>
 <PARTYGSTIN>{$gstin}</PARTYGSTIN>
 <ADDRESS.LIST TYPE="String">
  <ADDRESS>{$address}</ADDRESS>
 </ADDRESS.LIST>
 <STATENAME>{$state}</STATENAME>
 <ISCOSTCENTRESON>No</ISCOSTCENTRESON>
</LEDGER>
XML);
    }

    public function buildUomXml(Uom $uom): string
    {
        $mapping = $this->mappingService->findForDms(Uom::class, $uom->id, 'uom');
        $action  = $mapping ? 'Alter' : 'Create';

        $name    = $this->xml($mapping?->tally_name ?: $uom->name);
        $current = $this->xml($mapping?->tally_name ?: $uom->name);

        return $this->wrapMaster('UOM', <<<XML
<UNIT NAME="{$current}" ACTION="{$action}">
 <NAME>{$name}</NAME>
 <ISSIMPLEUNIT>Yes</ISSIMPLEUNIT>
</UNIT>
XML);
    }

    public function buildGodownXml(Warehouse $warehouse): string
    {
        $mapping = $this->mappingService->findForDms(Warehouse::class, $warehouse->id, 'godown');
        $action  = $mapping ? 'Alter' : 'Create';

        $name    = $this->xml($warehouse->name);
        $current = $this->xml($mapping?->tally_name ?: $warehouse->name);

        return $this->wrapMaster('GODOWN', <<<XML
<GODOWN NAME="{$current}" ACTION="{$action}">
 <NAME>{$name}</NAME>
</GODOWN>
XML);
    }

    // ─────────────────────────────────────────────────────────────
    // XML building blocks
    // ─────────────────────────────────────────────────────────────

    protected function voucherXml(
        string $documentType,
        Model $document,
        string $voucherType,
        string $date,
        string $voucherNo,
        string $party,
        string $entries,
        bool $isInvoice = false,
        ?string $reference = null
    ): string {
        $action = $this->getVoucherAction($document);
        $remoteId = $this->xml($this->remoteId($documentType, $document));
        $vchType = $this->xml($voucherType);
        $number = $this->xml($voucherNo);
        $partyXml = $this->xml($party);
        $view = $isInvoice ? 'Invoice Voucher View' : 'Accounting Voucher View';
        $invoiceFlag = $isInvoice ? 'Yes' : 'No';
        $referenceXml = $reference ? '<REFERENCE>'.$this->xml($reference).'</REFERENCE>' : '';
        $narration = $this->xml("DMS {$voucherType} {$voucherNo}");

        if (in_array($voucherType, ['Purchase Order', 'Sales Order'], true)) {
            $view = 'Invoice Voucher View';
            $invoiceFlag = 'No';
        }

        return <<<XML
<VOUCHER REMOTEID="{$remoteId}" VCHTYPE="{$vchType}" ACTION="{$action}" OBJVIEW="{$view}">
 <DATE>{$date}</DATE>
 <EFFECTIVEDATE>{$date}</EFFECTIVEDATE>
 <VOUCHERTYPENAME>{$vchType}</VOUCHERTYPENAME>
 <VOUCHERNUMBER>{$number}</VOUCHERNUMBER>
 {$referenceXml}
 <PARTYLEDGERNAME>{$partyXml}</PARTYLEDGERNAME>
 <PARTYNAME>{$partyXml}</PARTYNAME>
 <PERSISTEDVIEW>{$view}</PERSISTEDVIEW>
 <ISINVOICE>{$invoiceFlag}</ISINVOICE>
 <NARRATION>{$narration}</NARRATION>
{$entries}
</VOUCHER>
XML;
    }

    protected function ledgerEntry(
        string $ledger,
        float $signedAmount,
        bool $isParty = false,
        string $billAllocation = '',
        string $tag = 'LEDGERENTRIES.LIST'
    ): string {
        $name = $this->xml($ledger);
        $deemed = $signedAmount < 0 ? 'Yes' : 'No';
        $partyFlag = $isParty ? 'Yes' : 'No';
        $amount = $this->money($signedAmount);

        return <<<XML
 <{$tag}>
  <LEDGERNAME>{$name}</LEDGERNAME>
  <ISDEEMEDPOSITIVE>{$deemed}</ISDEEMEDPOSITIVE>
  <ISPARTYLEDGER>{$partyFlag}</ISPARTYLEDGER>
  <AMOUNT>{$amount}</AMOUNT>
  {$billAllocation}
 </{$tag}>

XML;
    }

    protected function billAllocation(string $name, string $type, float $signedAmount): string
    {
        $billName = $this->xml($name);
        $amount = $this->money($signedAmount);

        return "<BILLALLOCATIONS.LIST><NAME>{$billName}</NAME><BILLTYPE>{$type}</BILLTYPE><AMOUNT>{$amount}</AMOUNT></BILLALLOCATIONS.LIST>";
    }

    protected function inventoryEntry(
        string $stockItem,
        float $qty,
        string $unit,
        float $rate,
        float $signedAmount,
        string $godown,
        string $ledger,
        string $batch = 'Primary Batch'
    ): string {
        $item = $this->xml($stockItem);
        $deemed = $signedAmount < 0 ? 'Yes' : 'No';
        $amount = $this->money($signedAmount);
        $unitXml = $this->xml($unit);
        $qtyText = trim($this->quantity($qty).' '.$unitXml);
        $rateText = number_format($rate, 2, '.', '').($unitXml !== '' ? '/'.$unitXml : '');
        $godownXml = $this->xml($godown);
        $batchXml = $this->xml($batch);
        $ledgerXml = $this->xml($ledger);

        return <<<XML
 <ALLINVENTORYENTRIES.LIST>
  <STOCKITEMNAME>{$item}</STOCKITEMNAME>
  <ISDEEMEDPOSITIVE>{$deemed}</ISDEEMEDPOSITIVE>
  <RATE>{$rateText}</RATE>
  <AMOUNT>{$amount}</AMOUNT>
  <ACTUALQTY>{$qtyText}</ACTUALQTY>
  <BILLEDQTY>{$qtyText}</BILLEDQTY>
  <BATCHALLOCATIONS.LIST>
   <GODOWNNAME>{$godownXml}</GODOWNNAME>
   <BATCHNAME>{$batchXml}</BATCHNAME>
   <AMOUNT>{$amount}</AMOUNT>
   <ACTUALQTY>{$qtyText}</ACTUALQTY>
   <BILLEDQTY>{$qtyText}</BILLEDQTY>
  </BATCHALLOCATIONS.LIST>
  <ACCOUNTINGALLOCATIONS.LIST>
   <LEDGERNAME>{$ledgerXml}</LEDGERNAME>
   <ISDEEMEDPOSITIVE>{$deemed}</ISDEEMEDPOSITIVE>
   <AMOUNT>{$amount}</AMOUNT>
  </ACCOUNTINGALLOCATIONS.LIST>
 </ALLINVENTORYENTRIES.LIST>

XML;
    }

    /**
     * Appends CGST/SGST/IGST ledger lines and returns the signed total added.
     * $ledgerSet is "input" (purchase side) or "output" (sales side).
     *
     * @param  list<string>  $entries
     */
    protected function appendGstEntries(
        array &$entries,
        float $cgst,
        float $sgst,
        float $igst,
        bool $isDebit,
        string $ledgerSet,
        string $tag = 'LEDGERENTRIES.LIST'
    ): float {
        $total = 0.0;

        foreach (['cgst' => $cgst, 'sgst' => $sgst, 'igst' => $igst] as $key => $amount) {
            if ($amount < 0.01) {
                continue;
            }
            $signed = $isDebit ? -$amount : $amount;
            $entries[] = $this->ledgerEntry((string) config("services.tally.{$ledgerSet}_{$key}_ledger"), $signed, false, '', $tag);
            $total += $signed;
        }

        return $total;
    }

    /** Posts whatever keeps the voucher from netting to zero to the round-off ledger. */
    protected function roundOffEntry(float $sum, string $tag = 'LEDGERENTRIES.LIST'): string
    {
        $difference = round(-$sum, 2);
        if (abs($difference) < 0.01) {
            return '';
        }

        return $this->ledgerEntry((string) config('services.tally.round_off_ledger'), $difference, false, '', $tag);
    }

    protected function purchaseLedger(bool $interstate): string
    {
        $ledger = $interstate ? config('services.tally.purchase_ledger_interstate') : null;

        return (string) ($ledger ?: config('services.tally.purchase_ledger'));
    }

    protected function remoteId(string $documentType, Model $document): string
    {
        return "dms-{$documentType}-{$document->getKey()}";
    }

    protected function partyName(?Customer $party, string $fallback): string
    {
        if (! $party) {
            return $fallback;
        }

        $mapping = $this->mappingService->findForDms(Customer::class, $party->id, 'ledger');

        return $mapping?->tally_name ?: $party->name;
    }

    protected function stockItemName(?Product $product): string
    {
        if (! $product) {
            return 'Item';
        }

        $mapping = $this->mappingService->findForDms(Product::class, $product->id, 'stock_item');

        return $mapping?->tally_name ?: $product->name;
    }

    protected function unitName(?Uom $uom, ?Product $product): string
    {
        $uom ??= $product?->baseUom;
        if (! $uom) {
            return '';
        }

        $mapping = $this->mappingService->findForDms(Uom::class, $uom->id, 'uom');

        return $mapping?->tally_name ?: (string) $uom->name;
    }

    protected function godownName(?Warehouse $warehouse): string
    {
        if (! $warehouse) {
            return (string) config('services.tally.default_godown');
        }

        $mapping = $this->mappingService->findForDms(Warehouse::class, $warehouse->id, 'godown');

        return $mapping?->tally_name ?: $warehouse->name;
    }

    protected function money(float $value): string
    {
        return number_format(round($value, 2), 2, '.', '');
    }

    protected function quantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    protected function xml(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES);
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
