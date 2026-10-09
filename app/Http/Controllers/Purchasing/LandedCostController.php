<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Purchasing\Models\FreightBill;
use App\Domains\Purchasing\Models\LandedCost;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Services\LandedCostService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Support\Traits\SortableAndSearchable;

class LandedCostController extends Controller
{
    use SortableAndSearchable;

    public function __construct(protected LandedCostService $landedCostService) {}

    public function index(Request $request): View
    {
        $query = LandedCost::query()
            ->with(['purchaseInvoice.supplier', 'freightBill', 'creator'])
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('landed_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('landed_date', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['landed_no', 'notes'],
            ['purchaseInvoice' => ['invoice_number', 'supplier_invoice_number']]
        );

        $sortData = $this->applySorting(
            $query,
            $request,
            ['landed_no', 'landed_date', 'allocation_method', 'total_additional_cost', 'created_at'],
            defaultSort: 'landed_date',
            defaultDirection: 'desc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('purchasing.landed-costs.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('purchasing.landed-costs.create', [
            'invoices' => PurchaseInvoice::with('supplier')->where('status', 'posted')->latest()->limit(100)->get(),
            'freightBills' => FreightBill::whereIn('status', ['posted', 'draft'])->latest()->limit(100)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'landed_date' => 'required|date',
            'purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'freight_bill_id' => 'nullable|exists:freight_bills,id',
            'allocation_method' => 'required|in:qty,value,weight,volume,equal,manual',
            'total_additional_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'manual_allocations' => 'nullable|array',
            'manual_allocations.*' => 'nullable|numeric|min:0',
        ]);

        $landed = $this->landedCostService->allocate($validated, $request->user());

        return $this->flashSuccess('Landed cost allocated.', 'purchasing.landed-costs.show', ['landedCost' => $landed]);
    }

    public function show(LandedCost $landedCost): View
    {
        $landedCost->load(['items.product', 'items.uom', 'purchaseInvoice.supplier', 'freightBill', 'creator']);

        return view('purchasing.landed-costs.show', compact('landedCost'));
    }
}
