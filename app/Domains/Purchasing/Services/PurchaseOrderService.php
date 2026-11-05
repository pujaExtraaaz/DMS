<?php
namespace App\Domains\Purchasing\Services;

use App\Domains\Banking\Models\BankAccountTransaction;
use App\Domains\Banking\Models\OdAccount;
use App\Domains\Inventory\Models\ProductSerial;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\CompanyDeliveryAddress;
use App\Domains\Organization\Models\Warehouse;
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

            $warehouse = !empty($data['warehouse_id'])
                ? Warehouse::with(['branch', 'company'])->find($data['warehouse_id'])
                : null;

            $companyId = $data['company_id'] ?? $warehouse?->company_id ?? $actor->company_id ?? Company::defaultCompanyId();
            $addresses = $this->resolvePurchaseOrderPartyAddresses($supplier, $data, $warehouse, $companyId);

            $freight = $this->freightCharges($data, $subtotal);
            $grandTotal = round($subtotal + $taxAmount + $freight['freight_total'], 2);

            $creditDaysOverride = (isset($data['credit_days']) && $data['credit_days'] !== '' && $data['credit_days'] !== null)
                ? max(0, (int) $data['credit_days'])
                : null;

            $due = $this->dueDateService->forPurchaseOrder(
                $supplier,
                $data['po_date'],
                $creditDaysOverride,
            );

            $po = PurchaseOrder::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? $actor->branch_id,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'supplier_id' => $supplier->id,
                'billing_address_id' => $addresses['billing_address_id'],
                'shipping_address_id' => null,
                'company_delivery_address_id' => $addresses['company_delivery_address_id'],
                'billing_address' => $addresses['billing_address'],
                'shipping_address' => $addresses['shipping_address'],
                'po_no' => $this->resolvePurchaseOrderNumber($data, $companyId),
                'po_date' => $data['po_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'credit_days' => $due['credit_days'],
                'due_date' => $due['due_date'],
                'status' => ! empty($data['submit_for_approval']) ? 'pending_approval' : 'draft',
                'is_freight_applicable' => $freight['is_freight_applicable'],
                'freight_mode' => $freight['freight_mode'],
                'freight_percent' => $freight['freight_percent'],
                'freight_amount' => $freight['freight_amount'],
                'freight_tax_rate' => $freight['freight_tax_rate'],
                'freight_tax_amount' => $freight['freight_tax_amount'],
                'freight_total' => $freight['freight_total'],
                'freight_notes' => $freight['freight_notes'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
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
                    'mrp' => $item['mrp'] ?? null,
                    'purchase_price_inclusive' => $item['purchase_price_inclusive'] ?? null,
                    'purchase_discount_percent' => $item['purchase_discount_percent'] ?? null,
                    'tax_percent' => $item['tax_percent'],
                    'cgst_percent' => $item['cgst_percent'],
                    'sgst_percent' => $item['sgst_percent'],
                    'cgst_amount' => $item['cgst_amount'],
                    'sgst_amount' => $item['sgst_amount'],
                    'line_total' => $line + $tax,
                    'weight' => $item['weight'] ?? null,
                    'batch_no' => $item['batch_no'] ?? null,
                    'serial_no' => filled($item['serial_no'] ?? null) ? trim((string) $item['serial_no']) : null,
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

    public function update(PurchaseOrder $order, array $data, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $data, $actor) {
            if (! in_array($order->status, ['draft', 'pending_approval', 'approved'], true)) {
                throw new InvalidArgumentException("Cannot edit purchase order in '{$order->status}' status.");
            }

            if ($order->inwards()->exists()) {
                throw new InvalidArgumentException('Cannot edit purchase order because goods receipts exist for it.');
            }

            if ($order->invoices()->exists()) {
                throw new InvalidArgumentException('Cannot edit purchase order because purchase invoices exist for it.');
            }

            $supplier = Customer::query()->findOrFail($data['supplier_id']);
            $items = $this->normalizeItems($data['items'] ?? []);

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($items as &$item) {
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

                $item['cgst_percent'] = $cgstPercent;
                $item['sgst_percent'] = $sgstPercent;
                $item['cgst_amount'] = $cgstAmount;
                $item['sgst_amount'] = $sgstAmount;

                $subtotal += $line;
                $taxAmount += $tax;
            }

            unset($item);

            $warehouse = !empty($data['warehouse_id'])
                ? Warehouse::with(['branch', 'company'])->find($data['warehouse_id'])
                : ($order->warehouse()->with(['branch', 'company'])->first());

            $companyId = $data['company_id'] ?? $warehouse?->company_id ?? $order->company_id ?? Company::defaultCompanyId();
            $addresses = $this->resolvePurchaseOrderPartyAddresses(
                $supplier,
                $data,
                $warehouse,
                $companyId,
                $order->shipping_address,
                $order->billing_address,
            );

            $newStatus = $order->status;
            if (! empty($data['submit_for_approval'])) {
                $newStatus = 'pending_approval';
            }

            $freight = $this->freightCharges($data, $subtotal);
            $grandTotal = round($subtotal + $taxAmount + $freight['freight_total'], 2);

            $creditDaysOverride = (isset($data['credit_days']) && $data['credit_days'] !== '' && $data['credit_days'] !== null)
                ? max(0, (int) $data['credit_days'])
                : null;

            $due = $this->dueDateService->forPurchaseOrder(
                $supplier,
                $data['po_date'],
                $creditDaysOverride,
            );

            $order->update([
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'supplier_id' => $supplier->id,
                'billing_address_id' => $addresses['billing_address_id'] ?? $order->billing_address_id,
                'shipping_address_id' => null,
                'company_delivery_address_id' => $addresses['company_delivery_address_id'],
                'billing_address' => $addresses['billing_address'],
                'shipping_address' => $addresses['shipping_address'],
                'po_date' => $data['po_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'credit_days' => $due['credit_days'],
                'due_date' => $due['due_date'],
                'status' => $newStatus,
                'is_freight_applicable' => $freight['is_freight_applicable'],
                'freight_mode' => $freight['freight_mode'],
                'freight_percent' => $freight['freight_percent'],
                'freight_amount' => $freight['freight_amount'],
                'freight_tax_rate' => $freight['freight_tax_rate'],
                'freight_tax_amount' => $freight['freight_tax_amount'],
                'freight_total' => $freight['freight_total'],
                'freight_notes' => $freight['freight_notes'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'notes' => $data['notes'] ?? null,
            ]);

            $order->items()->delete();

            foreach ($items as $item) {
                $line = $item['quantity'] * $item['unit_cost'];
                $tax = $line * ($item['tax_percent'] / 100);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'],
                    'quantity' => $item['quantity'],
                    'received_qty' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'mrp' => $item['mrp'] ?? null,
                    'purchase_price_inclusive' => $item['purchase_price_inclusive'] ?? null,
                    'purchase_discount_percent' => $item['purchase_discount_percent'] ?? null,
                    'tax_percent' => $item['tax_percent'],
                    'cgst_percent' => $item['cgst_percent'],
                    'sgst_percent' => $item['sgst_percent'],
                    'cgst_amount' => $item['cgst_amount'],
                    'sgst_amount' => $item['sgst_amount'],
                    'line_total' => $line + $tax,
                    'weight' => $item['weight'] ?? null,
                    'batch_no' => $item['batch_no'] ?? null,
                    'serial_no' => filled($item['serial_no'] ?? null) ? trim((string) $item['serial_no']) : null,
                ]);
            }

            return $order->fresh(['items.product', 'items.uom', 'supplier']);
        });
    }

    public function delete(PurchaseOrder $order, User $actor): void
    {
        DB::transaction(function () use ($order) {
            $blockReason = $order->getDeleteBlockReason();
            if ($blockReason) {
                throw new InvalidArgumentException($blockReason);
            }

            $order->items()->delete();
            $order->delete();
        });
    }

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

            $priceType = strtolower(trim((string)($data['price_type'] ?? 'exclusive'))) === 'inclusive' ? 'inclusive' : 'exclusive';
            $subtotal = 0;
            $taxAmount = 0;
            $cgstTotal = 0;
            $sgstTotal = 0;
            $igstTotal = 0;
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

                $enteredUnitCost = (float) $item['unit_cost'];
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

                $qty = (float) $item['quantity'];
                if ($priceType === 'inclusive') {
                    $baseUnitCost = $taxPercent > 0 ? round($enteredUnitCost * 100 / (100 + $taxPercent), 4) : $enteredUnitCost;
                    $line = round($qty * $baseUnitCost, 2);
                    $tax = round($line * ($taxPercent / 100), 2);
                    $lineTotal = round($line + $tax, 2);
                } else {
                    $baseUnitCost = $enteredUnitCost;
                    $line = round($qty * $baseUnitCost, 2);
                    $tax = round($line * ($taxPercent / 100), 2);
                    $lineTotal = round($line + $tax, 2);
                }

                $cgstAmount = round($tax / 2, 2);
                $sgstAmount = round($tax - $cgstAmount, 2);

                $subtotal += $line;
                $taxAmount += $tax;
                $cgstTotal += $cgstAmount;
                $sgstTotal += $sgstAmount;

                $prepared[] = [
                    ...$item,
                    'price_type' => $priceType,
                    'entered_unit_cost' => $enteredUnitCost,
                    'unit_cost' => $baseUnitCost,
                    'taxable_amount' => $line,
                    'tax_percent' => $taxPercent,
                    'cgst_percent' => $cgstPercent,
                    'sgst_percent' => $sgstPercent,
                    'cgst_amount' => $cgstAmount,
                    'sgst_amount' => $sgstAmount,
                    'line_total' => $lineTotal,
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

            $billingAddress = null;
            if (!empty($data['billing_address_id'])) {
                $billingAddress = \App\Domains\Master\Models\PartyAddress::where('id', $data['billing_address_id'])
                    ->where('customer_id', $supplierId)
                    ->first();
                if (!$billingAddress) {
                    throw ValidationException::withMessages([
                        'billing_address_id' => 'The selected billing address does not belong to the selected supplier.',
                    ]);
                }
            }
            $shippingAddress = null;
            if (!empty($data['shipping_address_id'])) {
                $shippingAddress = \App\Domains\Master\Models\PartyAddress::where('id', $data['shipping_address_id'])
                    ->where('customer_id', $supplierId)
                    ->first();
                if (!$shippingAddress) {
                    throw ValidationException::withMessages([
                        'shipping_address_id' => 'The selected delivery address does not belong to the selected supplier.',
                    ]);
                }
            }

            if (!$billingAddress) {
                $billingAddress = $supplier->defaultBillingAddress();
            }
            if (!$shippingAddress) {
                $shippingAddress = $supplier->defaultDeliveryAddress() ?? $billingAddress;
            }

            $billingSnapshot = !empty($data['billing_address'])
                ? $data['billing_address']
                : ($billingAddress ? $billingAddress->formatSnapshot() : $supplier->address);

            $shippingSnapshot = !empty($data['shipping_address'])
                ? $data['shipping_address']
                : ($shippingAddress ? $shippingAddress->formatSnapshot() : ($billingSnapshot ?: $supplier->address));

            $commercial = $this->invoiceCommercial($data, $subtotal);
            $freightCharge = $commercial['freight_charge'];
            $otherCharges = $commercial['other_charges'];
            $totalBeforeRound = round($subtotal + $taxAmount + $freightCharge + $otherCharges, 2);
            $roundOff = isset($data['round_off']) && $data['round_off'] !== null && $data['round_off'] !== ''
                ? round((float) $data['round_off'], 2)
                : \App\Domains\Master\Services\GstTaxCalculationService::computeRoundOff($totalBeforeRound);
            $grandTotal = round(max(0, $totalBeforeRound + $roundOff), 2);

            $warehouse = !empty($data['warehouse_id']) ? Warehouse::find($data['warehouse_id']) : null;
            $companyId = $data['company_id'] ?? $warehouse?->company_id ?? $actor->company_id ?? Company::defaultCompanyId() ?? 1;

            $odAccount = null;
            if (!empty($data['od_account_id'])) {
                $odAccount = OdAccount::where('id', $data['od_account_id'])->lockForUpdate()->first();
                if (!$odAccount) {
                    throw ValidationException::withMessages([
                        'od_account_id' => 'The selected OD bank account was not found.',
                    ]);
                }
                if ($odAccount->status !== 'active') {
                    throw ValidationException::withMessages([
                        'od_account_id' => 'The selected OD bank account is inactive.',
                    ]);
                }
                if ($companyId && (int) $odAccount->company_id !== (int) $companyId) {
                    throw ValidationException::withMessages([
                        'od_account_id' => 'The selected OD bank account belongs to another company.',
                    ]);
                }

                $availableLimit = $odAccount->getAvailableLimit();
                if ($grandTotal > $availableLimit) {
                    throw ValidationException::withMessages([
                        'od_account_id' => "The invoice payable amount (₹" . number_format($grandTotal, 2) . ") exceeds the available OD limit of ₹" . number_format($availableLimit, 2) . " for account {$odAccount->bank_name} - {$odAccount->account_number}."
                    ]);
                }
            }

            $invoice = PurchaseInvoice::create([
                'company_id' => $companyId,
                'od_account_id' => $odAccount?->id,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'purchase_inward_id' => $data['purchase_inward_id'] ?? null,
                'supplier_id' => $supplierId,
                'billing_address_id' => $billingAddress?->id,
                'shipping_address_id' => $shippingAddress?->id,
                'billing_address' => $billingSnapshot,
                'shipping_address' => $shippingSnapshot,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'invoice_no' => $this->documentNumberService->next('PI'),
                'supplier_invoice_no' => $data['supplier_invoice_no'] ?? $data['supplier_invoice_number'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'purchase_receive_date' => $data['purchase_receive_date'] ?? null,
                'material_dispatch_date' => $data['material_dispatch_date'] ?? null,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'actual_material_receipt_date' => null,
                'payment_order_date' => $data['payment_order_date'] ?? null,
                'due_date' => $data['due_date'] ?? $due['due_date'],
                'credit_days' => $data['credit_days'] ?? null,
                'due_date_basis' => $due['due_date_basis'],
                'due_date_source_date' => $due['due_date_source_date'],
                // Financial invoice is recorded; physical stock stays In Transit until receipt confirmation.
                'status' => 'posted',
                'material_receipt_status' => PurchaseInvoice::MATERIAL_IN_TRANSIT,
                'price_type' => $priceType,
                'subtotal' => $subtotal,
                'taxable_amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'cgst_amount' => $cgstTotal,
                'sgst_amount' => $sgstTotal,
                'igst_amount' => $igstTotal,
                'freight_charge' => $freightCharge,
                'other_charges' => $otherCharges,
                'round_off' => $roundOff,
                'grand_total' => $grandTotal,
                'total_amount' => $grandTotal,
                'rate_override_reason' => $overrideReason,
                'notes' => $data['notes'] ?? null,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'freight_allocation_method' => $data['freight_allocation_method'] ?? null,
                'created_by' => $actor->id,
            ] + $commercial['attributes']);

            foreach ($prepared as $item) {
                $piItem = PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'],
                    'quantity' => $item['quantity'],
                    'received_qty' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'purchase_price_inclusive' => $item['purchase_price_inclusive'] ?? null,
                    'purchase_discount_percent' => $item['purchase_discount_percent'] ?? null,
                    'price_type' => $priceType,
                    'entered_unit_cost' => $item['entered_unit_cost'] ?? $item['unit_cost'],
                    'unit_cost' => $item['unit_cost'],
                    'taxable_amount' => $item['taxable_amount'] ?? ($item['quantity'] * $item['unit_cost']),
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

                if (! empty($item['serials']) && is_array($item['serials'])) {
                    $prod = Product::find($item['product_id']);
                    if ($prod) {
                        $this->serialBatchService->assignSerials(
                            $prod,
                            $item['serials'],
                            $invoice->warehouse_id,
                            $piItem,
                            $actor,
                            'in_transit',
                        );
                    }
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

            if ($odAccount) {
                BankAccountTransaction::updateOrCreate(
                    [
                        'reference_type' => $invoice->getMorphClass(),
                        'reference_id' => $invoice->id,
                    ],
                    [
                        'company_id' => $companyId,
                        'account_number' => $odAccount->account_number,
                        'od_account_id' => $odAccount->id,
                        'transaction_date' => $invoice->invoice_date,
                        'transaction_no' => 'OD-' . $invoice->invoice_no,
                        'description' => "OD utilization for Purchase Invoice {$invoice->invoice_no}",
                        'transaction_type' => 'debit',
                        'debit' => $invoice->grand_total,
                        'credit' => 0,
                        'created_by' => $actor->id,
                    ]
                );
            }

            return $invoice->load(['items.product', 'items.uom', 'supplier', 'odAccount']);
        });
    }

    public function updateInvoice(PurchaseInvoice $invoice, array $data, User $actor): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice, $data, $actor) {
            if (
                $invoice->inventoryMovements()->exists()
                || (float) $invoice->items()->sum('received_qty') > 0
                || in_array($invoice->material_receipt_status, [
                    PurchaseInvoice::MATERIAL_PARTIALLY_RECEIVED,
                    PurchaseInvoice::MATERIAL_RECEIVED,
                ], true)
            ) {
                throw ValidationException::withMessages([
                    'items' => 'This invoice has material receipt activity. Edit is blocked so stock stays accurate. Use Receive Material for further receipts.',
                ]);
            }

            $supplierId = (int) $data['supplier_id'];
            $items = $this->normalizeItems($data['items'] ?? []);
            $overrideReason = $data['rate_override_reason'] ?? $invoice->rate_override_reason;
            $needsOverride = false;

            $priceType = strtolower(trim((string)($data['price_type'] ?? 'exclusive'))) === 'inclusive' ? 'inclusive' : 'exclusive';
            $subtotal = 0;
            $taxAmount = 0;
            $cgstTotal = 0;
            $sgstTotal = 0;
            $igstTotal = 0;
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

                $enteredUnitCost = (float) $item['unit_cost'];
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

                $qty = (float) $item['quantity'];
                if ($priceType === 'inclusive') {
                    $baseUnitCost = $taxPercent > 0 ? round($enteredUnitCost * 100 / (100 + $taxPercent), 4) : $enteredUnitCost;
                    $line = round($qty * $baseUnitCost, 2);
                    $tax = round($line * ($taxPercent / 100), 2);
                    $lineTotal = round($line + $tax, 2);
                } else {
                    $baseUnitCost = $enteredUnitCost;
                    $line = round($qty * $baseUnitCost, 2);
                    $tax = round($line * ($taxPercent / 100), 2);
                    $lineTotal = round($line + $tax, 2);
                }

                $cgstAmount = round($tax / 2, 2);
                $sgstAmount = round($tax - $cgstAmount, 2);

                $subtotal += $line;
                $taxAmount += $tax;
                $cgstTotal += $cgstAmount;
                $sgstTotal += $sgstAmount;

                $prepared[] = [
                    ...$item,
                    'price_type' => $priceType,
                    'entered_unit_cost' => $enteredUnitCost,
                    'unit_cost' => $baseUnitCost,
                    'taxable_amount' => $line,
                    'tax_percent' => $taxPercent,
                    'cgst_percent' => $cgstPercent,
                    'sgst_percent' => $sgstPercent,
                    'cgst_amount' => $cgstAmount,
                    'sgst_amount' => $sgstAmount,
                    'line_total' => $lineTotal,
                    'other_vendor_rate' => $otherRate,
                ];
            }

            if ($needsOverride && blank($overrideReason)) {
                throw ValidationException::withMessages([
                    'rate_override_reason' => 'Unit cost is higher than another vendor rate. Provide an override reason.',
                ]);
            }

            $supplier = Customer::query()->findOrFail($supplierId);
            $inwardDate = ! empty($data['purchase_receive_date']) ? $data['purchase_receive_date'] : null;
            if (! $inwardDate && ! empty($data['purchase_inward_id'])) {
                $inwardDate = PurchaseInward::query()->whereKey($data['purchase_inward_id'])->value('inward_date');
            }

            $due = $this->dueDateService->forPurchaseInvoice(
                $supplier,
                $data['invoice_date'],
                $data['due_date_basis'] ?? null,
                isset($data['credit_days']) ? (int) $data['credit_days'] : null,
                $inwardDate,
            );

            $billingAddress = null;
            if (! empty($data['billing_address_id'])) {
                $billingAddress = \App\Domains\Master\Models\PartyAddress::where('id', $data['billing_address_id'])
                    ->where('customer_id', $supplierId)
                    ->first();
                if (! $billingAddress) {
                    throw ValidationException::withMessages([
                        'billing_address_id' => 'The selected billing address does not belong to the selected supplier.',
                    ]);
                }
            }
            $shippingAddress = null;
            if (! empty($data['shipping_address_id'])) {
                $shippingAddress = \App\Domains\Master\Models\PartyAddress::where('id', $data['shipping_address_id'])
                    ->where('customer_id', $supplierId)
                    ->first();
                if (! $shippingAddress) {
                    throw ValidationException::withMessages([
                        'shipping_address_id' => 'The selected delivery address does not belong to the selected supplier.',
                    ]);
                }
            }

            if (! $billingAddress) {
                $billingAddress = $supplier->defaultBillingAddress();
            }
            if (! $shippingAddress) {
                $shippingAddress = $supplier->defaultDeliveryAddress() ?? $billingAddress;
            }

            $billingSnapshot = ! empty($data['billing_address'])
                ? $data['billing_address']
                : ($billingAddress ? $billingAddress->formatSnapshot() : $supplier->address);

            $shippingSnapshot = ! empty($data['shipping_address'])
                ? $data['shipping_address']
                : ($shippingAddress ? $shippingAddress->formatSnapshot() : ($billingSnapshot ?: $supplier->address));

            $commercial = $this->invoiceCommercial($data, $subtotal);
            $freightCharge = $commercial['freight_charge'];
            $otherCharges = $commercial['other_charges'];
            $totalBeforeRound = round($subtotal + $taxAmount + $freightCharge + $otherCharges, 2);
            $roundOff = isset($data['round_off']) && $data['round_off'] !== null && $data['round_off'] !== ''
                ? round((float) $data['round_off'], 2)
                : \App\Domains\Master\Services\GstTaxCalculationService::computeRoundOff($totalBeforeRound);
            $grandTotal = round(max(0, $totalBeforeRound + $roundOff), 2);

            $warehouse = ! empty($data['warehouse_id']) ? Warehouse::find($data['warehouse_id']) : null;
            $companyId = $data['company_id'] ?? $invoice->company_id ?? $warehouse?->company_id ?? $actor->company_id ?? Company::defaultCompanyId() ?? 1;

            $odAccount = null;
            if (! empty($data['od_account_id'])) {
                $odAccount = OdAccount::where('id', $data['od_account_id'])->lockForUpdate()->first();
                if (! $odAccount) {
                    throw ValidationException::withMessages([
                        'od_account_id' => 'The selected OD bank account was not found.',
                    ]);
                }
                if ($odAccount->status !== 'active') {
                    throw ValidationException::withMessages([
                        'od_account_id' => 'The selected OD bank account is inactive.',
                    ]);
                }
                if ($companyId && (int) $odAccount->company_id !== (int) $companyId) {
                    throw ValidationException::withMessages([
                        'od_account_id' => 'The selected OD bank account belongs to another company.',
                    ]);
                }

                $existingTx = BankAccountTransaction::where('reference_type', $invoice->getMorphClass())
                    ->where('reference_id', $invoice->id)
                    ->first();

                $existingDebit = ($existingTx && (int) $existingTx->od_account_id === (int) $odAccount->id)
                    ? (float) $existingTx->debit
                    : 0.0;

                $availableLimit = (float) $odAccount->getAvailableLimit() + $existingDebit;
                if ($grandTotal > $availableLimit) {
                    throw ValidationException::withMessages([
                        'od_account_id' => "The invoice payable amount (₹" . number_format($grandTotal, 2) . ") exceeds the available OD limit of ₹" . number_format($availableLimit, 2) . " for account {$odAccount->bank_name} - {$odAccount->account_number}."
                    ]);
                }
            }

            $invoice->update([
                'company_id' => $companyId,
                'od_account_id' => $odAccount?->id,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'purchase_inward_id' => $data['purchase_inward_id'] ?? null,
                'supplier_id' => $supplierId,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'billing_address_id' => $billingAddress?->id,
                'shipping_address_id' => $shippingAddress?->id,
                'billing_address' => $billingSnapshot,
                'shipping_address' => $shippingSnapshot,
                'supplier_invoice_no' => $data['supplier_invoice_number'] ?? $data['supplier_invoice_no'] ?? $invoice->supplier_invoice_no,
                'invoice_date' => $data['invoice_date'],
                'purchase_receive_date' => $data['purchase_receive_date'] ?? null,
                'material_dispatch_date' => $data['material_dispatch_date'] ?? $invoice->material_dispatch_date,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? $invoice->expected_delivery_date,
                'payment_order_date' => $data['payment_order_date'] ?? null,
                'due_date' => $data['due_date'] ?? $due['due_date'],
                'credit_days' => $data['credit_days'] ?? null,
                'due_date_basis' => $due['due_date_basis'],
                'due_date_source_date' => $due['due_date_source_date'],
                'material_receipt_status' => PurchaseInvoice::MATERIAL_IN_TRANSIT,
                'price_type' => $priceType,
                'subtotal' => $subtotal,
                'taxable_amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'cgst_amount' => $cgstTotal,
                'sgst_amount' => $sgstTotal,
                'igst_amount' => $igstTotal,
                'freight_charge' => $freightCharge,
                'other_charges' => $otherCharges,
                'round_off' => $roundOff,
                'grand_total' => $grandTotal,
                'total_amount' => $grandTotal,
                'rate_override_reason' => $overrideReason,
                'notes' => $data['notes'] ?? null,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? $invoice->terms_and_conditions,
                'freight_allocation_method' => $data['freight_allocation_method'] ?? $invoice->freight_allocation_method,
            ] + $commercial['attributes']);

            // Clean up old items and history
            foreach ($invoice->items as $oldItem) {
                ProductSerial::where('source_type', $oldItem->getMorphClass())
                    ->where('source_id', $oldItem->id)
                    ->delete();
            }
            $invoice->items()->delete();
            VendorPriceHistory::where('reference_type', $invoice->getMorphClass())
                ->where('reference_id', $invoice->id)
                ->delete();

            foreach ($prepared as $item) {
                $piItem = PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'],
                    'quantity' => $item['quantity'],
                    'received_qty' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'purchase_price_inclusive' => $item['purchase_price_inclusive'] ?? null,
                    'purchase_discount_percent' => $item['purchase_discount_percent'] ?? null,
                    'price_type' => $priceType,
                    'entered_unit_cost' => $item['entered_unit_cost'] ?? $item['unit_cost'],
                    'unit_cost' => $item['unit_cost'],
                    'taxable_amount' => $item['taxable_amount'] ?? ($item['quantity'] * $item['unit_cost']),
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

                if (! empty($item['serials']) && is_array($item['serials'])) {
                    $prod = Product::find($item['product_id']);
                    if ($prod) {
                        $this->serialBatchService->assignSerials(
                            $prod,
                            $item['serials'],
                            $invoice->warehouse_id,
                            $piItem,
                            $actor,
                            'in_transit',
                        );
                    }
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

            // Sync SupplierPayable
            SupplierPayable::where('reference_type', $invoice->getMorphClass())
                ->where('reference_id', $invoice->id)
                ->delete();

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

            // Sync BankAccountTransaction (OD utilization)
            if ($odAccount) {
                BankAccountTransaction::updateOrCreate(
                    [
                        'reference_type' => $invoice->getMorphClass(),
                        'reference_id' => $invoice->id,
                    ],
                    [
                        'company_id' => $companyId,
                        'account_number' => $odAccount->account_number,
                        'od_account_id' => $odAccount->id,
                        'transaction_date' => $invoice->invoice_date,
                        'transaction_no' => 'OD-' . $invoice->invoice_no,
                        'description' => "OD utilization for Purchase Invoice {$invoice->invoice_no}",
                        'transaction_type' => 'debit',
                        'debit' => $invoice->grand_total,
                        'credit' => 0,
                        'created_by' => $actor->id,
                    ]
                );
            } else {
                BankAccountTransaction::where('reference_type', $invoice->getMorphClass())
                    ->where('reference_id', $invoice->id)
                    ->delete();
            }

            return $invoice->fresh(['items.product', 'items.uom', 'supplier', 'warehouse', 'odAccount']);
        });
    }

    public function deleteInvoice(PurchaseInvoice $invoice, User $actor): void
    {
        DB::transaction(function () use ($invoice, $actor) {
            $blockReason = $invoice->getDeleteBlockReason();
            if ($blockReason) {
                throw new \RuntimeException($blockReason);
            }

            // 1. Revert any stock movements linked to this invoice
            foreach ($invoice->inventoryMovements as $movement) {
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

            // 2. Clean up SupplierPayable
            SupplierPayable::where('reference_type', $invoice->getMorphClass())
                ->where('reference_id', $invoice->id)
                ->delete();

            // 2b. Clean up BankAccountTransaction (OD utilization)
            BankAccountTransaction::where('reference_type', $invoice->getMorphClass())
                ->where('reference_id', $invoice->id)
                ->delete();

            // 3. Clean up VendorPriceHistory
            VendorPriceHistory::where('reference_type', $invoice->getMorphClass())
                ->where('reference_id', $invoice->id)
                ->delete();

            // 4. Disassociate landed costs if any (draft/unposted)
            $invoice->landedCosts()->update(['purchase_invoice_id' => null]);

            // 5. Delete serials, items and invoice
            foreach ($invoice->items as $oldItem) {
                \App\Domains\Inventory\Models\ProductSerial::where('source_type', $oldItem->getMorphClass())
                    ->where('source_id', $oldItem->id)
                    ->delete();
            }
            $invoice->items()->delete();
            $invoice->delete();
        });
    }

    public function postInvoice(PurchaseInvoice $invoice): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice) {
            if ($invoice->status === 'posted') {
                return $invoice;
            }

            // Financial posting only — physical stock is added via receiveMaterial().
            $invoice->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            return $invoice;
        });
    }

    /**
     * Confirm physical material receipt (full or partial) and post stock for newly received qty only.
     *
     * @param  array{actual_material_receipt_date?:string, mark_all_received?:bool, items?:array<int, array{id:int|string, receive_qty?:float|int|string}>}  $data
     */
    public function receiveMaterial(PurchaseInvoice $invoice, array $data, User $actor): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice, $data, $actor) {
            if ($invoice->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'invoice' => 'Cancelled purchase invoices cannot receive material.',
                ]);
            }

            if (! $invoice->warehouse_id) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'A warehouse is required before material can be received into stock.',
                ]);
            }

            $invoice->load(['items.product.baseUom', 'items.uom']);
            $receiptDate = $data['actual_material_receipt_date']
                ?? $data['purchase_receive_date']
                ?? now()->toDateString();
            $markAll = (bool) ($data['mark_all_received'] ?? false);
            $incoming = collect($data['items'] ?? [])->keyBy(fn ($row) => (string) ($row['id'] ?? ''));
            $anyReceived = false;

            foreach ($invoice->items as $item) {
                $already = (float) $item->received_qty;
                $ordered = (float) $item->quantity;
                $remaining = max(0, $ordered - $already);

                if ($remaining <= 0) {
                    continue;
                }

                if ($markAll) {
                    $delta = $remaining;
                } else {
                    $row = $incoming->get((string) $item->id);
                    $delta = (float) ($row['receive_qty'] ?? 0);
                }

                if ($delta <= 0) {
                    continue;
                }

                if ($delta > $remaining + 0.0001) {
                    throw ValidationException::withMessages([
                        "items.{$item->id}.receive_qty" => "Receive qty for {$item->product?->name} cannot exceed remaining {$remaining}.",
                    ]);
                }

                $delta = min($delta, $remaining);
                $uom = $item->uom ?? $item->product?->baseUom;
                if (! $item->product || ! $uom) {
                    throw ValidationException::withMessages([
                        'items' => 'Product/UOM missing on an invoice line; cannot receive stock.',
                    ]);
                }

                $this->stockMovementService->recordIn(
                    $item->product,
                    $uom,
                    $delta,
                    'purchase_invoice',
                    $invoice,
                    "Material receipt for PI {$invoice->invoice_no}",
                    $actor,
                    $invoice->warehouse_id,
                    (float) $item->unit_cost,
                    null,
                    $item->batch_no,
                );

                if (! empty($item->batch_no)) {
                    $this->serialBatchService->upsertBatch(
                        $item->product,
                        $uom,
                        (string) $item->batch_no,
                        $delta,
                        (float) $item->unit_cost,
                        $invoice->warehouse_id,
                        $item->expiry_date?->toDateString(),
                        $item,
                        $item->batch_selling_price !== null ? (float) $item->batch_selling_price : null,
                        $item->batch_mrp !== null ? (float) $item->batch_mrp : null,
                    );
                }

                $activateCount = (int) max(1, (int) round($delta));
                ProductSerial::query()
                    ->where('source_type', $item->getMorphClass())
                    ->where('source_id', $item->id)
                    ->where('status', 'in_transit')
                    ->orderBy('id')
                    ->limit($activateCount)
                    ->update(['status' => 'in_stock']);

                $item->update(['received_qty' => $already + $delta]);
                $anyReceived = true;
            }

            if (! $anyReceived) {
                throw ValidationException::withMessages([
                    'items' => 'Enter at least one receive quantity greater than zero.',
                ]);
            }

            $invoice->refresh()->load('items');
            $totalOrdered = (float) $invoice->items->sum('quantity');
            $totalReceived = (float) $invoice->items->sum('received_qty');

            $status = PurchaseInvoice::MATERIAL_IN_TRANSIT;
            if ($totalReceived <= 0) {
                $status = PurchaseInvoice::MATERIAL_IN_TRANSIT;
            } elseif ($totalReceived + 0.0001 >= $totalOrdered) {
                $status = PurchaseInvoice::MATERIAL_RECEIVED;
            } else {
                $status = PurchaseInvoice::MATERIAL_PARTIALLY_RECEIVED;
            }

            $invoice->update([
                'material_receipt_status' => $status,
                'actual_material_receipt_date' => $receiptDate,
                'purchase_receive_date' => $invoice->purchase_receive_date ?: $receiptDate,
            ]);

            return $invoice->fresh(['items.product', 'items.uom', 'supplier', 'warehouse', 'purchaseOrder']);
        });
    }

    public function cancelInvoice(PurchaseInvoice $invoice): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice) {
            if ($invoice->status === 'cancelled') {
                return $invoice;
            }

            // Reverse stock movements
            foreach ($invoice->inventoryMovements as $movement) {
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

            // Reverse supplier payable
            $supplierPayable = SupplierPayable::where('reference_type', $invoice->getMorphClass())
                ->where('reference_id', $invoice->id)
                ->first();

            if ($supplierPayable) {
                $supplierPayable->delete();
            }

            // Reverse bank account transaction (OD utilization)
            BankAccountTransaction::where('reference_type', $invoice->getMorphClass())
                ->where('reference_id', $invoice->id)
                ->delete();

            $invoice->update([
                'status' => 'cancelled',
            ]);

            return $invoice;
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

            $uomId = $item['uom_id'] ?? null;
            if (! $uomId && ! empty($item['product_id'])) {
                $uomId = Product::query()->whereKey($item['product_id'])->value('base_uom_id');
            }

            $taxPercent = isset($item['tax_percent'])
                ? (float) $item['tax_percent']
                : ((float) ($item['cgst_percent'] ?? 0) + (float) ($item['sgst_percent'] ?? 0));
            $cgstPercent = isset($item['cgst_percent'])
                ? (float) $item['cgst_percent']
                : ($taxPercent / 2);
            $sgstPercent = isset($item['sgst_percent'])
                ? (float) $item['sgst_percent']
                : ($taxPercent / 2);

            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $mrp = null;
            if (isset($item['mrp']) && $item['mrp'] !== '') {
                $mrp = (float) $item['mrp'];
            } elseif (isset($item['batch_mrp']) && $item['batch_mrp'] !== '') {
                $mrp = (float) $item['batch_mrp'];
            }

            $purchasePriceInclusive = null;
            if (isset($item['purchase_price_inclusive']) && $item['purchase_price_inclusive'] !== '') {
                $purchasePriceInclusive = (float) $item['purchase_price_inclusive'];
            } elseif (isset($item['price_inclusive_tax']) && $item['price_inclusive_tax'] !== '') {
                $purchasePriceInclusive = (float) $item['price_inclusive_tax'];
            } elseif ($unitCost > 0) {
                $purchasePriceInclusive = round($unitCost * (1 + ($taxPercent / 100)), 4);
            }

            $purchaseDiscountPercent = null;
            if (isset($item['purchase_discount_percent']) && $item['purchase_discount_percent'] !== '') {
                $purchaseDiscountPercent = round((float) $item['purchase_discount_percent'], 2);
            } elseif ($mrp !== null && $mrp > 0 && $purchasePriceInclusive !== null) {
                $purchaseDiscountPercent = round((($mrp - $purchasePriceInclusive) / $mrp) * 100, 2);
            }

            $normalized[] = [
                'product_id' => (int) $item['product_id'],
                'uom_id' => (int) $uomId,
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'mrp' => $mrp,
                'purchase_price_inclusive' => $purchasePriceInclusive,
                'purchase_discount_percent' => $purchaseDiscountPercent,
                'tax_percent' => $taxPercent,
                'cgst_percent' => $cgstPercent,
                'sgst_percent' => $sgstPercent,
                'weight' => isset($item['weight']) ? (float) $item['weight'] : null,
                'batch_no' => $item['batch_no'] ?? $item['batch_number'] ?? $item['batch_name'] ?? null,
                'serial_no' => filled($item['serial_no'] ?? null) ? trim((string) $item['serial_no']) : null,
                'batch_selling_price' => isset($item['batch_selling_price']) && $item['batch_selling_price'] !== '' ? (float) $item['batch_selling_price'] : null,
                'batch_mrp' => $mrp,
                'serials' => ! empty($item['serials'])
                    ? (is_array($item['serials']) ? $item['serials'] : preg_split('/[\s,;]+/', (string) $item['serials'], -1, PREG_SPLIT_NO_EMPTY))
                    : (! empty($item['serial_numbers'])
                        ? (is_array($item['serial_numbers']) ? array_values(array_filter($item['serial_numbers'], fn($s) => trim((string)$s) !== '')) : preg_split('/[\s,;]+/', (string) $item['serial_numbers'], -1, PREG_SPLIT_NO_EMPTY))
                        : []),
            ];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages(['items' => 'At least one line item with quantity is required.']);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{freight_charge: float, other_charges: float, attributes: array<string, mixed>}
     */
    protected function invoiceCommercial(array $data, float $subtotal): array
    {
        if (! array_key_exists('is_freight_applicable', $data) && (float) ($data['freight_charge'] ?? 0) > 0) {
            $data['is_freight_applicable'] = true;
            $data['freight_mode'] = $data['freight_mode'] ?? 'amount';
            $data['freight_amount'] = $data['freight_amount'] ?? $data['freight_charge'];
        }

        $freight = $this->freightCharges($data, $subtotal);
        $blank = fn (string $key) => filled($data[$key] ?? null) ? trim((string) $data[$key]) : null;

        return [
            'freight_charge' => $freight['freight_total'],
            'other_charges' => max(0.0, (float) ($data['other_charges'] ?? 0)),
            'attributes' => [
                'is_freight_applicable' => $freight['is_freight_applicable'],
                'freight_mode' => $freight['freight_mode'],
                'freight_percent' => $freight['freight_percent'],
                'freight_amount' => $freight['freight_amount'],
                'freight_tax_rate' => $freight['freight_tax_rate'],
                'freight_tax_amount' => $freight['freight_tax_amount'],
                'freight_total' => $freight['freight_total'],
                'freight_notes' => $freight['freight_notes'],
                'receipt_note_nos' => $blank('receipt_note_nos'),
                'receipt_doc_no' => $blank('receipt_doc_no'),
                'dispatched_through' => $blank('dispatched_through'),
                'carrier_name' => $blank('carrier_name'),
                'lr_number' => $blank('lr_number'),
                'lr_date' => filled($data['lr_date'] ?? null) ? $data['lr_date'] : null,
                'vehicle_number' => $blank('vehicle_number'),
                'order_nos' => $blank('order_nos'),
                'payment_terms' => $blank('payment_terms'),
                'other_references' => $blank('other_references'),
                'terms_of_delivery' => $blank('terms_of_delivery'),
            ],
        ];
    }

    /**
     * Freight is either a fixed rupee amount or a percent of the taxable item total.
     * GST on freight is calculated on that rupee amount.
     *
     * @param  array<string, mixed>  $data
     * @return array{is_freight_applicable: bool, freight_mode: string, freight_percent: float|null, freight_amount: float, freight_tax_rate: float, freight_tax_amount: float, freight_total: float, freight_notes: string|null}
     */
    protected function freightCharges(array $data, float $subtotal): array
    {
        $applicable = ! empty($data['is_freight_applicable']);
        $mode = ($data['freight_mode'] ?? 'amount') === 'percent' ? 'percent' : 'amount';
        $percent = null;
        $amount = 0.0;
        $taxRate = 0.0;
        $taxAmount = 0.0;
        $notes = null;

        if ($applicable) {
            $taxRate = round((float) ($data['freight_tax_rate'] ?? 0), 2);
            if ($mode === 'percent') {
                $percent = round(min(100, max(0, (float) ($data['freight_percent'] ?? 0))), 4);
                $amount = round($subtotal * $percent / 100, 2);
            } else {
                $amount = round(max(0, (float) ($data['freight_amount'] ?? 0)), 2);
            }
            if ($amount > 0 && $taxRate > 0) {
                $taxAmount = round($amount * ($taxRate / 100), 2);
            }
            $notes = filled($data['freight_notes'] ?? null) ? trim((string) $data['freight_notes']) : null;
        } else {
            $mode = 'amount';
        }

        return [
            'is_freight_applicable' => $applicable,
            'freight_mode' => $mode,
            'freight_percent' => $percent,
            'freight_amount' => $amount,
            'freight_tax_rate' => $taxRate,
            'freight_tax_amount' => $taxAmount,
            'freight_total' => round($amount + $taxAmount, 2),
            'freight_notes' => $notes,
        ];
    }

    protected function resolvePurchaseOrderNumber(array $data, ?int $companyId): string
    {
        $series = $this->documentNumberService->resolveSeries('PO', $companyId);
        $poDate = ! empty($data['po_date']) ? \Illuminate\Support\Carbon::parse($data['po_date']) : now();

        if ($series && $series->isManual()) {
            $poNo = strtoupper(trim((string) ($data['po_no'] ?? '')));
            if ($poNo === '') {
                throw ValidationException::withMessages([
                    'po_no' => 'Voucher number is required when Purchase Order numbering is set to manual.',
                ]);
            }

            $exists = PurchaseOrder::query()
                ->where('po_no', $poNo)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'po_no' => 'This voucher number already exists.',
                ]);
            }

            return $poNo;
        }

        if ($series) {
            return $this->documentNumberService->nextFromSeries($series, $poDate, $companyId);
        }

        return $this->documentNumberService->next('PO', $poDate, 4, $companyId);
    }

    /**
     * Billing = supplier (auto). Delivery = our company delivery address (dropdown / create).
     *
     * @return array{billing_address_id: int|null, billing_address: string|null, company_delivery_address_id: int|null, shipping_address: string|null}
     */
    protected function resolvePurchaseOrderPartyAddresses(
        Customer $supplier,
        array $data,
        ?Warehouse $warehouse,
        ?int $companyId,
        ?string $existingShipping = null,
        ?string $existingBilling = null,
    ): array {
        $billingAddress = null;
        if (! empty($data['billing_address_id'])) {
            $billingAddress = \App\Domains\Master\Models\PartyAddress::where('id', $data['billing_address_id'])
                ->where('customer_id', $supplier->id)
                ->first();
            if (! $billingAddress) {
                throw ValidationException::withMessages([
                    'billing_address_id' => 'The selected billing address does not belong to the selected supplier.',
                ]);
            }
        }

        if (! $billingAddress) {
            $billingAddress = $supplier->defaultBillingAddress();
        }

        $billingSnapshot = ! empty($data['billing_address'])
            ? trim((string) $data['billing_address'])
            : ($billingAddress ? $billingAddress->formatSnapshot() : ($existingBilling ?: $supplier->address));

        $companyDelivery = null;
        if (! empty($data['company_delivery_address_id'])) {
            $query = CompanyDeliveryAddress::query()
                ->where('id', $data['company_delivery_address_id'])
                ->where('is_active', true);
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            $companyDelivery = $query->first();
            if (! $companyDelivery) {
                throw ValidationException::withMessages([
                    'company_delivery_address_id' => 'The selected delivery address was not found for this company.',
                ]);
            }
        }

        $defaultDeliveryAddress = $warehouse?->address
            ?: ($warehouse?->branch?->address
                ?: ($warehouse?->company?->address
                    ?: (Company::query()->find($companyId)?->address)));

        $rawShipping = ! empty($data['shipping_address']) ? trim((string) $data['shipping_address']) : null;
        if ($rawShipping && ($rawShipping === $billingSnapshot || $rawShipping === $supplier->address)) {
            $rawShipping = null;
        }

        $existingClean = $existingShipping;
        if ($existingClean && ($existingClean === $existingBilling || $existingClean === $supplier->address || $existingClean === $billingSnapshot)) {
            $existingClean = null;
        }

        $shippingSnapshot = $rawShipping
            ?: ($companyDelivery ? $companyDelivery->formatSnapshot() : ($existingClean ?: $defaultDeliveryAddress));

        return [
            'billing_address_id' => $billingAddress?->id,
            'billing_address' => $billingSnapshot,
            'company_delivery_address_id' => $companyDelivery?->id,
            'shipping_address' => $shippingSnapshot,
        ];
    }
}