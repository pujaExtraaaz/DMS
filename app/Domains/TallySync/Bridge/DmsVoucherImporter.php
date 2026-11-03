<?php

namespace App\Domains\TallySync\Bridge;

use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockAdjustmentItem;
use App\Domains\Inventory\Models\StockTransfer;
use App\Domains\Inventory\Models\StockTransferItem;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Sales\Models\QuotationItem;
use App\Domains\Tally\Services\TallySyncMappingService;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Schema;

/**
 * Tally VOUCHER collection → DMS commercial / inventory documents.
 * Skips rows that originated from DMS (REMOTEID dms-*).
 */
class DmsVoucherImporter
{
    /** @var array{created:int,updated:int,conflicts:int,skipped:int} */
    private array $counts;

    public function __construct(
        private Company $organization,
        array &$counts,
    ) {
        $this->counts = &$counts;
    }

    public function import(\DOMElement $node): void
    {
        if ($this->yes($this->text($node, 'ISDELETED')) || $this->yes($this->text($node, 'ISCANCELLED'))) {
            $this->counts['skipped']++;

            return;
        }

        $remote = trim($node->getAttribute('REMOTEID'));
        if ($remote !== '' && str_starts_with(mb_strtolower($remote), 'dms-')) {
            $this->counts['skipped']++;

            return;
        }

        $typeName = $this->text($node, 'VOUCHERTYPENAME') ?: $node->getAttribute('VCHTYPE');
        $normalized = mb_strtolower(trim($typeName));
        if ($normalized === '') {
            $this->counts['skipped']++;

            return;
        }

        match (true) {
            str_contains($normalized, 'stock journal') => $this->stockJournal($node),
            str_contains($normalized, 'sales order') => $this->salesOrder($node),
            str_contains($normalized, 'purchase order') => $this->purchaseOrder($node),
            str_contains($normalized, 'receipt note') || str_contains($normalized, 'goods receipt') => $this->goodsReceipt($node),
            str_contains($normalized, 'credit note') => $this->creditNote($node),
            str_contains($normalized, 'quotation') || str_contains($normalized, 'proforma') => $this->quotation($node),
            $normalized === 'sales' || str_contains($normalized, 'sales') => $this->salesInvoice($node),
            $normalized === 'purchase' || str_contains($normalized, 'purchase') => $this->purchaseInvoice($node),
            $normalized === 'receipt' || str_contains($normalized, 'receipt') => $this->receipt($node),
            $normalized === 'payment' || str_contains($normalized, 'payment') => $this->paymentVoucher($node),
            default => $this->counts['skipped']++,
        };
    }

    private function salesInvoice(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $party = $this->ensureParty($this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME'), 'customer');
        if (! $number || ! $date || ! $party) {
            $this->counts['skipped']++;

            return;
        }

        $this->ensureFinancialYear($date);
        $lines = $this->inventoryLines($node);
        $totals = $this->sumLines($lines);

        $invoice = Invoice::query()
            ->where('company_id', $this->organization->id)
            ->where('invoice_no', $number)
            ->first();

        $attrs = [
            'company_id' => $this->organization->id,
            'invoice_no' => $number,
            'customer_id' => $party->id,
            'invoice_date' => $date,
            'status' => 'issued',
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax'],
            'grand_total' => $totals['grand'],
            'notes' => $this->text($node, 'NARRATION') ?: null,
            'reference_no' => $this->text($node, 'REFERENCE') ?: null,
        ];

        if ($invoice) {
            $invoice->fill($attrs)->save();
            $invoice->items()->delete();
            $this->counts['updated']++;
        } else {
            try {
                $invoice = Invoice::query()->create($attrs);
                $this->counts['created']++;
            } catch (UniqueConstraintViolationException) {
                $invoice = Invoice::query()
                    ->where('company_id', $this->organization->id)
                    ->where('invoice_no', $number)
                    ->firstOrFail();
                $invoice->fill($attrs)->save();
                $invoice->items()->delete();
                $this->counts['updated']++;
            }
        }

        $this->writeInvoiceItems($invoice->id, $lines);
        $this->map('voucher', Invoice::class, $invoice->id, $node, $number);
    }

    private function purchaseInvoice(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $party = $this->ensureParty($this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME'), 'supplier');
        if (! $number || ! $date || ! $party) {
            $this->counts['skipped']++;

            return;
        }

        $this->ensureFinancialYear($date);
        $lines = $this->inventoryLines($node);
        $totals = $this->sumLines($lines);
        $warehouse = $this->firstWarehouseFromLines($lines);

        $doc = PurchaseInvoice::query()
            ->where('company_id', $this->organization->id)
            ->where('invoice_no', $number)
            ->first();

        $attrs = [
            'company_id' => $this->organization->id,
            'invoice_no' => $number,
            'supplier_id' => $party->id,
            'warehouse_id' => $warehouse?->id,
            'invoice_date' => $date,
            'status' => 'posted',
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax'],
            'grand_total' => $totals['grand'],
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ];

        if ($doc) {
            $doc->fill($attrs)->save();
            $doc->items()->delete();
            $this->counts['updated']++;
        } else {
            $doc = PurchaseInvoice::query()->create($attrs);
            $this->counts['created']++;
        }

        foreach ($lines as $line) {
            PurchaseInvoiceItem::query()->create([
                'purchase_invoice_id' => $doc->id,
                'product_id' => $line['product_id'],
                'uom_id' => $line['uom_id'],
                'quantity' => $line['qty'],
                'unit_cost' => $line['rate'],
                'tax_percent' => 0,
                'line_total' => $line['amount'],
            ]);
        }

        $this->map('voucher', PurchaseInvoice::class, $doc->id, $node, $number);
    }

    private function receipt(\DOMElement $node): void
    {
        $this->moneyVoucher($node, 'receipt');
    }

    private function paymentVoucher(\DOMElement $node): void
    {
        $this->moneyVoucher($node, 'payment');
    }

    private function moneyVoucher(\DOMElement $node, string $kind): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $partyName = $this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME');
        $party = $this->ensureParty($partyName, $kind === 'receipt' ? 'customer' : 'supplier');
        $amount = abs($this->partyAmount($node));
        if (! $number || ! $date || ! $party || $amount < 0.01) {
            $this->counts['skipped']++;

            return;
        }

        $this->ensureFinancialYear($date);

        $payment = Payment::query()->where('payment_no', $number)->first();

        $attrs = [
            'payment_no' => $number,
            'customer_id' => $party->id,
            'amount' => $amount,
            'status' => 'completed',
            'method' => 'bank',
            'paid_at' => $date,
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ];

        if ($payment) {
            $payment->fill($attrs)->save();
            $this->counts['updated']++;
        } else {
            try {
                $payment = Payment::query()->create($attrs);
                $this->counts['created']++;
            } catch (UniqueConstraintViolationException) {
                $payment = Payment::query()->where('payment_no', $number)->firstOrFail();
                $payment->fill($attrs)->save();
                $this->counts['updated']++;
            }
        }

        $this->map('voucher', Payment::class, (int) $payment->id, $node, $number);
    }

    private function creditNote(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $party = $this->ensureParty($this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME'), 'customer');
        $amount = abs($this->partyAmount($node));
        if (! $number || ! $date || ! $party) {
            $this->counts['skipped']++;

            return;
        }

        $this->ensureFinancialYear($date);

        $doc = CreditNote::query()->where('credit_note_no', $number)->first();

        $attrs = [
            'credit_note_no' => $number,
            'customer_id' => $party->id,
            'credit_note_date' => $date,
            'subtotal' => $amount > 0 ? $amount : 0,
            'tax_amount' => 0,
            'grand_total' => $amount > 0 ? $amount : 0,
            'status' => 'posted',
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ];

        if ($doc) {
            $doc->fill($attrs)->save();
            $this->counts['updated']++;
        } else {
            try {
                $doc = CreditNote::query()->create($attrs);
                $this->counts['created']++;
            } catch (UniqueConstraintViolationException) {
                $doc = CreditNote::query()->where('credit_note_no', $number)->firstOrFail();
                $doc->fill($attrs)->save();
                $this->counts['updated']++;
            }
        }

        $this->map('voucher', CreditNote::class, (int) $doc->id, $node, $number);
    }

    private function salesOrder(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $party = $this->ensureParty($this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME'), 'customer');
        if (! $number || ! $date || ! $party) {
            $this->counts['skipped']++;

            return;
        }

        $lines = $this->inventoryLines($node);
        $totals = $this->sumLines($lines);

        $order = Order::query()->where('order_no', $number)->first();

        $attrs = [
            'order_no' => $number,
            'customer_id' => $party->id,
            'salesperson_id' => $this->defaultSalespersonId(),
            'order_date' => $date,
            'status' => 'approved',
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax'],
            'grand_total' => $totals['grand'],
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ];

        if ($order) {
            $order->fill($attrs)->save();
            $order->items()->delete();
            $this->counts['updated']++;
        } else {
            try {
                $order = Order::query()->create($attrs);
                $this->counts['created']++;
            } catch (UniqueConstraintViolationException) {
                $order = Order::query()->where('order_no', $number)->firstOrFail();
                $order->fill($attrs)->save();
                $order->items()->delete();
                $this->counts['updated']++;
            }
        }

        foreach ($lines as $line) {
            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $line['product_id'],
                'uom_id' => $line['uom_id'],
                'quantity' => $line['qty'],
                'unit_price' => $line['rate'],
                'line_total' => $line['amount'],
            ]);
        }

        $this->map('voucher', Order::class, $order->id, $node, $number);
    }

    private function purchaseOrder(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $party = $this->ensureParty($this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME'), 'supplier');
        if (! $number || ! $date || ! $party) {
            $this->counts['skipped']++;

            return;
        }

        $lines = $this->inventoryLines($node);
        $totals = $this->sumLines($lines);

        $doc = PurchaseOrder::query()
            ->where('company_id', $this->organization->id)
            ->where('po_no', $number)
            ->first();

        $attrs = [
            'company_id' => $this->organization->id,
            'po_no' => $number,
            'supplier_id' => $party->id,
            'po_date' => $date,
            'status' => 'approved',
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax'],
            'grand_total' => $totals['grand'],
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ];

        if ($doc) {
            $doc->fill($attrs)->save();
            $doc->items()->delete();
            $this->counts['updated']++;
        } else {
            $doc = PurchaseOrder::query()->create($attrs);
            $this->counts['created']++;
        }

        foreach ($lines as $line) {
            PurchaseOrderItem::query()->create([
                'purchase_order_id' => $doc->id,
                'product_id' => $line['product_id'],
                'uom_id' => $line['uom_id'],
                'quantity' => $line['qty'],
                'unit_cost' => $line['rate'],
                'tax_percent' => 0,
                'line_total' => $line['amount'],
            ]);
        }

        $this->map('voucher', PurchaseOrder::class, $doc->id, $node, $number);
    }

    private function quotation(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $party = $this->ensureParty($this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME'), 'customer');
        if (! $number || ! $date || ! $party) {
            $this->counts['skipped']++;

            return;
        }

        $lines = $this->inventoryLines($node);
        $totals = $this->sumLines($lines);

        $doc = Quotation::query()->where('quotation_no', $number)->first();

        $attrs = [
            'quotation_no' => $number,
            'customer_id' => $party->id,
            'quotation_date' => $date,
            'status' => 'sent',
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax'],
            'grand_total' => $totals['grand'],
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ];

        if ($doc) {
            $doc->fill($attrs)->save();
            $doc->items()->delete();
            $this->counts['updated']++;
        } else {
            try {
                $doc = Quotation::query()->create($attrs);
                $this->counts['created']++;
            } catch (UniqueConstraintViolationException) {
                $doc = Quotation::query()->where('quotation_no', $number)->firstOrFail();
                $doc->fill($attrs)->save();
                $doc->items()->delete();
                $this->counts['updated']++;
            }
        }

        foreach ($lines as $line) {
            QuotationItem::query()->create([
                'quotation_id' => $doc->id,
                'product_id' => $line['product_id'],
                'uom_id' => $line['uom_id'],
                'quantity' => $line['qty'],
                'unit_price' => $line['rate'],
                'line_total' => $line['amount'],
            ]);
        }

        $this->map('voucher', Quotation::class, $doc->id, $node, $number);
    }

    private function goodsReceipt(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $party = $this->ensureParty($this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME'), 'supplier');
        $lines = $this->inventoryLines($node);
        $warehouse = $this->firstWarehouseFromLines($lines);
        if (! $number || ! $date) {
            $this->counts['skipped']++;

            return;
        }

        $docQuery = PurchaseInward::query()->where('inward_no', $number);
        if (Schema::hasColumn('purchase_inwards', 'grn_no')) {
            $docQuery->orWhere('grn_no', $number);
        }
        $doc = $docQuery->first();

        $attrs = array_filter([
            'inward_no' => $number,
            'grn_no' => Schema::hasColumn('purchase_inwards', 'grn_no') ? $number : null,
            'supplier_id' => $party?->id,
            'warehouse_id' => $warehouse?->id,
            'inward_date' => $date,
            'status' => 'posted',
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ], fn ($v) => $v !== null);

        if ($doc) {
            $doc->fill($attrs)->save();
            $this->counts['updated']++;
        } else {
            PurchaseInward::query()->create($attrs);
            $this->counts['created']++;
        }
    }

    private function stockJournal(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node) ?: ('SJ-'.uniqid());
        $date = $this->date($this->text($node, 'DATE')) ?: now()->toDateString();
        $lines = $this->inventoryLines($node);
        if ($lines === []) {
            $this->counts['skipped']++;

            return;
        }

        $from = null;
        $to = null;
        foreach ($lines as $line) {
            $from = $from ?: $line['godown'];
            $to = $to ?: $line['destination'];
        }

        if ($from && $to && $from->id !== $to->id) {
            $transfer = StockTransfer::query()->where('transfer_no', $number)->first();
            $attrs = [
                'transfer_no' => $number,
                'transfer_date' => $date,
                'from_warehouse_id' => $from->id,
                'to_warehouse_id' => $to->id,
                'status' => 'received',
                'notes' => $this->text($node, 'NARRATION') ?: null,
            ];
            if ($transfer) {
                $transfer->fill($attrs)->save();
                $transfer->items()->delete();
                $this->counts['updated']++;
            } else {
                $transfer = StockTransfer::query()->create($attrs);
                $this->counts['created']++;
            }
            foreach ($lines as $line) {
                StockTransferItem::query()->create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $line['product_id'],
                    'uom_id' => $line['uom_id'],
                    'quantity' => abs($line['qty']),
                ]);
            }

            return;
        }

        $warehouse = $from ?: $to ?: Warehouse::query()->where('company_id', $this->organization->id)->orderBy('id')->first();
        if (! $warehouse) {
            $this->counts['skipped']++;

            return;
        }

        $adj = StockAdjustment::query()->where('adjustment_no', $number)->first();
        $attrs = [
            'adjustment_no' => $number,
            'adjustment_date' => $date,
            'warehouse_id' => $warehouse->id,
            'reason' => 'other',
            'status' => 'posted',
            'notes' => $this->text($node, 'NARRATION') ?: 'Imported from Tally',
        ];
        if ($adj) {
            $adj->fill($attrs)->save();
            $adj->items()->delete();
            $this->counts['updated']++;
        } else {
            $adj = StockAdjustment::query()->create($attrs);
            $this->counts['created']++;
        }
        foreach ($lines as $line) {
            StockAdjustmentItem::query()->create([
                'stock_adjustment_id' => $adj->id,
                'product_id' => $line['product_id'],
                'uom_id' => $line['uom_id'],
                'quantity' => $line['qty'],
            ]);
        }
    }

    /**
     * @return list<array{product_id:int,uom_id:?int,qty:float,rate:float,amount:float,tax:float,godown:?Warehouse,destination:?Warehouse}>
     */
    private function inventoryLines(\DOMElement $node): array
    {
        $lines = [];
        foreach ($node->getElementsByTagName('ALLINVENTORYENTRIES.LIST') as $entry) {
            if (! $entry instanceof \DOMElement || $entry->parentNode !== $node) {
                continue;
            }
            $itemName = $this->text($entry, 'STOCKITEMNAME');
            if ($itemName === '') {
                continue;
            }
            $product = $this->ensureProduct($itemName, $this->text($entry, 'BASEUNITS') ?: 'Nos');
            $batch = $entry->getElementsByTagName('BATCHALLOCATIONS.LIST')->item(0);
            $godownName = $this->text($entry, 'GODOWNNAME');
            if ($godownName === '' && $batch instanceof \DOMElement) {
                $godownName = $this->text($batch, 'GODOWNNAME');
            }
            $destName = $this->text($entry, 'DESTINATIONGODOWNNAME');
            if ($destName === '' && $batch instanceof \DOMElement) {
                $destName = $this->text($batch, 'DESTINATIONGODOWNNAME');
            }
            $qtyText = $this->text($entry, 'BILLEDQTY') ?: $this->text($entry, 'ACTUALQTY');
            if ($qtyText === '' && $batch instanceof \DOMElement) {
                $qtyText = $this->text($batch, 'BILLEDQTY') ?: $this->text($batch, 'ACTUALQTY');
            }
            $qty = $this->leadingNumber($qtyText);
            $amount = abs((float) str_replace(',', '', $this->text($entry, 'AMOUNT') ?: '0'));
            $rate = $this->leadingNumber($this->text($entry, 'RATE'));
            if ($rate <= 0 && $qty > 0) {
                $rate = round($amount / $qty, 2);
            }
            $lines[] = [
                'product_id' => $product->id,
                'uom_id' => $product->base_uom_id,
                'qty' => $qty,
                'rate' => $rate,
                'amount' => $amount > 0 ? $amount : round($qty * $rate, 2),
                'tax' => 0.0,
                'godown' => $godownName !== '' ? $this->ensureWarehouse($godownName) : null,
                'destination' => $destName !== '' ? $this->ensureWarehouse($destName) : null,
            ];
        }

        return $lines;
    }

    /**
     * @param  list<array{product_id:int,uom_id:?int,qty:float,rate:float,amount:float,tax:float}>  $lines
     */
    private function writeInvoiceItems(int $invoiceId, array $lines): void
    {
        foreach ($lines as $line) {
            InvoiceItem::query()->create([
                'invoice_id' => $invoiceId,
                'product_id' => $line['product_id'],
                'uom_id' => $line['uom_id'],
                'quantity' => $line['qty'],
                'unit_price' => $line['rate'],
                'tax_amount' => $line['tax'],
                'line_total' => $line['amount'],
            ]);
        }
    }

    /**
     * @param  list<array{amount:float,tax:float}>  $lines
     * @return array{subtotal:float,tax:float,grand:float}
     */
    private function sumLines(array $lines): array
    {
        $subtotal = 0.0;
        $tax = 0.0;
        foreach ($lines as $line) {
            $subtotal += (float) $line['amount'];
            $tax += (float) $line['tax'];
        }

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'grand' => round($subtotal + $tax, 2),
        ];
    }

    /**
     * @param  list<array{godown:?Warehouse}>  $lines
     */
    private function firstWarehouseFromLines(array $lines): ?Warehouse
    {
        foreach ($lines as $line) {
            if ($line['godown'] ?? null) {
                return $line['godown'];
            }
        }

        return Warehouse::query()->where('company_id', $this->organization->id)->orderBy('id')->first();
    }

    private function defaultSalespersonId(): int
    {
        static $id = null;
        if ($id !== null) {
            return $id;
        }

        $id = (int) (User::query()->orderBy('id')->value('id') ?: 0);
        if ($id < 1) {
            throw new \RuntimeException('No DMS user exists to assign as salesperson on imported sales orders.');
        }

        return $id;
    }

    private function ensureParty(string $name, string $role): ?Customer
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $party = Customer::query()
            ->where('company_id', $this->organization->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        $partyType = $role === 'supplier' ? 'supplier' : 'customer';
        if ($party) {
            return $party;
        }

        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'PARTY', 0, 16));
        if ($base === '') {
            $base = 'PARTY';
        }
        $code = $base;
        $suffix = 1;
        while (Customer::query()->where('code', $code)->exists()) {
            $code = $base.str_pad((string) $suffix, 3, '0', STR_PAD_LEFT);
            $suffix++;
            if ($suffix > 999) {
                $code = 'P'.substr((string) time(), -9).random_int(10, 99);
                break;
            }
        }

        return Customer::query()->create([
            'company_id' => $this->organization->id,
            'name' => $name,
            'code' => $code,
            'party_type' => $partyType,
            'is_active' => true,
        ]);
    }

    private function ensureProduct(string $name, string $unitName): Product
    {
        $product = Product::query()
            ->where('company_id', $this->organization->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
        if ($product) {
            return $product;
        }

        $uom = Uom::query()
            ->where(function ($q) use ($unitName) {
                $q->whereRaw('LOWER(code) = ?', [mb_strtolower($unitName)])
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($unitName)]);
            })
            ->first();
        if (! $uom) {
            $uom = Uom::query()->create([
                'name' => $unitName,
                'code' => $unitName,
                'is_active' => true,
            ]);
        }

        return Product::query()->create([
            'company_id' => $this->organization->id,
            'name' => $name,
            'sku' => \App\Support\CodeGenerator::forProduct($this->organization->id),
            'serial_no' => \App\Support\CodeGenerator::forProductSerial(),
            'base_uom_id' => $uom->id,
            'supply_type' => 'goods',
            'is_active' => true,
        ]);
    }

    private function ensureWarehouse(string $name): Warehouse
    {
        $warehouse = Warehouse::query()
            ->where('company_id', $this->organization->id)
            ->where(function ($q) use ($name) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->orWhereRaw('LOWER(code) = ?', [mb_strtolower($name)]);
            })
            ->first();
        if ($warehouse) {
            return $warehouse;
        }

        return Warehouse::query()->create([
            'company_id' => $this->organization->id,
            'name' => $name,
            'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'WH', 0, 12)),
            'is_active' => true,
        ]);
    }

    private function ensureFinancialYear(string $date): void
    {
        $exists = FinancialYear::query()
            ->where('company_id', $this->organization->id)
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->exists();
        if ($exists) {
            return;
        }

        $carbon = \Illuminate\Support\Carbon::parse($date);
        $start = $carbon->month >= 4
            ? $carbon->copy()->month(4)->day(1)->startOfDay()
            : $carbon->copy()->subYear()->month(4)->day(1)->startOfDay();
        $end = $start->copy()->addYear()->subDay();

        FinancialYear::query()->create([
            'company_id' => $this->organization->id,
            'name' => $start->format('Y').'-'.$end->format('y'),
            'starts_on' => $start->toDateString(),
            'ends_on' => $end->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);
        $this->counts['created']++;
    }

    private function map(string $tallyType, string $class, int $id, \DOMElement $node, string $name): void
    {
        $model = $class::query()->find($id);
        if (! $model) {
            return;
        }
        app(TallySyncMappingService::class)->createOrUpdate(
            $model,
            $class,
            $tallyType,
            $this->text($node, 'GUID') ?: null,
            $name,
            'synced'
        );
        \App\Domains\TallySync\Support\InboundActivityLog::item('vouchers', 'synced', $name);
    }

    private function voucherNumber(\DOMElement $node): string
    {
        return trim($this->text($node, 'VOUCHERNUMBER') ?: $node->getAttribute('VCHNUMBER'));
    }

    private function partyAmount(\DOMElement $node): float
    {
        foreach ($node->getElementsByTagName('ALLLEDGERENTRIES.LIST') as $entry) {
            if (! $entry instanceof \DOMElement) {
                continue;
            }
            if ($this->yes($this->text($entry, 'ISPARTYLEDGER'))) {
                return (float) str_replace(',', '', $this->text($entry, 'AMOUNT') ?: '0');
            }
        }

        return (float) str_replace(',', '', $this->text($node, 'AMOUNT') ?: '0');
    }

    private function leadingNumber(string $text): float
    {
        if (preg_match('/-?\d+(?:\.\d+)?/', str_replace(',', '', $text), $m)) {
            return (float) $m[0];
        }

        return 0.0;
    }

    private function date(string $value): ?string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (strlen($digits) >= 8) {
            return substr($digits, 0, 4).'-'.substr($digits, 4, 2).'-'.substr($digits, 6, 2);
        }

        return null;
    }

    private function text(\DOMElement $node, string $tag): string
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement && strcasecmp($child->tagName, $tag) === 0) {
                return trim($child->textContent ?? '');
            }
        }

        return '';
    }

    private function yes(string $value): bool
    {
        return in_array(mb_strtolower(trim($value)), ['yes', 'y', 'true', '1'], true);
    }
}
