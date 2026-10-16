<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Models\StockReclassification;
use App\Domains\Inventory\Models\StockTransfer;
use App\Domains\Inventory\Models\StockTransferItem;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use App\Support\DocumentNumberService;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class StockTransferController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected StockMovementService $stockMovementService,
        protected DocumentNumberService $documentNumberService,
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'location');

        if ($tab === 'name') {
            $query = StockReclassification::query()
                ->with(['warehouse', 'fromProduct', 'toProduct', 'uom', 'creator'])
                ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
                ->when($request->filled('from_product_id'), fn ($q) => $q->where('from_product_id', $request->from_product_id))
                ->when($request->filled('to_product_id'), fn ($q) => $q->where('to_product_id', $request->to_product_id))
                ->when($request->filled('date_from'), fn ($q) => $q->whereDate('reclassification_date', '>=', $request->date_from))
                ->when($request->filled('date_to'), fn ($q) => $q->whereDate('reclassification_date', '<=', $request->date_to));

            $this->applySearch(
                $query,
                $request->input('search'),
                ['reclassification_no', 'notes'],
                [
                    'fromProduct' => ['name', 'sku'],
                    'toProduct' => ['name', 'sku'],
                    'warehouse' => ['name'],
                ]
            );

            $allowedSorts = [
                'reclassification_no' => 'reclassification_no',
                'reclassification_date' => 'reclassification_date',
                'quantity' => 'quantity',
                'created_at' => 'created_at',
            ];

            $sortData = $this->applySorting(
                $query,
                $request,
                $allowedSorts,
                defaultSort: 'reclassification_date',
                defaultDirection: 'desc'
            );

            $reclassifications = $query->paginate(15)->withQueryString();

            return view('inventory.transfers.index', [
                'tab' => 'name',
                'reclassifications' => $reclassifications,
                'transfers' => null,
                'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
                'search' => $request->string('search'),
                'warehouseId' => $request->input('warehouse_id'),
                'sort' => $sortData['sort'],
                'direction' => $sortData['direction'],
            ]);
        }

        // Location transfers (existing)
        $query = StockTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'creator'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from_warehouse_id'), fn ($q) => $q->where('from_warehouse_id', $request->from_warehouse_id))
            ->when($request->filled('to_warehouse_id'), fn ($q) => $q->where('to_warehouse_id', $request->to_warehouse_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('transfer_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('transfer_date', '<=', $request->date_to));

        $this->applySearch($query, $request->input('search'), ['transfer_no', 'notes']);

        $sortData = $this->applySorting(
            $query,
            $request,
            ['transfer_no', 'transfer_date', 'status', 'created_at'],
            defaultSort: 'transfer_date',
            defaultDirection: 'desc'
        );

        $transfers = $query->paginate(15)->withQueryString();

        return view('inventory.transfers.index', [
            'tab' => 'location',
            'transfers' => $transfers,
            'reclassifications' => null,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'fromWarehouseId' => $request->input('from_warehouse_id'),
            'toWarehouseId' => $request->input('to_warehouse_id'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(Request $request): View
    {
        $products = Product::where('is_active', true)
            ->with(['baseUom', 'brand'])
            ->orderBy('name')
            ->get();

        return view('inventory.transfers.create', [
            'tab' => $request->input('tab', 'location'),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => $products,
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function stockAvailability(Request $request): JsonResponse
    {
        $productId = $request->integer('product_id');
        $warehouseId = $request->input('warehouse_id') ? (int) $request->input('warehouse_id') : null;

        $product = Product::with(['baseUom'])->find($productId);
        if (! $product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $stockLevelsQuery = StockLevel::query()
            ->where('product_id', $productId)
            ->with(['warehouse', 'uom']);

        if ($warehouseId) {
            $stockLevelsQuery->where('warehouse_id', $warehouseId);
        }

        $stockLevels = $stockLevelsQuery->get();

        $selectedLevel = $warehouseId
            ? $stockLevels->firstWhere('warehouse_id', $warehouseId)
            : ($stockLevels->firstWhere('quantity', '>', 0) ?? $stockLevels->first());

        $allWarehousesWithStock = StockLevel::query()
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->with(['warehouse', 'uom'])
            ->get()
            ->map(fn ($sl) => [
                'warehouse_id' => $sl->warehouse_id,
                'warehouse_name' => $sl->warehouse?->name ?? 'Unknown',
                'quantity' => (float) $sl->quantity,
                'uom_id' => $sl->uom_id,
                'uom_code' => $sl->uom?->code ?? $sl->uom?->name ?? 'PCS',
            ]);

        return response()->json([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'uom_id' => $selectedLevel?->uom_id ?? $product->base_uom_id,
            'uom_code' => $selectedLevel?->uom?->code ?? $product->baseUom?->code ?? 'PCS',
            'warehouse_id' => $selectedLevel?->warehouse_id ?? $warehouseId,
            'warehouse_name' => $selectedLevel?->warehouse?->name,
            'available_quantity' => (float) ($selectedLevel?->quantity ?? 0),
            'warehouses_with_stock' => $allWarehousesWithStock,
        ]);
    }

    public function storeNameTransfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transfer_date' => 'required|date',
            'warehouse_id' => 'required|exists:warehouses,id',
            'from_product_id' => 'required|exists:products,id|different:to_product_id',
            'to_product_id' => 'required|exists:products,id',
            'uom_id' => 'required|exists:uoms,id',
            'quantity' => 'required|numeric|min:0.0001',
            'notes' => 'nullable|string|max:1000',
        ], [
            'from_product_id.different' => 'The destination product must be different from the current product.',
            'from_product_id.required' => 'The current product is required.',
            'to_product_id.required' => 'The new product is required.',
            'quantity.min' => 'The quantity to transfer must be greater than 0.',
        ]);

        if ($validated['from_product_id'] == $validated['to_product_id']) {
            throw ValidationException::withMessages([
                'to_product_id' => 'The destination product cannot be the same as the current product.',
            ]);
        }

        try {
            $reclassification = DB::transaction(function () use ($validated, $request) {
                // Lock the source stock level row for concurrency safety
                $sourceStockLevel = StockLevel::query()
                    ->where('warehouse_id', $validated['warehouse_id'])
                    ->where('product_id', $validated['from_product_id'])
                    ->where('uom_id', $validated['uom_id'])
                    ->lockForUpdate()
                    ->first();

                $available = (float) ($sourceStockLevel?->quantity ?? 0);
                $required = (float) $validated['quantity'];

                if ($available < $required) {
                    throw ValidationException::withMessages([
                        'quantity' => "Insufficient stock for reclassification. Available: {$available}, Requested: {$required}.",
                    ]);
                }

                $reclassificationNo = $this->documentNumberService->next('SNT');

                $reclassification = StockReclassification::create([
                    'reclassification_no' => $reclassificationNo,
                    'reclassification_date' => $validated['transfer_date'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'from_product_id' => $validated['from_product_id'],
                    'to_product_id' => $validated['to_product_id'],
                    'uom_id' => $validated['uom_id'],
                    'quantity' => $validated['quantity'],
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                $fromProduct = Product::findOrFail($validated['from_product_id']);
                $toProduct = Product::findOrFail($validated['to_product_id']);
                $uom = Uom::findOrFail($validated['uom_id']);
                $reasonText = !empty($validated['notes']) ? " ({$validated['notes']})" : '';

                // Deduct source product stock
                $this->stockMovementService->recordOut(
                    product: $fromProduct,
                    uom: $uom,
                    quantity: $required,
                    type: 'reclassification',
                    reference: $reclassification,
                    notes: "Stock reclassified to {$toProduct->name}{$reasonText}",
                    user: $request->user(),
                    warehouseId: (int) $validated['warehouse_id'],
                );

                // Add destination product stock
                $this->stockMovementService->recordIn(
                    product: $toProduct,
                    uom: $uom,
                    quantity: $required,
                    type: 'reclassification',
                    reference: $reclassification,
                    notes: "Stock reclassified from {$fromProduct->name}{$reasonText}",
                    user: $request->user(),
                    warehouseId: (int) $validated['warehouse_id'],
                    unitCost: (float) ($fromProduct->purchase_price ?? 0),
                );

                return $reclassification;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess(
            'Stock reclassification completed successfully.',
            'inventory.transfers.reclassifications.show',
            ['reclassification' => $reclassification]
        );
    }

    public function showReclassification(StockReclassification $reclassification): View
    {
        $reclassification->load([
            'warehouse',
            'fromProduct',
            'toProduct',
            'uom',
            'creator',
            'movements.product',
            'movements.uom',
        ]);

        return view('inventory.transfers.show-reclassification', compact('reclassification'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transfer_date' => 'required|date',
            'from_warehouse_id' => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'notes' => 'nullable|string',
            'dispatch_now' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        try {
            $transfer = DB::transaction(function () use ($validated, $request) {
                $transfer = StockTransfer::create([
                    'transfer_no' => $this->documentNumberService->next('TRF'),
                    'transfer_date' => $validated['transfer_date'],
                    'from_warehouse_id' => $validated['from_warehouse_id'],
                    'to_warehouse_id' => $validated['to_warehouse_id'],
                    'status' => $request->boolean('dispatch_now') ? 'in_transit' : 'draft',
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                foreach ($validated['items'] as $item) {
                    StockTransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'product_id' => $item['product_id'],
                        'uom_id' => $item['uom_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }

                if ($request->boolean('dispatch_now')) {
                    $this->dispatchStock($transfer, $request->user());
                }

                return $transfer;
            });
        } catch (\Throwable $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Stock transfer created.', 'inventory.transfers.show', ['transfer' => $transfer]);
    }

    public function show(StockTransfer $transfer): View
    {
        $transfer->load(['items.product', 'items.uom', 'fromWarehouse', 'toWarehouse', 'creator']);

        return view('inventory.transfers.show', compact('transfer'));
    }

    public function dispatch(StockTransfer $transfer): RedirectResponse
    {
        if ($transfer->status !== 'draft') {
            return $this->flashError('Only draft transfers can be dispatched.');
        }

        try {
            DB::transaction(function () use ($transfer) {
                $this->dispatchStock($transfer->load('items'), request()->user());
                $transfer->update(['status' => 'in_transit']);
            });
        } catch (\Throwable $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Transfer dispatched.');
    }

    public function receive(StockTransfer $transfer): RedirectResponse
    {
        if ($transfer->status !== 'in_transit') {
            return $this->flashError('Only in-transit transfers can be received.');
        }

        try {
            DB::transaction(function () use ($transfer) {
                $transfer->load('items');
                foreach ($transfer->items as $item) {
                    $product = Product::findOrFail($item->product_id);
                    $uom = Uom::findOrFail($item->uom_id);
                    $this->stockMovementService->recordIn(
                        product: $product,
                        uom: $uom,
                        quantity: (float) $item->quantity,
                        type: 'transfer_in',
                        reference: $transfer,
                        notes: "Transfer {$transfer->transfer_no} in",
                        user: request()->user(),
                        warehouseId: $transfer->to_warehouse_id,
                    );
                }
                $transfer->update([
                    'status' => 'received',
                    'received_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Transfer received into destination warehouse.');
    }

    public function edit(StockTransfer $transfer): View|RedirectResponse
    {
        if (in_array($transfer->status, ['received', 'completed', 'cancelled'])) {
            return redirect()->route('inventory.transfers.show', $transfer)
                ->with('error', "Cannot edit stock transfer '{$transfer->transfer_no}' because its status is '{$transfer->status}'. Only draft or in-transit transfers can be edited.");
        }

        $transfer->load(['items.product', 'items.uom', 'fromWarehouse', 'toWarehouse']);

        $products = Product::where('is_active', true)
            ->with(['baseUom', 'brand'])
            ->orderBy('name')
            ->get();

        return view('inventory.transfers.edit', [
            'transfer' => $transfer,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => $products,
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, StockTransfer $transfer): RedirectResponse
    {
        $user = $request->user();
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('inventory.manage') &&
            ! $user->can('inventory.edit') &&
            ! $user->can('stock.edit')) {
            return back()->with('error', 'Unauthorized: You do not have permission to edit stock transfers.');
        }

        if (in_array($transfer->status, ['received', 'completed', 'cancelled'])) {
            return redirect()->route('inventory.transfers.show', $transfer)
                ->with('error', "Cannot update stock transfer '{$transfer->transfer_no}' because its status is '{$transfer->status}'. Finalized transfers cannot be edited.");
        }

        $validated = $request->validate([
            'transfer_date' => 'required|date',
            'from_warehouse_id' => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'notes' => 'nullable|string',
            'dispatch_now' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        try {
            DB::transaction(function () use ($validated, $request, $transfer) {
                $transfer->loadMissing('items');

                // If previously in_transit, reverse prior dispatch movements
                if ($transfer->status === 'in_transit') {
                    foreach ($transfer->items as $item) {
                        $product = Product::findOrFail($item->product_id);
                        $uom = Uom::findOrFail($item->uom_id);
                        $this->stockMovementService->recordIn(
                            product: $product,
                            uom: $uom,
                            quantity: (float) $item->quantity,
                            type: 'transfer_in',
                            reference: $transfer,
                            notes: "Reversal of dispatch for edit on transfer {$transfer->transfer_no}",
                            user: $request->user(),
                            warehouseId: $transfer->from_warehouse_id,
                        );
                    }
                }

                $newStatus = $transfer->status;
                if ($transfer->status === 'draft' && $request->boolean('dispatch_now')) {
                    $newStatus = 'in_transit';
                }

                $transfer->update([
                    'transfer_date' => $validated['transfer_date'],
                    'from_warehouse_id' => $validated['from_warehouse_id'],
                    'to_warehouse_id' => $validated['to_warehouse_id'],
                    'status' => $newStatus,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Replace line items
                $transfer->items()->delete();
                foreach ($validated['items'] as $item) {
                    StockTransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'product_id' => $item['product_id'],
                        'uom_id' => $item['uom_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }

                // If in_transit, dispatch new items from the (possibly updated) source warehouse
                if ($newStatus === 'in_transit') {
                    $this->dispatchStock($transfer, $request->user());
                }
            });

            return $this->flashSuccess('Stock transfer updated successfully.', 'inventory.transfers.show', ['transfer' => $transfer]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to update transfer: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, StockTransfer $transfer): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('inventory.manage') &&
            ! $user->can('inventory.edit') &&
            ! $user->can('stock.edit')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to delete stock transfers.',
                ], 403);
            }
            return back()->with('error', 'Unauthorized: You do not have permission to delete stock transfers.');
        }

        $transferNo = $transfer->transfer_no;

        if ($transfer->status === 'received' || $transfer->status === 'completed') {
            $message = "Cannot delete stock transfer '{$transferNo}' because it has already been received at the destination warehouse. Received transfers cannot be deleted to protect inventory integrity.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }
            return back()->with('error', $message);
        }

        try {
            DB::transaction(function () use ($transfer, $request) {
                $transfer->loadMissing('items');

                if ($transfer->status === 'in_transit') {
                    foreach ($transfer->items as $item) {
                        $product = Product::findOrFail($item->product_id);
                        $uom = Uom::findOrFail($item->uom_id);
                        $this->stockMovementService->recordIn(
                            product: $product,
                            uom: $uom,
                            quantity: (float) $item->quantity,
                            type: 'transfer_in',
                            reference: $transfer,
                            notes: "Stock restored from deleted in-transit transfer {$transfer->transfer_no}",
                            user: $request->user(),
                            warehouseId: $transfer->from_warehouse_id,
                        );
                    }
                }

                $transfer->items()->delete();
                StockMovement::where('reference_type', $transfer->getMorphClass())
                    ->where('reference_id', $transfer->id)
                    ->delete();

                DB::table('stock_cost_consumptions')
                    ->where('reference_type', $transfer->getMorphClass())
                    ->where('reference_id', $transfer->id)
                    ->delete();
                DB::table('stock_cost_layers')
                    ->where('source_type', $transfer->getMorphClass())
                    ->where('source_id', $transfer->id)
                    ->delete();

                $transfer->delete();
            });

            $message = "Stock transfer '{$transferNo}' deleted successfully.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return $this->flashSuccess($message, 'inventory.transfers.index', ['tab' => 'location']);
        } catch (\Throwable $e) {
            report($e);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete transfer: ' . $e->getMessage(),
                ], 500);
            }
            return $this->flashError('Failed to delete transfer: ' . $e->getMessage(), 'inventory.transfers.index', ['tab' => 'location']);
        }
    }

    public function editReclassification(StockReclassification $reclassification): View
    {
        $reclassification->load(['warehouse', 'fromProduct.baseUom', 'toProduct.baseUom', 'uom']);

        $products = Product::where('is_active', true)
            ->with(['baseUom', 'brand'])
            ->orderBy('name')
            ->get();

        return view('inventory.transfers.edit-reclassification', [
            'reclassification' => $reclassification,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => $products,
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function updateReclassification(Request $request, StockReclassification $reclassification): RedirectResponse
    {
        $user = $request->user();
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('inventory.manage') &&
            ! $user->can('inventory.edit') &&
            ! $user->can('stock.edit')) {
            return back()->with('error', 'Unauthorized: You do not have permission to edit stock reclassifications.');
        }

        $validated = $request->validate([
            'transfer_date' => 'required|date',
            'warehouse_id' => 'required|exists:warehouses,id',
            'from_product_id' => 'required|exists:products,id|different:to_product_id',
            'to_product_id' => 'required|exists:products,id',
            'uom_id' => 'required|exists:uoms,id',
            'quantity' => 'required|numeric|min:0.0001',
            'notes' => 'nullable|string|max:1000',
        ], [
            'from_product_id.different' => 'The destination product must be different from the current product.',
            'quantity.min' => 'The quantity to transfer must be greater than 0.',
        ]);

        try {
            DB::transaction(function () use ($validated, $request, $reclassification) {
                $oldFromProduct = Product::findOrFail($reclassification->from_product_id);
                $oldToProduct = Product::findOrFail($reclassification->to_product_id);
                $oldUom = Uom::findOrFail($reclassification->uom_id);
                $oldQuantity = (float) $reclassification->quantity;
                $oldWarehouseId = (int) $reclassification->warehouse_id;

                // 1. Check if destination product has sufficient stock to reverse the previous reclassification
                $destStockLevel = StockLevel::query()
                    ->where('warehouse_id', $oldWarehouseId)
                    ->where('product_id', $oldToProduct->id)
                    ->where('uom_id', $oldUom->id)
                    ->lockForUpdate()
                    ->first();

                $destAvailable = (float) ($destStockLevel?->quantity ?? 0);
                if ($destAvailable < $oldQuantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "Cannot reverse prior reclassification: Destination product {$oldToProduct->name} has only {$destAvailable} available in warehouse #{$oldWarehouseId}, but {$oldQuantity} is needed for reversal.",
                    ]);
                }

                // 2. Reverse prior movements
                $this->stockMovementService->recordOut(
                    product: $oldToProduct,
                    uom: $oldUom,
                    quantity: $oldQuantity,
                    type: 'reclassification',
                    reference: $reclassification,
                    notes: "Reversal of reclassification {$reclassification->reclassification_no} (deduct new item)",
                    user: $request->user(),
                    warehouseId: $oldWarehouseId,
                );

                $this->stockMovementService->recordIn(
                    product: $oldFromProduct,
                    uom: $oldUom,
                    quantity: $oldQuantity,
                    type: 'reclassification',
                    reference: $reclassification,
                    notes: "Reversal of reclassification {$reclassification->reclassification_no} (restore original item)",
                    user: $request->user(),
                    warehouseId: $oldWarehouseId,
                    unitCost: (float) ($oldFromProduct->purchase_price ?? 0),
                );

                // 3. Check availability for the new reclassification
                $newWarehouseId = (int) $validated['warehouse_id'];
                $newFromProduct = Product::findOrFail($validated['from_product_id']);
                $newToProduct = Product::findOrFail($validated['to_product_id']);
                $newUom = Uom::findOrFail($validated['uom_id']);
                $newQuantity = (float) $validated['quantity'];

                $sourceStockLevel = StockLevel::query()
                    ->where('warehouse_id', $newWarehouseId)
                    ->where('product_id', $newFromProduct->id)
                    ->where('uom_id', $newUom->id)
                    ->lockForUpdate()
                    ->first();

                $available = (float) ($sourceStockLevel?->quantity ?? 0);
                if ($available < $newQuantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "Insufficient stock for reclassification. Available: {$available}, Requested: {$newQuantity}.",
                    ]);
                }

                // 4. Update the reclassification model
                $reclassification->update([
                    'reclassification_date' => $validated['transfer_date'],
                    'warehouse_id' => $newWarehouseId,
                    'from_product_id' => $newFromProduct->id,
                    'to_product_id' => $newToProduct->id,
                    'uom_id' => $newUom->id,
                    'quantity' => $newQuantity,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $reasonText = !empty($validated['notes']) ? " ({$validated['notes']})" : '';

                // Deduct new source product stock
                $this->stockMovementService->recordOut(
                    product: $newFromProduct,
                    uom: $newUom,
                    quantity: $newQuantity,
                    type: 'reclassification',
                    reference: $reclassification,
                    notes: "Stock reclassified to {$newToProduct->name}{$reasonText}",
                    user: $request->user(),
                    warehouseId: $newWarehouseId,
                );

                // Add new destination product stock
                $this->stockMovementService->recordIn(
                    product: $newToProduct,
                    uom: $newUom,
                    quantity: $newQuantity,
                    type: 'reclassification',
                    reference: $reclassification,
                    notes: "Stock reclassified from {$newFromProduct->name}{$reasonText}",
                    user: $request->user(),
                    warehouseId: $newWarehouseId,
                    unitCost: (float) ($newFromProduct->purchase_price ?? 0),
                );
            });

            return $this->flashSuccess(
                'Stock reclassification updated successfully.',
                'inventory.transfers.reclassifications.show',
                ['reclassification' => $reclassification]
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to update reclassification: ' . $e->getMessage());
        }
    }

    public function destroyReclassification(Request $request, StockReclassification $reclassification): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('inventory.manage') &&
            ! $user->can('inventory.edit') &&
            ! $user->can('stock.edit')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to delete stock reclassifications.',
                ], 403);
            }
            return back()->with('error', 'Unauthorized: You do not have permission to delete stock reclassifications.');
        }

        $recNo = $reclassification->reclassification_no;

        try {
            DB::transaction(function () use ($reclassification, $request) {
                $fromProduct = Product::findOrFail($reclassification->from_product_id);
                $toProduct = Product::findOrFail($reclassification->to_product_id);
                $uom = Uom::findOrFail($reclassification->uom_id);
                $quantity = (float) $reclassification->quantity;
                $warehouseId = (int) $reclassification->warehouse_id;

                // Check if destination product has sufficient stock to reverse
                $destStockLevel = StockLevel::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $toProduct->id)
                    ->where('uom_id', $uom->id)
                    ->lockForUpdate()
                    ->first();

                $destAvailable = (float) ($destStockLevel?->quantity ?? 0);
                if ($destAvailable < $quantity) {
                    throw new \RuntimeException(
                        "Cannot delete reclassification '{$reclassification->reclassification_no}' because the destination product '{$toProduct->name}' has already been consumed or transferred. Available: {$destAvailable}, needed for reversal: {$quantity}."
                    );
                }

                // Deduct from destination product
                $this->stockMovementService->recordOut(
                    product: $toProduct,
                    uom: $uom,
                    quantity: $quantity,
                    type: 'reclassification',
                    reference: $reclassification,
                    notes: "Reversal for deleted reclassification {$reclassification->reclassification_no}",
                    user: $request->user(),
                    warehouseId: $warehouseId,
                );

                // Restore to source product
                $this->stockMovementService->recordIn(
                    product: $fromProduct,
                    uom: $uom,
                    quantity: $quantity,
                    type: 'reclassification',
                    reference: $reclassification,
                    notes: "Reversal for deleted reclassification {$reclassification->reclassification_no}",
                    user: $request->user(),
                    warehouseId: $warehouseId,
                    unitCost: (float) ($fromProduct->purchase_price ?? 0),
                );

                StockMovement::where('reference_type', $reclassification->getMorphClass())
                    ->where('reference_id', $reclassification->id)
                    ->delete();

                DB::table('stock_cost_consumptions')
                    ->where('reference_type', $reclassification->getMorphClass())
                    ->where('reference_id', $reclassification->id)
                    ->delete();
                DB::table('stock_cost_layers')
                    ->where('source_type', $reclassification->getMorphClass())
                    ->where('source_id', $reclassification->id)
                    ->delete();

                $reclassification->delete();
            });

            $message = "Stock reclassification '{$recNo}' deleted and stock movements safely reversed.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return $this->flashSuccess($message, 'inventory.transfers.index', ['tab' => 'name']);
        } catch (\Throwable $e) {
            report($e);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    protected function dispatchStock(StockTransfer $transfer, $user): void
    {
        $transfer->loadMissing('items');
        foreach ($transfer->items as $item) {
            $product = Product::findOrFail($item->product_id);
            $uom = Uom::findOrFail($item->uom_id);
            $this->stockMovementService->recordOut(
                product: $product,
                uom: $uom,
                quantity: (float) $item->quantity,
                type: 'transfer_out',
                reference: $transfer,
                notes: "Transfer {$transfer->transfer_no} out",
                user: $user,
                warehouseId: $transfer->from_warehouse_id,
            );
        }
    }
}
