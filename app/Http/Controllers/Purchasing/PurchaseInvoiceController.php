<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Support\QrCodeRenderer;
use App\Domains\Purchasing\Models\VendorPriceHistory;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Domains\Organization\Services\FinancialYearService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseInvoiceController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $purchaseOrderService,
        protected FinancialYearService $financialYearService,
    ) {}

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

        $company = Company::query()->find(
            auth()->user()?->company_id
        ) ?? Company::query()->first();

        $vendorRates = VendorPriceHistory::query()
            ->latest('effective_from')
            ->get()
            ->groupBy(function ($rate) {
                return $rate->supplier_id . ':' . $rate->product_id . ':' . $rate->uom_id;
            });

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
            'company' => $company,


            'purchaseTermsAndConditions' => $company?->purchase_terms_and_conditions,
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
            'terms_and_conditions' => 'nullable|string',
            'freight_allocation_method' => 'nullable|in:qty,value,weight,volume,equal,manual',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0',
            'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.batch_no' => 'nullable|string|max:60',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.batch_selling_price' => 'nullable|numeric|min:0',
            'items.*.batch_mrp' => 'nullable|numeric|min:0',
        ]);

        $this->financialYearService->assertOpen($validated['invoice_date']);

        $invoice = $this->purchaseOrderService->createInvoice($validated, $request->user());

        return $this->flashSuccess('Purchase invoice posted.', 'purchasing.invoices.show', ['invoice' => $invoice]);
    }

    public function show(PurchaseInvoice $invoice): View
    {
        $invoice->load([
            'items.product',
            'items.uom',
            'supplier',
            'warehouse',
            'purchaseOrder',
            'creator',
        ]);

        return view('purchasing.invoices.show', compact('invoice'));
    }

    public function preview(PurchaseInvoice $invoice): View
    {
        $invoice->load([
            'items.product',
            'items.uom',
            'supplier',
            'warehouse',
            'purchaseOrder',
            'creator',
        ]);

       $company = Company::query()->find(
            auth()->user()?->company_id
        ) ?? Company::query()->first();

        $url = route('invoice.qr', [
            'type' => 'purchase',
            'token' => $invoice->qr_token,
        ]);

        $invoiceQrDataUri = QrCodeRenderer::dataUri($url, 180);

        return view('purchasing.invoices.preview', [
            'invoice' => $invoice,
            'company' => $company,
            'invoiceQrDataUri' => $invoiceQrDataUri,
        ]);
    }

    public function purchaseOrderData(PurchaseOrder $order)
    {
        $order->load([
            'supplier',
            'items.product',
            'items.uom',
        ]);

        return response()->json([
            'id' => $order->id,
            'po_no' => $order->po_no,
            'supplier_id' => $order->supplier_id,
            'warehouse_id' => $order->warehouse_id,
            'items' => $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'uom_id' => $item->uom_id,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'tax_percent' => $item->tax_percent,
                    'cgst_percent' => $item->cgst_percent,
                    'sgst_percent' => $item->sgst_percent,
                    'cgst_amount' => $item->cgst_amount,
                    'sgst_amount' => $item->sgst_amount,
                ];
            })->values(),
        ]);
    }
}
