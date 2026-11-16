<?php

namespace App\Http\Controllers\Sales;

use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Purchasing\Services\SerialBatchService;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Services\PriceMasterService;
use App\Domains\Master\Services\ProductDiscountService;
use App\Domains\Master\Services\SalePricingService;
use App\Domains\Organization\Services\FinancialYearService;
use App\Domains\Organization\Models\Company;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Payment\Services\PaymentLinkService;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\EInvoice;
use App\Domains\Sales\Models\EWayBill;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Sales\Services\GstComplianceService;
use App\Domains\Sales\Services\InvoiceNumberGenerator;
use App\Domains\Sales\Services\MastersIndiaGspService;
use App\Domains\Scheme\Models\Scheme;
use App\Domains\Scheme\Services\SchemeService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DueDateService;
use App\Support\WarrantyPeriod;
use App\Support\QrCodeRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
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

class InvoiceController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected InvoiceNumberGenerator $invoiceNumberGenerator,
        protected PriceMasterService $priceMasterService,
        protected SalePricingService $salePricingService,
        protected StockMovementService $stockMovementService,
        protected OutstandingLedgerService $outstandingLedgerService,
        protected PaymentLinkService $paymentLinkService,
        protected DueDateService $dueDateService,
        protected ProductDiscountService $productDiscountService,
        protected MastersIndiaGspService $mastersIndiaService,
        protected GstComplianceService $complianceService,
        protected FinancialYearService $financialYearService,
        protected SchemeService $schemeService,
        protected SerialBatchService $serialBatchService,
    ) {}

    /**
     * Resolve salespersons dynamically from roles or employees.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public static function getSalespersons(): \Illuminate\Database\Eloquent\Collection
    {
        $salespeople = User::query()
            ->where(function ($q) {
                $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['salesperson', 'sales_person', 'sales-executive', 'sales-manager']))
                  ->orWhereHas('employee', fn ($e) => $e->where('is_salesperson', true))
                  ->orWhereIn('name', ['Kapil Dhir', 'Abdul Majid']);
            })
            ->orderBy('name')
            ->get();

        if ($salespeople->isEmpty()) {
            $salespeople = User::orderBy('name')->get();
        }

        return $salespeople;
    }

    /**
     * Resolve transporter master records from party master.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Customer>
     */
    public static function getTransporters(): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function index(Request $request): View|Response
    {
        $query = Invoice::query()
            ->with(['customer', 'salesperson', 'eInvoice', 'eWayBill'])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('salesperson_id'), function ($q) use ($request) {
                if ($request->salesperson_id === 'unassigned') {
                    $q->whereNull('salesperson_id');
                } else {
                    $q->where('salesperson_id', $request->salesperson_id);
                }
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('einvoice_status'), fn ($q) => $q->where('einvoice_status', $request->einvoice_status))
            ->when($request->filled('eway_bill_status'), fn ($q) => $q->where('eway_bill_status', $request->eway_bill_status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('invoice_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('invoice_date', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['invoice_no', 'notes'],
            ['customer' => ['name', 'code', 'phone']]
        );

        $allowedSorts = [
            'invoice_no' => 'invoice_no',
            'invoice_date' => 'invoice_date',
            'status' => 'status',
            'grand_total' => 'grand_total',
            'created_at' => 'created_at',
            'salesperson' => function ($q, $dir) {
                $q->leftJoin('users as sp_users', 'invoices.salesperson_id', '=', 'sp_users.id')
                  ->orderBy('sp_users.name', $dir)
                  ->select('invoices.*');
            },
            'customer' => function ($q, $dir) {
                $q->join('customers', 'invoices.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $dir)
                  ->select('invoices.*');
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
                return DocumentExporter::exportSalesInvoicesListing($query->get(), $exportFormat);
            }
        }

        $invoices = $query->paginate(15)->withQueryString();

        return view('sales.invoices.index', [
            'invoices' => $invoices,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'salespersons' => self::getSalespersons(),
            'salespersonId' => $request->input('salesperson_id'),
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'einvoiceStatus' => $request->string('einvoice_status'),
            'ewayBillStatus' => $request->string('eway_bill_status'),
            'customerId' => $request->input('customer_id'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(Request $request): View
    {
        $company = Company::resolveCurrent() ?? Company::query()->first();

        $selectedOrderId = $request->filled('order_id') ? (int) $request->order_id : null;
        $order = $selectedOrderId
            ? Order::with(['items.product.baseUom', 'items.uom', 'customer', 'billingAddress', 'shippingAddress'])->find($selectedOrderId)
            : null;

        $selectedCustomerId = old('customer_id', $order?->customer_id);
        $customer = $selectedCustomerId ? Customer::find($selectedCustomerId) : null;
        $initialOrders = $customer ? $this->getEligibleOrdersForCustomer($customer, $request->user()) : collect();

        if ($order && ! $initialOrders->contains('id', $order->id)) {
            $totalOrdered = (float) $order->items->sum('quantity');
            $invoicedQty = (float) DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->where('invoices.order_id', $order->id)
                ->where('invoices.status', '!=', 'cancelled')
                ->sum('invoice_items.quantity');
            $rem = max(0, $totalOrdered - $invoicedQty);

            $initialOrders->prepend([
                'id' => $order->id,
                'order_no' => $order->order_no,
                'order_date' => $order->order_date?->format('d M Y') ?? '—',
                'grand_total' => (float) $order->grand_total,
                'status' => ucfirst(str_replace('_', ' ', $order->status)),
                'credit_days' => $order->customer?->credit_days ?? 0,
                'remaining_qty' => $rem,
                'total_qty' => $totalOrdered,
                'label' => sprintf(
                    '%s (%s) - ₹%s%s',
                    $order->order_no,
                    $order->order_date?->format('d M Y') ?? 'No date',
                    number_format((float) $order->grand_total, 2),
                    $invoicedQty > 0 ? " [Remaining: {$rem}/{$totalOrdered}]" : ''
                ),
            ]);
        }

        $savedDraft = $request->session()->get('tax_invoice_draft');
        if ($savedDraft && ($request->filled('new_product_id') || $request->query('returned_from') === 'product_create')) {
            $request->session()->forget('tax_invoice_draft');
        }

        $newProduct = null;
        if ($request->filled('new_product_id')) {
            $newProduct = Product::with(['baseUom', 'productType'])
                ->find($request->input('new_product_id'));
        }

        $products = Product::with(['baseUom', 'productType'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($newProduct && ! $products->contains('id', $newProduct->id)) {
            $products->prepend($newProduct);
        }

        return view('sales.invoices.create', [
            'order' => $order,
            'initialOrders' => $initialOrders,
            'company' => $company,
            'customers' => Customer::with('customerType')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'transporters' => self::getTransporters(),
            'salespersons' => self::getSalespersons(),

            'products' => $products,
            'newProduct' => $newProduct,
            'targetRow' => $request->query('target_row'),
            'savedDraft' => $savedDraft,

            'uoms' => Uom::where('is_active', true)
                ->orderBy('name')
                ->get(),

            'schemes' => Scheme::with(['brand', 'slabs', 'products'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),

            'stateOptions' => \App\Support\IndianStates::options(),
            'sellingTermsAndConditions' => $company?->selling_terms_and_conditions,
        ]);
    }

    public function saveDraft(Request $request): JsonResponse
    {
        $draft = $request->input('draft', []);
        $request->session()->put('tax_invoice_draft', $draft);

        return response()->json([
            'success' => true,
            'message' => 'Invoice draft saved successfully.',
        ]);
    }

    public function customerSalesOrders(Request $request, Customer $customer)
    {
        $user = $request->user();
        if ($user && $user->company_id && $customer->company_id && (int) $customer->company_id !== (int) $user->company_id) {
            return response()->json(['orders' => []], 403);
        }

        $orders = $this->getEligibleOrdersForCustomer($customer, $user);

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function getEligibleOrdersForCustomer(Customer $customer, ?User $user = null): Collection
    {
        $query = Order::query()
            ->with(['items.product', 'customer'])
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['approved'])
            ->whereDoesntHave('invoice', fn ($q) => $q->where('status', '!=', 'cancelled'));

        $orders = $query->orderByDesc('order_date')->orderByDesc('id')->get();

        return $orders->map(function (Order $order) {
            $totalOrdered = (float) $order->items->sum('quantity');
            $invoicedQty = (float) DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->where('invoices.order_id', $order->id)
                ->where('invoices.status', '!=', 'cancelled')
                ->sum('invoice_items.quantity');
            $remainingQty = max(0, $totalOrdered - $invoicedQty);

            $label = sprintf(
                '%s (%s) - ₹%s%s',
                $order->order_no,
                $order->order_date?->format('d M Y') ?? 'No date',
                number_format((float) $order->grand_total, 2),
                $invoicedQty > 0 ? " [Remaining: {$remainingQty}/{$totalOrdered}]" : ''
            );

            return [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'order_date' => $order->order_date?->format('d M Y') ?? '—',
                'grand_total' => (float) $order->grand_total,
                'status' => ucfirst(str_replace('_', ' ', $order->status)),
                'credit_days' => $order->customer?->credit_days ?? 0,
                'remaining_qty' => $remainingQty,
                'total_qty' => $totalOrdered,
                'label' => $label,
            ];
        })->filter(fn ($o) => $o['total_qty'] == 0 || $o['remaining_qty'] > 0)->values();
    }

    public function salesOrderData(Request $request, Order $order)
    {
        $user = auth()->user();
        if ($user && $user->company_id && $order->customer?->company_id && (int) $order->customer->company_id !== (int) $user->company_id) {
            abort(403, 'Unauthorized');
        }

        if (! in_array($order->status, ['approved'], true)) {
            return response()->json([
                'error' => "Sales order in '{$order->status}' status cannot be invoiced.",
            ], 422);
        }

        $order->load(['items.product.baseUom', 'items.uom', 'customer', 'warehouse', 'billingAddress', 'shippingAddress']);

        $invoicedByProduct = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.order_id', $order->id)
            ->where('invoices.status', '!=', 'cancelled')
            ->select('invoice_items.product_id', DB::raw('SUM(invoice_items.quantity) as invoiced_qty'))
            ->groupBy('invoice_items.product_id')
            ->pluck('invoiced_qty', 'product_id');

        $items = $order->items->map(function (OrderItem $item) use ($invoicedByProduct) {
            $alreadyInvoiced = (float) ($invoicedByProduct[$item->product_id] ?? 0);
            $remaining = max(0, (float) $item->quantity - $alreadyInvoiced);
            $product = $item->product;
            $taxRate = (float) ($product?->tax_rate ?? 0);

            return [
                'product_id' => $item->product_id,
                'product_name' => $product?->name ?? 'Item',
                'uom_id' => $item->uom_id ?? $product?->base_uom_id,
                'unit' => $item->uom?->code ?? $product?->baseUom?->code ?? 'PCS',
                'tracking_type' => $product?->tracking_type ?? 'none',
                'quantity' => $alreadyInvoiced > 0 ? $remaining : (float) $item->quantity,
                'ordered_quantity' => (float) $item->quantity,
                'unit_price' => (float) ($item->unit_price ?? $product?->selling_price ?? 0),
                'tax_rate' => $taxRate,
                'cgst_percent' => round($taxRate / 2, 2),
                'sgst_percent' => round($taxRate / 2, 2),
                'discount_type' => 'flat',
                'discount_value' => 0,
                'discount_amount' => 0,
                'line_total' => (float) ($item->line_total ?? 0),
                'batch_no' => '',
                'serial_numbers' => is_array($item->serial_numbers) ? implode(', ', $item->serial_numbers) : ($item->serial_numbers ?? ''),
            ];
        })->filter(fn ($it) => $it['quantity'] > 0)->values();

        return response()->json([
            'order' => [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'order_number' => $order->order_no,
                'customer_id' => $order->customer_id,
                'salesperson_id' => $order->salesperson_id,
                'salesperson_name' => $order->salesperson?->name ?? $order->salesperson_name,
                'warehouse_id' => $order->warehouse_id,
                'credit_days' => $order->customer?->credit_days ?? 0,
                'reference_no' => $order->reference_no,
                'other_references' => $order->reference_no,
                'order_no' => $order->order_no,
                'payment_terms' => $order->payment_terms ?? $order->customer?->payment_terms,
                'terms_of_delivery' => $order->terms_of_delivery,
                'destination' => $order->shippingAddress?->city ?? $order->billingAddress?->city ?? $order->customer?->city,
                'bill_to_place' => $order->billingAddress?->city ?? $order->customer?->city,
                'ship_to_place' => $order->shippingAddress?->city ?? $order->customer?->shipping_city ?? $order->billingAddress?->city ?? $order->customer?->city,
                'notes' => $order->notes,
                'billing_address_id' => $order->billing_address_id,
                'shipping_address_id' => $order->shipping_address_id,
                'billing_address' => $order->billing_address,
                'shipping_address' => $order->shipping_address,
                'delivery_state' => $order->shippingAddress?->state ?? $order->billingAddress?->state ?? $order->customer?->state,
                'items' => $items,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->has('eway_bill_required')) {
            $request->merge(['eway_bill_required' => strtolower((string) $request->input('eway_bill_required'))]);
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:orders,id',
            'salesperson_id' => 'nullable|exists:users,id',
            'scheme_id' => 'nullable|exists:schemes,id',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'vehicle_no' => 'nullable|string|max:60',
            'motor_vehicle_no' => 'nullable|string|max:60',
            'transport_mode' => 'nullable|string|max:150',
            'dispatched_through' => 'nullable|string|max:150',
            'reference_no' => 'nullable|string|max:255',
            'other_references' => 'nullable|string|max:255',
            'delivery_state' => 'nullable|string|max:100',
            'bill_to_place' => 'nullable|string|max:100',
            'ship_to_place' => 'nullable|string|max:100',
            'credit_days' => 'nullable|integer|min:0',
            'due_date' => 'nullable|date',
            'delivery_note_no' => 'nullable|string|max:255',
            'delivery_note_na' => 'nullable|boolean',
            'dispatch_doc_no' => 'nullable|string|max:100',
            'destination' => 'nullable|string|max:150',
            'carrier_name' => 'nullable|string|max:150',
            'lr_rr_no' => 'nullable|string|max:100',
            'lr_rr_date' => 'nullable|date',
            'order_no' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:255',
            'terms_of_delivery' => 'nullable|string|max:3000',
            'billing_address_id' => 'nullable|exists:party_addresses,id',
            'shipping_address_id' => 'nullable|exists:party_addresses,id',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'universal_discount_type' => 'nullable|in:percent,flat',
            'universal_discount_value' => 'nullable|numeric|min:0',
            'price_type' => 'nullable|in:exclusive,inclusive',
            'tax_type' => 'nullable|in:intra,inter,cgst_sgst,igst',
            'round_off' => 'nullable|numeric|between:-500,500',
            'eway_bill_required' => 'nullable|in:auto,yes,no',
            'transporter_id' => 'nullable|string|max:25',
            'transporter_name' => 'nullable|string|max:255',
            'distance_km' => 'nullable|integer|min:0|max:9999',
            'vehicle_type' => 'nullable|in:R,O',
            'eway_bill_no' => 'nullable|string|max:50',
            'eway_bill_date' => 'nullable|date',
            'eway_sub_type' => 'nullable|string|max:50',
            'eway_doc_type' => 'nullable|string|max:50',
            'eway_supply_type' => 'nullable|string|max:50',
            'consignor_name' => 'nullable|string|max:255',
            'consignor_gstin' => 'nullable|string|max:25',
            'consignor_state' => 'nullable|string|max:100',
            'consignor_address_type' => 'nullable|string|max:100',
            'consignor_address_line_1' => 'nullable|string|max:255',
            'consignor_address_line_2' => 'nullable|string|max:255',
            'consignor_pincode' => 'nullable|string|max:20',
            'consignor_city' => 'nullable|string|max:100',
            'consignor_actual_state' => 'nullable|string|max:100',
            'consignee_name' => 'nullable|string|max:255',
            'consignee_gstin' => 'nullable|string|max:25',
            'consignee_state' => 'nullable|string|max:100',
            'consignee_address_type' => 'nullable|string|max:100',
            'consignee_address_line_1' => 'nullable|string|max:255',
            'consignee_address_line_2' => 'nullable|string|max:255',
            'consignee_pincode' => 'nullable|string|max:20',
            'consignee_city' => 'nullable|string|max:100',
            'consignee_actual_state' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:percent,flat',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'items.*.batch_no' => 'nullable|string|max:60',
            'items.*.serial_numbers' => 'nullable',
        ]);

        // Enforce FY period lock — refuse to post into a closed/unmapped period.
        $this->financialYearService->assertOpen($validated['invoice_date']);

        try {
            $invoice = DB::transaction(function () use ($validated) {
                $customer = Customer::findOrFail($validated['customer_id']);

                $linkedOrder = null;
                if (!empty($validated['order_id'])) {
                    $linkedOrder = Order::with('items')->findOrFail($validated['order_id']);

                    if ((int) $linkedOrder->customer_id !== (int) $customer->id) {
                        throw ValidationException::withMessages([
                            'order_id' => ['The selected sales order does not belong to the selected customer.'],
                        ]);
                    }

                    $user = auth()->user();
                    $orderCompanyId = $linkedOrder->warehouse?->company_id ?? $linkedOrder->customer?->company_id;
                    if ($user && $user->company_id && $orderCompanyId && (int) $orderCompanyId !== (int) $user->company_id) {
                        throw ValidationException::withMessages([
                            'order_id' => ['You are not authorized to access this sales order.'],
                        ]);
                    }

                    if (!in_array($linkedOrder->status, ['approved'], true)) {
                        throw ValidationException::withMessages([
                            'order_id' => ["Sales order with status '{$linkedOrder->status}' cannot be invoiced."],
                        ]);
                    }

                    $existingInvoice = Invoice::where('order_id', $linkedOrder->id)
                        ->where('status', '!=', 'cancelled')
                        ->first();
                    if ($existingInvoice) {
                        throw ValidationException::withMessages([
                            'order_id' => ['This sales order has already been invoiced.'],
                        ]);
                    }

                    // Validate line item quantities do not exceed remaining order quantities
                    $orderItemsByProduct = $linkedOrder->items->groupBy('product_id');
                    $invoicedByProduct = DB::table('invoice_items')
                        ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                        ->where('invoices.order_id', $linkedOrder->id)
                        ->where('invoices.status', '!=', 'cancelled')
                        ->select('invoice_items.product_id', DB::raw('SUM(invoice_items.quantity) as invoiced_qty'))
                        ->groupBy('invoice_items.product_id')
                        ->pluck('invoiced_qty', 'product_id');

                    foreach ($validated['items'] as $itemIdx => $valItem) {
                        $pId = $valItem['product_id'];
                        $orderLines = $orderItemsByProduct->get($pId);
                        if ($orderLines) {
                            $totalOrdQty = (float) $orderLines->sum('quantity');
                            $prevInvoiced = (float) ($invoicedByProduct[$pId] ?? 0);
                            $remainingMax = max(0, $totalOrdQty - $prevInvoiced);
                            if ((float) $valItem['quantity'] > $remainingMax + 0.0001) {
                                throw ValidationException::withMessages([
                                    "items.{$itemIdx}.quantity" => ["The quantity exceeds the remaining ordered quantity ({$remainingMax})."],
                                ]);
                            }
                        }
                    }
                }

                // Verify address ownership
                $billingAddress = null;
                if (!empty($validated['billing_address_id'])) {
                    $billingAddress = \App\Domains\Master\Models\PartyAddress::where('id', $validated['billing_address_id'])
                        ->where('customer_id', $customer->id)
                        ->first();
                    if (!$billingAddress) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'billing_address_id' => ['The selected billing address does not belong to the selected customer.'],
                        ]);
                    }
                }

                $shippingAddress = null;
                if (!empty($validated['shipping_address_id'])) {
                    $shippingAddress = \App\Domains\Master\Models\PartyAddress::where('id', $validated['shipping_address_id'])
                        ->where('customer_id', $customer->id)
                        ->first();
                    if (!$shippingAddress) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'shipping_address_id' => ['The selected delivery address does not belong to the selected customer.'],
                        ]);
                    }
                }

                if (!$billingAddress) {
                    $billingAddress = $customer->defaultBillingAddress();
                }
                if (!$shippingAddress) {
                    $shippingAddress = $customer->defaultDeliveryAddress() ?? $billingAddress;
                }

                $billingSnapshot = !empty($validated['billing_address'])
                    ? $validated['billing_address']
                    : ($billingAddress ? $billingAddress->formatSnapshot() : $customer->address);

                $shippingSnapshot = !empty($validated['shipping_address'])
                    ? $validated['shipping_address']
                    : ($shippingAddress ? $shippingAddress->formatSnapshot() : ($billingSnapshot ?: $customer->address));

                $deliveryState = !empty($validated['delivery_state'])
                    ? trim((string)$validated['delivery_state'])
                    : trim((string)($shippingAddress?->state ?? ($customer->shipping_state ?? ($customer->state ?? ''))));
                $company = Company::find($customer->company_id) ?? Company::resolveCurrent() ?? Company::first();
                $sellerState = \App\Domains\Master\Services\GstTaxCalculationService::resolveSellerState($company);

                $isIntraState = \App\Domains\Master\Services\GstTaxCalculationService::isIntraState($sellerState, $deliveryState);
                if (isset($validated['tax_type'])) {
                    if (in_array($validated['tax_type'], ['inter', 'igst'], true)) {
                        $isIntraState = false;
                    } elseif (in_array($validated['tax_type'], ['intra', 'cgst_sgst'], true)) {
                        $isIntraState = true;
                    }
                }
                $taxType = $isIntraState ? 'intra' : 'inter';

                $priceType = $validated['price_type'] ?? 'exclusive';
                $pricing = $this->salePricingService->price(
                    $customer,
                    $validated['items'],
                    (float) ($validated['discount_amount'] ?? 0),
                    $priceType,
                    $isIntraState
                );
                $due = $this->dueDateService->forSalesInvoice($customer, $validated['invoice_date']);

                // Universal (header-level) discount on top of any item-level discounts already reduced in $pricing['subtotal'].
                $universalType = $validated['universal_discount_type'] ?? 'flat';
                $universalValue = (float) ($validated['universal_discount_value'] ?? 0);
                $universalDiscount = $this->productDiscountService->universalDiscount((float) $pricing['subtotal'], $universalType, $universalValue);

                // The pricing service returns 'discount' as the item-level total; grand total gets universal deducted on top.
                $itemDiscountTotal = (float) $pricing['discount'];
                $taxableAmount = max(0, (float) $pricing['subtotal'] - $universalDiscount);
                $totalTax = (float) $pricing['tax'];
                $totalDiscount = round($itemDiscountTotal + $universalDiscount, 2);

                $totalBeforeRound = round($taxableAmount + $totalTax, 2);
                $roundOff = isset($validated['round_off']) && $validated['round_off'] !== null && $validated['round_off'] !== ''
                    ? round((float) $validated['round_off'], 2)
                    : (float) $pricing['round_off'];
                $adjustedTotal = round(max(0, $totalBeforeRound + $roundOff), 2);

                // Scheme evaluation
                $scheme = null;
                $schemeBenefitAmount = 0;
                $schemeQualifiedValue = 0;
                $schemeSlab = null;

                if (!empty($validated['scheme_id'])) {
                    $scheme = Scheme::with(['slabs', 'products'])->findOrFail($validated['scheme_id']);

                    $evalItems = collect($pricing['lines'])->map(fn ($l) => [
                        'product_id' => $l['product']->id,
                        'brand_id' => $l['product']->brand_id,
                        'quantity' => (float) $l['quantity'],
                        'unit_price' => (float) $l['unitPrice'],
                        'discount_amount' => (float) $l['discount'],
                        'line_total' => (float) $l['lineTotal'],
                    ])->all();

                    $eval = $this->schemeService->evaluateScheme($scheme, $validated['invoice_date'], $evalItems);
                    if (! $eval['eligible']) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'scheme_id' => [$eval['reason'] ?? 'The selected scheme is not eligible for this invoice.'],
                        ]);
                    }

                    $schemeBenefitAmount = (float) $eval['benefit'];
                    $schemeQualifiedValue = (float) $eval['qualified'];
                    $schemeSlab = $eval['slab'];
                }

                    $deliveryNoteNa = !empty($validated['delivery_note_na']);
                    $deliveryNoteNo = $deliveryNoteNa ? null : (isset($validated['delivery_note_no']) ? trim((string)$validated['delivery_note_no']) : null);
                    if ($deliveryNoteNo === '') {
                        $deliveryNoteNo = null;
                    }

                    $vehicleNo = trim((string)($validated['motor_vehicle_no'] ?? ($validated['vehicle_no'] ?? ''))) ?: null;
                    $transportMode = trim((string)($validated['transport_mode'] ?? ($validated['dispatched_through'] ?? '1 - Road'))) ?: '1 - Road';
                    $dispatchedThrough = trim((string)($validated['dispatched_through'] ?? $transportMode)) ?: null;
                    $orderNo = trim((string)($validated['order_no'] ?? ($linkedOrder?->order_no ?? ''))) ?: null;
                    $paymentTerms = trim((string)($validated['payment_terms'] ?? ($linkedOrder?->payment_terms ?? ($customer->payment_terms ?? '')))) ?: null;
                    $otherReferences = trim((string)($validated['other_references'] ?? ($validated['reference_no'] ?? ($linkedOrder?->reference_no ?? '')))) ?: null;
                    $termsOfDelivery = trim((string)($validated['terms_of_delivery'] ?? ($linkedOrder?->terms_of_delivery ?? ''))) ?: null;
                    $dispatchDocNo = trim((string)($validated['dispatch_doc_no'] ?? '')) ?: null;
                    $destination = trim((string)($validated['destination'] ?? '')) ?: null;
                    $carrierName = trim((string)($validated['carrier_name'] ?? '')) ?: null;
                    $lrRrNo = trim((string)($validated['lr_rr_no'] ?? '')) ?: null;
                    $lrRrDate = !empty($validated['lr_rr_date']) ? $validated['lr_rr_date'] : null;

                    $salespersonId = array_key_exists('salesperson_id', $validated)
                        ? (!empty($validated['salesperson_id']) ? (int) $validated['salesperson_id'] : null)
                        : ($linkedOrder?->salesperson_id ?? null);

                    $buyerGstin = trim((string) ($billingAddress?->gstin ?: ($customer->gstin ?: '')));
                    $isB2B = $buyerGstin !== '' && strtoupper($buyerGstin) !== 'URP' && strlen($buyerGstin) === 15;
                    $initialEinvoiceStatus = $isB2B ? 'pending' : 'not_applicable';

                    $ewayReq = $validated['eway_bill_required'] ?? 'auto';
                    $isOverEway = $adjustedTotal >= GstComplianceService::EWAY_BILL_THRESHOLD;
                    $ewayBillNo = trim((string)($validated['eway_bill_no'] ?? '')) ?: null;
                    $ewayBillDate = !empty($validated['eway_bill_date']) ? $validated['eway_bill_date'] : null;
                    $ewaySubType = trim((string)($validated['eway_sub_type'] ?? 'Supply')) ?: 'Supply';
                    $ewayDocType = trim((string)($validated['eway_doc_type'] ?? 'Tax Invoice')) ?: 'Tax Invoice';
                    $ewaySupplyType = trim((string)($validated['eway_supply_type'] ?? 'Outward')) ?: 'Outward';

                    $consignorName = trim((string)($validated['consignor_name'] ?? ($company?->name ?? ''))) ?: null;
                    $consignorGstin = trim((string)($validated['consignor_gstin'] ?? ($company?->gstin ?? ''))) ?: null;
                    $consignorState = trim((string)($validated['consignor_state'] ?? ($company?->state ?? ''))) ?: null;
                    $consignorAddressType = trim((string)($validated['consignor_address_type'] ?? 'Principal Place of Business')) ?: 'Principal Place of Business';
                    $consignorLine1 = trim((string)($validated['consignor_address_line_1'] ?? ($company?->address ?? ''))) ?: null;
                    $consignorLine2 = trim((string)($validated['consignor_address_line_2'] ?? '')) ?: null;
                    $consignorPincode = trim((string)($validated['consignor_pincode'] ?? ($company?->pincode ?? ''))) ?: null;
                    $consignorCity = trim((string)($validated['consignor_city'] ?? '')) ?: null;
                    $consignorActualState = trim((string)($validated['consignor_actual_state'] ?? ($company?->state ?? ''))) ?: null;

                    $targetConsigneeAddr = $shippingAddress ?? $billingAddress;
                    $consigneeName = trim((string)($validated['consignee_name'] ?? ($targetConsigneeAddr?->name ?? $customer->name))) ?: null;
                    $consigneeGstin = trim((string)($validated['consignee_gstin'] ?? ($targetConsigneeAddr?->gstin ?? ($customer->gstin ?? '')))) ?: null;
                    $consigneeState = trim((string)($validated['consignee_state'] ?? ($targetConsigneeAddr?->state ?? ($customer->shipping_state ?? ($customer->state ?? ''))))) ?: null;
                    $consigneeAddressType = trim((string)($validated['consignee_address_type'] ?? ($targetConsigneeAddr?->label ?? 'Delivery'))) ?: 'Delivery';
                    $consigneeLine1 = trim((string)($validated['consignee_address_line_1'] ?? ($targetConsigneeAddr?->address_line_1 ?? ($targetConsigneeAddr?->address ?? ($customer->shipping_address ?? ($customer->address ?? '')))))) ?: null;
                    $consigneeLine2 = trim((string)($validated['consignee_address_line_2'] ?? ($targetConsigneeAddr?->address_line_2 ?? ''))) ?: null;
                    $consigneePincode = trim((string)($validated['consignee_pincode'] ?? ($targetConsigneeAddr?->pincode ?? ($customer->shipping_pincode ?? ($customer->pincode ?? ''))))) ?: null;
                    $consigneeCity = trim((string)($validated['consignee_city'] ?? ($targetConsigneeAddr?->city ?? ($customer->shipping_city ?? ($customer->city ?? ''))))) ?: null;
                    $consigneeActualState = trim((string)($validated['consignee_actual_state'] ?? ($targetConsigneeAddr?->state ?? ($customer->shipping_state ?? ($customer->state ?? ''))))) ?: null;

                    $billToPlace = trim((string)($validated['bill_to_place'] ?? ($billingAddress?->city ?? ($customer->city ?? '')))) ?: null;
                    $shipToPlace = trim((string)($validated['ship_to_place'] ?? ($shippingAddress?->city ?? ($customer->shipping_city ?? ($consigneeCity ?? ($billToPlace ?? '')))))) ?: null;

                    $initialEwayStatus = $ewayBillNo ? 'generated' : (($isOverEway || $ewayReq === 'yes') ? 'pending' : 'not_applicable');

                    $invoice = Invoice::create([
                        'company_id' => $customer->company_id ?? \App\Domains\Organization\Models\Company::resolveCurrent()?->id,
                        'invoice_no' => $this->invoiceNumberGenerator->generate(),
                        'customer_id' => $validated['customer_id'],
                        'order_id' => $linkedOrder?->id,
                        'scheme_id' => $scheme?->id,
                        'billing_address_id' => $billingAddress?->id,
                        'shipping_address_id' => $shippingAddress?->id,
                        'billing_address' => $billingSnapshot,
                        'shipping_address' => $shippingSnapshot,
                        'salesperson_id' => $salespersonId,
                        'invoice_date' => $validated['invoice_date'],
                        'due_date' => $validated['due_date'] ?? $due['due_date'],
                        'due_date_basis' => $due['due_date_basis'],
                        'due_date_source_date' => $due['due_date_source_date'],
                        'payment_terms' => $paymentTerms,
                        'credit_days' => isset($validated['credit_days']) ? (int) $validated['credit_days'] : $due['credit_days'],
                        'price_type' => $priceType,
                        'tax_type' => $taxType,
                        'status' => 'issued',
                        'subtotal' => $taxableAmount,
                        'taxable_amount' => $taxableAmount,
                        'discount_amount' => $totalDiscount,
                        'universal_discount_type' => $universalType,
                        'universal_discount_value' => $universalValue,
                        'item_discount_total' => $itemDiscountTotal,
                        'tax_amount' => $totalTax,
                        'cgst_amount' => $pricing['cgst_amount'],
                        'sgst_amount' => $pricing['sgst_amount'],
                        'igst_amount' => $pricing['igst_amount'],
                        'round_off' => $roundOff,
                        'grand_total' => $adjustedTotal,
                        'scheme_benefit_amount' => $schemeBenefitAmount,
                        'paid_amount' => 0,
                        'notes' => $validated['notes'] ?? null,
                        'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
                        'vehicle_no' => $vehicleNo,
                        'transport_mode' => $transportMode,
                        'reference_no' => $otherReferences,
                        'delivery_note_no' => $deliveryNoteNo,
                        'delivery_note_na' => $deliveryNoteNa,
                        'dispatch_doc_no' => $dispatchDocNo,
                        'dispatched_through' => $dispatchedThrough,
                        'destination' => $destination,
                        'carrier_name' => $carrierName,
                        'lr_rr_no' => $lrRrNo,
                        'lr_rr_date' => $lrRrDate,
                        'order_no' => $orderNo,
                        'other_references' => $otherReferences,
                        'terms_of_delivery' => $termsOfDelivery,
                        'delivery_state' => $deliveryState,
                        'place_of_supply_state' => $deliveryState,
                        'bill_to_place' => $billToPlace,
                        'ship_to_place' => $shipToPlace,
                        'einvoice_status' => $initialEinvoiceStatus,
                        'eway_bill_status' => $initialEwayStatus,
                        'eway_bill_required' => $ewayReq,
                        'eway_bill_no' => $ewayBillNo,
                        'eway_bill_date' => $ewayBillDate,
                        'eway_sub_type' => $ewaySubType,
                        'eway_doc_type' => $ewayDocType,
                        'eway_supply_type' => $ewaySupplyType,
                        'consignor_name' => $consignorName,
                        'consignor_gstin' => $consignorGstin,
                        'consignor_state' => $consignorState,
                        'consignor_address_type' => $consignorAddressType,
                        'consignor_address_line_1' => $consignorLine1,
                        'consignor_address_line_2' => $consignorLine2,
                        'consignor_pincode' => $consignorPincode,
                        'consignor_city' => $consignorCity,
                        'consignor_actual_state' => $consignorActualState,
                        'consignee_name' => $consigneeName,
                        'consignee_gstin' => $consigneeGstin,
                        'consignee_state' => $consigneeState,
                        'consignee_address_type' => $consigneeAddressType,
                        'consignee_address_line_1' => $consigneeLine1,
                        'consignee_address_line_2' => $consigneeLine2,
                        'consignee_pincode' => $consigneePincode,
                        'consignee_city' => $consigneeCity,
                        'consignee_actual_state' => $consigneeActualState,
                        'transporter_id' => $validated['transporter_id'] ?? null,
                        'transporter_name' => $validated['transporter_name'] ?? null,
                        'distance_km' => !empty($validated['distance_km']) ? (int) $validated['distance_km'] : null,
                        'vehicle_type' => $validated['vehicle_type'] ?? 'R',
                    ]);

                    if ($ewayBillNo) {
                        EWayBill::updateOrCreate(
                            ['invoice_id' => $invoice->id],
                            [
                                'status' => 'generated',
                                'provider' => 'manual',
                                'eway_bill_no' => $ewayBillNo,
                                'ewb_date' => $ewayBillDate ?? now(),
                                'distance_km' => !empty($validated['distance_km']) ? (int) $validated['distance_km'] : null,
                                'transporter_id' => $validated['transporter_id'] ?? null,
                                'transporter_name' => $validated['transporter_name'] ?? null,
                                'vehicle_no' => $vehicleNo,
                                'vehicle_type' => $validated['vehicle_type'] ?? 'R',
                                'transport_mode' => match (true) {
                                    str_contains(strtolower($transportMode), '2') || str_contains(strtolower($transportMode), 'rail') => '2',
                                    str_contains(strtolower($transportMode), '3') || str_contains(strtolower($transportMode), 'air') => '3',
                                    str_contains(strtolower($transportMode), '4') || str_contains(strtolower($transportMode), 'ship') => '4',
                                    str_contains(strtolower($transportMode), 'not applicable') || strtolower($transportMode) === 'na' => 'NA',
                                    default => '1',
                                },
                                'doc_no' => $lrRrNo,
                                'doc_date' => $lrRrDate,
                                'from_pincode' => $consignorPincode,
                                'to_pincode' => $consigneePincode,
                                'from_state' => $consignorState,
                                'to_state' => $consigneeState,
                            ]
                        );
                    }

                if ($scheme && $schemeBenefitAmount >= 0) {
                    $this->schemeService->applySchemeToInvoice($invoice, $scheme, $schemeQualifiedValue, $schemeSlab, $schemeBenefitAmount);
                }

                foreach ($pricing['lines'] as $idx => $line) {
                    $product = $line['product'];
                    $uom = $line['uom'];
                    $inputRow = $validated['items'][$idx] ?? [];
                    $serialsRaw = $inputRow['serial_numbers'] ?? null;
                    if (is_array($serialsRaw)) {
                        $serials = collect($serialsRaw)->map(fn ($s) => trim((string) $s))->filter()->values()->all();
                    } else {
                        $serials = WarrantyPeriod::split($serialsRaw);
                    }

                    $invoiceItem = InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $product->id,
                        'uom_id' => $uom->id,
                        'quantity' => $line['quantity'],
                        'price_type' => $line['price_type'] ?? $priceType,
                        'entered_unit_price' => $line['entered_unit_price'] ?? $line['unitPrice'],
                        'unit_price' => $line['unit_price'] ?? $line['unitPrice'],
                        'discount_amount' => $line['discount_amount'] ?? $line['discount'],
                        'discount_type' => $inputRow['discount_type'] ?? 'flat',
                        'discount_value' => (float) ($inputRow['discount_value'] ?? $line['discount']),
                        'taxable_amount' => $line['taxable_amount'] ?? $line['unitPrice'],
                        'tax_rate' => $line['tax_rate'] ?? 0,
                        'cgst_rate' => $line['cgst_rate'] ?? 0,
                        'sgst_rate' => $line['sgst_rate'] ?? 0,
                        'igst_rate' => $line['igst_rate'] ?? 0,
                        'hsn_code' => $product->hsn_code,
                        'batch_no' => $inputRow['batch_no'] ?? null,
                        'serial_numbers' => $serials !== [] ? $serials : null,
                        'tax_amount' => $line['tax_amount'] ?? $line['tax'],
                        'cgst_amount' => $line['cgst_amount'] ?? 0,
                        'sgst_amount' => $line['sgst_amount'] ?? 0,
                        'igst_amount' => $line['igst_amount'] ?? 0,
                        'line_total' => $line['line_total'] ?? $line['lineTotal'],
                    ]);

                    $this->serialBatchService->sellSerials($serials, $invoice, $product, $invoiceItem);

                    $this->stockMovementService->recordOut(
                        product: $product,
                        uom: $uom,
                        quantity: (float) $line['quantity'],
                        type: 'sale',
                        reference: $invoice,
                        notes: $linkedOrder ? "Sale from order {$linkedOrder->order_no} via invoice {$invoice->invoice_no}" : "Direct invoice {$invoice->invoice_no}",
                        user: auth()->user(),
                    );
                }

                if ($linkedOrder) {
                    // Update delivered quantities on order items
                    foreach ($linkedOrder->items as $orderItem) {
                        $invoicedThisTime = collect($validated['items'])
                            ->where('product_id', $orderItem->product_id)
                            ->sum(fn ($i) => (float) ($i['quantity'] ?? 0));
                        if ($invoicedThisTime > 0) {
                            $orderItem->update([
                                'delivered_qty' => min((float) $orderItem->quantity, (float) $orderItem->delivered_qty + $invoicedThisTime),
                            ]);
                        }
                    }

                    // Release stock reservations for the order
                    app(\App\Domains\Order\Services\OrderService::class)->releaseReservations($linkedOrder);

                    // Mark order converted
                    $linkedOrder->update([
                        'status' => 'converted',
                        'converted_by_name' => auth()->user()?->name ?? 'System',
                    ]);
                }

                $this->outstandingLedgerService->recordInvoice($invoice);
                $this->paymentLinkService->createForInvoice($invoice);

                return $invoice;
            });
        } catch (\InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Invoice created successfully.', 'invoices.show', ['invoice' => $invoice]);
    }

    public function edit(Invoice $invoice): View
    {
        $invoice->load(['items.product.baseUom', 'items.uom', 'customer', 'billingAddress', 'shippingAddress', 'order', 'salesperson']);
        $company = Company::resolveCurrent() ?? Company::query()->first();
        $initialOrders = $invoice->customer ? $this->getEligibleOrdersForCustomer($invoice->customer, auth()->user()) : collect();

        $salespersons = self::getSalespersons();
        if ($invoice->salesperson && ! $salespersons->contains('id', $invoice->salesperson_id)) {
            $salespersons->prepend($invoice->salesperson);
        }

        return view('sales.invoices.edit', [
            'invoice' => $invoice,
            'order' => $invoice->order,
            'initialOrders' => $initialOrders,
            'company' => $company,
            'customers' => Customer::with('customerType')->where('is_active', true)->orderBy('name')->get(),
            'transporters' => self::getTransporters(),
            'salespersons' => $salespersons,
            'products' => Product::with(['baseUom', 'productType'])->where('is_active', true)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
            'schemes' => Scheme::with(['brand', 'slabs', 'products'])->where('status', 'active')->orderBy('name')->get(),
            'stateOptions' => \App\Support\IndianStates::options(),
            'sellingTermsAndConditions' => $company?->selling_terms_and_conditions,
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($request->has('eway_bill_required')) {
            $request->merge(['eway_bill_required' => strtolower((string) $request->input('eway_bill_required'))]);
        }

        $validated = $request->validate([
            'salesperson_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'vehicle_no' => 'nullable|string|max:60',
            'motor_vehicle_no' => 'nullable|string|max:60',
            'transport_mode' => 'nullable|string|max:150',
            'dispatched_through' => 'nullable|string|max:150',
            'reference_no' => 'nullable|string|max:255',
            'other_references' => 'nullable|string|max:255',
            'delivery_state' => 'nullable|string|max:100',
            'bill_to_place' => 'nullable|string|max:100',
            'ship_to_place' => 'nullable|string|max:100',
            'delivery_note_no' => 'nullable|string|max:255',
            'delivery_note_na' => 'nullable|boolean',
            'dispatch_doc_no' => 'nullable|string|max:100',
            'destination' => 'nullable|string|max:150',
            'carrier_name' => 'nullable|string|max:150',
            'lr_rr_no' => 'nullable|string|max:100',
            'lr_rr_date' => 'nullable|date',
            'order_no' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:255',
            'terms_of_delivery' => 'nullable|string|max:3000',
            'billing_address_id' => 'nullable|exists:party_addresses,id',
            'shipping_address_id' => 'nullable|exists:party_addresses,id',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'eway_bill_required' => 'nullable|in:auto,yes,no',
            'transporter_id' => 'nullable|string|max:25',
            'transporter_name' => 'nullable|string|max:255',
            'distance_km' => 'nullable|integer|min:0|max:9999',
            'vehicle_type' => 'nullable|in:R,O',
            'eway_bill_no' => 'nullable|string|max:50',
            'eway_bill_date' => 'nullable|date',
            'eway_sub_type' => 'nullable|string|max:50',
            'eway_doc_type' => 'nullable|string|max:50',
            'eway_supply_type' => 'nullable|string|max:50',
            'consignor_name' => 'nullable|string|max:255',
            'consignor_gstin' => 'nullable|string|max:25',
            'consignor_state' => 'nullable|string|max:100',
            'consignor_address_type' => 'nullable|string|max:100',
            'consignor_address_line_1' => 'nullable|string|max:255',
            'consignor_address_line_2' => 'nullable|string|max:255',
            'consignor_pincode' => 'nullable|string|max:20',
            'consignor_city' => 'nullable|string|max:100',
            'consignor_actual_state' => 'nullable|string|max:100',
            'consignee_name' => 'nullable|string|max:255',
            'consignee_gstin' => 'nullable|string|max:25',
            'consignee_state' => 'nullable|string|max:100',
            'consignee_address_type' => 'nullable|string|max:100',
            'consignee_address_line_1' => 'nullable|string|max:255',
            'consignee_address_line_2' => 'nullable|string|max:255',
            'consignee_pincode' => 'nullable|string|max:20',
            'consignee_city' => 'nullable|string|max:100',
            'consignee_actual_state' => 'nullable|string|max:100',
        ]);

        $deliveryNoteNa = !empty($validated['delivery_note_na']);
        $deliveryNoteNo = $deliveryNoteNa ? null : (isset($validated['delivery_note_no']) ? trim((string)$validated['delivery_note_no']) : null);
        if ($deliveryNoteNo === '') {
            $deliveryNoteNo = null;
        }

        $vehicleNo = trim((string)($validated['motor_vehicle_no'] ?? ($validated['vehicle_no'] ?? ''))) ?: null;
        $rawTransportMode = trim((string)($validated['transport_mode'] ?? ($validated['dispatched_through'] ?? ($invoice->transport_mode ?? '1 - Road'))));
        $transportMode = $rawTransportMode ?: '1 - Road';
        $dispatchedThrough = trim((string)($validated['dispatched_through'] ?? $transportMode)) ?: null;
        $otherReferences = trim((string)($validated['other_references'] ?? ($validated['reference_no'] ?? ''))) ?: null;

        $billingAddress = null;
        if (!empty($validated['billing_address_id'])) {
            $billingAddress = \App\Domains\Master\Models\PartyAddress::where('id', $validated['billing_address_id'])
                ->where('customer_id', $invoice->customer_id)
                ->first();
        }

        $shippingAddress = null;
        if (!empty($validated['shipping_address_id'])) {
            $shippingAddress = \App\Domains\Master\Models\PartyAddress::where('id', $validated['shipping_address_id'])
                ->where('customer_id', $invoice->customer_id)
                ->first();
        }
        $shippingSnapshot = $validated['shipping_address'] ?? ($shippingAddress ? $shippingAddress->formatSnapshot() : $invoice->shipping_address);

        $newDeliveryState = !empty($validated['delivery_state'])
            ? trim((string)$validated['delivery_state'])
            : trim((string)($shippingAddress?->state ?? $invoice->delivery_state));
        $company = $invoice->selling_company ?? Company::resolveCurrent() ?? Company::first();
        $sellerState = \App\Domains\Master\Services\GstTaxCalculationService::resolveSellerState($company);
        $newIsIntra = \App\Domains\Master\Services\GstTaxCalculationService::isIntraState($sellerState, $newDeliveryState);
        $newTaxType = $newIsIntra ? 'intra' : 'inter';

        $billToPlace = array_key_exists('bill_to_place', $validated)
            ? (trim((string)$validated['bill_to_place']) ?: null)
            : ($invoice->bill_to_place ?: ($billingAddress?->city ?: ($invoice->billingAddress?->city ?: ($invoice->customer?->city ?: null))));

        $shipToPlace = array_key_exists('ship_to_place', $validated)
            ? (trim((string)$validated['ship_to_place']) ?: null)
            : ($invoice->ship_to_place ?: ($shippingAddress?->city ?: ($invoice->shippingAddress?->city ?: ($invoice->customer?->shipping_city ?: ($billToPlace ?? null)))));

        $ewayBillNo = array_key_exists('eway_bill_no', $validated) ? (trim((string)$validated['eway_bill_no']) ?: null) : $invoice->eway_bill_no;
        $ewayBillDate = array_key_exists('eway_bill_date', $validated) ? (!empty($validated['eway_bill_date']) ? $validated['eway_bill_date'] : null) : $invoice->eway_bill_date;
        $ewayStatus = $ewayBillNo ? 'generated' : $invoice->eway_bill_status;

        $invoice->update([
            'salesperson_id' => array_key_exists('salesperson_id', $validated)
                ? (!empty($validated['salesperson_id']) ? (int) $validated['salesperson_id'] : null)
                : $invoice->salesperson_id,
            'delivery_note_no' => $deliveryNoteNo,
            'delivery_note_na' => $deliveryNoteNa,
            'dispatch_doc_no' => trim((string)($validated['dispatch_doc_no'] ?? '')) ?: null,
            'dispatched_through' => $dispatchedThrough,
            'destination' => trim((string)($validated['destination'] ?? '')) ?: null,
            'carrier_name' => trim((string)($validated['carrier_name'] ?? '')) ?: null,
            'lr_rr_no' => trim((string)($validated['lr_rr_no'] ?? '')) ?: null,
            'lr_rr_date' => !empty($validated['lr_rr_date']) ? $validated['lr_rr_date'] : null,
            'vehicle_no' => $vehicleNo,
            'transport_mode' => $transportMode,
            'order_no' => trim((string)($validated['order_no'] ?? '')) ?: null,
            'payment_terms' => trim((string)($validated['payment_terms'] ?? '')) ?: null,
            'other_references' => $otherReferences,
            'reference_no' => $otherReferences,
            'terms_of_delivery' => trim((string)($validated['terms_of_delivery'] ?? '')) ?: null,
            'notes' => $validated['notes'] ?? $invoice->notes,
            'terms_and_conditions' => $validated['terms_and_conditions'] ?? $invoice->terms_and_conditions,
            'delivery_state' => $newDeliveryState,
            'place_of_supply_state' => $newDeliveryState,
            'bill_to_place' => $billToPlace,
            'ship_to_place' => $shipToPlace,
            'tax_type' => $newTaxType,
            'billing_address_id' => $validated['billing_address_id'] ?? $invoice->billing_address_id,
            'shipping_address_id' => $validated['shipping_address_id'] ?? $invoice->shipping_address_id,
            'billing_address' => $validated['billing_address'] ?? $invoice->billing_address,
            'shipping_address' => $shippingSnapshot,
            'eway_bill_required' => $validated['eway_bill_required'] ?? $invoice->eway_bill_required ?? 'auto',
            'eway_bill_no' => $ewayBillNo,
            'eway_bill_date' => $ewayBillDate,
            'eway_sub_type' => $validated['eway_sub_type'] ?? $invoice->eway_sub_type ?? 'Supply',
            'eway_doc_type' => $validated['eway_doc_type'] ?? $invoice->eway_doc_type ?? 'Tax Invoice',
            'eway_supply_type' => $validated['eway_supply_type'] ?? $invoice->eway_supply_type ?? 'Outward',
            'consignor_name' => $validated['consignor_name'] ?? $invoice->consignor_name,
            'consignor_gstin' => $validated['consignor_gstin'] ?? $invoice->consignor_gstin,
            'consignor_state' => $validated['consignor_state'] ?? $invoice->consignor_state,
            'consignor_address_type' => $validated['consignor_address_type'] ?? $invoice->consignor_address_type,
            'consignor_address_line_1' => $validated['consignor_address_line_1'] ?? $invoice->consignor_address_line_1,
            'consignor_address_line_2' => $validated['consignor_address_line_2'] ?? $invoice->consignor_address_line_2,
            'consignor_pincode' => $validated['consignor_pincode'] ?? $invoice->consignor_pincode,
            'consignor_city' => $validated['consignor_city'] ?? $invoice->consignor_city,
            'consignor_actual_state' => $validated['consignor_actual_state'] ?? $invoice->consignor_actual_state,
            'consignee_name' => $validated['consignee_name'] ?? $invoice->consignee_name,
            'consignee_gstin' => $validated['consignee_gstin'] ?? $invoice->consignee_gstin,
            'consignee_state' => $validated['consignee_state'] ?? $invoice->consignee_state,
            'consignee_address_type' => $validated['consignee_address_type'] ?? $invoice->consignee_address_type,
            'consignee_address_line_1' => $validated['consignee_address_line_1'] ?? $invoice->consignee_address_line_1,
            'consignee_address_line_2' => $validated['consignee_address_line_2'] ?? $invoice->consignee_address_line_2,
            'consignee_pincode' => $validated['consignee_pincode'] ?? $invoice->consignee_pincode,
            'consignee_city' => $validated['consignee_city'] ?? $invoice->consignee_city,
            'consignee_actual_state' => $validated['consignee_actual_state'] ?? $invoice->consignee_actual_state,
            'eway_bill_status' => $ewayStatus,
            'transporter_id' => $validated['transporter_id'] ?? $invoice->transporter_id,
            'transporter_name' => $validated['transporter_name'] ?? $invoice->transporter_name,
            'distance_km' => array_key_exists('distance_km', $validated) ? (!empty($validated['distance_km']) ? (int) $validated['distance_km'] : null) : $invoice->distance_km,
            'vehicle_type' => $validated['vehicle_type'] ?? $invoice->vehicle_type ?? 'R',
        ]);

        if ($ewayBillNo) {
            EWayBill::updateOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'status' => 'generated',
                    'provider' => 'manual',
                    'eway_bill_no' => $ewayBillNo,
                    'ewb_date' => $ewayBillDate ?? now(),
                    'distance_km' => array_key_exists('distance_km', $validated) ? (!empty($validated['distance_km']) ? (int) $validated['distance_km'] : null) : $invoice->distance_km,
                    'transporter_id' => $validated['transporter_id'] ?? $invoice->transporter_id,
                    'transporter_name' => $validated['transporter_name'] ?? $invoice->transporter_name,
                    'vehicle_no' => $vehicleNo,
                    'vehicle_type' => $validated['vehicle_type'] ?? $invoice->vehicle_type ?? 'R',
                    'transport_mode' => match (true) {
                        str_contains(strtolower($transportMode), '2') || str_contains(strtolower($transportMode), 'rail') => '2',
                        str_contains(strtolower($transportMode), '3') || str_contains(strtolower($transportMode), 'air') => '3',
                        str_contains(strtolower($transportMode), '4') || str_contains(strtolower($transportMode), 'ship') => '4',
                        str_contains(strtolower($transportMode), 'not applicable') || strtolower($transportMode) === 'na' => 'NA',
                        default => '1',
                    },
                    'transport_mode' => $dispatchedThrough,
                    'doc_no' => trim((string)($validated['lr_rr_no'] ?? '')) ?: $invoice->lr_rr_no,
                    'doc_date' => !empty($validated['lr_rr_date']) ? $validated['lr_rr_date'] : $invoice->lr_rr_date,
                    'from_pincode' => $validated['consignor_pincode'] ?? $invoice->consignor_pincode,
                    'to_pincode' => $validated['consignee_pincode'] ?? $invoice->consignee_pincode,
                    'from_state' => $validated['consignor_state'] ?? $invoice->consignor_state,
                    'to_state' => $validated['consignee_state'] ?? $invoice->consignee_state,
                ]
            );
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice details updated successfully.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['customer', 'salesperson', 'items.product', 'items.uom', 'payments', 'eInvoice', 'eWayBill', 'order', 'scheme.brand', 'schemeAchievement']);

        $eInvoiceEligibility = $this->complianceService->evaluateEInvoiceEligibility($invoice);
        $eWayBillEligibility = $this->complianceService->evaluateEWayBillEligibility($invoice);

        return view('sales.invoices.show', compact('invoice', 'eInvoiceEligibility', 'eWayBillEligibility'));
    }

    /**
     * Resolve the ordered list of statutory copy designations.
     *
     * @return array<int, string>
     */
    public static function resolveCopies(string $copyType = 'all', int $numberOfCopies = 1): array
    {
        $normalizedType = strtolower(trim($copyType));
        $count = max(1, min(20, $numberOfCopies));

        if ($normalizedType === 'all') {
            $standard = [
                'Original for Recipient',
                'Duplicate for Transporter',
                'Triplicate for Supplier',
            ];
            if ($count === 1 || $count === 3) {
                return $standard;
            }
            $sets = max(1, (int) round($count / 3));
            $copies = [];
            for ($s = 0; $s < $sets; $s++) {
                foreach ($standard as $label) {
                    $copies[] = $label;
                }
            }
            return ! empty($copies) ? $copies : $standard;
        }

        $label = match ($normalizedType) {
            'original', 'original for recipient' => 'Original for Recipient',
            'duplicate', 'duplicate for transporter' => 'Duplicate for Transporter',
            'triplicate', 'triplicate for supplier' => 'Triplicate for Supplier',
            'extra', 'extra copy' => 'Extra Copy',
            'na', 'not applicable', 'none' => '',
            default => 'Original for Recipient',
        };

        return array_fill(0, $count, $label);
    }

    public function pdf(Request $request, Invoice $invoice): Response
    {
        $invoice->load(['customer', 'salesperson', 'items.product', 'items.uom', 'eInvoice', 'eWayBill', 'scheme.brand']);

        $company = Company::resolveCurrent() ?? Company::query()->first();

        // Prefer the official signed QR from Masters India / IRP. Fall back to
        // a deterministic invoice QR so the PDF always shows something scannable.
        $invoiceQrDataUri = $invoice->eInvoice?->getQrDataUri(180);
        if (blank($invoiceQrDataUri)) {
            $invoiceUrl = route('invoice.qr', [
                'type' => 'sales',
                'token' => $invoice->qr_token,
            ]);
            $invoiceQrDataUri = QrCodeRenderer::dataUri($invoiceUrl, 180);
        }

        $upiQrDataUri = null;
        if ($company && filled($company->upi_id)) {
            $upiUri = QrCodeRenderer::upiIntent($company->upi_id, $company->name, (float) $invoice->grand_total, 'Inv '.$invoice->invoice_no, $invoice->invoice_no);
            $upiQrDataUri = QrCodeRenderer::dataUri($upiUri, 160);
        }

        $copyType = $request->query('copy_type', $request->query('copy', 'all'));
        $copies = (int) $request->query('copies', ($copyType === 'all' ? 3 : 1));
        $copiesList = self::resolveCopies($copyType, $copies);

        $pdf = Pdf::loadView('sales.invoices.preview', [
            'invoice' => $invoice,
            'company' => $company,
            'invoiceQrDataUri' => $invoiceQrDataUri,
            'upiQrDataUri' => $upiQrDataUri,
            'copyType' => $copyType,
            'copies' => $copies,
            'copiesList' => $copiesList,
            'isPdf' => true,
        ])->setPaper('a4');

        return $request->boolean('download')
            ? $pdf->download($invoice->invoice_no.'.pdf')
            : $pdf->stream($invoice->invoice_no.'.pdf');
    }

    public function eInvoiceDocument(Invoice $invoice): View|RedirectResponse
    {
        $invoice->load(['customer', 'salesperson', 'items.product', 'items.uom', 'eInvoice', 'scheme.brand']);
        if (! $invoice->eInvoice) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'E-Invoice has not been generated for this invoice.');
        }

        $eInvoice = $invoice->eInvoice;

        return view('sales.invoices.e-invoice', compact('invoice', 'eInvoice'));
    }

    public function eInvoicePreview(Invoice $invoice): View
    {
        $invoice->load(['customer', 'salesperson', 'items.product', 'items.uom', 'eInvoice', 'eWayBill', 'scheme.brand']);

        $company = Company::resolveCurrent() ?? Company::query()->first();

        $invoiceQrDataUri = $invoice->eInvoice?->getQrDataUri(180);
        if (blank($invoiceQrDataUri)) {
            $invoiceUrl = route('invoice.qr', [
                'type' => 'sales',
                'token' => $invoice->qr_token,
            ]);
            $invoiceQrDataUri = QrCodeRenderer::dataUri($invoiceUrl, 180);
        }

        $upiQrDataUri = null;

        if ($company && filled($company->upi_id)) {
            $upiUri = QrCodeRenderer::upiIntent(
                $company->upi_id,
                $company->name,
                (float) $invoice->grand_total,
                'Inv '.$invoice->invoice_no,
                $invoice->invoice_no
            );

            $upiQrDataUri = QrCodeRenderer::dataUri($upiUri, 160);
        }

        return view('sales.invoices.pdf', [
            'invoice' => $invoice,
            'company' => $company,
            'invoiceQrDataUri' => $invoiceQrDataUri,
            'upiQrDataUri' => $upiQrDataUri,
        ]);
    }

    public function preview(Request $request, Invoice $invoice): View
    {
        $invoice->load(['customer', 'salesperson', 'items.product', 'items.uom', 'eInvoice', 'eWayBill', 'scheme.brand', 'schemeAchievement']);
        $company = Company::resolveCurrent() ?? Company::query()->first();

        $invoiceQrDataUri = $invoice->eInvoice?->getQrDataUri(180);
        if (blank($invoiceQrDataUri)) {
            $invoiceUrl = route('invoice.qr', [
                'type' => 'sales',
                'token' => $invoice->qr_token,
            ]);
            $invoiceQrDataUri = QrCodeRenderer::dataUri($invoiceUrl, 180);
        }

        $upiQrDataUri = null;
        if ($company && filled($company->upi_id)) {
            $upiUri = QrCodeRenderer::upiIntent(
                $company->upi_id,
                $company->name,
                (float) $invoice->grand_total,
                'Inv '.$invoice->invoice_no,
                $invoice->invoice_no
            );
            $upiQrDataUri = QrCodeRenderer::dataUri($upiUri, 160);
        }

        $copyType = $request->query('copy_type', $request->query('copy', 'all'));
        $copies = (int) $request->query('copies', ($copyType === 'all' ? 3 : 1));
        $copiesList = self::resolveCopies($copyType, $copies);
        $autoPrint = $request->boolean('auto_print');

        return view('sales.invoices.preview', [
            'invoice' => $invoice,
            'company' => $company,
            'invoiceQrDataUri' => $invoiceQrDataUri,
            'upiQrDataUri' => $upiQrDataUri,
            'copyType' => $copyType,
            'copies' => $copies,
            'copiesList' => $copiesList,
            'autoPrint' => $autoPrint,
        ]);
    }

    public function export(Request $request, Invoice $invoice, string $format): Response
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['pdf', 'xlsx', 'excel', 'csv'], true), 404);

        $copyType = $request->query('copy_type', $request->query('copy', 'all'));
        $copies = (int) $request->query('copies', ($copyType === 'all' ? 3 : 1));

        return DocumentExporter::exportSalesInvoice($invoice, $format, $copyType, $copies);
    }

    public function eWayBillDocument(Invoice $invoice): View|RedirectResponse
    {
        $invoice->load(['customer', 'items.product', 'items.uom', 'eWayBill']);
        if (! $invoice->eWayBill) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'E-Way Bill has not been generated for this invoice.');
        }

        $eWayBill = $invoice->eWayBill;
        $company = Company::resolveCurrent() ?? Company::query()->first();

        return view('sales.invoices.e-way-bill', compact('invoice', 'eWayBill', 'company'));
    }

    public function generateEInvoice(Invoice $invoice): RedirectResponse
    {
        $invoice->load(['customer', 'items.product', 'items.uom', 'eInvoice']);

        if ($invoice->eInvoice && $invoice->eInvoice->status === 'generated') {
            return $this->flashError('E-Invoice has already been generated with IRN: ' . $invoice->eInvoice->irn, 'invoices.show', ['invoice' => $invoice]);
        }

        try {
            $result = $this->mastersIndiaService->generateIrn($invoice);
        } catch (\Throwable $e) {
            $invoice->update(['einvoice_status' => 'failed']);
            EInvoice::updateOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'status' => 'failed',
                    'last_error' => $e->getMessage(),
                    'requested_at' => now(),
                ]
            );
            return $this->flashError('E-Invoice generation failed: ' . $e->getMessage(), 'invoices.show', ['invoice' => $invoice]);
        }

        EInvoice::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => $result['status'],
                'provider' => 'mastersindia',
                'irn' => $result['irn'],
                'ack_no' => $result['ack_no'] ?? null,
                'ack_date' => isset($result['ack_date']) ? \Carbon\Carbon::parse($result['ack_date']) : null,
                'signed_invoice' => $result['signed_invoice'] ?? null,
                'signed_qr_base64' => $result['signed_qr_base64'] ?? null,
                'requested_at' => now(),
                'last_error' => null,
                'payload' => $result['raw'],
            ]
        );

        $invoice->update(['einvoice_status' => 'generated']);

        $note = ! $this->mastersIndiaService->isConfigured()
            ? 'E-Invoice generated with deterministic IRN (Masters India sandbox/demo mode).'
            : 'E-Invoice generated successfully.';

        return redirect()->route('invoices.show', $invoice)->with('success', $note);
    }

    public function cancelEInvoice(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'cancel_reason' => 'required|in:1,2,3,4',
            'cancel_remarks' => 'required|string|min:3|max:100',
        ]);

        try {
            $this->mastersIndiaService->cancelIrn($invoice, $validated['cancel_reason'], $validated['cancel_remarks']);
        } catch (\Throwable $e) {
            return $this->flashError('E-Invoice cancellation failed: ' . $e->getMessage(), 'invoices.show', ['invoice' => $invoice]);
        }

        $reasonText = match ($validated['cancel_reason']) {
            '1' => 'Duplicate',
            '2' => 'Data entry mistake',
            '3' => 'Order cancelled',
            default => 'Others',
        };

        EInvoice::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => 'cancelled',
                'cancel_reason' => $reasonText,
                'cancel_remarks' => $validated['cancel_remarks'],
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
            ]
        );

        $invoice->update(['einvoice_status' => 'cancelled']);

        return redirect()->route('invoices.show', $invoice)->with('success', 'E-Invoice IRN cancelled successfully.');
    }

    public function generateEway(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->load(['customer', 'items.product', 'items.uom', 'eInvoice', 'eWayBill']);

        if ($invoice->eWayBill && $invoice->eWayBill->status === 'generated') {
            return $this->flashError('E-Way Bill has already been generated with number: ' . $invoice->eWayBill->eway_bill_no, 'invoices.show', ['invoice' => $invoice]);
        }

        $validated = $request->validate([
            'transport_mode' => 'nullable|in:1,2,3,4,Road,Rail,Air,Ship',
            'vehicle_no' => 'nullable|string|max:40',
            'vehicle_type' => 'nullable|in:R,O',
            'transporter_id' => 'nullable|string|max:25',
            'transporter_name' => 'nullable|string|max:255',
            'distance_km' => 'nullable|integer|min:1|max:9999',
            'lr_rr_no' => 'nullable|string|max:100',
            'lr_rr_date' => 'nullable|date',
        ]);

        $transportUpdates = array_filter([
            'transport_mode' => $validated['transport_mode'] ?? $invoice->transport_mode ?? '1',
            'vehicle_no' => $validated['vehicle_no'] ?? $invoice->vehicle_no,
            'vehicle_type' => $validated['vehicle_type'] ?? $invoice->vehicle_type ?? 'R',
            'transporter_id' => $validated['transporter_id'] ?? $invoice->transporter_id,
            'transporter_name' => $validated['transporter_name'] ?? $invoice->transporter_name,
            'distance_km' => !empty($validated['distance_km']) ? (int) $validated['distance_km'] : ($invoice->distance_km ?: 50),
            'lr_rr_no' => $validated['lr_rr_no'] ?? $invoice->lr_rr_no,
            'lr_rr_date' => $validated['lr_rr_date'] ?? $invoice->lr_rr_date,
        ], fn ($val) => $val !== null && $val !== '');

        if (! empty($transportUpdates)) {
            $invoice->update($transportUpdates);
        }

        $eligibility = $this->complianceService->evaluateEWayBillEligibility($invoice);
        if (! empty($eligibility['missing_fields'])) {
            return $this->flashError('Cannot generate E-Way Bill: ' . implode(', ', $eligibility['missing_fields']) . ' must be provided.', 'invoices.show', ['invoice' => $invoice]);
        }

        try {
            $result = $this->mastersIndiaService->generateEWayBill($invoice, [
                'VehNo' => $invoice->vehicle_no,
                'VehType' => $invoice->vehicle_type ?? 'R',
                'TransMode' => $invoice->transport_mode ?? '1',
                'TransId' => $invoice->transporter_id,
                'TransName' => $invoice->transporter_name,
                'Distance' => $invoice->distance_km ?: 50,
            ]);
        } catch (\Throwable $e) {
            $invoice->update(['eway_bill_status' => 'failed']);
            EWayBill::updateOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'status' => 'failed',
                    'last_error' => $e->getMessage(),
                ]
            );
            return $this->flashError('E-Way Bill generation failed: ' . $e->getMessage(), 'invoices.show', ['invoice' => $invoice]);
        }

        EWayBill::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => $result['status'],
                'provider' => 'mastersindia',
                'eway_bill_no' => $result['eway_bill_no'],
                'valid_upto' => isset($result['valid_upto']) ? \Carbon\Carbon::parse($result['valid_upto']) : null,
                'ewb_date' => isset($result['ewb_date']) ? \Carbon\Carbon::parse($result['ewb_date']) : now(),
                'distance_km' => $invoice->distance_km ?: 50,
                'transporter_id' => $invoice->transporter_id,
                'transporter_name' => $invoice->transporter_name,
                'vehicle_no' => $invoice->vehicle_no,
                'vehicle_type' => $invoice->vehicle_type ?? 'R',
                'transport_mode' => $invoice->transport_mode ?? '1',
                'doc_no' => $invoice->lr_rr_no,
                'doc_date' => $invoice->lr_rr_date,
                'last_error' => null,
                'payload' => $result['raw'],
            ]
        );

        $invoice->update(['eway_bill_status' => 'generated']);

        $note = ! $this->mastersIndiaService->isConfigured()
            ? 'E-Way Bill generated with number ' . $result['eway_bill_no'] . ' (Masters India sandbox/demo mode).'
            : 'E-Way Bill generated successfully.';

        return redirect()->route('invoices.show', $invoice)->with('success', $note);
    }

    public function cancelEway(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'cancel_reason' => 'required|in:1,2,3,4',
            'cancel_remarks' => 'required|string|min:3|max:100',
        ]);

        try {
            $this->mastersIndiaService->cancelEWayBill($invoice, $validated['cancel_reason'], $validated['cancel_remarks']);
        } catch (\Throwable $e) {
            return $this->flashError('E-Way Bill cancellation failed: ' . $e->getMessage(), 'invoices.show', ['invoice' => $invoice]);
        }

        $reasonText = match ($validated['cancel_reason']) {
            '1' => 'Duplicate',
            '2' => 'Order Cancelled',
            '3' => 'Data Entry Mistake',
            default => 'Others',
        };

        EWayBill::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => 'cancelled',
                'cancel_reason' => $reasonText,
                'cancel_remarks' => $validated['cancel_remarks'],
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
            ]
        );

        $invoice->update(['eway_bill_status' => 'cancelled']);

        return redirect()->route('invoices.show', $invoice)->with('success', 'E-Way Bill cancelled successfully.');
    }

    public function updateEwayVehicle(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_no' => 'required|string|max:40',
            'vehicle_type' => 'nullable|in:R,O',
            'reason' => 'nullable|string|max:100',
            'reason_code' => 'nullable|string|max:20',
            'remarks' => 'nullable|string|max:255',
            'from_place' => 'nullable|string|max:100',
            'from_state' => 'nullable|string|max:100',
        ]);

        $reason = $validated['reason'] ?? $validated['remarks'] ?? $validated['reason_code'] ?? 'Transshipment';

        $invoice->update([
            'vehicle_no' => $validated['vehicle_no'],
            'vehicle_type' => $validated['vehicle_type'] ?? 'R',
        ]);

        if ($invoice->eWayBill) {
            $invoice->eWayBill->update([
                'vehicle_no' => $validated['vehicle_no'],
                'vehicle_type' => $validated['vehicle_type'] ?? 'R',
            ]);
        }

        app(\App\Support\AuditLogService::class)->record($invoice, 'eway_vehicle_updated', null, [
            'vehicle_no' => $validated['vehicle_no'],
            'reason' => $reason,
        ]);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Vehicle number updated successfully.');
    }
}
