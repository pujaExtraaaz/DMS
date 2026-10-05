<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $query = StockLevel::query()
            ->with(['product.category', 'product.brand', 'warehouse', 'batch'])
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->whereHas('product', fn ($pq) => $pq->where('category_id', $request->category_id));
            })
            ->when($request->filled('brand_id'), function ($q) use ($request) {
                $q->whereHas('product', fn ($pq) => $pq->where('brand_id', $request->brand_id));
            })
            ->when($request->boolean('low_stock'), function ($q) {
                $q->whereHas('product', function ($pq) {
                    $pq->whereColumn('stock_levels.quantity_on_hand', '<=', 'products.reorder_level');
                });
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('product', function ($pq) use ($request) {
                    $pq->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('sku', 'like', '%'.$request->search.'%');
                });
            });

        $stockLevels = $query->paginate(20)->withQueryString();

        return view('inventory.stock.index', [
            'stockLevels' => $stockLevels,
            'warehouses' => Warehouse::where('is_active', true)->get(),
            'categories' => Category::where('is_active', true)->get(),
            'brands' => Brand::where('is_active', true)->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']),
            'filters' => $request->only(['warehouse_id', 'product_id', 'category_id', 'brand_id', 'search', 'low_stock']),
        ]);
    }

    public function movements(Request $request): View
    {
        $query = InventoryMovement::query()
            ->with(['product', 'warehouse', 'creator', 'batch'])
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('type'), fn ($q) => $q->where('movement_type', $request->type))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('movement_date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('movement_date', '<=', $request->to_date))
            ->latest('movement_date')
            ->latest('id');

        return view('inventory.stock.movements', [
            'movements' => $query->paginate(25)->withQueryString(),
            'warehouses' => Warehouse::where('is_active', true)->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']),
            'filters' => $request->only(['warehouse_id', 'product_id', 'type', 'from_date', 'to_date']),
        ]);
    }
}