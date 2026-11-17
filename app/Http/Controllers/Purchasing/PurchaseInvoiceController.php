<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Banking\Models\OdAccount;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Organization\Services\FinancialYearService;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\ReceiptNote;
use App\Domains\Purchasing\Models\ReceiptNoteItem;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
        protected PurchaseOrderService $service,
        protected FinancialYearService $financialYearService,
    ) {}

    public function index(Request $request): View|Response
    {
        $query = PurchaseInvoice::query()
            ->with(['supplier', 'warehouse', 'purchaseOrder'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('material_receipt_status'), fn ($q) => $q->where('material_receipt_status', $request->material_receipt_status))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('invoice_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('invoice_date', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['invoice_no', 'supplier_invoice_no', 'notes'],
            ['supplier' => ['name', 'code']]
        );

        $allowedSorts = [
            'invoice_number' => 'invoice_no',
            'invoice_no' => 'invoice_no',
            'supplier_invoice_number' => 'supplier_invoice_no',
            'supplier_invoice_no' => 'supplier_invoice_no',
            'invoice_date' => 'invoice_date',
            'status' => 'status',
            'total_amount' => 'grand_total',
            'grand_total' => 'grand_total',
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

            $productNames = $order->items
                ->map(fn ($item) => $item->product?->name)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $initialOrders->prepend([
                'id' => $order->id,
                'po_no' => $order->po_no,
                'po_date' => $order->po_date?->format('d M Y') ?? '—',
                'raw_date' => $order->po_date?->toDateString() ?? '',
                'grand_total' => (float) $order->grand_total,
                'status' => ucfirst(str_replace('_', ' ', $order->status)),
                'raw_status' => $order->status,
                'credit_days' => $order->credit_days,
                'remaining_qty' => $rem,
                'total_qty' => $totalOrdered,
                'supplier_name' => $order->supplier?->name ?? '',
                'products' => $productNames,
                'products_summary' => implode(', ', array_slice($productNames, 0, 3)) . (count($productNames) > 3 ? ' +' . (count($productNames) - 3) . ' more' : ''),
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

        $companyId = $request->user()?->company_id ?? Company::defaultCompanyId();
        $odAccountsQuery = OdAccount::query()->active()->orderBy('bank_name');
        if ($companyId) {
            $odAccountsQuery->where('company_id', $companyId);
        }
        $odAccounts = $odAccountsQuery->get()->map(function (OdAccount $acc) {
            $acc->current_utilization = $acc->getCurrentUtilization();
            $acc->available_limit = $acc->getAvailableLimit();
            $last4 = strlen($acc->account_number) > 4 ? substr($acc->account_number, -4) : $acc->account_number;
            $acc->masked_account_number = '•••• ' . $last4;
            return $acc;
        });

        $newProduct = null;
        if ($request->filled('new_product_id')) {
            $candidateProduct = Product::with(['baseUom', 'productType'])
                ->where('is_active', true)
                ->find($request->input('new_product_id'));
            if ($candidateProduct) {
                $newProduct = $candidateProduct;
            }
        }

        $products = $this->invoiceProducts();

        if ($newProduct && ! $products->contains('id', $newProduct->id)) {
            $products->prepend($newProduct);
        }

        $savedDraft = $request->session()->get('purchase_invoice_draft');

        return view('purchasing.invoices.create', [
            'order' => $order,
            'initialOrders' => $initialOrders,
            'defaultReceiveDate' => $defaultReceiveDate,
            'defaultPaymentOrderDate' => null,
            'suppliers' => Customer::whereIn('party_type', [Customer::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier', Customer::PARTY_TYPE_BOTH])->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => $products,
            'newProduct' => $newProduct,
            'savedDraft' => $savedDraft,
            'odAccounts' => $odAccounts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'save_mode' => 'nullable|in:draft,open',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'receipt_note_id' => 'nullable|exists:receipt_notes,id',
            'supplier_id' => 'required|exists:customers,id',
            'od_account_id' => 'nullable|exists:od_accounts,id',
            'billing_address_id' => 'nullable|exists:party_addresses,id',
            'shipping_address_id' => 'nullable|exists:party_addresses,id',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'warehouse_id' => 'required|exists:warehouses,id',
            'invoice_date' => 'required|date',
            'purchase_receive_date' => 'nullable|date',
            'material_dispatch_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'payment_order_date' => 'nullable|date',
            'supplier_invoice_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'price_type' => 'nullable|in:exclusive,inclusive',
            'round_off' => 'nullable|numeric|between:-500,500',
            'freight_charge' => 'nullable|numeric|min:0',
            'is_freight_applicable' => 'nullable|boolean',
            'freight_mode' => 'nullable|in:amount,percent',
            'freight_percent' => 'nullable|numeric|min:0|max:100',
            'freight_amount' => 'nullable|numeric|min:0',
            'freight_tax_rate' => 'nullable|numeric|min:0|max:100',
            'freight_notes' => 'nullable|string|max:1000',
            'receipt_doc_no' => 'nullable|string|max:100',
            'courier_docket_no' => 'nullable|string|max:100',
            'dispatched_through' => 'nullable|string|max:150',
            'courier_name' => 'nullable|string|max:150',
            'vehicle_number' => 'nullable|string|max:50',
            'receipt_note_nos' => 'nullable|string|max:255',
            'carrier_name' => 'nullable|string|max:150',
            'lr_number' => 'nullable|string|max:100',
            'lr_date' => 'nullable|date',
            'order_nos' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:255',
            'other_references' => 'nullable|string|max:255',
            'terms_of_delivery' => 'nullable|string|max:255',
            'other_charges' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'due_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.receipt_note_item_id' => 'nullable|exists:receipt_note_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.batch_mrp' => 'nullable|numeric|min:0',
            'items.*.purchase_price_inclusive' => 'nullable|numeric|min:0',
            'items.*.purchase_discount_percent' => 'nullable|numeric',
            'items.*.serial_numbers' => 'nullable|array',
            'items.*.serial_numbers.*' => 'nullable|string|max:100',
        ]);

        if (filled($request->input('courier_docket_no')) && ! filled($validated['receipt_doc_no'] ?? null)) {
            $validated['receipt_doc_no'] = $request->input('courier_docket_no');
        }
        if (filled($request->input('courier_name')) && ! filled($validated['dispatched_through'] ?? null)) {
            $validated['dispatched_through'] = $request->input('courier_name');
        }

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

        $productIds = collect($validated['items'])->pluck('product_id')->filter()->unique();
        $dbProducts = Product::whereIn('id', $productIds)->get()->keyBy('id');
        foreach ($validated['items'] as &$it) {
            $prod = $dbProducts->get($it['product_id'] ?? null);
            if (! $prod || ! $prod->isSerialTracked()) {
                $it['serial_numbers'] = [];
                $it['serials'] = [];
            } else {
                $it['serial_numbers'] = array_values(array_filter(
                    (array) ($it['serial_numbers'] ?? []),
                    fn ($s) => trim((string) $s) !== ''
                ));
            }
        }
        unset($it);

        $this->assertTrackedSerials($validated['items']);

        $saveMode = $validated['save_mode'] ?? 'open';
        unset($validated['save_mode']);

        $invoice = DB::transaction(function () use ($validated, $request, $saveMode) {
            $invoice = $this->service->createInvoice($validated, $request->user());

            // Draft = material already received → post stock now and mark ready to pay.
            // Open = still in transit (default create behaviour).
            if ($saveMode === 'draft' && $invoice->canReceiveMaterial()) {
                $invoice = $this->service->receiveMaterial($invoice, [
                    'mark_all_received' => true,
                    'actual_material_receipt_date' => $validated['purchase_receive_date']
                        ?? $validated['invoice_date']
                        ?? now()->toDateString(),
                ], $request->user());
            }

            return $invoice;
        });

        $request->session()->forget('purchase_invoice_draft');

        $message = $saveMode === 'draft'
            ? 'Supplier invoice saved as Draft. Material marked received and stock posted — ready for payment.'
            : 'Supplier invoice saved as Open. Material is In Transit — confirm receipt later to add stock.';

        return $this->flashSuccess(
            $message,
            'purchasing.invoices.show',
            ['invoice' => $invoice]
        );
    }

    public function saveDraftSession(Request $request): \Illuminate\Http\JsonResponse
    {
        $draft = $request->input('draft', $request->all());
        $request->session()->put('purchase_invoice_draft', $draft);

        return response()->json(['success' => true]);
    }

    public function clearDraftSession(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->session()->forget('purchase_invoice_draft');

        return response()->json(['success' => true]);
    }

    public function show(PurchaseInvoice $invoice): View
    {
        $invoice->load([
            'supplier',
            'warehouse',
            'purchaseOrder',
            'items.product',
            'items.uom',
            'items.serials',
            'inventoryMovements',
            'odAccount',
            'bankAccountTransactions',
        ]);

        return view('purchasing.invoices.show', compact('invoice'));
    }

    public function post(PurchaseInvoice $invoice): RedirectResponse
    {
        try {
            $this->service->postInvoice($invoice);

            return $this->flashSuccess(
                'Purchase invoice posted financially. Confirm material receipt separately to add stock.',
                'purchasing.invoices.show',
                ['invoice' => $invoice]
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to post invoice: '.$e->getMessage());
        }
    }

    public function receiveMaterialForm(PurchaseInvoice $invoice): View|RedirectResponse
    {
        if (! $invoice->canReceiveMaterial()) {
            return redirect()
                ->route('purchasing.invoices.show', $invoice)
                ->with('error', 'Material is already fully received or the invoice is cancelled.');
        }

        $invoice->load(['supplier', 'warehouse', 'items.product', 'items.uom']);

        return view('purchasing.invoices.receive-material', compact('invoice'));
    }

    public function receiveMaterial(Request $request, PurchaseInvoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'actual_material_receipt_date' => 'required|date',
            'mark_all_received' => 'nullable|boolean',
            'items' => 'nullable|array',
            'items.*.id' => 'required|exists:purchase_invoice_items,id',
            'items.*.receive_qty' => 'nullable|numeric|min:0',
        ]);

        try {
            $updated = $this->service->receiveMaterial($invoice, $validated, $request->user());

            return $this->flashSuccess(
                'Material receipt recorded. Stock updated for received quantities only.',
                'purchasing.invoices.show',
                ['invoice' => $updated]
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to receive material: '.$e->getMessage());
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

    protected function authorizeInvoiceAction(Request $request, PurchaseInvoice $invoice, string $action): void
    {
        $user = $request->user();
        if ($user && ! $user->hasRole('super-admin') && ! $user->hasAnyPermission(['purchases.edit', 'purchases.manage', 'purchase-orders.edit', 'purchase-orders.manage'])) {
            if ($request->wantsJson() || $request->ajax()) {
                abort(response()->json(['success' => false, 'message' => "You do not have permission to {$action} purchase invoices."], 403));
            }
            abort(403, "You do not have permission to {$action} purchase invoices.");
        }

        if ($user && ! $user->hasRole('super-admin')) {
            if ($user->company_id && $invoice->company_id && (int) $invoice->company_id !== (int) $user->company_id) {
                if ($request->wantsJson() || $request->ajax()) {
                    abort(response()->json(['success' => false, 'message' => 'Unauthorized action for this company.'], 403));
                }
                abort(403, 'Unauthorized action for this company.');
            }
            $allowedBranchIds = method_exists($user, 'allowedBranchIds') ? $user->allowedBranchIds() : [];
            if (! empty($allowedBranchIds) && $invoice->branch_id && ! in_array($invoice->branch_id, $allowedBranchIds)) {
                if ($request->wantsJson() || $request->ajax()) {
                    abort(response()->json(['success' => false, 'message' => 'Unauthorized action for this branch.'], 403));
                }
                abort(403, 'Unauthorized action for this branch.');
            }
        }
    }

    public function edit(Request $request, PurchaseInvoice $invoice): View|RedirectResponse
    {
        $this->authorizeInvoiceAction($request, $invoice, 'edit');

        if (! $invoice->isEditable()) {
            return $this->flashError(
                "Purchase invoice {$invoice->invoice_no} cannot be edited in its current status.",
                'purchasing.invoices.show',
                ['invoice' => $invoice]
            );
        }

        $invoice->load([
            'supplier',
            'warehouse',
            'purchaseOrder.items',
            'items.product',
            'items.uom',
            'items.serials',
            'billingAddress',
            'shippingAddress',
            'odAccount',
            'bankAccountTransactions',
        ]);

        $suppliers = Customer::whereIn('party_type', [Customer::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier', Customer::PARTY_TYPE_BOTH])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products = $this->invoiceProducts();

        $companyId = $invoice->company_id ?? $request->user()?->company_id ?? Company::defaultCompanyId();
        $odAccountsQuery = OdAccount::query()->orderBy('bank_name');
        if ($companyId) {
            $odAccountsQuery->where('company_id', $companyId);
        }
        $odAccountsQuery->where(function ($q) use ($invoice) {
            $q->where('status', 'active');
            if ($invoice->od_account_id) {
                $q->orWhere('id', $invoice->od_account_id);
            }
        });
        $odAccounts = $odAccountsQuery->get()->map(function (OdAccount $acc) use ($invoice) {
            $acc->current_utilization = $acc->getCurrentUtilization();
            $existingTxDebit = ($invoice->od_account_id == $acc->id)
                ? (float) $invoice->bankAccountTransactions->where('od_account_id', $acc->id)->sum('debit')
                : 0.0;
            $acc->available_limit = round($acc->getAvailableLimit() + $existingTxDebit, 2);
            $last4 = strlen($acc->account_number) > 4 ? substr($acc->account_number, -4) : $acc->account_number;
            $acc->masked_account_number = '•••• ' . $last4;
            return $acc;
        });

        $initialOrders = $invoice->supplier ? $this->getEligibleOrdersForSupplier($invoice->supplier, $request->user()) : collect();

        if ($invoice->purchaseOrder && ! $initialOrders->contains('id', $invoice->purchaseOrder->id)) {
            $po = $invoice->purchaseOrder;
            $totalOrdered = (float) $po->items->sum('quantity');
            $invoicedQty = (float) DB::table('purchase_invoice_items')
                ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
                ->where('purchase_invoices.purchase_order_id', $po->id)
                ->where('purchase_invoices.id', '!=', $invoice->id)
                ->where('purchase_invoices.status', '!=', 'cancelled')
                ->sum('purchase_invoice_items.quantity');
            $rem = max(0, $totalOrdered - $invoicedQty);

            $productNames = $po->items
                ->map(fn ($item) => $item->product?->name)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $initialOrders->prepend([
                'id' => $po->id,
                'po_no' => $po->po_no,
                'po_date' => $po->po_date?->format('d M Y') ?? '—',
                'raw_date' => $po->po_date?->toDateString() ?? '',
                'grand_total' => (float) $po->grand_total,
                'status' => ucfirst(str_replace('_', ' ', $po->status)),
                'raw_status' => $po->status,
                'credit_days' => $po->credit_days,
                'remaining_qty' => $rem,
                'total_qty' => $totalOrdered,
                'supplier_name' => $po->supplier?->name ?? '',
                'products' => $productNames,
                'products_summary' => implode(', ', array_slice($productNames, 0, 3)) . (count($productNames) > 3 ? ' +' . (count($productNames) - 3) . ' more' : ''),
                'label' => sprintf(
                    '%s (%s) - ₹%s%s',
                    $po->po_no,
                    $po->po_date?->format('d M Y') ?? 'No date',
                    number_format($po->grand_total, 2),
                    $invoicedQty > 0 ? " [Remaining: {$rem}/{$totalOrdered}]" : ''
                ),
            ]);
        }

        $invoiceItems = $invoice->items->map(function ($it) {
            $unitCost = (float) $it->unit_cost;
            $taxPercent = (float) $it->tax_percent;
            $batchMrp = (float) ($it->batch_mrp ?? ($it->product?->mrp ?? 0));
            $purchasePriceInclusive = $it->purchase_price_inclusive !== null
                ? (float) $it->purchase_price_inclusive
                : round($unitCost * (1 + ($taxPercent / 100)), 4);
            $purchaseDiscountPercent = $it->purchase_discount_percent !== null
                ? (float) $it->purchase_discount_percent
                : ($batchMrp > 0 ? round((($batchMrp - $purchasePriceInclusive) / $batchMrp) * 100, 2) : 0);

            return [
                'product_id' => $it->product_id,
                'unit' => $it->product?->unit ?? ($it->uom?->code ?? 'PCS'),
                'tracking_type' => $it->product?->tracking_type ?? 'none',
                'quantity' => (float) $it->quantity,
                'unit_cost' => $unitCost,
                'entered_unit_cost' => $it->entered_unit_cost !== null ? (float) $it->entered_unit_cost : null,
                'purchase_price_inclusive' => $purchasePriceInclusive,
                'purchase_discount_percent' => $purchaseDiscountPercent,
                'cgst_percent' => (float) $it->cgst_percent,
                'sgst_percent' => (float) $it->sgst_percent,
                'tax_percent' => $taxPercent,
                'batch_number' => $it->batch_no ?? '',
                'batch_mrp' => $batchMrp,
                'serial_numbers' => $it->serialNumbers(),
                'line_total' => (float) $it->line_total,
            ];
        })->values();

        return view('purchasing.invoices.edit', [
            'invoice' => $invoice,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'products' => $products,
            'odAccounts' => $odAccounts,
            'initialOrders' => $initialOrders,
            'invoiceItems' => $invoiceItems,
        ]);
    }

    public function update(Request $request, PurchaseInvoice $invoice): RedirectResponse
    {
        $this->authorizeInvoiceAction($request, $invoice, 'edit');

        if (! $invoice->isEditable()) {
            return $this->flashError(
                "Purchase invoice {$invoice->invoice_no} cannot be edited in its current status.",
                'purchasing.invoices.show',
                ['invoice' => $invoice]
            );
        }

        $validated = $request->validate([
            'save_mode' => 'nullable|in:draft,open',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'receipt_note_id' => 'nullable|exists:receipt_notes,id',
            'supplier_id' => 'required|exists:customers,id',
            'od_account_id' => 'nullable|exists:od_accounts,id',
            'billing_address_id' => 'nullable|exists:party_addresses,id',
            'shipping_address_id' => 'nullable|exists:party_addresses,id',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'warehouse_id' => 'required|exists:warehouses,id',
            'invoice_date' => 'required|date',
            'purchase_receive_date' => 'nullable|date',
            'material_dispatch_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'payment_order_date' => 'nullable|date',
            'supplier_invoice_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'price_type' => 'nullable|in:exclusive,inclusive',
            'round_off' => 'nullable|numeric|between:-500,500',
            'freight_charge' => 'nullable|numeric|min:0',
            'is_freight_applicable' => 'nullable|boolean',
            'freight_mode' => 'nullable|in:amount,percent',
            'freight_percent' => 'nullable|numeric|min:0|max:100',
            'freight_amount' => 'nullable|numeric|min:0',
            'freight_tax_rate' => 'nullable|numeric|min:0|max:100',
            'freight_notes' => 'nullable|string|max:1000',
            'receipt_doc_no' => 'nullable|string|max:100',
            'courier_docket_no' => 'nullable|string|max:100',
            'dispatched_through' => 'nullable|string|max:150',
            'courier_name' => 'nullable|string|max:150',
            'vehicle_number' => 'nullable|string|max:50',
            'receipt_note_nos' => 'nullable|string|max:255',
            'carrier_name' => 'nullable|string|max:150',
            'lr_number' => 'nullable|string|max:100',
            'lr_date' => 'nullable|date',
            'order_nos' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:255',
            'other_references' => 'nullable|string|max:255',
            'terms_of_delivery' => 'nullable|string|max:255',
            'other_charges' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'due_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.receipt_note_item_id' => 'nullable|exists:receipt_note_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.batch_mrp' => 'nullable|numeric|min:0',
            'items.*.purchase_price_inclusive' => 'nullable|numeric|min:0',
            'items.*.purchase_discount_percent' => 'nullable|numeric',
            'items.*.serial_numbers' => 'nullable|array',
            'items.*.serial_numbers.*' => 'nullable|string|max:100',
        ]);

        if (filled($request->input('courier_docket_no')) && ! filled($validated['receipt_doc_no'] ?? null)) {
            $validated['receipt_doc_no'] = $request->input('courier_docket_no');
        }
        if (filled($request->input('courier_name')) && ! filled($validated['dispatched_through'] ?? null)) {
            $validated['dispatched_through'] = $request->input('courier_name');
        }

        if (! empty($validated['purchase_order_id'])) {
            $po = PurchaseOrder::with('items')->findOrFail($validated['purchase_order_id']);

            if ((int) $po->supplier_id !== (int) $validated['supplier_id']) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'The selected purchase order does not belong to the selected supplier.',
                ]);
            }

            $user = $request->user();
            if ($user && $user->company_id && $po->company_id && $po->company_id !== $user->company_id) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'You are not authorized to access this purchase order.',
                ]);
            }

            if (! in_array($po->status, ['approved', 'partially_received', 'closed'], true)) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => "Purchase order with status '{$po->status}' cannot be invoiced.",
                ]);
            }

            // Invoiced quantity check excluding this current invoice's previous count
            $totalOrdered = (float) $po->items->sum('quantity');
            $invoicedQty = (float) DB::table('purchase_invoice_items')
                ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
                ->where('purchase_invoices.purchase_order_id', $po->id)
                ->where('purchase_invoices.id', '!=', $invoice->id)
                ->where('purchase_invoices.status', '!=', 'cancelled')
                ->sum('purchase_invoice_items.quantity');

            if ($totalOrdered > 0 && $invoicedQty >= $totalOrdered) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'This purchase order has already been fully invoiced.',
                ]);
            }
        }

        $this->financialYearService->assertOpen($validated['invoice_date'], $invoice->company_id);
        $productIds = collect($validated['items'])->pluck('product_id')->filter()->unique();
        $dbProducts = Product::whereIn('id', $productIds)->get()->keyBy('id');
        foreach ($validated['items'] as &$it) {
            $prod = $dbProducts->get($it['product_id'] ?? null);
            if (! $prod || ! $prod->isSerialTracked()) {
                $it['serial_numbers'] = [];
                $it['serials'] = [];
            } else {
                $it['serial_numbers'] = array_values(array_filter(
                    (array) ($it['serial_numbers'] ?? []),
                    fn ($s) => trim((string) $s) !== ''
                ));
            }
        }
        unset($it);

        $this->assertTrackedSerials($validated['items']);

        $saveMode = $validated['save_mode'] ?? 'open';
        unset($validated['save_mode']);

        try {
            $updatedInvoice = DB::transaction(function () use ($invoice, $validated, $request, $saveMode) {
                $updated = $this->service->updateInvoice($invoice, $validated, $request->user());

                if ($saveMode === 'draft' && $updated->canReceiveMaterial()) {
                    $updated = $this->service->receiveMaterial($updated, [
                        'mark_all_received' => true,
                        'actual_material_receipt_date' => $validated['purchase_receive_date']
                            ?? $validated['invoice_date']
                            ?? now()->toDateString(),
                    ], $request->user());
                }

                return $updated;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to update invoice: ' . $e->getMessage());
        }

        $message = $saveMode === 'draft' && $updatedInvoice->material_receipt_status === PurchaseInvoice::MATERIAL_RECEIVED
            ? 'Purchase invoice updated. Material marked received and stock posted — ready for payment.'
            : 'Purchase invoice updated successfully.';

        return $this->flashSuccess($message, 'purchasing.invoices.show', ['invoice' => $updatedInvoice]);
    }

    public function destroy(Request $request, PurchaseInvoice $invoice): Response|RedirectResponse
    {
        $this->authorizeInvoiceAction($request, $invoice, 'delete');

        $blockReason = $invoice->getDeleteBlockReason();
        if ($blockReason) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $blockReason,
                ], 422);
            }
            return $this->flashError($blockReason, 'purchasing.invoices.index');
        }

        $this->financialYearService->assertOpen($invoice->invoice_date, $invoice->company_id);

        $invoiceNo = $invoice->invoice_number ?? $invoice->invoice_no;

        try {
            $this->service->deleteInvoice($invoice, $request->user());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Purchase invoice {$invoiceNo} deleted successfully.",
                ]);
            }

            return $this->flashSuccess("Purchase invoice {$invoiceNo} deleted successfully.", 'purchasing.invoices.index');
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete purchase invoice: ' . $e->getMessage(),
                ], 422);
            }

            return $this->flashError('Failed to delete purchase invoice: ' . $e->getMessage(), 'purchasing.invoices.index');
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
            ->with(['items.product', 'supplier'])
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
        })->map(function (PurchaseOrder $order) use ($supplier) {
            $totalOrdered = (float) $order->items->sum('quantity');
            $invoicedQty = (float) DB::table('purchase_invoice_items')
                ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
                ->where('purchase_invoices.purchase_order_id', $order->id)
                ->where('purchase_invoices.status', '!=', 'cancelled')
                ->sum('purchase_invoice_items.quantity');
            $remainingQty = max(0, $totalOrdered - $invoicedQty);

            $productNames = $order->items
                ->map(fn ($item) => $item->product?->name)
                ->filter()
                ->unique()
                ->values()
                ->all();

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
                'raw_date' => $order->po_date?->toDateString() ?? '',
                'grand_total' => (float) $order->grand_total,
                'status' => ucfirst(str_replace('_', ' ', $order->status)),
                'raw_status' => $order->status,
                'credit_days' => $order->credit_days,
                'remaining_qty' => $remainingQty,
                'total_qty' => $totalOrdered,
                'supplier_name' => $order->supplier?->name ?? $supplier->name,
                'products' => $productNames,
                'products_summary' => implode(', ', array_slice($productNames, 0, 3)) . (count($productNames) > 3 ? ' +' . (count($productNames) - 3) . ' more' : ''),
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
            $unitCost = (float) ($item->unit_cost ?? $item->unit_price ?? 0);
            $taxPercent = (float) (($item->cgst_percent ?? 0) + ($item->sgst_percent ?? 0));
            $batchMrp = (float) ($item->mrp ?? $item->product?->mrp ?? 0);
            $purchasePriceInclusive = $item->purchase_price_inclusive !== null
                ? (float) $item->purchase_price_inclusive
                : round($unitCost * (1 + ($taxPercent / 100)), 4);
            $purchaseDiscountPercent = $item->purchase_discount_percent !== null
                ? (float) $item->purchase_discount_percent
                : ($batchMrp > 0 ? round((($batchMrp - $purchasePriceInclusive) / $batchMrp) * 100, 2) : 0);

            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'Item',
                'unit' => $item->product?->unit ?? 'PCS',
                'tracking_type' => $item->product?->tracking_type ?? 'none',
                'is_serial_tracked' => $item->product?->isSerialTracked() ?? false,
                'track_serial' => $item->product?->isSerialTracked() ?? false,
                'quantity' => $alreadyInvoiced > 0 ? $remaining : (float) $item->quantity,
                'ordered_quantity' => (float) $item->quantity,
                'unit_cost' => $unitCost,
                'purchase_price_inclusive' => $purchasePriceInclusive,
                'purchase_discount_percent' => $purchaseDiscountPercent,
                'cgst_percent' => (float) ($item->cgst_percent ?? 0),
                'sgst_percent' => (float) ($item->sgst_percent ?? 0),
                'cgst_amount' => (float) ($item->cgst_amount ?? 0),
                'sgst_amount' => (float) ($item->sgst_amount ?? 0),
                'tax_amount' => (float) ($item->tax_amount ?? 0),
                'line_total' => (float) ($item->line_total ?? 0),
                'batch_number' => $item->batch_no ?? '',
                'selling_price' => (float) ($item->product?->selling_price ?? 0),
                'batch_mrp' => $batchMrp,
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
                'is_freight_applicable' => (bool) $order->is_freight_applicable,
                'freight_mode' => $order->freight_mode ?: 'amount',
                'freight_percent' => $order->freight_percent !== null ? (float) $order->freight_percent : null,
                'freight_amount' => (float) ($order->freight_amount ?? 0),
                'freight_tax_rate' => (float) ($order->freight_tax_rate ?? 0),
                'freight_notes' => $order->freight_notes,
                'receive_date' => $latestInwardDate,
                'billing_address_id' => $order->billing_address_id,
                'shipping_address_id' => $order->shipping_address_id,
                'billing_address' => $order->billing_address,
                'shipping_address' => $order->shipping_address,
                'items' => $items,
            ],
        ]);
    }

    public function supplierReceiptNotes(Request $request, Customer $supplier)
    {
        $user = $request->user();
        if ($user && $user->company_id && $supplier->company_id && $supplier->company_id !== $user->company_id) {
            return response()->json(['receipt_notes' => []], 403);
        }

        $receiptNotes = ReceiptNote::query()
            ->where('supplier_id', $supplier->id)
            ->when($user?->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->whereIn('status', [
                ReceiptNote::STATUS_RECEIVED,
                ReceiptNote::STATUS_PARTIALLY_INVOICED,
                ReceiptNote::STATUS_PARTIALLY_RETURNED,
            ])
            ->with(['items.product', 'warehouse'])
            ->latest('receipt_date')
            ->get()
            ->filter(fn (ReceiptNote $rn) => $rn->pending_invoice_qty > 0.0001)
            ->map(fn (ReceiptNote $rn) => [
                'id' => $rn->id,
                'receipt_note_no' => $rn->receipt_note_no,
                'receipt_date' => $rn->receipt_date->format('d/m/Y'),
                'supplier_challan_no' => $rn->supplier_challan_no,
                'warehouse_id' => $rn->warehouse_id,
                'warehouse_name' => $rn->warehouse?->name,
                'pending_qty' => $rn->pending_invoice_qty,
                'label' => "{$rn->receipt_note_no}".($rn->supplier_challan_no ? " (Challan: {$rn->supplier_challan_no})" : "")." - Pending: {$rn->pending_invoice_qty} qty ({$rn->receipt_date->format('d M Y')})",
            ])
            ->values();

        return response()->json([
            'receipt_notes' => $receiptNotes,
        ]);
    }

    public function receiptNoteData(ReceiptNote $receiptNote)
    {
        $user = auth()->user();
        if ($user && $user->company_id && $receiptNote->company_id && $receiptNote->company_id !== $user->company_id) {
            abort(403, 'Unauthorized');
        }

        $receiptNote->load(['items.product.baseUom', 'items.uom', 'items.serials', 'supplier', 'warehouse']);

        $items = $receiptNote->items->map(function (ReceiptNoteItem $item) {
            $pending = $item->pending_invoice_qty;
            if ($pending <= 0.0001) {
                return null;
            }

            $product = $item->product;
            $taxPercent = (float) ($product?->tax_rate ?? 18);
            $unitCost = (float) $item->unit_cost;
            $purchasePriceInclusive = round($unitCost * (1 + ($taxPercent / 100)), 4);
            $batchMrp = (float) ($product?->mrp ?? 0);
            $purchaseDiscountPercent = $batchMrp > 0 ? round((($batchMrp - $purchasePriceInclusive) / $batchMrp) * 100, 2) : 0;

            $serials = $item->serials->pluck('serial_number')->all();

            return [
                'receipt_note_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $product?->name ?? 'Product',
                'unit' => $item->uom?->short_name ?? $product?->unit ?? 'PCS',
                'tracking_type' => $product?->tracking_type ?? 'none',
                'is_serial_tracked' => $product?->isSerialTracked() ?? false,
                'track_serial' => $product?->isSerialTracked() ?? false,
                'quantity' => $pending,
                'ordered_quantity' => (float) $item->ordered_qty,
                'received_qty' => (float) $item->received_qty,
                'unit_cost' => $unitCost,
                'purchase_price_inclusive' => $purchasePriceInclusive,
                'purchase_discount_percent' => $purchaseDiscountPercent,
                'cgst_percent' => round($taxPercent / 2, 2),
                'sgst_percent' => round($taxPercent / 2, 2),
                'cgst_amount' => round(($pending * $unitCost) * ($taxPercent / 200), 2),
                'sgst_amount' => round(($pending * $unitCost) * ($taxPercent / 200), 2),
                'tax_amount' => round(($pending * $unitCost) * ($taxPercent / 100), 2),
                'line_total' => round(($pending * $unitCost) * (1 + ($taxPercent / 100)), 2),
                'batch_number' => $item->batch_no ?? '',
                'selling_price' => (float) ($product?->selling_price ?? 0),
                'batch_mrp' => $batchMrp,
                'serials' => $serials,
                'serial_numbers' => $serials,
            ];
        })->filter()->values();

        return response()->json([
            'receipt_note' => [
                'id' => $receiptNote->id,
                'receipt_note_no' => $receiptNote->receipt_note_no,
                'receipt_date' => $receiptNote->receipt_date->toDateString(),
                'supplier_challan_no' => $receiptNote->supplier_challan_no,
                'supplier_challan_date' => $receiptNote->supplier_challan_date?->toDateString(),
                'supplier_id' => $receiptNote->supplier_id,
                'warehouse_id' => $receiptNote->warehouse_id,
                'purchase_order_id' => $receiptNote->purchase_order_id,
                'remarks' => $receiptNote->remarks,
                'items' => $items,
            ],
        ]);
    }

    public function preview(PurchaseInvoice $invoice): View
    {
        $invoice->load([
            'items.product',
            'items.uom',
            'items.serials',
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

    public function productDetails(Product $product): JsonResponse
    {
        $product->loadMissing(['baseUom', 'productType']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit ?? ($product->baseUom?->name ?? ($product->baseUom?->code ?? 'PCS')),
                'tax_rate' => (float) ($product->tax_rate ?? 0),
                'mrp' => (float) ($product->mrp ?? ($product->calculation_mrp ?? 0)),
                'purchase_price' => (float) ($product->purchase_price ?? 0),
                'tracking_type' => $product->tracking_type ?? 'none',
                'tracking_types' => $product->tracking_types,
                'is_serial_tracked' => $product->isSerialTracked(),
                'track_serial' => $product->isSerialTracked(),
                'is_batch_tracked' => $product->isBatchTracked(),
            ],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function assertTrackedSerials(array $items): void
    {
        $products = Product::whereIn('id', collect($items)->pluck('product_id')->filter())->get()->keyBy('id');
        $errors = [];
        $allTrackedSerials = [];

        foreach ($items as $index => $item) {
            $product = $products->get($item['product_id'] ?? null);
            if (! $product?->isSerialTracked()) {
                continue;
            }

            $needed = min(30, max(1, (int) round((float) ($item['quantity'] ?? 1))));
            $serials = collect($item['serial_numbers'] ?? [])
                ->map(fn ($serial) => trim((string) $serial))
                ->filter()
                ->values();

            if ($serials->count() < $needed) {
                $errors["items.{$index}.serial_numbers"] = ($product->name ?: 'This product').' needs '.$needed.' serial number'.($needed === 1 ? '' : 's').'.';
            } elseif ($serials->unique()->count() < $serials->count()) {
                $errors["items.{$index}.serial_numbers"] = 'Duplicate serial numbers entered for '.($product->name ?: 'this product').'.';
            }

            foreach ($serials as $s) {
                $lower = strtolower($s);
                if (in_array($lower, $allTrackedSerials, true)) {
                    $errors["items.{$index}.serial_numbers"] = "Serial number '{$s}' is repeated across multiple invoice items.";
                }
                $allTrackedSerials[] = $lower;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function invoiceProducts()
    {
        return Product::with(['baseUom', 'productType'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}