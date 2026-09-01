<?php

namespace App\Http\Controllers\Order;

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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        protected PriceMasterService $priceMasterService,
        protected SalePricingService $salePricingService,
        protected OrderConversionService $orderConversionService,
    ) {}

    public function index(Request $request): View
    {
        $sorts = ['order_no', 'order_date', 'status', 'grand_total', 'created_at'];
        $sort = in_array($request->input('sort'), $sorts, true) ? $request->input('sort') : 'order_date';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $orders = Order::query()
            ->with(['customer.area', 'customer.route', 'salesperson'])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('salesperson_id'), fn ($q) => $q->where('salesperson_id', $request->salesperson_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('area_id'), fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('area_id', $request->area_id)))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('order_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('order_date', '<=', $request->date_to))
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'salespersons' => User::role(['salesperson', 'sales-manager'])->orderBy('name')->get(),
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'statuses' => ['draft', 'pending', 'approved', 'converted', 'cancelled'],
        ]);
    }

    public function create(): View
    {
        return view('orders.create', [
            'customers' => Customer::with('customerType')->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::with('baseUom')->where('is_active', true)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'notes' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($validated, $request) {
            $customer = Customer::findOrFail($validated['customer_id']);
            $pricing = $this->salePricingService->price($customer, $validated['items'], (float) ($validated['discount_amount'] ?? 0));

            $order = Order::create([
                'order_no' => $this->generateOrderNo(),
                'customer_id' => $validated['customer_id'],
                'salesperson_id' => auth()->id(),
                'order_date' => $validated['order_date'],
                'status' => 'pending',
                'subtotal' => $pricing['subtotal'],
                'discount_amount' => $pricing['discount'],
                'tax_amount' => $pricing['tax'],
                'grand_total' => $pricing['total'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($pricing['lines'] as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'uom_id' => $line['uom']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unitPrice'],
                    'line_total' => $line['lineTotal'],
                ]);
            }

            return $order;
        });

        return $this->flashSuccess('Order booked successfully.', 'orders.show', ['order' => $order]);
    }

    public function show(Order $order): View
    {
        $order->load(['customer.customerType', 'salesperson', 'items.product', 'items.uom', 'invoice', 'approver']);

        return view('orders.show', compact('order'));
    }

    public function approve(Order $order): RedirectResponse
    {
        if (! in_array($order->status, ['pending', 'draft'], true)) {
            return $this->flashError('Only pending orders can be approved.');
        }

        $order->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $this->flashSuccess('Order approved successfully.');
    }

    public function convert(Order $order): RedirectResponse
    {
        try {
            $invoice = $this->orderConversionService->convertToInvoice($order);

            return $this->flashSuccess(
                "Order converted to invoice {$invoice->invoice_no}.",
                'invoices.show',
                ['invoice' => $invoice]
            );
        } catch (\InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }
    }

    public function resolvePrice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'uom_id' => 'required|exists:uoms,id',
            'quantity' => 'required|numeric|min:0.0001',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $product = Product::findOrFail($validated['product_id']);
        $uom = Uom::findOrFail($validated['uom_id']);

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
        $last = Order::where('order_no', 'like', $pattern)->orderByDesc('order_no')->value('order_no');
        $sequence = $last ? (int) Str::afterLast($last, '-') + 1 : 1;

        return sprintf('ORD-%s-%04d', $date, $sequence);
    }
}
