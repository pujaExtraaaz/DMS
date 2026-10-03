<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseInwardItem;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Domains\Purchasing\Models\SupplierPayable;
use App\Domains\Purchasing\Models\VendorPriceHistory;
use App\Models\User;
use App\Support\DocumentNumberService;
use App\Support\DueDateService;
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
    ) {}

    public function create(array $data, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $actor) {
            $supplier = Customer::query()->findOrFail($data['supplier_id']);
            $items = $this->normalizeItems($data['items'] ?? []);

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($items as &$item) {
                $line = $item['quantity'] * $item['unit_cost'];

                $taxPercent = (float) ($item['tax_percent'] ?? 0);

                // Automatically split total tax 50/50 unless
                // the user has supplied an editable CGST/SGST split.
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

                $item['cgst_percent'] = $cgstPercent;
                $item['sgst_percent'] = $sgstPercent;
                $item['cgst_amount'] = $cgstAmount;
                $item['sgst_amount'] = $sgstAmount;

                $subtotal += $line;
                $taxAmount += $tax;
            }

            unset($item);

            $po = PurchaseOrder::create([
                'company_id' => $data['company_id'] ?? $actor->company_id,
                'branch_id' => $data['branch_id'] ?? $actor->branch_id,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'supplier_id' => $supplier->id,
                'po_no' => $this->documentNumberService->next('PO'),
                'po_date' => $data['po_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'status' => ! empty($data['submit_for_approval']) ? 'pending_approval' : 'draft',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $subtotal + $taxAmount,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($items as $item) {
                $line = $item['quantity'] * $item['unit_cost'];
                $tax = $line * ($item['tax_percent'] / 100);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'],
                    'quantity' => $item['quantity'],
                    'received_qty' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'tax_percent' => $item['tax_percent'],
                    'cgst_percent' => $item['cgst_percent'],
                    'sgst_percent' => $item['sgst_percent'],
                    'cgst_amount' => $item['cgst_amount'],
                    'sgst_amount' => $item['sgst_amount'],
                    'line_total' => $line + $tax,
                    'weight' => $item['weight'] ?? null,
                ]);
            }

            return $po->load(['items.product', 'items.uom', 'supplier']);
        });
    }

    public function submitForApproval(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status !== 'draft') {
            throw new InvalidArgumentException('Only draft purchase orders can be submitted.');
        }

        $order->update(['status' => 'pending_approval']);

        return $order->fresh(['items.product', 'items.uom', 'supplier']);
    }

    public function approve(PurchaseOrder $order, User $actor): PurchaseOrder
    {
        if (! in_array($order->status, ['draft', 'pending_approval'], true)) {
            throw new InvalidArgumentException('Purchase order cannot be approved in its current status.');
        }

        $order->update([
            'status' => 'approved',
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        return $order->fresh(['items.product', 'items.uom', 'supplier', 'approver']);
    }

    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        if (in_array($order->status, ['closed', 'cancelled'], true)) {
            throw new InvalidArgumentException('Purchase order is already closed or cancelled.');
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
                    'batch_no' => $row['batch_no'] ?? null,
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
                        $actor,
                    );
                }

                if (! empty($row['batch_no'])) {
                    $this->serialBatchService->upsertBatch(
                        $product,
                        $uom,
                        (string) $row['batch_no'],
                        $accepted,
                        (float) $poItem->unit_cost,
                        $warehouseId,
                        $row['expiry_date'] ?? null,
                        $inwardItem,
                        isset($row['batch_selling_price']) && $row['batch_selling_price'] !== '' ? (float) $row['batch_selling_price'] : null,
                        isset($row['batch_mrp']) && $row['batch_mrp'] !== '' ? (float) $row['batch_mrp'] : null,
                    );
                }
            }

            $this->refreshOrderStatus($order);

            return $inward->load(['items.product', 'items.uom', 'purchaseOrder', 'supplier']);
        });
    }

    public function createInvoice(array $data, User $actor): PurchaseInvoice
    {
        return DB::transaction(function () use ($data, $actor) {
            $supplierId = (int) $data['supplier_id'];
            $items = $this->normalizeItems($data['items'] ?? []);
            $overrideReason = $data['rate_override_reason'] ?? null;
            $needsOverride = false;

            $subtotal = 0;
            $taxAmount = 0;
            $prepared = [];

            foreach ($items as $item) {
                $invoiceDate = $data['invoice_date'];

                $otherRate = VendorPriceHistory::query()
                    ->where('product_id', $item['product_id'])
                    ->where('uom_id', $item['uom_id'])
                    ->where('supplier_id', '!=', $supplierId)
                    ->whereDate('effective_from', '<=', $invoiceDate)
                    ->latest('effective_from')
                    ->value('unit_cost');

                if ($otherRate !== null && (float) $item['unit_cost'] > (float) $otherRate) {
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
                        'items' => 'CGST % and SGST % must add up to the Total Tax % for every item.',
                    ]);
                }

                $cgstAmount = $line * ($cgstPercent / 100);
                $sgstAmount = $line * ($sgstPercent / 100);

                $tax = $cgstAmount + $sgstAmount;

                $subtotal += $line;
                $taxAmount += $tax;

                $prepared[] = [
                    ...$item,
                    'tax_percent' => $taxPercent,
                    'cgst_percent' => $cgstPercent,
                    'sgst_percent' => $sgstPercent,
                    'cgst_amount' => $cgstAmount,
                    'sgst_amount' => $sgstAmount,
                    'line_total' => $line + $tax,
                    'other_vendor_rate' => $otherRate,
                ];
            }

            if ($needsOverride && blank($overrideReason)) {
                throw ValidationException::withMessages([
                    'rate_override_reason' => 'Unit cost is higher than another vendor rate. Provide an override reason.',
                ]);
            }

            $supplier = Customer::query()->findOrFail($supplierId);
            $inwardDate = null;
            if (! empty($data['purchase_inward_id'])) {
                $inwardDate = PurchaseInward::query()->whereKey($data['purchase_inward_id'])->value('inward_date');
            }
            $due = $this->dueDateService->forPurchaseInvoice(
                $supplier,
                $data['invoice_date'],
                $data['due_date_basis'] ?? null,
                isset($data['credit_days']) ? (int) $data['credit_days'] : null,
                $inwardDate,
            );

            $invoice = PurchaseInvoice::create([
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'purchase_inward_id' => $data['purchase_inward_id'] ?? null,
                'supplier_id' => $supplierId,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'invoice_no' => $this->documentNumberService->next('PI'),
                'supplier_invoice_no' => $data['supplier_invoice_no'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? $due['due_date'],
                'due_date_basis' => $due['due_date_basis'],
                'due_date_source_date' => $due['due_date_source_date'],
                'status' => 'posted',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $subtotal + $taxAmount,
                'rate_override_reason' => $overrideReason,
                'notes' => $data['notes'] ?? null,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'freight_allocation_method' => $data['freight_allocation_method'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($prepared as $item) {
                PurchaseInvoiceItem::create([
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
                    'other_vendor_rate' => $item['other_vendor_rate'],
                    'batch_no' => $item['batch_no'] ?? null,
                    'batch_selling_price' => $item['batch_selling_price'] ?? null,
                    'batch_mrp' => $item['batch_mrp'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);

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

            return $invoice->load(['items.product', 'items.uom', 'supplier']);
        });
    }

    protected function refreshOrderStatus(PurchaseOrder $order): void
    {
        $order->refresh()->load('items');
        $totalOrdered = (float) $order->items->sum('quantity');
        $totalReceived = (float) $order->items->sum('received_qty');

        if ($totalReceived <= 0) {
            $status = 'approved';
        } elseif ($totalReceived + 0.0001 >= $totalOrdered) {
            $status = 'closed';
        } else {
            $status = 'partially_received';
        }

        $order->update(['status' => $status]);
    }

    protected function normalizeItems(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'At least one line item is required.']);
        }

        $normalized = [];
        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $normalized[] = [
                'product_id' => (int) $item['product_id'],
                'uom_id' => (int) $item['uom_id'],
                'quantity' => $qty,
                'unit_cost' => (float) ($item['unit_cost'] ?? 0),
                'tax_percent' => (float) ($item['tax_percent'] ?? 0),
                'cgst_percent' => isset($item['cgst_percent'])
                    ? (float) $item['cgst_percent']
                    : ((float) ($item['tax_percent'] ?? 0) / 2),
                'sgst_percent' => isset($item['sgst_percent'])
                    ? (float) $item['sgst_percent']
                    : ((float) ($item['tax_percent'] ?? 0) / 2),
                'weight' => isset($item['weight']) ? (float) $item['weight'] : null,
                'batch_no' => $item['batch_no'] ?? null,
                'batch_selling_price' => isset($item['batch_selling_price']) && $item['batch_selling_price'] !== '' ? (float) $item['batch_selling_price'] : null,
                'batch_mrp' => isset($item['batch_mrp']) && $item['batch_mrp'] !== '' ? (float) $item['batch_mrp'] : null,
                'expiry_date' => $item['expiry_date'] ?? null,
            ];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages(['items' => 'At least one line item with quantity is required.']);
        }

        return $normalized;
    }
}
