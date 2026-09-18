<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Inventory\Models\ProductBatch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AJAX endpoint that returns the available FIFO batches for a product so the
 * sales invoice / order line "Pick Batch" popover can populate itself and
 * pre-fill unit_price + max qty from the earliest batch.
 */
class BatchLookupController extends Controller
{
    /**
     * GET /inventory/batches/{product}
     *
     * Query params:
     *   - warehouse_id (optional)  — limit to a single warehouse
     *   - uom_id       (optional)  — limit to a single UOM
     *   - include_zero (optional)  — set truthy to include emptied batches
     */
    public function __invoke(Request $request, int $product): JsonResponse
    {
        $batches = ProductBatch::query()
            ->with(['warehouse:id,name', 'uom:id,code'])
            ->where('product_id', $product)
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('uom_id'), fn ($q) => $q->where('uom_id', $request->uom_id))
            ->when(! $request->boolean('include_zero'), fn ($q) => $q->where('quantity', '>', 0))
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->limit(50)
            ->get(['id', 'product_id', 'warehouse_id', 'uom_id', 'batch_no', 'mfg_date', 'expiry_date', 'quantity', 'unit_cost', 'selling_price', 'mrp']);

        return response()->json([
            'product_id' => $product,
            'batches' => $batches->map(fn (ProductBatch $b) => [
                'id' => $b->id,
                'batch_no' => $b->batch_no,
                'warehouse' => $b->warehouse?->name,
                'warehouse_id' => $b->warehouse_id,
                'uom' => $b->uom?->code,
                'uom_id' => $b->uom_id,
                'mfg_date' => optional($b->mfg_date)->format('Y-m-d'),
                'expiry_date' => optional($b->expiry_date)->format('Y-m-d'),
                'available_qty' => (float) $b->quantity,
                'unit_cost' => (float) $b->unit_cost,
                'selling_price' => $b->selling_price !== null ? (float) $b->selling_price : null,
                'mrp' => $b->mrp !== null ? (float) $b->mrp : null,
            ]),
        ]);
    }
}
