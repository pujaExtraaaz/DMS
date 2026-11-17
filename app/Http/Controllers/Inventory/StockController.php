<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Services\InTransitStockService;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected InTransitStockService $inTransitStockService
    ) {}

    public function index(Request $request): View
    {
        $query = $this->inTransitStockService->buildStockQuery($request);

        $allowedSorts = [
            'product' => function ($q, $dir) {
                $q->join('products', 'stock_levels.product_id', '=', 'products.id')
                  ->orderBy('products.name', $dir);
            },
            'sku' => function ($q, $dir) {
                $q->join('products', 'stock_levels.product_id', '=', 'products.id')
                  ->orderBy('products.sku', $dir);
            },
            'serial_no' => function ($q, $dir) {
                $q->join('products', 'stock_levels.product_id', '=', 'products.id')
                  ->orderBy('products.serial_no', $dir);
            },
            'brand' => function ($q, $dir) {
                $q->join('products', 'stock_levels.product_id', '=', 'products.id')
                  ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                  ->orderBy('brands.name', $dir);
            },
            'category' => function ($q, $dir) {
                $q->join('products', 'stock_levels.product_id', '=', 'products.id')
                  ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                  ->orderBy('categories.name', $dir);
            },
            'warehouse' => function ($q, $dir) {
                $q->join('warehouses', 'stock_levels.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $dir);
            },
            'quantity' => 'stock_levels.quantity',
            'available_stock' => 'stock_levels.quantity',
            'in_transit' => 'transit_agg.in_transit_qty',
            'total_expected' => DB::raw('(stock_levels.quantity + COALESCE(transit_agg.in_transit_qty, 0))'),
            'expected_delivery_date' => 'transit_agg.next_delivery_date',
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
            'filters' => $request->only([
                'warehouse_id',
                'product_id',
                'category_id',
                'brand_id',
                'color_variant',
                'search',
                'low_stock',
                'stock_status',
                'expected_delivery_date',
            ]),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    /**
     * AJAX endpoint to fetch incoming purchase invoices for a product and warehouse.
     */
    public function inTransitDetails(Request $request, int $productId, ?int $warehouseId = null): JsonResponse
    {
        $details = $this->inTransitStockService->getInTransitDetails($productId, $warehouseId);
        $product = Product::select('id', 'name', 'sku', 'serial_no')->find($productId);
        $warehouse = $warehouseId ? Warehouse::select('id', 'name')->find($warehouseId) : null;

        return response()->json([
            'success' => true,
            'product' => $product,
            'warehouse' => $warehouse,
            'invoices' => $details,
            'total_in_transit' => (float) $details->sum('in_transit_qty'),
        ]);
    }
}
