<?php

namespace App\Http\Controllers\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Organization\Services\FinancialYearService;
use App\Domains\Sales\Models\DeliveryChallan;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Services\DeliveryChallanService;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class DeliveryChallanController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected DeliveryChallanService $deliveryChallanService,
        protected FinancialYearService $financialYearService,
    ) {}

    public function index(Request $request): View
    {
        $query = DeliveryChallan::query()
            ->with(['customer', 'order', 'invoice'])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('challan_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('challan_date', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['challan_no', 'vehicle_no', 'lr_no', 'status', 'notes'],
            ['customer' => ['name'], 'order' => ['order_no'], 'invoice' => ['invoice_no']]
        );

        $allowedSorts = [
            'challan_no' => 'challan_no',
            'status' => 'status',
            'challan_date' => 'challan_date',
            'customer' => function ($q, $dir) {
                $q->join('customers', 'delivery_challans.customer_id', '=', 'customers.id')
                    ->orderBy('customers.name', $dir)
                    ->select('delivery_challans.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'challan_date',
            defaultDirection: 'desc'
        );

        return view('sales.delivery-challans.index', [
            'items' => $query->paginate(15)->withQueryString(),
            'customers' => Customer::query()->debtors()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'customer_id', 'status', 'date_from', 'date_to']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(Request $request): View
    {
        return view('sales.delivery-challans.form', [
            'item' => new DeliveryChallan([
                'challan_date' => now()->toDateString(),
                'customer_id' => $request->input('customer_id'),
                'order_id' => $request->input('order_id'),
                'invoice_id' => $request->input('invoice_id'),
            ]),
            'customers' => Customer::query()->debtors()->where('is_active', true)->orderBy('name')->get(),
            'orders' => Order::with('customer')->orderByDesc('id')->limit(200)->get(),
            'invoices' => Invoice::with('customer')->orderByDesc('invoice_date')->limit(200)->get(),
            'products' => Product::where('is_active', true)->where('is_hidden', false)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:orders,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'challan_date' => 'required|date',
            'vehicle_no' => 'nullable|string|max:40',
            'transporter_name' => 'nullable|string|max:255',
            'lr_no' => 'nullable|string|max:60',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.uom_id' => 'nullable|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.description' => 'nullable|string',
        ]);

        $validated['company_id'] = Customer::query()->whereKey($validated['customer_id'])->value('company_id');
        $validated['branch_id'] = Customer::query()->whereKey($validated['customer_id'])->value('branch_id');

        $challan = $this->deliveryChallanService->create($validated, $request->user());

        return $this->flashSuccess('Delivery challan created.', 'delivery-challans.show', ['deliveryChallan' => $challan]);
    }

    public function show(DeliveryChallan $deliveryChallan): View
    {
        $deliveryChallan->load(['customer', 'order', 'invoice', 'warehouse', 'items.product', 'items.uom', 'creator']);

        return view('sales.delivery-challans.show', ['item' => $deliveryChallan]);
    }

    public function dispatchChallan(DeliveryChallan $deliveryChallan): RedirectResponse
    {
        try {
            $this->deliveryChallanService->dispatch($deliveryChallan, request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Delivery challan dispatched.');
    }

    public function deliver(DeliveryChallan $deliveryChallan): RedirectResponse
    {
        try {
            $this->deliveryChallanService->markDelivered($deliveryChallan, request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Delivery challan marked delivered.');
    }

    public function cancel(DeliveryChallan $deliveryChallan): RedirectResponse
    {
        try {
            $this->deliveryChallanService->cancel($deliveryChallan, request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Delivery challan cancelled.');
    }
}
