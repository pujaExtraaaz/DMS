<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockAdjustmentItem;
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

class StockAdjustmentController extends Controller
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected DocumentNumberService $documentNumberService,
    ) {}

    public function index(): View
    {
        $adjustments = StockAdjustment::query()
            ->with(['warehouse', 'creator'])
            ->latest('adjustment_date')
            ->paginate(15);

        return view('inventory.adjustments.index', compact('adjustments'));
    }

    public function create(): View
    {
        return view('inventory.adjustments.create', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'adjustment_date' => 'required|date',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'reason' => 'required|in:opening,damage,shrinkage,found,recount,other',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|not_in:0',
            'items.*.notes' => 'nullable|string',
        ]);

        try {
            $adjustment = DB::transaction(function () use ($validated, $request) {
                $adjustment = StockAdjustment::create([
                    'adjustment_no' => $this->documentNumberService->next('ADJ'),
                    'adjustment_date' => $validated['adjustment_date'],
                    'warehouse_id' => $validated['warehouse_id'] ?? null,
                    'reason' => $validated['reason'],
                    'status' => 'posted',
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                foreach ($validated['items'] as $item) {
                    StockAdjustmentItem::create([
                        'stock_adjustment_id' => $adjustment->id,
                        'product_id' => $item['product_id'],
                        'uom_id' => $item['uom_id'],
                        'quantity' => $item['quantity'],
                        'notes' => $item['notes'] ?? null,
                    ]);

                    $product = Product::findOrFail($item['product_id']);
                    $uom = Uom::findOrFail($item['uom_id']);
                    $qty = (float) $item['quantity'];
                    $type = $validated['reason'] === 'opening' ? 'opening' : 'adjustment';

                    if ($qty > 0) {
                        $this->stockMovementService->recordIn(
                            product: $product,
                            uom: $uom,
                            quantity: $qty,
                            type: $type,
                            reference: $adjustment,
                            notes: "Adjustment {$adjustment->adjustment_no}",
                            user: $request->user(),
                            warehouseId: $validated['warehouse_id'] ?? null,
                        );
                    } else {
                        $this->stockMovementService->recordOut(
                            product: $product,
                            uom: $uom,
                            quantity: abs($qty),
                            type: $type,
                            reference: $adjustment,
                            notes: "Adjustment {$adjustment->adjustment_no}",
                            user: $request->user(),
                            warehouseId: $validated['warehouse_id'] ?? null,
                        );
                    }
                }

                return $adjustment;
            });
        } catch (\Throwable $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Stock adjustment posted.', 'inventory.adjustments.show', ['adjustment' => $adjustment]);
    }

    public function show(StockAdjustment $adjustment): View
    {
        $adjustment->load(['items.product', 'items.uom', 'warehouse', 'creator']);

        return view('inventory.adjustments.show', compact('adjustment'));
    }
}
