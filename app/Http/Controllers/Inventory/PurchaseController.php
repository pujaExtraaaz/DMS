<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Inventory\Models\Purchase;
use App\Domains\Inventory\Models\PurchaseItem;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(
        protected StockMovementService $stockMovementService,
    ) {}

    public function index(Request $request): View
    {
        $purchases = Purchase::query()
            ->with(['creator', 'items.product', 'items.uom'])
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('purchase_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('purchase_date', '<=', $request->date_to))
            ->latest('purchase_date')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.purchases.index', compact('purchases'));
    }

    public function create(): View
    {
        return view('inventory.purchases.create', [
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_date' => 'required|date',
            'supplier_name' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.selling_price' => 'nullable|numeric|min:0',
        ]);

        $purchase = DB::transaction(function () use ($validated) {
            $grandTotal = collect($validated['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_cost']);

            $purchase = Purchase::create([
                'purchase_no' => $this->generatePurchaseNo(),
                'purchase_date' => $validated['purchase_date'],
                'supplier_name' => $validated['supplier_name'],
                'status' => 'posted',
                'grand_total' => $grandTotal,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $uom = Uom::findOrFail($item['uom_id']);

                if (isset($item['selling_price']) && (float) $item['selling_price'] > 0) {
                    $newSellingPrice = (float) $item['selling_price'];
                    $product->update(['selling_price' => $newSellingPrice]);

                    // Also update/create price master entries if applicable
                    \App\Domains\Master\Models\PriceMaster::where('product_id', $product->id)
                        ->where('uom_id', $uom->id)
                        ->update(['rate' => $newSellingPrice]);
                }

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $item['quantity'] * $item['unit_cost'],
                ]);

                $this->stockMovementService->recordIn(
                    product: $product,
                    uom: $uom,
                    quantity: (float) $item['quantity'],
                    type: 'purchase',
                    reference: $purchase,
                    notes: "Purchase {$purchase->purchase_no}",
                    user: auth()->user(),
                );
            }

            return $purchase;
        });

        return $this->flashSuccess('Purchase posted successfully.', 'inventory.purchases.show', ['purchase' => $purchase]);
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['items.product', 'items.uom', 'creator']);

        return view('inventory.purchases.show', compact('purchase'));
    }

    protected function generatePurchaseNo(): string
    {
        $date = now()->format('Ymd');
        $pattern = "PUR-{$date}-%";
        $last = Purchase::where('purchase_no', 'like', $pattern)->orderByDesc('purchase_no')->value('purchase_no');
        $sequence = $last ? (int) Str::afterLast($last, '-') + 1 : 1;

        return sprintf('PUR-%s-%04d', $date, $sequence);
    }
}
