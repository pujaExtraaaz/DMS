<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseInwardController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = PurchaseInward::query()
            ->with(['supplier', 'warehouse', 'purchaseOrder', 'creator'])
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('inward_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('inward_date', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['inward_no', 'remarks'],
            ['supplier' => ['name', 'code']]
        );

        $allowedSorts = [
            'inward_no' => 'inward_no',
            'inward_date' => 'inward_date',
            'created_at' => 'created_at',
            'supplier' => function ($q, $dir) {
                $q->join('customers', 'purchase_inwards.supplier_id', '=', 'customers.id')
                  ->orderBy('customers.name', $dir)
                  ->select('purchase_inwards.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'inward_date',
            defaultDirection: 'desc'
        );

        $inwards = $query->paginate(15)->withQueryString();

        return view('purchasing.inwards.index', [
            'inwards' => $inwards,
            'suppliers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'search' => $request->string('search'),
            'supplierId' => $request->input('supplier_id'),
            'warehouseId' => $request->input('warehouse_id'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function show(PurchaseInward $inward): View
    {
        $inward->load(['items.product', 'items.uom', 'supplier', 'warehouse', 'purchaseOrder', 'creator']);

        return view('purchasing.inwards.show', compact('inward'));
    }
}
