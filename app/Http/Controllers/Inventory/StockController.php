<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = StockLevel::query()
            ->with(['product.category', 'product.brand', 'warehouse', 'uom'])
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->whereHas('product', fn ($pq) => $pq->where('category_id', $request->category_id));
            })
            ->when($request->filled('brand_id'), function ($q) use ($request) {
                $q->whereHas('product', fn ($pq) => $pq->where('brand_id', $request->brand_id));
            })
            ->when($request->filled('color_variant'), function ($q) use ($request) {
                $q->whereHas('product', fn ($pq) => $pq->where('color_variant', 'like', '%'.$request->color_variant.'%'));
            })
            ->when($request->boolean('low_stock'), function ($q) {
                $q->where(function ($sub) {
                    $sub->where('quantity', '<', 10)
                        ->orWhereHas('product', function ($pq) {
                            $pq->whereNotNull('reorder_level')
                               ->where('reorder_level', '>', 0)
                               ->whereColumn('stock_levels.quantity', '<=', 'products.reorder_level');
                        });
                });
            });

        $this->applySearch(
            $query,
            $request->input('search'),
            [],
            [
                'product' => ['name', 'sku', 'serial_no', 'color_variant'],
                'warehouse' => ['name'],
            ]
        );

        $allowedSorts = [
            'product' => function ($q, $dir) {
                $q->join('products', 'stock_levels.product_id', '=', 'products.id')
                  ->orderBy('products.name', $dir)
                  ->select('stock_levels.*');
            },
            'sku' => function ($q, $dir) {
                $q->join('products', 'stock_levels.product_id', '=', 'products.id')
                  ->orderBy('products.sku', $dir)
                  ->select('stock_levels.*');
            },
            'serial_no' => function ($q, $dir) {
                $q->join('products', 'stock_levels.product_id', '=', 'products.id')
                  ->orderBy('products.serial_no', $dir)
                  ->select('stock_levels.*');
            },
            'brand' => function ($q, $dir) {
                $q->join('products', 'stock_levels.product_id', '=', 'products.id')
                  ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                  ->orderBy('brands.name', $dir)
                  ->select('stock_levels.*');
            },
            'category' => function ($q, $dir) {
                $q->join('products', 'stock_levels.product_id', '=', 'products.id')
                  ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                  ->orderBy('categories.name', $dir)
                  ->select('stock_levels.*');
            },
            'warehouse' => function ($q, $dir) {
                $q->join('warehouses', 'stock_levels.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $dir)
                  ->select('stock_levels.*');
            },
            'quantity' => 'quantity',
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'product',
            defaultDirection: 'asc'
        );

        $stockLevels = $query->paginate(20)->withQueryString();

        $movements = StockMovement::query()
            ->with(['product', 'uom', 'warehouse'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->latest()
            ->limit(50)
            ->get();

        $colors = Product::query()
            ->whereNotNull('color_variant')
            ->where('color_variant', '!=', '')
            ->distinct()
            ->orderBy('color_variant')
            ->pluck('color_variant');

        $products = Product::query()
            ->where('is_active', true)
            ->when($request->filled('product_id'), function ($q) use ($request) {
                $q->orWhere('id', $request->input('product_id'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return view('inventory.stock.index', [
            'stockLevels' => $stockLevels,
            'movements' => $movements,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'products' => $products,
            'colors' => $colors,
            'filters' => $request->only(['warehouse_id', 'product_id', 'category_id', 'brand_id', 'color_variant', 'search', 'low_stock']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }
}