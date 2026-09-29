<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Payment\Models\SupplierPayable;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseInwardItem;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Domains\Purchasing\Models\VendorPriceHistory;
use App\Models\User;
use App\Support\DocumentNumberService;
use App\Support\DueDateService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PurchaseOrderService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected StockMovementService $stockMovementService,
        protected SerialBatchService $serialBatchService,
        protected DueDateService $dueDateService,
        protected ?LandedCostService $landedCostService = null,
    ) {
        $this->landedCostService ??= app(LandedCostService::class);
    }

    /**
     * Create a new draft PO with line items.
     *
     * @param  array{company_id?:int,branch_id?:int,supplier_id:int,warehouse_id?:int,order_date:string,expected_date?:string,notes?:string,items:array}  $data
     */
    public function create(array $data, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $actor) {
            $items = $this->normalizeItems($data['items'] ?? []);
            if (empty($items)) {
                throw ValidationException::withMessages(['items' => 'At least one line item is required.']);
            }

            $order = PurchaseOrder::create([
                'company_id' => $data['company_id'] ?? $actor->company_id,
                'branch_id' => $data['branch_id'] ?? $actor->branch_id,
                'po_no' => $this->documentNumberService->next('PO'),
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'status' => 'draft',
                'subtotal' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($items as $item) {
                $line = $item['quantity'] * $item['unit_cost'];
                $tax = $line * (($item['tax_percent'] ?? 0) / 100);
                $subtotal += $line;
                $taxAmount += $tax;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'tax_percent' => $item['tax_percent'] ?? 0,
                    'line_total' => $line + $tax,
                    'received_qty' => 0,
                    'batch_name' => $item['batch_name'] ?? null,
                ]);
            }

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $subtotal + $taxAmount,
            ]);

            return $order->fresh(['items.product', 'items.uom', 'supplier']);
        });
    }

    /**
     * Submit PO for approval.
     */
    public function submit(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status !== 'draft') {
            throw new InvalidArgumentException('Only draft POs can be submitted.');
        }

        $order->update(['status' => 'submitted']);

        return $order->fresh();
    }

    /**
     * Approve PO: locks pricing, allows receiving.
     */
    public function approve(PurchaseOrder $order, User $actor): PurchaseOrder
    {
        if (! in_array($order->status, ['draft', 'submitted'], true)) {
            throw new InvalidArgumentException('PO is not in an approvable state.');
        }

        $order->update([
            'status' => 'approved',
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        return $order->fresh();
    }

    /**
     * Cancel a PO (only if no receipts exist).
     */
    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status === 'closed') {
            throw new InvalidArgumentException('Cannot cancel a closed purchase order.');
        }

        if ((float) $order->items()->sum('received_qty') > 0) {
            throw new InvalidArgumentException('Cannot cancel a partially received purchase order.');
        }

        $order->update(['status' => 'cancelled']);

        return $order->fresh();
    }

    /**
     * Receive against an approved PO: creates inward, updates stock, optional serials/batches.
     *
     * @param  array{inward_date:string,warehouse_id?:int,supplier_challan_no?:string,notes?:string,items:array}  $data
     */
    public function receive(PurchaseOrder $order, array $data, User $actor): PurchaseInward
    {
        if (! $order->isReceivable()) {
            throw new InvalidArgumentException('Purchase order is not receivable.');
        }

        return DB::transaction(function () use ($order, $data, $actor) {
            $order = PurchaseOrder::query()->lockForUpdate()->with('items')->findOrFail($order->id);
            $warehouseId = $data['warehouse_id'] ?? $order->warehouse_id;

            $inward = PurchaseInward::create([
                'purchase_order_id' => $order->id,
                'warehouse_id' => $warehouseId,
                'supplier_id' => $order->supplier_id,
                'inward_no' => $this->documentNumberService->next('GRN'),
                'inward_date' => $data['inward_date'],
                'supplier_challan_no' => $data['supplier_challan_no'] ?? null,
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($data['items'] as $row) {
                $poItem = $order->items->firstWhere('id', (int) $row['purchase_order_item_id']);
                if (! $poItem) {
                    throw ValidationException::withMessages([
                        'items' => 'Invalid purchase order item on receive.',
                    ]);
                }

                $accepted = (float) ($row['accepted_qty'] ?? $row['received_qty'] ?? 0);
                $received = (float) ($row['received_qty'] ?? $accepted);
                $rejected = (float) ($row['rejected_qty'] ?? max(0, $received - $accepted));

                if ($accepted <= 0) {
                    continue;
                }

                $remaining = $poItem->remainingQty();
                $tolerance = max(0.0001, $remaining * 0.05);
                if ($accepted > $remaining + $tolerance) {
                    throw ValidationException::withMessages([
                        'items' => "Accepted qty exceeds remaining for {$poItem->product?->name}. Remaining: {$remaining}.",
                    ]);
                }

                $inwardItem = PurchaseInwardItem::create([
                    'purchase_inward_id' => $inward->id,
                    'purchase_order_item_id' => $poItem->id,
                    'product_id' => $poItem->product_id,
                    'uom_id' => $poItem->uom_id,
                    'ordered_qty' => $poItem->quantity,
                    'received_qty' => $received,
                    'accepted_qty' => $accepted,
                    'rejected_qty' => $rejected,
                    'unit_cost' => $poItem->unit_cost,
                    'batch_no' => $row['batch_no'] ?? $poItem->batch_name ?? null,
                    'expiry_date' => $row['expiry_date'] ?? null,
                    'batch_selling_price' => isset($row['batch_selling_price']) && $row['batch_selling_price'] !== '' ? (float) $row['batch_selling_price'] : null,
                    'batch_mrp' => isset($row['batch_mrp']) && $row['batch_mrp'] !== '' ? (float) $row['batch_mrp'] : null,
                    'rejection_reason' => $row['rejection_reason'] ?? null,
                ]);

                $poItem->update([
                    'received_qty' => (float) $poItem->received_qty + $accepted,
                ]);

                $product = Product::findOrFail($poItem->product_id);
                $uom = Uom::findOrFail($poItem->uom_id);

                $this->stockMovementService->recordIn(
                    product: $product,
                    uom: $uom,
                    quantity: $accepted,
                    type: 'inward',
                    reference: $inward,
                    notes: "GRN {$inward->inward_no}",
                    user: $actor,
                    warehouseId: $warehouseId,
                );

                if (! empty($row['serials']) && is_array($row['serials'])) {
                    $this->serialBatchService->assignSerials(
                        $product,
                        $row['serials'],
                        $warehouseId,
                        $inwardItem,
                        $actor
                    );
                }
            }

            $order = $order->fresh('items');
            $allReceived = $order->items->every(fn (PurchaseOrderItem $i) => (float) $i->received_qty >= (float) $i->quantity);
            $anyReceived = $order->items->some(fn (PurchaseOrderItem $i) => (float) $i->received_qty > 0);

            $order->update([
                'status' => $allReceived ? 'closed' : ($anyReceived ? 'partially_received' : $order->status),
            ]);

            return $inward->fresh(['items.product', 'items.uom', 'supplier']);
        });
    }

    /**
     * Direct Purchase Invoice (with or without PO/GRN).
     *
     * @param  array{supplier_id:int,invoice_date:string,due_date?:string,credit_days?:int,warehouse_id?:int,purchase_order_id?:int,purchase_inward_id?:int,supplier_invoice_no?:string,rate_override_reason?:string,notes?:string,terms_and_conditions?:string,freight_amount?:float,freight_allocation_method?:string,items:array}  $data
     */
    public function createInvoice(array $data, User $actor): PurchaseInvoice
    {
        return DB::transaction(function () use ($data, $actor) {
            $supplierId = (int) $data['supplier_id'];
            $items = $this->normalizeItems($data['items'] ?? []);
            $overrideReason = $data['rate_override_reason'] ?? null;
            $needsOverride = false;
            $warehouseId = ! empty($data['warehouse_id']) ? (int) $data['warehouse_id'] : null;

            $subtotal = 0;
            $taxAmount = 0;
            $prepared = [];
            $allInvoiceSerials = [];

            foreach ($items as $item) {
                $invoiceDate = $data['invoice_date'];
                $product = Product::findOrFail($item['product_id']);

                // Validate Serial Numbers for Serial-Tracked Products
                if ($product->isSerialTracked()) {
                    $requiredQty = (int) round($item['quantity']);
                    $providedSerials = $item['serials'] ?? [];

                    if (count($providedSerials) !== $requiredQty) {
                        throw ValidationException::withMessages([
                            'items' => "Product '{$product->name}' is serial-tracked and requires exactly {$requiredQty} serial number(s). You provided " . count($providedSerials) . ".",
                        ]);
                    }

                    foreach ($providedSerials as $sn) {
                        if (in_array($sn, $allInvoiceSerials, true)) {
                            throw ValidationException::withMessages([
                                'items' => "Duplicate serial number '{$sn}' entered multiple times in this invoice.",
                            ]);
                        }
                        $allInvoiceSerials[] = $sn;
                    }
                }

                $otherRate = VendorPriceHistory::query()
                    ->where('product_id', $item['product_id'])
                    ->where('uom_id', $item['uom_id'])
                    ->where('supplier_id', '!=', $supplierId)
                    ->whereDate('effective_from', '<=', $invoiceDate)
                    ->latest('effective_from')
                    ->value('unit_cost');

                if ($otherRate !== null && (float) $otherRate > 0 && (float) $item['unit_cost'] > (float) $otherRate * 1.05) {
                    $needsOverride = true;
                }

                $line = $item['quantity'] * $item['unit_cost'];
                $taxPercent = (float) ($item['tax_percent'] ?? 0);
                $cgstPercent = array_key_exists('cgst_percent', $item)
                    ? (float) $item['cgst_percent']
                    : ($taxPercent / 2);
                $sgstPercent = array_key_exists('sgst_percent', $item)
                    ? (float) $item['sgst_percent']
                    : ($taxPercent / 2);

                if (abs(($cgstPercent + $sgstPercent) - $taxPercent) > 0.0001) {
                    throw ValidationException::withMessages([
                        'items' => 'CGST % and SGST % must add up to the total Tax % for every item.',
                    ]);
                }

                $cgstAmount = $line * ($cgstPercent / 100);
                $sgstAmount = $line * ($sgstPercent / 100);
                $tax = $cgstAmount + $sgstAmount;

                $subtotal += $line;
                $taxAmount += $tax;

                $prepared[] = [
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'tax_percent' => $taxPercent,
                    'cgst_percent' => $cgstPercent,
                    'sgst_percent' => $sgstPercent,
                    'cgst_amount' => $cgstAmount,
                    'sgst_amount' => $sgstAmount,
                    'line_total' => $line + $tax,
                    'batch_no' => $item['batch_no'] ?? null,
                    'batch_selling_price' => $item['batch_selling_price'] ?? null,
                    'batch_mrp' => $item['batch_mrp'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'serials' => $item['serials'] ?? [],
                ];
            }

            if ($needsOverride && empty($overrideReason)) {
                throw ValidationException::withMessages([
                    'rate_override_reason' => 'Rate is 5% higher than vendor price history. Override reason is mandatory.',
                ]);
            }

            $supplier = Customer::query()->findOrFail($supplierId);
            $creditDays = array_key_exists('credit_days', $data) && $data['credit_days'] !== null && $data['credit_days'] !== ''
                ? (int) $data['credit_days']
                : (int) ($supplier->credit_days ?? 0);

            $dueDate = ! empty($data['due_date'])
                ? $data['due_date']
                : (method_exists($this->dueDateService, 'forPurchaseInvoice')
                    ? $this->dueDateService->forPurchaseInvoice($supplier, $data['invoice_date'], null, $creditDays)['due_date']
                    : Carbon::parse($data['invoice_date'])->addDays($creditDays)->toDateString());

            // Compute freight & landed cost allocations across line items
            $freightAmount = (float) ($data['freight_amount'] ?? 0);
            $freightMethod = $data['freight_allocation_method'] ?? 'value';
            $landedAllocations = [];

            if ($freightAmount > 0) {
                $linesForLanded = array_map(fn ($item, $idx) => [
                    'invoice_item_id' => $idx,
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'],
                    'quantity' => (float) $item['quantity'],
                    'base_value' => (float) $item['quantity'] * (float) $item['unit_cost'],
                    'unit_cost' => (float) $item['unit_cost'],
                ], $prepared, array_keys($prepared));

                $landedAllocations = $this->landedCostService->computeAllocations($linesForLanded, $freightMethod, $freightAmount);
            }

            $invoice = PurchaseInvoice::create([
                'company_id' => $data['company_id'] ?? $actor->company_id,
                'branch_id' => $data['branch_id'] ?? $actor->branch_id,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'purchase_inward_id' => $data['purchase_inward_id'] ?? null,
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'invoice_no' => $this->documentNumberService->next('PINV'),
                'supplier_invoice_no' => $data['supplier_invoice_no'] ?? ($data['vendor_invoice_no'] ?? null),
                'invoice_date' => $data['invoice_date'],
                'credit_days' => $creditDays,
                'due_date' => $dueDate,
                'due_date_basis' => $data['due_date_basis'] ?? ($supplier->credit_period_basis ?? 'invoice_date'),
                'due_date_source_date' => $data['invoice_date'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'round_off' => (float) ($data['round_off'] ?? 0),
                'grand_total' => $subtotal + $taxAmount + (float) ($data['round_off'] ?? 0),
                'status' => 'posted',
                'payment_status' => 'unpaid',
                'rate_override_reason' => $overrideReason,
                'rate_overridden_by' => $needsOverride ? $actor->id : null,
                'notes' => $data['notes'] ?? null,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'freight_allocation_method' => $freightMethod,
                'created_by' => $actor->id,
            ]);

            foreach ($prepared as $idx => $item) {
                $piItem = PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'tax_percent' => $item['tax_percent'],
                    'cgst_percent' => $item['cgst_percent'],
                    'sgst_percent' => $item['sgst_percent'],
                    'cgst_amount' => $item['cgst_amount'],
                    'sgst_amount' => $item['sgst_amount'],
                    'line_total' => $item['line_total'],
                    'batch_no' => $item['batch_no'] ?? null,
                    'batch_selling_price' => isset($item['batch_selling_price']) && $item['batch_selling_price'] !== '' ? (float) $item['batch_selling_price'] : null,
                    'batch_mrp' => isset($item['batch_mrp']) && $item['batch_mrp'] !== '' ? (float) $item['batch_mrp'] : null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);

                $product = Product::findOrFail($item['product_id']);
                $uom = Uom::findOrFail($item['uom_id']);

                // Calculate landed unit cost for inventory valuation
                $landedUnitCost = isset($landedAllocations[$idx]['landed_unit_cost'])
                    ? (float) $landedAllocations[$idx]['landed_unit_cost']
                    : (float) $item['unit_cost'];

                // Assign serials if tracked or provided
                if (! empty($item['serials']) && is_array($item['serials'])) {
                    $this->serialBatchService->assignSerials(
                        $product,
                        $item['serials'],
                        $warehouseId,
                        $piItem,
                        $actor
                    );
                }

                // Upsert batch if batch tracked (batch_selling_price and batch_mrp flow strictly into ProductBatch)
                if (! empty($item['batch_no'])) {
                    $this->serialBatchService->upsertBatch(
                        $product,
                        $uom,
                        $item['batch_no'],
                        (float) $item['quantity'],
                        (float) $item['unit_cost'],
                        $warehouseId,
                        $item['expiry_date'] ?? null,
                        $piItem,
                        isset($item['batch_selling_price']) && $item['batch_selling_price'] !== '' ? (float) $item['batch_selling_price'] : null,
                        isset($item['batch_mrp']) && $item['batch_mrp'] !== '' ? (float) $item['batch_mrp'] : null,
                    );
                }

                // Record stock movement on direct invoice (when no previous inward)
                // Valuation layer receives pure Unit Cost and effective Landed Unit Cost
                if (empty($data['purchase_inward_id'])) {
                    $this->stockMovementService->recordIn(
                        product: $product,
                        uom: $uom,
                        quantity: (float) $item['quantity'],
                        type: 'purchase_invoice',
                        reference: $invoice,
                        notes: "PI {$invoice->invoice_no}",
                        user: $actor,
                        warehouseId: $warehouseId,
                        unitCost: (float) $item['unit_cost'],
                        landedUnitCost: (float) $landedUnitCost,
                        batchNo: $item['batch_no'] ?? null,
                    );
                }

                VendorPriceHistory::create([
                    'supplier_id' => $supplierId,
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'],
                    'unit_cost' => $item['unit_cost'],
                    'effective_from' => $data['invoice_date'],
                    'reference_type' => $invoice->getMorphClass(),
                    'reference_id' => $invoice->id,
                    'created_by' => $actor->id,
                ]);
            }

            $previous = (float) (SupplierPayable::query()
                ->where('supplier_id', $supplierId)
                ->orderByDesc('id')
                ->value('balance') ?? 0);

            SupplierPayable::create([
                'supplier_id' => $supplierId,
                'type' => 'invoice',
                'reference_type' => $invoice->getMorphClass(),
                'reference_id' => $invoice->id,
                'debit' => $invoice->grand_total,
                'credit' => 0,
                'balance' => $previous + (float) $invoice->grand_total,
                'notes' => "Purchase invoice {$invoice->invoice_no}",
            ]);

            return $invoice->load(['items.product', 'items.uom', 'items.serials', 'supplier']);
        });
    }

    /**
     * Normalize items array keys/types.
     */
    protected function normalizeItems(array $raw): array
    {
        $normalized = [];
        foreach ($raw as $row) {
            if (empty($row['product_id']) || empty($row['quantity'])) {
                continue;
            }

            $serials = [];
            if (! empty($row['serials'])) {
                if (is_array($row['serials'])) {
                    $serials = array_values(array_filter(array_map('trim', $row['serials'])));
                } elseif (is_string($row['serials'])) {
                    $serials = array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', $row['serials']))));
                }
            }

            $normalized[] = [
                'product_id' => (int) $row['product_id'],
                'uom_id' => (int) ($row['uom_id'] ?? 1),
                'quantity' => (float) $row['quantity'],
                'unit_cost' => (float) ($row['unit_cost'] ?? 0),
                'tax_percent' => (float) ($row['tax_percent'] ?? 0),
                'cgst_percent' => isset($row['cgst_percent']) ? (float) $row['cgst_percent'] : null,
                'sgst_percent' => isset($row['sgst_percent']) ? (float) $row['sgst_percent'] : null,
                'batch_name' => $row['batch_name'] ?? ($row['batch_no'] ?? null),
                'batch_no' => $row['batch_no'] ?? ($row['batch_name'] ?? null),
                'expiry_date' => $row['expiry_date'] ?? null,
                'batch_selling_price' => isset($row['batch_selling_price']) && $row['batch_selling_price'] !== '' ? (float) $row['batch_selling_price'] : null,
                'batch_mrp' => isset($row['batch_mrp']) && $row['batch_mrp'] !== '' ? (float) $row['batch_mrp'] : null,
                'serials' => $serials,
            ];
        }

        return $normalized;
    }
}