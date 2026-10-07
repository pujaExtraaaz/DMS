<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Domains\Organization\Services\FinancialYearService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\DocumentExporter;
use App\Support\Traits\SortableAndSearchable;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected PurchaseOrderService $purchaseOrderService,
        protected FinancialYearService $financialYearService,
    ) {}

    public function index(Request $request): View|Response
    {
        $query = PurchaseOrder::query()
            ->with(['supplier', 'warehouse', 'creator'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('po_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('po_date', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['po_no', 'notes'],
            ['supplier' => ['name', 'code']]
        );

        $allowedSorts = [
            'po_no' => 'po_no',
            'po_date' => 'po_date',
            'status' => 'status',
            'grand_total' => 'grand_total',
            'created_at' => 'created_at',
            'supplier' => function ($q, $dir) {
                $q->join('customers', 'purchase_orders.supplier_id', '=', 'customers.id')
                  ->orderBy('customers.name', $dir)
                  ->select('purchase_orders.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'po_date',
            defaultDirection: 'desc'
        );

        if ($request->filled('export')) {
            $exportFormat = strtolower($request->string('export')->toString());
            if (in_array($exportFormat, ['csv', 'excel', 'xlsx', 'pdf'], true)) {
                return DocumentExporter::exportPurchaseOrdersListing($query->get(), $exportFormat);
            }
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('purchasing.orders.index', [
            'orders' => $orders,
            'suppliers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'supplierId' => $request->input('supplier_id'),
            'warehouseId' => $request->input('warehouse_id'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('purchasing.orders.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:customers,id',
            'billing_address_id' => 'nullable|exists:party_addresses,id',
            'shipping_address_id' => 'nullable|exists:party_addresses,id',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'po_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'submit_for_approval' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.batch_no' => 'nullable|string|max:60',
            'items.*.batch_name' => 'nullable|string|max:60',
        ]);

        foreach ($validated['items'] as &$item) {
            if (isset($item['batch_name']) && !isset($item['batch_no'])) {
                $item['batch_no'] = $item['batch_name'];
            }
        }
        unset($item);

        $this->financialYearService->assertOpen($validated['po_date']);

        $validated['submit_for_approval'] = $request->boolean('submit_for_approval');

        $order = $this->purchaseOrderService->create($validated, $request->user());

        return $this->flashSuccess('Purchase order created.', 'purchasing.orders.show', ['order' => $order]);
    }

    public function show(PurchaseOrder $order): View
    {
        $order->load(['items.product', 'items.uom', 'supplier', 'warehouse', 'creator', 'approver', 'inwards']);

        return view('purchasing.orders.show', compact('order'));
    }

    public function preview(PurchaseOrder $order): View
    {
        $order->load(['items.product', 'items.uom', 'supplier', 'warehouse', 'creator', 'approver']);
        $company = $order->company ?? \App\Domains\Organization\Models\Company::query()->find(auth()->user()?->company_id) ?? \App\Domains\Organization\Models\Company::query()->first();

        return view('purchasing.orders.preview', [
            'order' => $order,
            'company' => $company,
        ]);
    }

    public function export(PurchaseOrder $order, string $format): Response
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['pdf', 'xlsx', 'excel', 'csv'], true), 404);

        return DocumentExporter::exportPurchaseOrder($order, $format);
    }

    public function approve(PurchaseOrder $order): RedirectResponse
    {
        try {
            $this->purchaseOrderService->approve($order, request()->user());
        } catch (\Throwable $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Purchase order approved.');
    }

    public function submit(PurchaseOrder $order): RedirectResponse
    {
        try {
            $this->purchaseOrderService->submitForApproval($order);
        } catch (\Throwable $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Purchase order submitted for approval.');
    }

    public function cancel(PurchaseOrder $order): RedirectResponse
    {
        try {
            $this->purchaseOrderService->cancel($order);
        } catch (\Throwable $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Purchase order cancelled.');
    }

    public function receiveForm(PurchaseOrder $order): View|RedirectResponse
    {
        if (! $order->isReceivable()) {
            return $this->flashError('This purchase order cannot be received.', 'purchasing.orders.show', ['order' => $order]);
        }

        $order->load(['items.product', 'items.uom', 'supplier']);

        return view('purchasing.orders.receive', [
            'order' => $order,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function receive(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'inward_date' => 'required|date',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'supplier_challan_no' => 'nullable|string|max:60',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.received_qty' => 'nullable|numeric|min:0',
            'items.*.accepted_qty' => 'nullable|numeric|min:0',
            'items.*.rejected_qty' => 'nullable|numeric|min:0',
            'items.*.batch_no' => 'nullable|string|max:60',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.serials' => 'nullable|string',
        ]);

        foreach ($validated['items'] as $i => $item) {
            if (! empty($item['serials'])) {
                $validated['items'][$i]['serials'] = preg_split('/[\s,;]+/', $item['serials'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
            }
        }

        $this->financialYearService->assertOpen($validated['inward_date']);

        try {
            $inward = $this->purchaseOrderService->receive($order, $validated, $request->user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Goods received.', 'purchasing.inwards.show', ['inward' => $inward]);
    }

    protected function formData(): array
    {
        return [
            'suppliers' => Customer::query()
                ->where('is_active', true)
                ->whereIn('party_type', [Customer::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier', Customer::PARTY_TYPE_BOTH])
                ->orderBy('name')
                ->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}