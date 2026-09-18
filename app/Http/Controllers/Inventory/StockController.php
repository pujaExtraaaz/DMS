<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $productFilter = function ($q) use ($request) {
            $q->when($request->filled('brand_id'), fn ($q) => $q->where('brand_id', $request->brand_id))
                ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
                ->when($request->filled('color_variant'), fn ($q) => $q->where('color_variant', 'like', '%'.$request->color_variant.'%'));
        };

        $sort = $request->input('sort', 'product');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc'
            ? 'desc'
            : 'asc';

        $allowedSorts = [
            'product',
            'serial_no',
            'quantity',
        ];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'product';
        }

        $stockLevels = StockLevel::query()
            ->with(['product.brand', 'product.category', 'uom', 'warehouse'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->boolean('low_stock'), fn ($q) => $q->where('quantity', '<', 10))
            ->when($request->hasAny(['brand_id', 'category_id', 'color_variant']),
                fn ($q) => $q->whereHas('product', $productFilter))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->input('search'));

                $q->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%')
                        ->orWhere('serial_no', 'like', '%'.$search.'%');
                });
            })
            ->when($sort === 'product', fn ($q) => $q->join('products', 'stock_levels.product_id', '=', 'products.id')
                ->orderBy('products.name', $direction)
                ->select('stock_levels.*'))
            ->when($sort === 'serial_no', fn ($q) => $q->join('products', 'stock_levels.product_id', '=', 'products.id')
                ->orderBy('products.serial_no', $direction)
                ->select('stock_levels.*'))
            ->when($sort === 'quantity', fn ($q) => $q->orderBy('quantity', $direction))
            ->paginate(20)
            ->withQueryString();

        $movements = StockMovement::query()
            ->with(['product', 'uom', 'warehouse'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->hasAny(['brand_id', 'category_id', 'color_variant']),
                fn ($q) => $q->whereHas('product', $productFilter))
            ->latest()
            ->limit(50)
            ->get();

        $colors = Product::query()
            ->whereNotNull('color_variant')
            ->where('color_variant', '!=', '')
            ->distinct()
            ->orderBy('color_variant')
            ->pluck('color_variant');

        return view('inventory.stock.index', [
            'stockLevels' => $stockLevels,
            'movements' => $movements,
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'colors' => $colors,
        ]);
    }
}
