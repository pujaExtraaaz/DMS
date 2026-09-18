<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\VendorPriceHistory;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseInvoiceController extends Controller
{
    public function __construct(protected PurchaseOrderService $purchaseOrderService) {}

    public function index(Request $request): View
    {
        $invoices = PurchaseInvoice::query()
            ->with(['supplier', 'creator'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('invoice_no', 'like', '%'.$request->search.'%')
                    ->orWhere('supplier_invoice_no', 'like', '%'.$request->search.'%');
            }))
            ->latest('invoice_date')
            ->paginate(15)
            ->withQueryString();

        return view('purchasing.invoices.index', compact('invoices'));
    }

    public function create(Request $request): View
    {
        $vendorRates = VendorPriceHistory::query()
            ->with('supplier')
            ->latest('effective_from')
            ->limit(100)
            ->get()
            ->groupBy('product_id');

        return view('purchasing.invoices.create', [
            'suppliers' => Customer::query()
                ->where('is_active', true)
                ->whereIn('party_type', ['supplier', 'both'])
                ->orderBy('name')
                ->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
            'orders' => PurchaseOrder::whereIn('status', ['approved', 'partially_received', 'closed'])->latest()->limit(50)->get(),
            'vendorRates' => $vendorRates,
            'selectedOrderId' => $request->integer('purchase_order_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:customers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'purchase_inward_id' => 'nullable|exists:purchase_inwards,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'supplier_invoice_no' => 'nullable|string|max:60',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'rate_override_reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0',
        ]);

        $invoice = $this->purchaseOrderService->createInvoice($validated, $request->user());

        return $this->flashSuccess('Purchase invoice posted.', 'purchasing.invoices.show', ['invoice' => $invoice]);
    }

    public function show(PurchaseInvoice $invoice): View
    {
        $invoice->load(['items.product', 'items.uom', 'supplier', 'warehouse', 'purchaseOrder', 'creator']);

        return view('purchasing.invoices.show', compact('invoice'));
    }
}
