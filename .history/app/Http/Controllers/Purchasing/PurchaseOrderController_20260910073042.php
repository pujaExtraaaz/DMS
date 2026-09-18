<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(protected PurchaseOrderService $purchaseOrderService) {}

    public function index(Request $request): View
    {
        $orders = PurchaseOrder::query()
            ->with(['supplier', 'warehouse', 'creator'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('po_no', 'like', '%'.$request->search.'%'))
            ->latest('po_date')
            ->paginate(15)
            ->withQueryString();

        return view('purchasing.orders.index', compact('orders'));
    }

    public function create(): View
    {
        return view('purchasing.orders.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:customers,id',
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
            'items.*.tax_percent' => 'nullable|numeric|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
        ]);

        $validated['submit_for_approval'] = $request->boolean('submit_for_approval');

        $order = $this->purchaseOrderService->create($validated, $request->user());

        return $this->flashSuccess('Purchase order created.', 'purchasing.orders.show', ['order' => $order]);
    }

    public function show(PurchaseOrder $order): View
    {
        $order->load(['items.product', 'items.uom', 'supplier', 'warehouse', 'creator', 'approver', 'inwards']);

        return view('purchasing.orders.show', compact('order'));
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
                ->whereIn('party_type', ['supplier', 'both'])
                ->orderBy('name')
                ->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
