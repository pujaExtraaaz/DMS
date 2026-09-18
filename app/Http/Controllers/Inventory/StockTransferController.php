<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Inventory\Models\StockTransfer;
use App\Domains\Inventory\Models\StockTransferItem;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use App\Support\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected DocumentNumberService $documentNumberService,
    ) {}

    public function index(): View
    {
        $transfers = StockTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'creator'])
            ->latest('transfer_date')
            ->paginate(15);

        return view('inventory.transfers.index', compact('transfers'));
    }

    public function create(): View
    {
        return view('inventory.transfers.create', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
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
