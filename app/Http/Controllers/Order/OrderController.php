<?php

namespace App\Http\Controllers\Order;
use App\Domains\Order\Services\OrderService;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Services\PriceMasterService;
use App\Domains\Master\Services\SalePricingService;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Order\Services\OrderConversionService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected PriceMasterService $priceMasterService,
        protected SalePricingService $salePricingService,
        protected OrderConversionService $orderConversionService,
        protected AuditLogService $auditLogService,
    ) {}

    public function index(Request $request): View
    {
        $sorts = [
            'order_no',
            'order_date',
            'status',
            'grand_total',
            'created_at',
        ];

        $sort = in_array(
            $request->input('sort'),
            $sorts,
            true
        )
            ? $request->input('sort')
            : 'order_date';

        $direction = $request->input('direction') === 'asc'
            ? 'asc'
            : 'desc';

        $statusCounts = Order::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $orders = Order::query()
            ->with([
                'customer.area',
                'customer.route',
                'salesperson',
            ])
            ->when(
                $request->filled('q'),
                function ($query) use ($request) {
                    $search = trim($request->input('q'));

                    if ($search === '') {
                        return;
                    }

                    $query->where(function ($q) use ($search) {
                        $q->where('order_no', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                $customerQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            });
                    });
                }
            )
            ->when(
                $request->filled('customer_id'),
                fn ($q) => $q->where(
                    'customer_id',
                    $request->input('customer_id')
                )
            )
            ->when(
                $request->filled('salesperson_id'),
                fn ($q) => $q->where(
                    'salesperson_id',
                    $request->input('salesperson_id')
                )
            )
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where(
                    'status',
                    $request->input('status')
                )
            )
            ->when(
                $request->filled('area_id'),
                fn ($q) => $q->whereHas(
                    'customer',
                    fn ($customerQuery) => $customerQuery->where(
                        'area_id',
                        $request->input('area_id')
                    )
                )
            )
            ->when(
                $request->filled('date_from'),
                fn ($q) => $q->whereDate(
                    'order_date',
                    '>=',
                    $request->input('date_from')
                )
            )
            ->when(
                $request->filled('date_to'),
                fn ($q) => $q->whereDate(
                    'order_date',
                    '<=',
                    $request->input('date_to')
                )
            )
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,

            'customers' => Customer::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'salespersons' => User::role([
                'salesperson',
                'sales-manager',
            ])
                ->orderBy('name')
                ->get(),

            'areas' => Area::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'statuses' => [
                'draft',
                'pending',
                'approved',
                'converted',
                'cancelled',
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $selectedCustomer = null;

        if ($request->filled('customer_id')) {
            $selectedCustomer = Customer::query()
                ->with('customerType')
                ->where('is_active', true)
                ->find($request->input('customer_id'));
        }

        $initialItems = collect(old('items', []))
            ->map(function (array $item) {
                return [
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'] ?? null,
                    'product_sku' => $item['product_sku'] ?? null,
                    'uom_id' => $item['uom_id'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? null,
                ];
            })
            ->values()
            ->all();

        return view('orders.create', [
            'selectedCustomer' => $selectedCustomer,
            'initialItems' => $initialItems,

            'customers' => Customer::query()
                ->with('customerType')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'products' => Product::query()
                ->with('baseUom')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'uoms' => Uom::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $term = trim($validated['q'] ?? '');

        if ($term === '') {
            return response()->json([]);
        }

        $customers = Customer::query()
            ->with('customerType')
            ->where('is_active', true)
            ->where(function ($query) use ($term) {
                $query
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get([
                'id',
                'code',
                'name',
                'phone',
                'customer_type_id',
            ]);

        return response()->json(
            $customers->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'customer_type_id' => $customer->customer_type_id,
                'customer_type_name' => $customer->customerType?->name,
                'label' => trim(
                    $customer->name .
                    ($customer->code ? " ({$customer->code})" : '')
                ),
            ])->values()
        );
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $term = trim($validated['q'] ?? '');

        if ($term === '') {
            return response()->json([]);
        }

        $products = Product::query()
            ->with('baseUom')
            ->where('is_active', true)
            ->where(function ($query) use ($term) {
                $query
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('hsn_code', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get([
                'id',
                'name',
                'sku',
                'hsn_code',
                'base_uom_id',
                'selling_price',
                'tax_rate',
            ]);

        return response()->json(
            $products->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'hsn_code' => $product->hsn_code,
                'base_uom_id' => $product->base_uom_id,
                'base_uom_name' => $product->baseUom?->name,
                'base_uom_code' => $product->baseUom?->code,
                'selling_price' => $product->selling_price,
                'tax_rate' => $product->tax_rate,
                'label' => trim(
                    $product->name .
                    ($product->sku ? " ({$product->sku})" : '')
                ),
            ])->values()
        );
    }



    public function searchUoms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],
        ]);

        $product = Product::query()
            ->where('is_active', true)
            ->with([
                'productUoms.uom' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->orderBy('name');
                },
            ])
            ->findOrFail($validated['product_id']);

        $uoms = $product->productUoms
            ->filter(fn ($productUom) => $productUom->uom !== null)
            ->map(function ($productUom) {
                $uom = $productUom->uom;

                return [
                    'id' => $uom->id,
                    'name' => $uom->name,
                    'code' => $uom->code,
                    'conversion_factor' => $productUom->conversion_factor,
                    'is_base' => (bool) $productUom->is_base,
                    'label' => $uom->name . ' (' . $uom->code . ')',
                ];
            })
            ->values();

        return response()->json($uoms);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => [
                'required',
                'exists:customers,id',
            ],

            'order_date' => [
                'required',
                'date',
            ],

            'created_by_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'exists:products,id',
            ],

            'items.*.uom_id' => [
                'required',
                'exists:uoms,id',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.0001',
            ],

            'items.*.unit_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        $order = $this->orderService->create(
            $validated,
            auth()->user()
        );

        return $this->flashSuccess(
            'Order booked successfully.',
            'orders.show',
            ['order' => $order]
        );
    }

    public function edit(Order $order): View
    {
        $order->load([
            'customer.customerType',
            'items.product',
            'items.uom',
        ]);

        return view('orders.edit', [
            'order' => $order,

            'customers' => Customer::with('customerType')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'products' => Product::with('baseUom')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'uoms' => Uom::where(
                'is_active',
                true
            )
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(
        Request $request,
        Order $order
    ): RedirectResponse {
        if (! in_array(
            $order->status,
            ['draft', 'pending'],
            true
        )) {
            return $this->flashError(
                'Only draft or pending orders can be edited.'
            );
        }

        $validated = $request->validate([
            'customer_id' => [
                'required',
                'exists:customers,id',
            ],

            'order_date' => [
                'required',
                'date',
            ],

            'updated_by_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'exists:products,id',
            ],

            'items.*.uom_id' => [
                'required',
                'exists:uoms,id',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.0001',
            ],

            'items.*.unit_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        $order = $this->orderService->update(
            $order,
            $validated,
            auth()->user()
        );

        return $this->flashSuccess(
            'Order updated successfully.',
            'orders.show',
            ['order' => $order]
        );
    }

    public function show(Order $order): View
    {
        $order->load([
            'customer.customerType',
            'salesperson',
            'items.product',
            'items.uom',
            'invoice',
            'approver',
        ]);

        return view(
            'orders.show',
            compact('order')
        );
    }

    public function approve(
        Request $request,
        Order $order
    ): RedirectResponse {
        $request->validate([
            'approved_by_name' => ['nullable', 'string', 'max:255'],
        ]);

        $actor = $request->user();

        abort_unless($actor instanceof User, 401);

        $approvedOrder = $this->orderService->approve(
            $order,
            $actor
        );

        return redirect()
            ->route('orders.show', $approvedOrder)
            ->with(
                'status',
                "Order {$approvedOrder->order_no} approved successfully."
            );
    }

    public function convert(Request $request, Order $order)
    {
        $validated = $request->validate([
            'converted_by_name' => ['nullable', 'string', 'max:255'],
        ]);

        $invoice = $this->orderConversionService->convertToInvoice(
            $order,
            $validated['converted_by_name'] ?? null
        );

        return redirect()
            ->route('orders.show', $order)
            ->with(
                'success',
                "Order {$order->order_no} converted to invoice {$invoice->invoice_no}."
            );
    }

    public function resolvePrice(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'customer_id' => [
                'required',
                'exists:customers,id',
            ],

            'product_id' => [
                'required',
                'exists:products,id',
            ],

            'uom_id' => [
                'required',
                'exists:uoms,id',
            ],

            'quantity' => [
                'required',
                'numeric',
                'min:0.0001',
            ],
        ]);

        $customer = Customer::findOrFail(
            $validated['customer_id']
        );

        $product = Product::findOrFail(
            $validated['product_id']
        );

        $uom = Uom::findOrFail(
            $validated['uom_id']
        );

        $price = $this->priceMasterService->resolvePrice(
            $customer,
            $product,
            $uom,
            (float) $validated['quantity']
        );

        return response()->json([
            'unit_price' => $price,
            'found' => $price !== null,
        ]);
    }

    protected function generateOrderNo(): string
    {
        $date = now()->format('Ymd');

        $pattern = "ORD-{$date}-%";

        $last = Order::where(
            'order_no',
            'like',
            $pattern
        )
            ->orderByDesc('order_no')
            ->value('order_no');

        $sequence = $last
            ? (int) Str::afterLast(
                $last,
                '-'
            ) + 1
            : 1;

        return sprintf(
            'ORD-%s-%04d',
            $date,
            $sequence
        );
    }

    public function cancel(Request $request, Order $order)
    {
        $validated = $request->validate([
            'cancelled_by_name' => ['nullable', 'string', 'max:255'],
        ]);

        $cancelledOrder = $this->orderService->cancel(
            $order,
            $validated['cancelled_by_name'] ?? null
        );

        return redirect()
            ->route('orders.show', $cancelledOrder)
            ->with('success', "Order {$cancelledOrder->order_no} cancelled successfully.");
    }
    
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
            'approved_by_name' => ['nullable', 'string', 'max:255'],
        ]);

        $count = $this->orderService->bulkApprove(
            $validated['order_ids'],
            $validated['approved_by_name'] ?? null
        );

        return redirect()
            ->route('orders.index')
            ->with(
                'success',
                "{$count} order(s) approved successfully."
            );
    }

    public function bulkConvert(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'order_ids' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'order_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:orders,id',
            ],
        ]);

        try {
            $invoices = $this->orderConversionService->convertMany(
                $validated['order_ids'],
                auth()->user()?->name
            );

            return $this->flashSuccess(
                count($invoices).
                ' order(s) converted to invoice(s) successfully.'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->flashError(
                $e->getMessage()
            );
        }
    }
    public function export(
        Request $request
    ): StreamedResponse {
        $orders = $this->filteredOrdersQuery($request)
            ->with([
                'customer',
                'salesperson',
            ])
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->get();

        $filename = 'orders-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(
            function () use ($orders) {
                $handle = fopen('php://output', 'w');

                fputcsv($handle, [
                    'Order No',
                    'Order Date',
                    'Customer',
                    'Customer Code',
                    'Salesperson',
                    'Status',
                    'Subtotal',
                    'Discount',
                    'Tax',
                    'Grand Total',
                ]);

                foreach ($orders as $order) {
                    fputcsv($handle, [
                        $order->order_no,
                        $order->order_date?->format('Y-m-d'),
                        $order->customer?->name,
                        $order->customer?->code,
                        $order->salesperson?->name,
                        $order->status,
                        $order->subtotal,
                        $order->discount_amount,
                        $order->tax_amount,
                        $order->grand_total,
                    ]);
                }

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    public function pdf(
        Request $request
    ): Response {
        $query = $this->filteredOrdersQuery($request)
            ->with([
                'customer',
                'salesperson',
            ])
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $query->limit(1000);
        }

        $orders = $query->get();

        $pdf = Pdf::loadView(
            'orders.pdf',
            compact('orders')
        )->setPaper('a4', 'landscape');

        return $request->boolean('download')
            ? $pdf->download(
                'orders-'.now()->format('Ymd-His').'.pdf'
            )
            : $pdf->stream(
                'orders-'.now()->format('Ymd-His').'.pdf'
            );
    }

    protected function filteredOrdersQuery(
        Request $request
    ) {
        return Order::query()
            ->when(
                $request->filled('q'),
                function ($query) use ($request) {
                    $search = trim($request->input('q'));

                    if ($search === '') {
                        return;
                    }

                    $query->where(function ($q) use ($search) {
                        $q->where(
                            'order_no',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'customer',
                            function ($customerQuery) use ($search) {
                                $customerQuery
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'code',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                    });
                }
            )
            ->when(
                $request->filled('customer_id'),
                fn ($q) => $q->where(
                    'customer_id',
                    $request->input('customer_id')
                )
            )
            ->when(
                $request->filled('salesperson_id'),
                fn ($q) => $q->where(
                    'salesperson_id',
                    $request->input('salesperson_id')
                )
            )
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where(
                    'status',
                    $request->input('status')
                )
            )
            ->when(
                $request->filled('area_id'),
                fn ($q) => $q->whereHas(
                    'customer',
                    fn ($customerQuery) => $customerQuery->where(
                        'area_id',
                        $request->input('area_id')
                    )
                )
            )
            ->when(
                $request->filled('date_from'),
                fn ($q) => $q->whereDate(
                    'order_date',
                    '>=',
                    $request->input('date_from')
                )
            )
            ->when(
                $request->filled('date_to'),
                fn ($query) => $query->whereDate(
                    'order_date',
                    '<=',
                    $request->input('date_to')
                )
            );
    }
}