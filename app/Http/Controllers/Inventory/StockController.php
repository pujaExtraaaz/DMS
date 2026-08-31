<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Master\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $stockLevels = StockLevel::query()
            ->with(['product', 'uom'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->boolean('low_stock'), fn ($q) => $q->where('quantity', '<', 10))
            ->orderBy('product_id')
            ->paginate(20)
            ->withQueryString();

        $movements = StockMovement::query()
            ->with(['product', 'uom'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->latest()
            ->limit(50)
            ->get();

        return view('inventory.stock.index', [
            'stockLevels' => $stockLevels,
            'movements' => $movements,
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
