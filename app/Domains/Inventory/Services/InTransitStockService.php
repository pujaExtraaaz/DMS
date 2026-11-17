<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Master\Models\Product;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InTransitStockService
{
    /**
     * Ensure that all product + warehouse combinations referenced in pending in-transit
     * purchase invoices have a stock_levels record (with quantity = 0 if none exists).
     */
    public function ensureStockLevelsExistForPendingInvoices(): void
    {
        $pendingItems = DB::table('purchase_invoice_items')
            ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->where('purchase_invoices.status', '!=', 'cancelled')
            ->where('purchase_invoices.material_receipt_status', '!=', PurchaseInvoice::MATERIAL_RECEIVED)
            ->whereNull('purchase_invoice_items.receipt_note_item_id')
            ->whereRaw('purchase_invoice_items.quantity > COALESCE(purchase_invoice_items.received_qty, 0)')
            ->whereNotNull('purchase_invoices.warehouse_id')
            ->select('purchase_invoice_items.product_id', 'purchase_invoices.warehouse_id', 'purchase_invoice_items.uom_id')
            ->distinct()
            ->get();

        foreach ($pendingItems as $row) {
            $exists = DB::table('stock_levels')
                ->where('product_id', $row->product_id)
                ->where('warehouse_id', $row->warehouse_id)
                ->exists();

            if (! $exists) {
                // If uom_id is not set, resolve base UOM from product
                $uomId = $row->uom_id;
                if (! $uomId) {
                    $uomId = DB::table('products')->where('id', $row->product_id)->value('base_uom_id');
                }

                if ($uomId) {
                    DB::table('stock_levels')->insertOrIgnore([
                        'product_id' => $row->product_id,
                        'warehouse_id' => $row->warehouse_id,
                        'uom_id' => $uomId,
                        'quantity' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Subquery to calculate in-transit quantity, next delivery date, and count of invoices
     * grouped by product_id and warehouse_id.
     */
    public function getInTransitSubquery()
    {
        return DB::table('purchase_invoice_items')
            ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->where('purchase_invoices.status', '!=', 'cancelled')
            ->where('purchase_invoices.material_receipt_status', '!=', PurchaseInvoice::MATERIAL_RECEIVED)
            ->whereNull('purchase_invoice_items.receipt_note_item_id')
            ->whereRaw('purchase_invoice_items.quantity > COALESCE(purchase_invoice_items.received_qty, 0)')
            ->select([
                'purchase_invoice_items.product_id',
                'purchase_invoices.warehouse_id',
                DB::raw('SUM(purchase_invoice_items.quantity - COALESCE(purchase_invoice_items.received_qty, 0)) as in_transit_qty'),
                DB::raw('MIN(CASE WHEN purchase_invoices.expected_delivery_date IS NOT NULL THEN purchase_invoices.expected_delivery_date ELSE NULL END) as next_delivery_date'),
                DB::raw('COUNT(DISTINCT purchase_invoices.id) as in_transit_invoices_count'),
            ])
            ->groupBy('purchase_invoice_items.product_id', 'purchase_invoices.warehouse_id');
    }

    /**
     * Build the query for Stock Register and Current Stock screens.
     */
    public function buildStockQuery(Request $request): Builder
    {
        $this->ensureStockLevelsExistForPendingInvoices();

        $inTransitSub = $this->getInTransitSubquery();

        $query = StockLevel::query()
            ->with(['product.category', 'product.brand', 'warehouse', 'uom'])
            ->leftJoinSub($inTransitSub, 'transit_agg', function ($join) {
                $join->on('stock_levels.product_id', '=', 'transit_agg.product_id')
                     ->on('stock_levels.warehouse_id', '=', 'transit_agg.warehouse_id');
            })
            ->select([
                'stock_levels.*',
                DB::raw('COALESCE(transit_agg.in_transit_qty, 0) as in_transit_quantity'),
                DB::raw('transit_agg.next_delivery_date as expected_delivery_date'),
                DB::raw('(stock_levels.quantity + COALESCE(transit_agg.in_transit_qty, 0)) as total_expected_quantity'),
                DB::raw('COALESCE(transit_agg.in_transit_invoices_count, 0) as incoming_invoices_count'),
            ]);

        // Filter: Warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('stock_levels.warehouse_id', $request->input('warehouse_id'));
        }

        // Filter: Product
        if ($request->filled('product_id')) {
            $query->where('stock_levels.product_id', $request->input('product_id'));
        }

        // Filter: Category
        if ($request->filled('category_id')) {
            $query->whereHas('product', fn ($pq) => $pq->where('category_id', $request->input('category_id')));
        }

        // Filter: Brand
        if ($request->filled('brand_id')) {
            $query->whereHas('product', fn ($pq) => $pq->where('brand_id', $request->input('brand_id')));
        }

        // Filter: Color / Variant
        if ($request->filled('color_variant')) {
            $query->whereHas('product', fn ($pq) => $pq->where('color_variant', 'like', '%'.$request->input('color_variant').'%'));
        }

        // Filter: Expected Delivery Date
        if ($request->filled('expected_delivery_date')) {
            $query->whereDate('transit_agg.next_delivery_date', $request->input('expected_delivery_date'));
        }
        if ($request->filled('delivery_date_from')) {
            $query->whereDate('transit_agg.next_delivery_date', '>=', $request->input('delivery_date_from'));
        }
        if ($request->filled('delivery_date_to')) {
            $query->whereDate('transit_agg.next_delivery_date', '<=', $request->input('delivery_date_to'));
        }

        // Filter: Stock Status
        $stockStatus = $request->input('stock_status');
        if ($stockStatus === 'available' || $stockStatus === 'physical') {
            $query->where('stock_levels.quantity', '>', 0);
        } elseif ($stockStatus === 'in_transit') {
            $query->whereRaw('COALESCE(transit_agg.in_transit_qty, 0) > 0');
        } elseif ($stockStatus === 'both') {
            $query->where('stock_levels.quantity', '>', 0)
                  ->whereRaw('COALESCE(transit_agg.in_transit_qty, 0) > 0');
        } elseif ($stockStatus === 'low_stock' || $request->boolean('low_stock')) {
            $query->where(function ($sub) {
                $sub->where('stock_levels.quantity', '<', 10)
                    ->orWhereHas('product', function ($pq) {
                        $pq->whereNotNull('reorder_level')
                           ->where('reorder_level', '>', 0)
                           ->whereColumn('stock_levels.quantity', '<=', 'products.reorder_level');
                    });
            });
        }

        // Search
        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $query->where(function ($sq) use ($search) {
                $sq->whereHas('product', function ($pq) use ($search) {
                    $pq->where('name', 'like', "%{$search}%")
                       ->orWhere('sku', 'like', "%{$search}%")
                       ->orWhere('serial_no', 'like', "%{$search}%")
                       ->orWhere('color_variant', 'like', "%{$search}%");
                })->orWhereHas('warehouse', function ($wq) use ($search) {
                    $wq->where('name', 'like', "%{$search}%");
                });
            });
        }

        return $query;
    }

    /**
     * Get detailed list of all incoming purchase invoice lines for a given product and warehouse.
     */
    public function getInTransitDetails(int $productId, ?int $warehouseId = null): Collection
    {
        $query = DB::table('purchase_invoice_items')
            ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->leftJoin('customers', 'purchase_invoices.supplier_id', '=', 'customers.id')
            ->leftJoin('warehouses', 'purchase_invoices.warehouse_id', '=', 'warehouses.id')
            ->where('purchase_invoice_items.product_id', $productId)
            ->where('purchase_invoices.status', '!=', 'cancelled')
            ->where('purchase_invoices.material_receipt_status', '!=', PurchaseInvoice::MATERIAL_RECEIVED)
            ->whereNull('purchase_invoice_items.receipt_note_item_id')
            ->whereRaw('purchase_invoice_items.quantity > COALESCE(purchase_invoice_items.received_qty, 0)')
            ->when($warehouseId, fn ($q) => $q->where('purchase_invoices.warehouse_id', $warehouseId))
            ->select([
                'purchase_invoice_items.id as item_id',
                'purchase_invoice_items.purchase_invoice_id',
                'purchase_invoice_items.product_id',
                'purchase_invoice_items.quantity as ordered_qty',
                'purchase_invoice_items.received_qty',
                DB::raw('(purchase_invoice_items.quantity - COALESCE(purchase_invoice_items.received_qty, 0)) as in_transit_qty'),
                'purchase_invoices.warehouse_id',
                'purchase_invoices.invoice_no',
                'purchase_invoices.expected_delivery_date',
                'purchase_invoices.invoice_date',
                'purchase_invoices.material_receipt_status',
                'customers.name as supplier_name',
                'warehouses.name as warehouse_name',
            ])
            ->orderByRaw('CASE WHEN purchase_invoices.expected_delivery_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('purchase_invoices.expected_delivery_date', 'asc');

        return $query->get()->map(function ($row) {
            $expDate = $row->expected_delivery_date ? Carbon::parse($row->expected_delivery_date) : null;
            return [
                'item_id' => $row->item_id,
                'invoice_id' => $row->purchase_invoice_id,
                'invoice_no' => $row->invoice_no,
                'supplier_name' => $row->supplier_name ?: '—',
                'warehouse_id' => $row->warehouse_id,
                'warehouse_name' => $row->warehouse_name ?: '—',
                'ordered_qty' => (float) $row->ordered_qty,
                'received_qty' => (float) $row->received_qty,
                'in_transit_qty' => (float) $row->in_transit_qty,
                'expected_delivery_date' => $expDate?->format('d M Y'),
                'expected_delivery_date_raw' => $row->expected_delivery_date,
                'is_overdue' => $expDate ? $expDate->isPast() : false,
                'material_receipt_status' => $row->material_receipt_status,
                'status_label' => match ($row->material_receipt_status) {
                    PurchaseInvoice::MATERIAL_PARTIALLY_RECEIVED => 'Partially Received',
                    default => 'In Transit',
                },
            ];
        });
    }

    /**
     * Compute aggregated stock summary (Physical Available, In-Transit, Total Expected,
     * Next Expected Delivery Date, warehouse breakdown, incoming invoice details)
     * for a list of products.
     */
    public function getAggregatedStockForProducts(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        // 1. Physical stock levels across warehouses
        $physicalRows = DB::table('stock_levels')
            ->join('warehouses', 'stock_levels.warehouse_id', '=', 'warehouses.id')
            ->whereIn('stock_levels.product_id', $productIds)
            ->select([
                'stock_levels.product_id',
                'stock_levels.warehouse_id',
                'warehouses.name as warehouse_name',
                'stock_levels.quantity as physical_stock',
            ])
            ->get();

        // 2. In-transit items from pending purchase invoices
        $inTransitRows = DB::table('purchase_invoice_items')
            ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->leftJoin('customers', 'purchase_invoices.supplier_id', '=', 'customers.id')
            ->leftJoin('warehouses', 'purchase_invoices.warehouse_id', '=', 'warehouses.id')
            ->whereIn('purchase_invoice_items.product_id', $productIds)
            ->where('purchase_invoices.status', '!=', 'cancelled')
            ->where('purchase_invoices.material_receipt_status', '!=', PurchaseInvoice::MATERIAL_RECEIVED)
            ->whereNull('purchase_invoice_items.receipt_note_item_id')
            ->whereRaw('purchase_invoice_items.quantity > COALESCE(purchase_invoice_items.received_qty, 0)')
            ->select([
                'purchase_invoice_items.id as item_id',
                'purchase_invoice_items.product_id',
                'purchase_invoice_items.purchase_invoice_id',
                'purchase_invoice_items.quantity as ordered_qty',
                'purchase_invoice_items.received_qty',
                DB::raw('(purchase_invoice_items.quantity - COALESCE(purchase_invoice_items.received_qty, 0)) as in_transit_qty'),
                'purchase_invoices.warehouse_id',
                'purchase_invoices.invoice_no',
                'purchase_invoices.expected_delivery_date',
                'purchase_invoices.invoice_date',
                'purchase_invoices.material_receipt_status',
                'customers.name as supplier_name',
                'warehouses.name as warehouse_name',
            ])
            ->orderByRaw('CASE WHEN purchase_invoices.expected_delivery_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('purchase_invoices.expected_delivery_date', 'asc')
            ->get();

        // Structure results keyed by product_id
        $result = [];
        foreach ($productIds as $pId) {
            $result[$pId] = [
                'physical_stock' => 0.0,
                'in_transit_stock' => 0.0,
                'total_expected_stock' => 0.0,
                'next_expected_delivery_date' => null,
                'next_expected_delivery_date_formatted' => '—',
                'is_delivery_overdue' => false,
                'warehouses' => [],
                'invoices' => [],
            ];
        }

        // Populate physical stock
        foreach ($physicalRows as $pRow) {
            $pId = $pRow->product_id;
            if (! isset($result[$pId])) {
                continue;
            }
            $wId = $pRow->warehouse_id;
            $qty = (float) $pRow->physical_stock;

            $result[$pId]['physical_stock'] += $qty;

            if (! isset($result[$pId]['warehouses'][$wId])) {
                $result[$pId]['warehouses'][$wId] = [
                    'warehouse_id' => $wId,
                    'warehouse_name' => $pRow->warehouse_name,
                    'physical_stock' => 0.0,
                    'in_transit_stock' => 0.0,
                    'total_expected_stock' => 0.0,
                    'next_delivery_date' => null,
                ];
            }
            $result[$pId]['warehouses'][$wId]['physical_stock'] += $qty;
        }

        // Populate in-transit stock
        foreach ($inTransitRows as $tRow) {
            $pId = $tRow->product_id;
            if (! isset($result[$pId])) {
                continue;
            }
            $wId = $tRow->warehouse_id;
            $transitQty = (float) $tRow->in_transit_qty;
            $expDate = $tRow->expected_delivery_date ? Carbon::parse($tRow->expected_delivery_date) : null;

            $result[$pId]['in_transit_stock'] += $transitQty;

            // Track earliest upcoming delivery date for the product
            if ($expDate) {
                if ($result[$pId]['next_expected_delivery_date'] === null || $expDate->lt(Carbon::parse($result[$pId]['next_expected_delivery_date']))) {
                    $result[$pId]['next_expected_delivery_date'] = $tRow->expected_delivery_date;
                    $result[$pId]['next_expected_delivery_date_formatted'] = $expDate->format('d M Y');
                    $result[$pId]['is_delivery_overdue'] = $expDate->isPast();
                }
            }

            // Warehouse-level in-transit tracking
            if ($wId) {
                if (! isset($result[$pId]['warehouses'][$wId])) {
                    $result[$pId]['warehouses'][$wId] = [
                        'warehouse_id' => $wId,
                        'warehouse_name' => $tRow->warehouse_name ?: 'Warehouse #'.$wId,
                        'physical_stock' => 0.0,
                        'in_transit_stock' => 0.0,
                        'total_expected_stock' => 0.0,
                        'next_delivery_date' => null,
                    ];
                }
                $result[$pId]['warehouses'][$wId]['in_transit_stock'] += $transitQty;
                if ($expDate) {
                    $currDate = $result[$pId]['warehouses'][$wId]['next_delivery_date'];
                    if ($currDate === null || $expDate->lt(Carbon::parse($currDate))) {
                        $result[$pId]['warehouses'][$wId]['next_delivery_date'] = $expDate->format('d M Y');
                    }
                }
            }

            // Add incoming invoice detail
            $result[$pId]['invoices'][] = [
                'item_id' => $tRow->item_id,
                'invoice_id' => $tRow->purchase_invoice_id,
                'invoice_no' => $tRow->invoice_no,
                'supplier_name' => $tRow->supplier_name ?: '—',
                'warehouse_id' => $wId,
                'warehouse_name' => $tRow->warehouse_name ?: '—',
                'ordered_qty' => (float) $tRow->ordered_qty,
                'received_qty' => (float) $tRow->received_qty,
                'in_transit_qty' => $transitQty,
                'expected_delivery_date' => $expDate?->format('d M Y') ?? '—',
                'expected_delivery_date_raw' => $tRow->expected_delivery_date,
                'is_overdue' => $expDate ? $expDate->isPast() : false,
                'status_label' => match ($tRow->material_receipt_status) {
                    PurchaseInvoice::MATERIAL_PARTIALLY_RECEIVED => 'Partially Received',
                    default => 'In Transit',
                },
            ];
        }

        // Calculate total expected stock for product and each warehouse
        foreach ($result as $pId => &$data) {
            $data['total_expected_stock'] = $data['physical_stock'] + $data['in_transit_stock'];
            foreach ($data['warehouses'] as $wId => &$wData) {
                $wData['total_expected_stock'] = $wData['physical_stock'] + $wData['in_transit_stock'];
            }
            unset($wData);
        }
        unset($data);

        return $result;
    }
}
