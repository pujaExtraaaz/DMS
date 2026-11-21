<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Inventory\Models\ProductSerial;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Domains\Purchasing\Models\ReceiptNote;
use App\Domains\Purchasing\Models\ReceiptNoteItem;
use App\Domains\Purchasing\Models\ReceiptNoteReturn;
use App\Domains\Purchasing\Models\ReceiptNoteReturnItem;
use App\Models\User;
use App\Support\DocumentNumberService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptNoteService
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected SerialBatchService $serialBatchService,
        protected DocumentNumberService $documentNumberService,
        protected PurchaseOrderService $purchaseOrderService,
    ) {}

    /**
     * Create a new Receipt Note.
     *
     * @param  array<string, mixed>  $data
     */
    public function createReceiptNote(array $data, User $actor): ReceiptNote
    {
        return DB::transaction(function () use ($data, $actor) {
            $companyId = $data['company_id'] ?? $actor->company_id;
            $supplierId = (int) ($data['supplier_id'] ?? 0);
            $warehouseId = (int) ($data['warehouse_id'] ?? 0);

            if (! $supplierId) {
                throw ValidationException::withMessages(['supplier_id' => 'Supplier is required.']);
            }
            if (! $warehouseId) {
                throw ValidationException::withMessages(['warehouse_id' => 'Warehouse is required.']);
            }

            $poId = ! empty($data['purchase_order_id']) ? (int) $data['purchase_order_id'] : null;
            $receiptDate = ! empty($data['receipt_date']) ? Carbon::parse($data['receipt_date']) : now();

            $receiptNoteNo = ! empty($data['receipt_note_no'])
                ? strtoupper(trim((string) $data['receipt_note_no']))
                : $this->resolveReceiptNoteNumber($companyId, $receiptDate);

            // Check uniqueness of receipt note number for this company
            $exists = ReceiptNote::query()
                ->where('receipt_note_no', $receiptNoteNo)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'receipt_note_no' => 'Receipt Note number already exists.',
                ]);
            }

            $attachmentPath = null;
            if (isset($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                $attachmentPath = $data['attachment']->store('receipt-notes', 'public');
            }

            $status = $data['status'] ?? ReceiptNote::STATUS_DRAFT;

            $note = ReceiptNote::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? $actor->branch_id,
                'receipt_note_no' => $receiptNoteNo,
                'receipt_date' => $receiptDate->toDateString(),
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'purchase_order_id' => $poId,
                'supplier_challan_no' => $data['supplier_challan_no'] ?? null,
                'supplier_challan_date' => ! empty($data['supplier_challan_date']) ? Carbon::parse($data['supplier_challan_date'])->toDateString() : null,
                'received_by' => $data['received_by'] ?? $actor->name,
                'reference_no' => $data['reference_no'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'status' => ReceiptNote::STATUS_DRAFT,
                'attachment_path' => $attachmentPath,
                'created_by' => $actor->id,
            ]);

            $rawItems = $data['items'] ?? [];
            if (empty($rawItems)) {
                throw ValidationException::withMessages(['items' => 'At least one line item is required.']);
            }

            $itemsData = [];
            foreach ($rawItems as $row) {
                $productId = (int) ($row['product_id'] ?? 0);
                if (! $productId) {
                    continue;
                }

                $product = Product::findOrFail($productId);
                $uomId = (int) ($row['uom_id'] ?? $product->base_uom_id);
                $poItemId = ! empty($row['purchase_order_item_id']) ? (int) $row['purchase_order_item_id'] : null;

                $challanQty = (float) ($row['challan_qty'] ?? $row['received_qty'] ?? 0);
                $receivedQty = (float) ($row['received_qty'] ?? 0);
                $orderedQty = (float) ($row['ordered_qty'] ?? 0);
                $unitCost = isset($row['unit_cost']) && $row['unit_cost'] !== '' ? (float) $row['unit_cost'] : (float) ($product->purchase_price ?? 0);

                if ($receivedQty <= 0 && $challanQty <= 0) {
                    continue;
                }

                $serials = [];
                if (! empty($row['serials'])) {
                    if (is_array($row['serials'])) {
                        $serials = array_values(array_filter(array_map('trim', $row['serials'])));
                    } elseif (is_string($row['serials'])) {
                        $serials = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $row['serials']))));
                    }
                }

                $item = $note->items()->create([
                    'purchase_order_item_id' => $poItemId,
                    'product_id' => $productId,
                    'uom_id' => $uomId,
                    'ordered_qty' => $orderedQty,
                    'challan_qty' => $challanQty,
                    'received_qty' => $receivedQty,
                    'invoiced_qty' => 0,
                    'returned_qty' => 0,
                    'unit_cost' => $unitCost,
                    'batch_no' => ! empty($row['batch_no']) ? trim((string) $row['batch_no']) : null,
                    'expiry_date' => ! empty($row['expiry_date']) ? Carbon::parse($row['expiry_date'])->toDateString() : null,
                    'remarks' => $row['remarks'] ?? null,
                ]);

                $itemsData[] = [
                    'item' => $item,
                    'serials' => $serials,
                ];
            }

            if ($note->items()->count() === 0) {
                throw ValidationException::withMessages(['items' => 'At least one valid item with positive quantity is required.']);
            }

            // If requested to receive immediately (or default flow where physical material is confirmed)
            if ($status === ReceiptNote::STATUS_RECEIVED || ! empty($data['confirm_immediately'])) {
                $this->confirmReceiptNoteInternal($note, $actor, $itemsData);
            }

            return $note->fresh(['supplier', 'warehouse', 'purchaseOrder', 'items.product', 'items.uom']);
        });
    }

    /**
     * Confirm physical receipt and record stock movements.
     */
    public function confirmReceiptNote(ReceiptNote $note, User $actor): ReceiptNote
    {
        return DB::transaction(function () use ($note, $actor) {
            if (! $note->canBeConfirmed()) {
                throw ValidationException::withMessages([
                    'receipt_note' => "Receipt Note cannot be confirmed in {$note->status} status.",
                ]);
            }

            $note->load(['items.product.baseUom', 'items.uom', 'warehouse']);

            $itemsData = [];
            foreach ($note->items as $item) {
                $itemsData[] = [
                    'item' => $item,
                    'serials' => [], // serials assigned if already saved or handled
                ];
            }

            $this->confirmReceiptNoteInternal($note, $actor, $itemsData);

            return $note->fresh(['supplier', 'warehouse', 'purchaseOrder', 'items.product', 'items.uom']);
        });
    }

    /**
     * Internal confirmation helper.
     *
     * @param  array<int, array{item: ReceiptNoteItem, serials: array<int, string>}>  $itemsData
     */
    protected function confirmReceiptNoteInternal(ReceiptNote $note, User $actor, array $itemsData = []): void
    {
        $warehouseId = $note->warehouse_id;

        foreach ($note->items as $item) {
            $receivedQty = (float) $item->received_qty;
            if ($receivedQty <= 0) {
                continue;
            }

            $product = $item->product;
            $uom = $item->uom ?? $product->baseUom;

            // 1. Record stock movement IN (receipt_note)
            $this->stockMovementService->recordIn(
                product: $product,
                uom: $uom,
                quantity: $receivedQty,
                type: 'receipt_note',
                reference: $note,
                notes: "Receipt Note {$note->receipt_note_no}".($note->supplier_challan_no ? " (Challan: {$note->supplier_challan_no})" : ''),
                user: $actor,
                warehouseId: $warehouseId,
                unitCost: (float) $item->unit_cost,
                batchNo: $item->batch_no,
            );

            // 2. Upsert batch if specified
            if (! empty($item->batch_no)) {
                $this->serialBatchService->upsertBatch(
                    product: $product,
                    uom: $uom,
                    batchNo: (string) $item->batch_no,
                    quantity: $receivedQty,
                    unitCost: (float) $item->unit_cost,
                    warehouseId: $warehouseId,
                    expiryDate: $item->expiry_date?->toDateString(),
                    source: $item,
                );
            }

            // 3. Find serials for this item if passed
            $serials = [];
            foreach ($itemsData as $entry) {
                if ($entry['item']->id === $item->id && ! empty($entry['serials'])) {
                    $serials = $entry['serials'];
                    break;
                }
            }

            if (! empty($serials)) {
                $this->serialBatchService->assignSerials(
                    product: $product,
                    serials: $serials,
                    warehouseId: $warehouseId,
                    source: $item,
                    actor: $actor,
                );
            }

            // 4. Update linked Purchase Order item received_qty if present
            if ($item->purchase_order_item_id && $item->purchaseOrderItem) {
                $poItem = $item->purchaseOrderItem;
                $poItem->update([
                    'received_qty' => (float) $poItem->received_qty + $receivedQty,
                ]);
            }
        }

        // If linked to a PO, refresh PO status
        if ($note->purchase_order_id && $note->purchaseOrder) {
            $this->refreshPurchaseOrderStatus($note->purchaseOrder);
        }

        $note->update([
            'status' => ReceiptNote::STATUS_RECEIVED,
            'confirmed_at' => now(),
            'confirmed_by' => $actor->id,
        ]);
    }

    /**
     * Cancel a Receipt Note and revert stock movements if previously confirmed.
     */
    public function cancelReceiptNote(ReceiptNote $note, User $actor): ReceiptNote
    {
        return DB::transaction(function () use ($note, $actor) {
            if ($note->status === ReceiptNote::STATUS_CANCELLED) {
                return $note;
            }

            if ($note->total_invoiced_qty > 0.0001) {
                throw ValidationException::withMessages([
                    'receipt_note' => 'Cannot cancel a Receipt Note that has already been partially or fully invoiced.',
                ]);
            }

            if ($note->total_returned_qty > 0.0001) {
                throw ValidationException::withMessages([
                    'receipt_note' => 'Cannot cancel a Receipt Note that has active material returns.',
                ]);
            }

            // If it was in received status, revert stock movements
            if ($note->isReceived()) {
                foreach ($note->inventoryMovements as $movement) {
                    if ($movement->quantity > 0) {
                        $stockLevel = StockLevel::query()
                            ->where('warehouse_id', $movement->warehouse_id)
                            ->where('product_id', $movement->product_id)
                            ->where('uom_id', $movement->uom_id)
                            ->first();

                        if ($stockLevel) {
                            $stockLevel->decrement('quantity', $movement->quantity);
                        }
                    }
                    $movement->delete();
                }

                // Delete serials created against this receipt note items
                foreach ($note->items as $item) {
                    ProductSerial::query()
                        ->where('source_type', $item->getMorphClass())
                        ->where('source_id', $item->id)
                        ->delete();

                    // Revert PO received qty if applicable
                    if ($item->purchase_order_item_id && $item->purchaseOrderItem) {
                        $poItem = $item->purchaseOrderItem;
                        $newReceived = max(0, (float) $poItem->received_qty - (float) $item->received_qty);
                        $poItem->update(['received_qty' => $newReceived]);
                    }
                }

                if ($note->purchase_order_id && $note->purchaseOrder) {
                    $this->refreshPurchaseOrderStatus($note->purchaseOrder);
                }
            }

            $note->update([
                'status' => ReceiptNote::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
            ]);

            return $note->fresh();
        });
    }

    /**
     * Return received material to supplier via Delivery Challan.
     *
     * @param  array<string, mixed>  $data
     */
    public function createReturn(ReceiptNote $note, array $data, User $actor): ReceiptNoteReturn
    {
        return DB::transaction(function () use ($note, $data, $actor) {
            if (! $note->canBeReturned()) {
                throw ValidationException::withMessages([
                    'receipt_note' => 'No returnable quantity remaining on this Receipt Note.',
                ]);
            }

            $returnDate = ! empty($data['return_date']) ? Carbon::parse($data['return_date']) : now();
            $returnNo = ! empty($data['return_no'])
                ? strtoupper(trim((string) $data['return_no']))
                : $this->resolveReturnNumber($note->company_id, $returnDate);

            $return = ReceiptNoteReturn::create([
                'company_id' => $note->company_id,
                'receipt_note_id' => $note->id,
                'return_no' => $returnNo,
                'return_date' => $returnDate->toDateString(),
                'warehouse_id' => $note->warehouse_id,
                'reference_no' => $data['reference_no'] ?? null,
                'reason' => $data['reason'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $actor->id,
            ]);

            $rawItems = $data['items'] ?? [];
            $returnedAny = false;

            foreach ($note->items as $noteItem) {
                $requestedQty = 0.0;
                if (isset($rawItems[$noteItem->id]['quantity'])) {
                    $requestedQty = (float) $rawItems[$noteItem->id]['quantity'];
                } elseif (isset($rawItems[$noteItem->id])) {
                    $requestedQty = (float) $rawItems[$noteItem->id];
                }

                if ($requestedQty <= 0) {
                    continue;
                }

                $maxReturnable = $noteItem->pending_return_qty;
                if ($requestedQty > $maxReturnable + 0.0001) {
                    throw ValidationException::withMessages([
                        "items.{$noteItem->id}" => "Return quantity for {$noteItem->product?->name} cannot exceed {$maxReturnable}.",
                    ]);
                }

                $requestedQty = min($requestedQty, $maxReturnable);

                // Create return item line
                $return->items()->create([
                    'receipt_note_item_id' => $noteItem->id,
                    'product_id' => $noteItem->product_id,
                    'uom_id' => $noteItem->uom_id,
                    'quantity' => $requestedQty,
                    'remarks' => $rawItems[$noteItem->id]['remarks'] ?? null,
                ]);

                // Record stock movement OUT (receipt_note_return)
                $product = $noteItem->product;
                $uom = $noteItem->uom ?? $product->baseUom;

                $this->stockMovementService->recordOut(
                    product: $product,
                    uom: $uom,
                    quantity: $requestedQty,
                    type: 'receipt_note_return',
                    reference: $return,
                    notes: "Material return {$returnNo} for RN {$note->receipt_note_no}",
                    user: $actor,
                    warehouseId: $note->warehouse_id,
                );

                // Update returned_qty on receipt note item
                $noteItem->update([
                    'returned_qty' => (float) $noteItem->returned_qty + $requestedQty,
                ]);

                $returnedAny = true;
            }

            if (! $returnedAny) {
                throw ValidationException::withMessages([
                    'items' => 'Please specify a return quantity greater than 0 for at least one item.',
                ]);
            }

            $this->syncReceiptNoteStatus($note);

            return $return->fresh(['items.product', 'items.uom']);
        });
    }

    /**
     * Synchronize the Receipt Note status based on invoiced and returned quantities.
     */
    public function syncReceiptNoteStatus(ReceiptNote $note): void
    {
        $note->refresh()->load('items');

        if ($note->isCancelled() || $note->isDraft()) {
            return;
        }

        $totalReceived = (float) $note->items->sum('received_qty');
        $totalReturned = (float) $note->items->sum('returned_qty');
        $totalInvoiced = (float) $note->items->sum('invoiced_qty');

        $retained = max(0, $totalReceived - $totalReturned);

        if ($retained <= 0.0001 && $totalReturned > 0) {
            $status = ReceiptNote::STATUS_FULLY_RETURNED;
        } elseif ($totalInvoiced >= $retained - 0.0001 && $retained > 0.0001) {
            $status = ReceiptNote::STATUS_FULLY_INVOICED;
        } elseif ($totalInvoiced > 0.0001) {
            $status = ReceiptNote::STATUS_PARTIALLY_INVOICED;
        } elseif ($totalReturned > 0.0001) {
            $status = ReceiptNote::STATUS_PARTIALLY_RETURNED;
        } else {
            $status = ReceiptNote::STATUS_RECEIVED;
        }

        $note->update(['status' => $status]);
    }

    protected function resolveReceiptNoteNumber(?int $companyId, Carbon $date): string
    {
        try {
            return $this->documentNumberService->nextDocument('RN', $date, 4, $companyId);
        } catch (\Throwable $e) {
            $count = ReceiptNote::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->count();
            return 'RN-' . $date->format('ym') . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
        }
    }

    protected function resolveReturnNumber(?int $companyId, Carbon $date): string
    {
        try {
            return $this->documentNumberService->nextDocument('DC-RET', $date, 4, $companyId);
        } catch (\Throwable $e) {
            $count = ReceiptNoteReturn::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->count();
            return 'DC-RET-' . $date->format('ym') . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
        }
    }

    protected function refreshPurchaseOrderStatus(PurchaseOrder $order): void
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
}
