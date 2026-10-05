<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Services\PurchaseInvoiceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseInvoiceController extends Controller
{
    public function __construct(
        protected PurchaseInvoiceService $service
    ) {}

    public function index(Request $request): View
    {
        $items = PurchaseInvoice::query()
            ->with(['supplier', 'warehouse', 'purchaseOrder'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('invoice_number', 'like', '%'.$request->search.'%')
                    ->orWhere('supplier_invoice_number', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('purchasing.invoices.index', [
            'items' => $items,
            'suppliers' => Customer::whereIn('party_type', ['supplier', 'both'])->where('is_active', true)->orderBy('name')->get(),
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'supplierId' => $request->string('supplier_id'),
        ]);
    }

    public function create(Request $request): View
    {
        $order = $request->filled('purchase_order_id')
            ? PurchaseOrder::with(['items.product', 'supplier', 'warehouse'])->find($request->purchase_order_id)
            : null;

        return view('purchasing.invoices.create', [
            'order' => $order,
            'suppliers' => Customer::whereIn('party_type', ['supplier', 'both'])->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'invoice_date' => 'required|date',
            'supplier_invoice_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'freight_charge' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.selling_price' => 'nullable|numeric|min:0',
            'items.*.batch_mrp' => 'nullable|numeric|min:0',
            'items.*.serial_numbers' => 'nullable|array',
            'items.*.serial_numbers.*' => 'nullable|string|max:100',
        ]);

        $invoice = $this->service->createInvoice($validated);

        return $this->flashSuccess('Purchase invoice created as draft.', 'purchasing.invoices.show', $invoice);
    }

    public function show(PurchaseInvoice $invoice): View
    {
        $invoice->load(['supplier', 'warehouse', 'purchaseOrder', 'items.product', 'inventoryMovements']);

        return view('purchasing.invoices.show', compact('invoice'));
    }

    public function post(PurchaseInvoice $invoice): RedirectResponse
    {
        try {
            $this->service->postInvoice($invoice);

            return $this->flashSuccess('Purchase invoice posted. Stock has been incremented.', 'purchasing.invoices.show', $invoice);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to post invoice: '.$e->getMessage());
        }
    }

    public function cancel(PurchaseInvoice $invoice): RedirectResponse
    {
        try {
            $this->service->cancelInvoice($invoice);

            return $this->flashSuccess('Purchase invoice cancelled and stock reversed.', 'purchasing.invoices.show', $invoice);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to cancel invoice: '.$e->getMessage());
        }
    }

    public function orderItems(PurchaseOrder $order)
    {
        $order->load(['items.product', 'supplier', 'warehouse']);

        return response()->json([
            'order' => [
                'id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'warehouse_id' => $order->warehouse_id,
                'order_number' => $order->order_number,
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name ?? 'Item',
                        'unit' => $item->product?->unit ?? 'PCS',
                        'tracking_type' => $item->product?->tracking_type ?? 'none',
                        'quantity' => (float) $item->quantity,
                        'unit_cost' => (float) $item->unit_price,
                        'cgst_percent' => $item->cgst_percent,
                        'sgst_percent' => $item->sgst_percent,
                        'cgst_amount' => $item->cgst_amount,
                        'sgst_amount' => $item->sgst_amount,
                        'tax_amount' => (float) $item->tax_amount,
                        'line_total' => (float) $item->line_total,
                        'selling_price' => (float) ($item->product?->selling_price ?? 0),
                        'batch_mrp' => (float) ($item->product?->mrp ?? 0),
                    ];
                }),
            ],
        ]);
    }
}