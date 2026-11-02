<?php

namespace App\Domains\Tally\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Tally\Models\TallySyncQueue;
use App\Domains\TallySync\Support\SyncGate;
use App\Domains\TallySync\Templates\MasterTemplates;
use App\Domains\TallySync\Templates\XmlEnvelope;
use App\Domains\TallySync\Templates\XmlHelpers;
use App\Jobs\ProcessTallySyncJob;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;


class TallyExportService
{
    /** Queue document_type => DMS model class. */
    public const DOCUMENT_CLASSES = [
        'invoice'          => Invoice::class,
        'payment'          => Payment::class,
        'credit_note'      => CreditNote::class,
        'purchase_invoice' => PurchaseInvoice::class,
        'purchase_order'   => PurchaseOrder::class,
        'sales_order'      => Order::class,
        'quotation'        => Quotation::class,
        'goods_receipt'    => PurchaseInward::class,
        'product'          => Product::class,
        'customer'         => Customer::class,
        'uom'              => Uom::class,
        'godown'           => Warehouse::class,
    ];

    public const VOUCHER_TYPES = [
        'invoice',
        'payment',
        'credit_note',
        'purchase_invoice',
        'purchase_order',
        'sales_order',
        'quotation',
        'goods_receipt',
    ];

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
        if (! SyncGate::enabled()) {
            throw new RuntimeException('TallyPrime sync is disabled. Native Books is the live accounting path. Set TALLY_SYNC_ENABLED=true to re-enable.');
        }

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
            'sales_order'      => $this->buildSalesOrderXml($document),
            'quotation'        => $this->buildQuotationXml($document),
            'goods_receipt'    => $this->buildGoodsReceiptXml($document),
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

        $books = app(\App\Domains\TallySync\Queue\BooksTwin::class)->target($document);
        if ($books instanceof \Illuminate\Database\Eloquent\Model) {
            return app(\App\Domains\TallySync\Queue\BooksSyncQueue::class)->enqueue($books);
        }
        if ($books === 'skip') {
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
            Order::class           => 'sales_order',
            Quotation::class       => 'quotation',
            PurchaseInward::class  => 'goods_receipt',
        ];

        $type = $map[$document::class] ?? null;
        if (! $type) {
            return null;
        }

        $status = (string) ($document->status ?? '');
        $postedStatuses = match ($type) {
            'sales_order' => ['approved'],
            'quotation' => ['sent', 'accepted'],
            'goods_receipt' => ['posted', 'completed', 'received'],
            'purchase_order' => ['approved'],
            default => ['issued', 'posted', 'paid', 'partial', 'completed', 'approved'],
        };

        if ($status !== '' && ! in_array($status, $postedStatuses, true)) {
            return null;
        }

        $books = app(\App\Domains\TallySync\Queue\BooksTwin::class)->target($document);
        if ($books instanceof \Illuminate\Database\Eloquent\Model) {
            return app(\App\Domains\TallySync\Queue\BooksSyncQueue::class)->enqueue($books);
        }
        if ($books === 'skip') {
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
            $document instanceof PurchaseInvoice,
            $document instanceof PurchaseOrder,
            $document instanceof PurchaseInward => $document->supplier,
            $document instanceof Invoice,
            $document instanceof Payment,
            $document instanceof CreditNote,
            $document instanceof Order,
            $document instanceof Quotation => $document->customer,
            default => null,
        };
        if ($party) {
            $masters[] = ['customer', $party];
        }

        if (
            ($document instanceof PurchaseInvoice
                || $document instanceof PurchaseOrder
                || $document instanceof PurchaseInward
                || $document instanceof Order)
            && $document->warehouse
        ) {
            $masters[] = ['godown', $document->warehouse];
        }

        if (
            $document instanceof PurchaseInvoice
            || $document instanceof PurchaseOrder
            || $document instanceof Invoice
            || $document instanceof Order
            || $document instanceof Quotation
            || $document instanceof PurchaseInward
        ) {
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
        $salesLedger = $this->tallyLedger('sales');

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

        $place = trim((string) ($invoice->delivery_state ?: ''));

        return $this->wrap('INVOICE', $this->voucherXml(
            'invoice',
            $invoice,
            'Sales',
            $invoice->invoice_date?->format('Ymd') ?? now()->format('Ymd'),
            $voucherNo,
            $party,
            $partyEntry.implode('', $entries).$this->roundOffEntry($sum),
            isInvoice: true,
            reference: $invoice->reference_no,
            extra: array_merge($this->partyGstExtras($invoice->customer), array_filter([
                'narration' => $invoice->notes,
                'basic_buyer_name' => $invoice->customer?->name,
                'basic_buyer_address' => trim((string) ($invoice->billing_address ?: $invoice->shipping_address ?: '')),
                'place_of_supply' => $place !== '' ? $place : null,
            ], fn ($value) => $value !== null && $value !== ''))
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
            ? $this->tallyLedger('cash')
            : $this->tallyLedger('bank');

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
            reference: $payment->reference_no,
            extra: array_merge($this->partyGstExtras($payment->customer), array_filter([
                'narration' => $payment->notes,
            ], fn ($value) => $value !== null && $value !== ''))
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
            $this->ledgerEntry($this->tallyLedger('sales'), -$subtotal, false, '', $tag),
        ];
        $sum = $grandTotal - $subtotal;
        $sum += $this->appendGstEntries($entries, round($tax / 2, 2), round($tax - round($tax / 2, 2), 2), 0.0, true, 'output', $tag);

        $narration = trim(implode(' | ', array_filter([
            $creditNote->reason,
            $creditNote->notes,
        ])));

        return $this->wrap('CREDIT_NOTE', $this->voucherXml(
            'credit_note',
            $creditNote,
            'Credit Note',
            $creditNote->credit_note_date?->format('Ymd') ?? now()->format('Ymd'),
            $voucherNo,
            $party,
            implode('', $entries).$this->roundOffEntry($sum, $tag),
            reference: $creditNote->invoice?->invoice_no,
            extra: array_merge($this->partyGstExtras($creditNote->customer), array_filter([
                'narration' => $narration !== '' ? $narration : null,
            ], fn ($value) => $value !== null && $value !== ''))
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
        $useBatches = (bool) config('tally_sync.use_batches', config('services.tally.use_batches', false));

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
                ($useBatches || filled($item->batch_no)) && $item->batch_no ? $item->batch_no : 'Primary Batch'
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
            reference: $invoice->supplier_invoice_no,
            extra: array_merge($this->partyGstExtras($invoice->supplier), array_filter([
                'narration' => $invoice->notes,
                'basic_buyer_name' => $invoice->supplier?->name,
                'basic_buyer_address' => trim((string) ($invoice->billing_address ?: $invoice->shipping_address ?: '')),
            ], fn ($value) => $value !== null && $value !== ''))
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
            $batch = filled($item->batch_no) ? (string) $item->batch_no : 'Primary Batch';

            $entries[] = $this->inventoryEntry(
                $this->stockItemName($item->product),
                $qty,
                $this->unitName($item->uom, $item->product),
                $rate,
                -$amount,
                $godown,
                $purchaseLedger,
                $batch
            );
            $sum -= $amount;
        }

        $sum += $this->appendGstEntries($entries, $cgst, $sgst, $igst, true, 'input');

        // grand_total already includes freight_total — post freight as its own debit so the voucher nets.
        $freightTotal = $order->is_freight_applicable ? round((float) ($order->freight_total ?? 0), 2) : 0.0;
        if ($freightTotal > 0.009) {
            $entries[] = $this->ledgerEntry($purchaseLedger, -$freightTotal, false);
            $sum -= $freightTotal;
        }

        $grandTotal = round((float) $order->grand_total, 2);
        $partyEntry = $this->ledgerEntry($party, $grandTotal, true);
        $sum += $grandTotal;

        $narrationParts = array_values(array_filter([
            'DMS Purchase Order '.$voucherNo,
            $order->expected_date ? 'Expected: '.$order->expected_date->format('d-M-Y') : null,
            $order->credit_days ? 'Credit: '.(int) $order->credit_days.' days' : null,
            $order->due_date ? 'Due: '.$order->due_date->format('d-M-Y') : null,
            $freightTotal > 0 ? 'Freight: '.number_format($freightTotal, 2, '.', '') : null,
            $order->freight_notes ? 'Freight note: '.$order->freight_notes : null,
            $order->notes,
        ]));

        return $this->wrap('PURCHASE_ORDER', $this->voucherXml(
            'purchase_order',
            $order,
            'Purchase Order',
            optional($order->po_date)->format('Ymd') ?? now()->format('Ymd'),
            $voucherNo,
            $party,
            $partyEntry.implode('', $entries).$this->roundOffEntry($sum),
            extra: array_merge($this->partyGstExtras($order->supplier), array_filter([
                'narration' => implode(' | ', $narrationParts),
                'reference_date' => $order->expected_date?->format('Y-m-d'),
                'basic_buyer_address' => trim((string) ($order->shipping_address ?: $order->billing_address ?: '')),
            ], fn ($value) => $value !== null && $value !== ''))
        ));
    }

    public function buildSalesOrderXml(Order $order): string
    {
        $order->loadMissing(['customer', 'warehouse', 'items.product.baseUom', 'items.uom']);

        $party = $this->partyName($order->customer, 'Customer');
        $voucherNo = $order->order_no ?? ('SO-'.$order->id);
        $godown = $this->godownName($order->warehouse);
        $salesLedger = $this->tallyLedger('sales');
        $tax = round((float) $order->tax_amount, 2);

        $entries = [];
        $sum = 0.0;

        foreach ($order->items as $item) {
            $qty = (float) $item->quantity;
            $rate = (float) $item->unit_price;
            $amount = round((float) ($item->line_total ?: ($qty * $rate)), 2);
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

        $sum += $this->appendGstEntries($entries, round($tax / 2, 2), round($tax - round($tax / 2, 2), 2), 0.0, false, 'output');

        $grandTotal = round((float) $order->grand_total, 2);
        if ($grandTotal < 0.01) {
            $grandTotal = round($sum, 2);
        }
        $partyEntry = $this->ledgerEntry($party, -$grandTotal, true);
        $sum -= $grandTotal;

        $narrationParts = array_values(array_filter([
            'DMS Sales Order '.$voucherNo,
            $order->due_date ? 'Due: '.$order->due_date->format('d-M-Y') : null,
            $order->notes,
        ]));

        return $this->wrap('SALES_ORDER', $this->voucherXml(
            'sales_order',
            $order,
            'Sales Order',
            optional($order->order_date)->format('Ymd') ?? now()->format('Ymd'),
            $voucherNo,
            $party,
            $partyEntry.implode('', $entries).$this->roundOffEntry($sum),
            extra: array_merge($this->partyGstExtras($order->customer), array_filter([
                'narration' => implode(' | ', $narrationParts),
                'basic_buyer_name' => $order->customer?->name,
                'basic_buyer_address' => trim((string) ($order->shipping_address ?: $order->billing_address ?: '')),
            ], fn ($value) => $value !== null && $value !== ''))
        ));
    }

    public function buildQuotationXml(Quotation $quotation): string
    {
        $quotation->loadMissing(['customer', 'items.product.baseUom', 'items.uom']);

        $party = $this->partyName($quotation->customer, 'Customer');
        $voucherNo = $quotation->quotation_no ?? ('QT-'.$quotation->id);
        $godown = $this->godownName(null);
        $salesLedger = $this->tallyLedger('sales');
        $tax = round((float) $quotation->tax_amount, 2);

        $entries = [];
        $sum = 0.0;

        foreach ($quotation->items as $item) {
            $qty = (float) $item->quantity;
            $rate = (float) $item->unit_price;
            $amount = round((float) ($item->line_total ?: ($qty * $rate - (float) $item->discount_amount)), 2);
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

        $sum += $this->appendGstEntries($entries, round($tax / 2, 2), round($tax - round($tax / 2, 2), 2), 0.0, false, 'output');

        $grandTotal = round((float) $quotation->grand_total, 2);
        if ($grandTotal < 0.01) {
            $grandTotal = round($sum, 2);
        }
        $partyEntry = $this->ledgerEntry($party, -$grandTotal, true);
        $sum -= $grandTotal;

        $narrationParts = array_values(array_filter([
            'DMS Quotation '.$voucherNo,
            $quotation->valid_until ? 'Valid until: '.$quotation->valid_until->format('d-M-Y') : null,
            $quotation->notes,
        ]));

        return $this->wrap('QUOTATION', $this->voucherXml(
            'quotation',
            $quotation,
            'Quotation',
            optional($quotation->quotation_date)->format('Ymd') ?? now()->format('Ymd'),
            $voucherNo,
            $party,
            $partyEntry.implode('', $entries).$this->roundOffEntry($sum),
            extra: array_merge($this->partyGstExtras($quotation->customer), array_filter([
                'narration' => implode(' | ', $narrationParts),
                'is_optional' => 'Yes',
                'reference_date' => $quotation->valid_until?->format('Y-m-d'),
                'basic_buyer_name' => $quotation->customer?->name,
            ], fn ($value) => $value !== null && $value !== ''))
        ));
    }

    public function buildGoodsReceiptXml(PurchaseInward $inward): string
    {
        $inward->loadMissing(['supplier', 'warehouse', 'purchaseOrder', 'items.product.baseUom', 'items.uom']);

        $party = $this->partyName($inward->supplier, 'Supplier');
        $voucherNo = $inward->inward_no ?? ('GRN-'.$inward->id);
        $godown = $this->godownName($inward->warehouse);
        $purchaseLedger = $this->tallyLedger('purchase');

        $entries = [];
        $sum = 0.0;

        foreach ($inward->items as $item) {
            $qty = (float) ($item->accepted_qty ?: $item->received_qty ?: 0);
            if ($qty <= 0) {
                continue;
            }
            $rate = (float) $item->unit_cost;
            $amount = round($qty * $rate, 2);
            $batch = filled($item->batch_no) ? (string) $item->batch_no : 'Primary Batch';

            $entries[] = $this->inventoryEntry(
                $this->stockItemName($item->product),
                $qty,
                $this->unitName($item->uom, $item->product),
                $rate,
                -$amount,
                $godown,
                $purchaseLedger,
                $batch
            );
            $sum -= $amount;
        }

        $grandTotal = round(abs($sum), 2);
        $partyEntry = $this->ledgerEntry($party, $grandTotal, true);
        $sum += $grandTotal;

        $poNo = $inward->purchaseOrder?->po_no;
        $challan = trim((string) ($inward->supplier_challan_no ?: ''));
        $narrationParts = array_values(array_filter([
            'DMS Receipt Note '.$voucherNo,
            $poNo ? 'PO: '.$poNo : null,
            $challan !== '' ? 'Challan: '.$challan : null,
            $inward->notes,
        ]));

        return $this->wrap('GOODS_RECEIPT', $this->voucherXml(
            'goods_receipt',
            $inward,
            'Receipt Note',
            optional($inward->inward_date)->format('Ymd') ?? now()->format('Ymd'),
            $voucherNo,
            $party,
            $partyEntry.implode('', $entries).$this->roundOffEntry($sum),
            reference: $poNo ?: ($challan !== '' ? $challan : null),
            extra: array_merge($this->partyGstExtras($inward->supplier), array_filter([
                'narration' => implode(' | ', $narrationParts),
                'basic_buyer_name' => $inward->supplier?->name,
            ], fn ($value) => $value !== null && $value !== ''))
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
        $product->loadMissing(['baseUom', 'brand', 'category']);

        $mapping = $this->mappingService->findForDms(Product::class, $product->id, 'stock_item');
        $action = $mapping ? 'Alter' : 'Create';
        $unit = $this->unitName($product->baseUom, null) ?: 'Nos';
        $gst = (float) ($product->tax_rate ?? 0);
        $supply = $this->tallySupplyType($product->supply_type);
        $trackBatch = $product->isBatchTracked();
        $partNo = trim((string) ($product->sku ?: $product->serial_no ?: ''));
        $parent = trim((string) (
            $product->category?->name
            ?: $product->brand?->name
            ?: 'Primary'
        ));
        $reorder = (float) ($product->reorder_level ?? 0);
        $minStock = (float) ($product->min_stock ?? 0);
        $purchase = (float) ($product->purchase_price ?? 0);
        $selling = (float) ($product->selling_price ?? 0);
        $qtyLabel = static function (float $qty) use ($unit): string {
            if ($qty <= 0) {
                return '';
            }

            return rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.').' '.$unit;
        };

        return app(MasterTemplates::class)->stockItem(
            (string) $product->name,
            $unit,
            $action,
            $mapping?->tally_name ?: $product->name,
            $product->hsn_code,
            $product->description,
            $gst,
            array_filter([
                'PARENT' => $parent !== '' ? $parent : 'Primary',
                'ADDITIONALNAME' => $partNo,
                'PARTNO' => $partNo,
                'GSTTYPEOFSUPPLY' => $supply,
                'REORDERLEVEL' => $qtyLabel($reorder),
                'MINORDERQTY' => $qtyLabel($minStock),
                'ISBATCHWISEON' => $trackBatch ? 'Yes' : 'No',
                'HASMFGDATE' => $trackBatch ? 'Yes' : 'No',
                'ISPERISHABLEON' => $trackBatch ? 'Yes' : 'No',
                'STANDARDCOST' => $purchase > 0 ? number_format($purchase, 2, '.', '') : '',
                'STANDARDPRICE' => $selling > 0 ? number_format($selling, 2, '.', '') : '',
            ], fn ($value) => $value !== null && $value !== ''),
        );
    }

    protected function tallySupplyType(?string $supplyType): string
    {
        return match (strtolower(trim((string) $supplyType))) {
            'services', 'service' => 'Services',
            default => 'Goods',
        };
    }

    public function buildLedgerXml(Customer $customer): string
    {
        $mapping = $this->mappingService->findForDms(Customer::class, $customer->id, 'ledger');
        $action = $mapping ? 'Alter' : 'Create';

        $customer->loadMissing(['addresses', 'bankAccounts', 'contacts']);

        $billing = $customer->defaultBillingAddress();
        $address = $this->customerPrimaryAddressLine($customer, $billing);
        $state = trim((string) ($billing?->state ?: $customer->state ?: ''));
        $pincode = trim((string) ($billing?->pincode ?: $customer->pincode ?: ''));
        $mailing = trim((string) ($customer->trade_name ?: $customer->name ?: ''));
        $contact = trim((string) (
            $customer->contacts->firstWhere('is_primary', true)?->name
            ?: $customer->contacts->first()?->name
            ?: ''
        ));
        $bank = $customer->bankAccounts->firstWhere('is_primary', true)
            ?? $customer->bankAccounts->first();

        $parent = match (true) {
            $customer->isSupplier() && ! $customer->isCustomerParty() => 'Sundry Creditors',
            $customer->isSupplier() && $customer->isBoth() => 'Sundry Creditors',
            $customer->isCustomerParty() => 'Sundry Debtors',
            default => 'Sundry Debtors',
        };

        $gstin = trim((string) ($customer->gstin ?: ''));
        $gstType = $gstin !== '' ? 'Regular' : 'Unregistered';
        $creditDays = (int) ($customer->credit_days ?? 0);
        $interestRate = (float) ($customer->interest_rate ?? 0);
        $interestOn = $interestRate > 0;

        return app(MasterTemplates::class)->ledger(
            (string) $customer->name,
            $parent,
            $action,
            $mapping?->tally_name ?: $customer->name,
            $gstin !== '' ? $gstin : null,
            $address !== '' ? $address : null,
            $state !== '' ? $state : null,
            array_filter([
                'MAILINGNAME' => $mailing,
                'PINCODE' => $pincode,
                'LEDGERPHONE' => trim((string) ($customer->phone ?: '')),
                'EMAIL' => trim((string) ($customer->email ?: '')),
                'LEDGERCONTACT' => $contact,
                'INCOMETAXNUMBER' => trim((string) ($customer->pan ?: '')),
                'GSTREGISTRATIONTYPE' => $gstType,
                'ISBILLWISEON' => 'Yes',
                'ISCOSTCENTRESON' => 'No',
                'CREDITLIMIT' => $customer->credit_limit !== null
                    ? number_format((float) $customer->credit_limit, 2, '.', '')
                    : '',
                'BILLCREDITPERIOD' => $creditDays > 0 ? $creditDays.' Days' : '',
                'ISINTERESTON' => $interestOn ? 'Yes' : 'No',
                'INTERESTRATE' => $interestOn ? number_format($interestRate, 2, '.', '') : '',
                'BANKNAME' => trim((string) ($bank?->bank_name ?: '')),
                'ACCOUNTNUMBER' => trim((string) ($bank?->account_number ?: '')),
                'IFSCODE' => trim((string) ($bank?->ifsc_code ?: '')),
            ], fn ($value) => $value !== null && $value !== ''),
        );
    }

    protected function customerPrimaryAddressLine(Customer $customer, mixed $billing): string
    {
        if ($billing) {
            $line1 = trim((string) ($billing->address_line_1 ?? ''));
            $line2 = trim((string) ($billing->address_line_2 ?? ''));
            $city = trim((string) ($billing->city ?? ''));
            $parts = array_values(array_filter([$line1, $line2, $city], fn ($part) => $part !== ''));
            if ($parts !== []) {
                return implode(', ', $parts);
            }
        }

        return trim((string) ($customer->address ?: ''));
    }

    public function buildUomXml(Uom $uom): string
    {
        $mapping = $this->mappingService->findForDms(Uom::class, $uom->id, 'uom');
        $action = $mapping ? 'Alter' : 'Create';
        $symbol = trim((string) ($uom->code ?: $uom->name));
        $tallyName = $symbol !== '' ? $symbol : (string) $uom->name;
        $uqc = strtoupper(trim((string) ($uom->code ?: '')));
        if ($uqc !== '' && ! preg_match('/^[A-Z0-9]{2,8}$/', $uqc)) {
            $uqc = '';
        }

        return app(MasterTemplates::class)->unit(
            $tallyName,
            $action,
            $mapping?->tally_name ?: $tallyName,
            array_filter([
                'ORIGINALNAME' => (string) $uom->name,
                'DECIMALPLACES' => '2',
                'ISSIMPLEUNIT' => 'Yes',
                'REPORTINGUQC' => $uqc,
            ], fn ($value) => $value !== null && $value !== ''),
        );
    }

    public function buildGodownXml(Warehouse $warehouse): string
    {
        $mapping = $this->mappingService->findForDms(Warehouse::class, $warehouse->id, 'godown');
        $action = $mapping ? 'Alter' : 'Create';
        $warehouse->loadMissing('branch');
        $address = trim((string) ($warehouse->address ?: $warehouse->branch?->address ?: ''));

        return app(MasterTemplates::class)->godown(
            (string) $warehouse->getRawOriginal('name') ?: (string) $warehouse->name,
            $action,
            $mapping?->tally_name ?: ((string) $warehouse->getRawOriginal('name') ?: (string) $warehouse->name),
            array_filter([
                'PARENT' => 'Primary',
                'ADDRESS' => $address,
            ], fn ($value) => $value !== null && $value !== ''),
        );
    }

    // ─────────────────────────────────────────────────────────────
    // XML building blocks
    // ─────────────────────────────────────────────────────────────

    /**
     * @param  array{
     *     narration?: string|null,
     *     party_gstin?: string|null,
     *     place_of_supply?: string|null,
     *     gst_registration_type?: string|null,
     *     basic_buyer_name?: string|null,
     *     basic_buyer_address?: string|null,
     *     reference_date?: string|null,
     *     is_optional?: string|null
     * }  $extra
     */
    protected function voucherXml(
        string $documentType,
        Model $document,
        string $voucherType,
        string $date,
        string $voucherNo,
        string $party,
        string $entries,
        bool $isInvoice = false,
        ?string $reference = null,
        array $extra = []
    ): string {
        $action = $this->getVoucherAction($document);
        $remoteId = $this->xml($this->remoteId($documentType, $document));
        $vchType = $this->xml($voucherType);
        $number = $this->xml($voucherNo);
        $partyXml = $this->xml($party);
        $view = $isInvoice ? 'Invoice Voucher View' : 'Accounting Voucher View';
        $invoiceFlag = $isInvoice ? 'Yes' : 'No';
        $referenceXml = $reference ? '<REFERENCE>'.$this->xml($reference).'</REFERENCE>' : '';
        $referenceDate = XmlHelpers::ymd($extra['reference_date'] ?? null);
        $referenceDateXml = $referenceDate !== '' ? '<REFERENCEDATE>'.$referenceDate.'</REFERENCEDATE>' : '';
        $narration = $this->xml((string) ($extra['narration'] ?? "DMS {$voucherType} {$voucherNo}"));
        $gstin = trim((string) ($extra['party_gstin'] ?? ''));
        $gstinXml = $gstin !== '' ? '<PARTYGSTIN>'.$this->xml($gstin).'</PARTYGSTIN>' : '';
        $gstType = trim((string) ($extra['gst_registration_type'] ?? ''));
        $gstTypeXml = $gstType !== '' ? '<GSTREGISTRATIONTYPE>'.$this->xml($gstType).'</GSTREGISTRATIONTYPE>' : '';
        $place = trim((string) ($extra['place_of_supply'] ?? ''));
        $placeXml = $place !== ''
            ? '<PLACEOFSUPPLY>'.$this->xml($place).'</PLACEOFSUPPLY><STATENAME>'.$this->xml($place).'</STATENAME>'
            : '';
        $buyer = trim((string) ($extra['basic_buyer_name'] ?? ''));
        $buyerXml = $buyer !== '' ? '<BASICBUYERNAME>'.$this->xml($buyer).'</BASICBUYERNAME>' : '';
        $buyerAddress = trim((string) ($extra['basic_buyer_address'] ?? ''));
        $buyerAddressXml = $buyerAddress !== ''
            ? '<BASICBUYERADDRESS.LIST TYPE="String"><BASICBUYERADDRESS>'.$this->xml($buyerAddress).'</BASICBUYERADDRESS></BASICBUYERADDRESS.LIST>'
            : '';
        $optional = strcasecmp((string) ($extra['is_optional'] ?? ''), 'Yes') === 0 ? 'Yes' : 'No';

        if (in_array($voucherType, ['Purchase Order', 'Sales Order', 'Quotation', 'Receipt Note'], true)) {
            $view = 'Invoice Voucher View';
            $invoiceFlag = 'No';
        }

        return <<<XML
<VOUCHER REMOTEID="{$remoteId}" VCHTYPE="{$vchType}" ACTION="{$action}" OBJVIEW="{$view}">
 <DATE>{$date}</DATE>
 <EFFECTIVEDATE>{$date}</EFFECTIVEDATE>
 <VOUCHERTYPENAME>{$vchType}</VOUCHERTYPENAME>
 <VOUCHERNUMBER>{$number}</VOUCHERNUMBER>
 {$referenceXml}{$referenceDateXml}
 <PARTYLEDGERNAME>{$partyXml}</PARTYLEDGERNAME>
 <PARTYNAME>{$partyXml}</PARTYNAME>
 {$gstinXml}{$gstTypeXml}{$placeXml}{$buyerXml}{$buyerAddressXml}
 <PERSISTEDVIEW>{$view}</PERSISTEDVIEW>
 <ISINVOICE>{$invoiceFlag}</ISINVOICE>
 <ISOPTIONAL>{$optional}</ISOPTIONAL>
 <NARRATION>{$narration}</NARRATION>
{$entries}
</VOUCHER>
XML;
    }

    protected function tallyLedger(string $key, ?string $fallback = null): string
    {
        $map = [
            'sales' => ['tally_sync.ledgers.sales', 'services.tally.sales_ledger', 'Sales'],
            'purchase' => ['tally_sync.ledgers.purchase', 'services.tally.purchase_ledger', 'Purchase'],
            'purchase_interstate' => ['tally_sync.ledgers.purchase_interstate', 'services.tally.purchase_ledger_interstate', null],
            'cash' => ['tally_sync.ledgers.cash', 'services.tally.cash_ledger', 'Cash'],
            'bank' => ['tally_sync.ledgers.bank', 'services.tally.bank_ledger', 'Bank'],
            'round_off' => ['tally_sync.ledgers.round_off', 'services.tally.round_off_ledger', 'Round Off'],
            'output_cgst' => ['tally_sync.ledgers.output_cgst', 'services.tally.output_cgst_ledger', 'Output CGST'],
            'output_sgst' => ['tally_sync.ledgers.output_sgst', 'services.tally.output_sgst_ledger', 'Output SGST'],
            'output_igst' => ['tally_sync.ledgers.output_igst', 'services.tally.output_igst_ledger', 'Output IGST'],
            'input_cgst' => ['tally_sync.ledgers.input_cgst', 'services.tally.input_cgst_ledger', 'Input CGST'],
            'input_sgst' => ['tally_sync.ledgers.input_sgst', 'services.tally.input_sgst_ledger', 'Input SGST'],
            'input_igst' => ['tally_sync.ledgers.input_igst', 'services.tally.input_igst_ledger', 'Input IGST'],
        ];

        $paths = $map[$key] ?? [null, null, $fallback];
        foreach ([$paths[0], $paths[1]] as $path) {
            if (! $path) {
                continue;
            }
            $value = config($path);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return (string) ($fallback ?? $paths[2] ?? $key);
    }

    protected function partyGstExtras(?Customer $party): array
    {
        if (! $party) {
            return [];
        }

        $gstin = trim((string) ($party->gstin ?: ''));

        return array_filter([
            'party_gstin' => $gstin,
            'place_of_supply' => trim((string) ($party->state ?: '')),
            'gst_registration_type' => $gstin !== '' ? 'Regular' : 'Unregistered',
        ], fn ($value) => $value !== null && $value !== '');
    }

    protected function ledgerEntry(
        string $ledger,
        float $signedAmount,
        bool $isParty = false,
        string $billAllocation = '',
        string $tag = 'LEDGERENTRIES.LIST'
    ): string {
        return XmlHelpers::ledgerEntry($ledger, $signedAmount, $isParty, $billAllocation, $tag);
    }

    protected function billAllocation(string $name, string $type, float $signedAmount): string
    {
        return XmlHelpers::billAllocation($name, $type, $signedAmount);
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
        return XmlHelpers::inventoryEntry($stockItem, $qty, $unit, $rate, $signedAmount, $godown, $ledger, $batch);
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
            $entries[] = $this->ledgerEntry($this->tallyLedger("{$ledgerSet}_{$key}"), $signed, false, '', $tag);
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

        return $this->ledgerEntry($this->tallyLedger('round_off'), $difference, false, '', $tag);
    }

    protected function purchaseLedger(bool $interstate): string
    {
        if ($interstate) {
            $interstateLedger = $this->tallyLedger('purchase_interstate', '');
            if ($interstateLedger !== '') {
                return $interstateLedger;
            }
        }

        return $this->tallyLedger('purchase');
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
        if ($mapping?->tally_name) {
            return (string) $mapping->tally_name;
        }

        $code = trim((string) ($uom->code ?: ''));

        return $code !== '' ? $code : (string) $uom->name;
    }

    protected function godownName(?Warehouse $warehouse): string
    {
        if (! $warehouse) {
            return (string) config('tally_sync.default_godown', config('services.tally.default_godown', 'Main Location'));
        }

        $mapping = $this->mappingService->findForDms(Warehouse::class, $warehouse->id, 'godown');

        return $mapping?->tally_name ?: $warehouse->name;
    }

    protected function money(float $value): string
    {
        return XmlHelpers::money($value);
    }

    protected function quantity(float $value): string
    {
        return XmlHelpers::quantity($value);
    }

    protected function xml(?string $value): string
    {
        return XmlHelpers::xml($value);
    }

    protected function wrapMaster(string $type, string $inner): string
    {
        return XmlEnvelope::masters($type, $inner);
    }

    protected function wrap(string $type, string $inner): string
    {
        return XmlEnvelope::vouchers($type, $inner);
    }
}
