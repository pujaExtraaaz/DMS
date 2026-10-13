<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Support\DocumentExporter;
use App\Support\Traits\SortableAndSearchable;
use Symfony\Component\HttpFoundation\Response;

class PurchaseInvoiceController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected PurchaseOrderService $service
    ) {}

    public function index(Request $request): View|Response
    {
        $query = PurchaseInvoice::query()
            ->with(['supplier', 'warehouse', 'purchaseOrder'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('invoice_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('invoice_date', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['invoice_number', 'supplier_invoice_number', 'notes'],
            ['supplier' => ['name', 'code']]
        );

        $allowedSorts = [
            'invoice_number' => 'invoice_number',
            'supplier_invoice_number' => 'supplier_invoice_number',
            'invoice_date' => 'invoice_date',
            'status' => 'status',
            'total_amount' => 'total_amount',
            'created_at' => 'created_at',
            'supplier' => function ($q, $dir) {
                $q->join('customers', 'purchase_invoices.supplier_id', '=', 'customers.id')
                  ->orderBy('customers.name', $dir)
                  ->select('purchase_invoices.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'invoice_date',
            defaultDirection: 'desc'
        );

        if ($request->filled('export')) {
            $exportFormat = strtolower($request->string('export')->toString());
            if (in_array($exportFormat, ['csv', 'excel', 'xlsx', 'pdf'], true)) {
                return DocumentExporter::exportPurchaseInvoicesListing($query->get(), $exportFormat);
            }
        }

        $invoices = $query->paginate(15)->withQueryString();

        return view('purchasing.invoices.index', [
            'invoices' => $invoices,
            'items' => $invoices,
            'suppliers' => Customer::whereIn('party_type', [Customer::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier', Customer::PARTY_TYPE_BOTH])->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'supplierId' => $request->string('supplier_id'),
            'warehouseId' => $request->string('warehouse_id'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(Request $request): View
    {
        $selectedOrderId = $request->filled('purchase_order_id') ? (int) $request->purchase_order_id : null;
        $order = $selectedOrderId
            ? PurchaseOrder::with(['items.product', 'supplier', 'warehouse', 'inwards'])->find($selectedOrderId)
            : null;

        $selectedSupplierId = old('supplier_id', $order?->supplier_id);
        $supplier = $selectedSupplierId ? Customer::find($selectedSupplierId) : null;
        $initialOrders = $supplier ? $this->getEligibleOrdersForSupplier($supplier, $request->user()) : collect();

        if ($order && ! $initialOrders->contains('id', $order->id)) {
            $totalOrdered = (float) $order->items->sum('quantity');
            $invoicedQty = (float) DB::table('purchase_invoice_items')
                ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
                ->where('purchase_invoices.purchase_order_id', $order->id)
                ->where('purchase_invoices.status', '!=', 'cancelled')
                ->sum('purchase_invoice_items.quantity');
            $rem = max(0, $totalOrdered - $invoicedQty);

            $initialOrders->prepend([
                'id' => $order->id,
                'po_no' => $order->po_no,
                'po_date' => $order->po_date?->format('d M Y') ?? '—',
                'grand_total' => (float) $order->grand_total,
                'status' => ucfirst(str_replace('_', ' ', $order->status)),
                'credit_days' => $order->credit_days,
                'remaining_qty' => $rem,
                'total_qty' => $totalOrdered,
                'label' => sprintf(
                    '%s (%s) - ₹%s%s',
                    $order->po_no,
                    $order->po_date?->format('d M Y') ?? 'No date',
                    number_format($order->grand_total, 2),
                    $invoicedQty > 0 ? " [Remaining: {$rem}/{$totalOrdered}]" : ''
                ),
            ]);
        }

        $defaultReceiveDate = null;
        if ($order && $order->relationLoaded('inwards') && $order->inwards->isNotEmpty()) {
            $latestInward = $order->inwards->sortByDesc('inward_date')->first();
            $defaultReceiveDate = $latestInward?->inward_date?->toDateString();
        }

        return view('purchasing.invoices.create', [
            'order' => $order,
            'initialOrders' => $initialOrders,
            'defaultReceiveDate' => $defaultReceiveDate,
            'defaultPaymentOrderDate' => null,
            'suppliers' => Customer::whereIn('party_type', [Customer::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier', Customer::PARTY_TYPE_BOTH])->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:customers,id',
            'billing_address_id' => 'nullable|exists:party_addresses,id',
            'shipping_address_id' => 'nullable|exists:party_addresses,id',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'warehouse_id' => 'required|exists:warehouses,id',
            'invoice_date' => 'required|date',
            'purchase_receive_date' => 'nullable|date',
            'payment_order_date' => 'nullable|date',
            'supplier_invoice_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'freight_charge' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'due_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.batch_mrp' => 'nullable|numeric|min:0',
            'items.*.serial_numbers' => 'nullable|array',
            'items.*.serial_numbers.*' => 'nullable|string|max:100',
        ]);

        if (!empty($validated['purchase_order_id'])) {
            $po = PurchaseOrder::with('items')->findOrFail($validated['purchase_order_id']);

            // 1. Validate that the purchase order belongs to the selected supplier
            if ((int) $po->supplier_id !== (int) $validated['supplier_id']) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'The selected purchase order does not belong to the selected supplier.',
                ]);
            }

            // 2. Validate company authorization
            $user = $request->user();
            if ($user && $user->company_id && $po->company_id && $po->company_id !== $user->company_id) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'You are not authorized to access this purchase order.',
                ]);
            }

            // 3. Validate status eligibility
            if (!in_array($po->status, ['approved', 'partially_received', 'closed'], true)) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => "Purchase order with status '{$po->status}' cannot be invoiced.",
                ]);
            }

            // 4. Validate that the order has not been fully invoiced already
            $totalOrdered = (float) $po->items->sum('quantity');
            $invoicedQty = (float) DB::table('purchase_invoice_items')
                ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
                ->where('purchase_invoices.purchase_order_id', $po->id)
                ->where('purchase_invoices.status', '!=', 'cancelled')
                ->sum('purchase_invoice_items.quantity');

            if ($totalOrdered > 0 && $invoicedQty >= $totalOrdered) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'This purchase order has already been fully invoiced.',
                ]);
            }
        }

        $invoice = $this->service->createInvoice($validated, $request->user());

        return $this->flashSuccess('Purchase invoice created as draft.', 'purchasing.invoices.show', ['invoice' => $invoice]);
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

            return $this->flashSuccess('Purchase invoice posted. Stock has been incremented.', 'purchasing.invoices.show', ['invoice' => $invoice]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to post invoice: '.$e->getMessage());
        }
    }

    public function cancel(PurchaseInvoice $invoice): RedirectResponse
    {
        try {
            $this->service->cancelInvoice($invoice);

            return $this->flashSuccess('Purchase invoice cancelled and stock reversed.', 'purchasing.invoices.show', ['invoice' => $invoice]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to cancel invoice: '.$e->getMessage());
        }
    }

    public function getEligibleOrdersForSupplier(?Customer $supplier, ?User $user = null): Collection
    {
        if (! $supplier) {
            return collect();
        }

        $companyId = $user?->company_id;

        $orders = PurchaseOrder::query()
            ->where('supplier_id', $supplier->id)
            ->when($companyId, fn ($q) => $q->where(fn ($sub) => $sub->where('company_id', $companyId)->orWhereNull('company_id')))
            ->whereIn('status', ['approved', 'partially_received', 'closed'])
            ->with(['items'])
            ->orderByDesc('po_date')
            ->orderByDesc('id')
            ->get();

        return $orders->filter(function (PurchaseOrder $order) {
            $totalOrdered = (float) $order->items->sum('quantity');
            if ($totalOrdered <= 0) {
                return false;
            }

            $invoicedQty = (float) DB::table('purchase_invoice_items')
                ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
                ->where('purchase_invoices.purchase_order_id', $order->id)
                ->where('purchase_invoices.status', '!=', 'cancelled')
                ->sum('purchase_invoice_items.quantity');

            return $invoicedQty < $totalOrdered;
        })->map(function (PurchaseOrder $order) {
            $totalOrdered = (float) $order->items->sum('quantity');
            $invoicedQty = (float) DB::table('purchase_invoice_items')
                ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
                ->where('purchase_invoices.purchase_order_id', $order->id)
                ->where('purchase_invoices.status', '!=', 'cancelled')
                ->sum('purchase_invoice_items.quantity');
            $remainingQty = max(0, $totalOrdered - $invoicedQty);

            $label = sprintf(
                '%s (%s) - ₹%s%s',
                $order->po_no,
                $order->po_date?->format('d M Y') ?? 'No date',
                number_format($order->grand_total, 2),
                $invoicedQty > 0 ? " [Remaining: {$remainingQty}/{$totalOrdered}]" : ''
            );

            return [
                'id' => $order->id,
                'po_no' => $order->po_no,
                'po_date' => $order->po_date?->format('d M Y') ?? '—',
                'grand_total' => (float) $order->grand_total,
                'status' => ucfirst(str_replace('_', ' ', $order->status)),
                'credit_days' => $order->credit_days,
                'remaining_qty' => $remainingQty,
                'total_qty' => $totalOrdered,
                'label' => $label,
            ];
        })->values();
    }

    public function supplierPurchaseOrders(Request $request, Customer $supplier)
    {
        $user = $request->user();
        if ($user && $user->company_id && $supplier->company_id && $supplier->company_id !== $user->company_id) {
            return response()->json(['orders' => []], 403);
        }

        $orders = $this->getEligibleOrdersForSupplier($supplier, $user);

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function orderItems(PurchaseOrder $order)
    {
        $user = auth()->user();
        if ($user && $user->company_id && $order->company_id && $order->company_id !== $user->company_id) {
            abort(403, 'Unauthorized');
        }

        if (! in_array($order->status, ['approved', 'partially_received', 'closed'], true)) {
            return response()->json([
                'error' => "Purchase order in '{$order->status}' status cannot be invoiced.",
            ], 422);
        }

        $order->load(['items.product', 'supplier', 'warehouse', 'inwards', 'billingAddress', 'shippingAddress']);
        $latestInwardDate = $order->inwards->sortByDesc('inward_date')->first()?->inward_date?->toDateString();

        $invoicedByProduct = DB::table('purchase_invoice_items')
            ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->where('purchase_invoices.purchase_order_id', $order->id)
            ->where('purchase_invoices.status', '!=', 'cancelled')
            ->select('purchase_invoice_items.product_id', DB::raw('SUM(purchase_invoice_items.quantity) as invoiced_qty'))
            ->groupBy('purchase_invoice_items.product_id')
            ->pluck('invoiced_qty', 'product_id');

        $items = $order->items->map(function ($item) use ($invoicedByProduct) {
            $alreadyInvoiced = (float) ($invoicedByProduct[$item->product_id] ?? 0);
            $remaining = max(0, (float) $item->quantity - $alreadyInvoiced);

            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'Item',
                'unit' => $item->product?->unit ?? 'PCS',
                'tracking_type' => $item->product?->tracking_type ?? 'none',
                'quantity' => $alreadyInvoiced > 0 ? $remaining : (float) $item->quantity,
                'ordered_quantity' => (float) $item->quantity,
                'unit_cost' => (float) ($item->unit_cost ?? $item->unit_price ?? 0),
                'cgst_percent' => (float) ($item->cgst_percent ?? 0),
                'sgst_percent' => (float) ($item->sgst_percent ?? 0),
                'cgst_amount' => (float) ($item->cgst_amount ?? 0),
                'sgst_amount' => (float) ($item->sgst_amount ?? 0),
                'tax_amount' => (float) ($item->tax_amount ?? 0),
                'line_total' => (float) ($item->line_total ?? 0),
                'batch_number' => $item->batch_no ?? '',
                'selling_price' => (float) ($item->product?->selling_price ?? 0),
                'batch_mrp' => (float) ($item->product?->mrp ?? 0),
            ];
        })->filter(fn ($it) => $it['quantity'] > 0)->values();

        return response()->json([
            'order' => [
                'id' => $order->id,
                'po_no' => $order->po_no,
                'order_number' => $order->po_no,
                'supplier_id' => $order->supplier_id,
                'warehouse_id' => $order->warehouse_id,
                'credit_days' => $order->credit_days,
                'freight_total' => (float) ($order->freight_total ?? 0),
                'receive_date' => $latestInwardDate,
                'billing_address_id' => $order->billing_address_id,
                'shipping_address_id' => $order->shipping_address_id,
                'billing_address' => $order->billing_address,
                'shipping_address' => $order->shipping_address,
                'items' => $items,
            ],
        ]);
    }

    public function preview(PurchaseInvoice $invoice): View
    {
        $invoice->load([
            'items.product',
            'supplier',
            'warehouse',
            'purchaseOrder',
        ]);

        $company = \App\Domains\Organization\Models\Company::resolveCurrent()
            ?? \App\Domains\Organization\Models\Company::query()->first();

        $url = route('invoice.qr', [
            'type' => 'purchase',
            'token' => $invoice->qr_token,
        ]);

        $invoiceQrDataUri = \App\Support\QrCodeRenderer::dataUri($url, 180);

        return view('purchasing.invoices.preview', [
            'invoice' => $invoice,
            'company' => $company,
            'invoiceQrDataUri' => $invoiceQrDataUri,
        ]);
    }

    public function export(PurchaseInvoice $invoice, string $format): Response
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['pdf', 'xlsx', 'excel', 'csv'], true), 404);

        return DocumentExporter::exportPurchaseInvoice($invoice, $format);
    }

    public function purchaseOrderData(PurchaseOrder $order)
    {
        return $this->orderItems($order);
    }
}