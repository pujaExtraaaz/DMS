<?php

namespace App\Http\Controllers\Reporting;

use App\Domains\Delivery\Models\Delivery;
use App\Domains\Inventory\Models\Purchase;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $invoices = Invoice::query()
            ->with(['customer', 'items.product', 'items.uom'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->orderByDesc('invoice_date')
            ->get();

        $totalSubtotal = $invoices->sum('subtotal');
        $totalTax = $invoices->sum('tax_amount');
        $totalGross = $invoices->sum('grand_total');
        $totalCollected = $invoices->sum('paid_amount');
        $totalOutstanding = $totalGross - $totalCollected;

        $summary = [
            'count' => $invoices->count(),
            'subtotal' => $totalSubtotal,
            'tax' => $totalTax,
            'total' => $totalGross,
            'collected' => $totalCollected,
            'outstanding' => $totalOutstanding,
            'percentage_collected' => $totalGross > 0 ? round(($totalCollected / $totalGross) * 100, 2) : 0,
        ];

        $byStatus = $invoices->groupBy('status')->map->count();
        $byCustomer = $invoices->groupBy('customer_id')->map(fn ($group) => [
            'customer' => $group->first()->customer->name,
            'count' => $group->count(),
            'total' => $group->sum('grand_total'),
            'collected' => $group->sum('paid_amount'),
        ]);

        return view('reporting.sales', [
            'invoices' => $invoices,
            'summary' => $summary,
            'byStatus' => $byStatus,
            'byCustomer' => $byCustomer,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function stock(Request $request): View
    {
        $stockLevels = StockLevel::query()
            ->with(['product', 'uom'])
            ->when($request->boolean('low_only'), fn ($q) => $q->where('quantity', '<', 10))
            ->orderBy('product_id')
            ->get();

        return view('reporting.stock', compact('stockLevels'));
    }

    public function payments(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $payments = Payment::query()
            ->with(['customer', 'invoice'])
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->orderBy('paid_at')
            ->get();

        $byMethod = $payments->groupBy('method')->map->sum('amount');

        return view('reporting.payments', compact('payments', 'byMethod', 'dateFrom', 'dateTo'));
    }

    public function outstanding(Request $request): View
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        $balances = $customers->map(function ($customer) {
            $latest = OutstandingLedger::where('customer_id', $customer->id)->orderByDesc('id')->first();

            return [
                'customer' => $customer,
                'balance' => (float) ($latest?->balance ?? 0),
            ];
        })->filter(fn ($row) => $row['balance'] != 0)->values();

        return view('reporting.outstanding', compact('balances'));
    }

    public function delivery(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $deliveries = Delivery::query()
            ->with(['customer', 'invoice', 'loadSheet'])
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $summary = $deliveries->groupBy('status')->map->count();

        return view('reporting.delivery', compact('deliveries', 'summary', 'dateFrom', 'dateTo'));
    }

    public function pendingOrders(Request $request): View
    {
        $orders = Order::query()
            ->with(['customer', 'items.product', 'items.uom'])
            ->whereIn('status', ['pending', 'approved'])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('fulfilment_mode'), fn ($q) => $q->where('fulfilment_mode', $request->fulfilment_mode))
            ->when($request->boolean('back_order_only'), fn ($q) => $q->where('back_order', true))
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderByDesc('order_date')
            ->get();

        $rows = $orders->map(function (Order $order) {
            $ordered = (float) $order->items->sum('quantity');
            $reserved = (float) $order->items->sum('reserved_qty');
            $delivered = (float) $order->items->sum('delivered_qty');
            $backOrder = (float) $order->items->sum('back_order_qty');

            return [
                'order' => $order,
                'ordered' => $ordered,
                'reserved' => $reserved,
                'delivered' => $delivered,
                'pending' => max(0, $ordered - $delivered),
                'back_order' => $backOrder,
            ];
        });

        return view('reporting.pending-orders', [
            'rows' => $rows,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function partyStatement(Request $request): View
    {
        $customerId = $request->input('customer_id');
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $entries = collect();
        $customer = null;
        $opening = 0.0;

        if ($customerId) {
            $customer = Customer::findOrFail($customerId);

            $opening = (float) (OutstandingLedger::query()
                ->where('customer_id', $customerId)
                ->whereDate('created_at', '<', $dateFrom)
                ->orderByDesc('id')
                ->value('balance') ?? 0);

            $entries = OutstandingLedger::query()
                ->where('customer_id', $customerId)
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->orderBy('id')
                ->get();
        }

        return view('reporting.party-statement', [
            'customer' => $customer,
            'entries' => $entries,
            'opening' => $opening,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function salesmanOutstanding(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $invoices = Invoice::query()
            ->with(['customer', 'salesperson'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->when($request->filled('salesperson_id'), fn ($q) => $q->where('salesperson_id', $request->salesperson_id))
            ->whereRaw('grand_total > paid_amount')
            ->orderBy('salesperson_id')
            ->orderBy('invoice_date')
            ->get()
            ->map(function (Invoice $invoice) {
                $outstanding = (float) $invoice->grand_total - (float) $invoice->paid_amount;
                $due = $invoice->due_date ? $invoice->due_date->copy() : $invoice->invoice_date->copy()->addDays((int) ($invoice->credit_days ?? 0));
                $overdueDays = max(0, now()->startOfDay()->diffInDays($due->startOfDay(), false) * -1);

                return [
                    'invoice' => $invoice,
                    'outstanding' => $outstanding,
                    'due_date' => $due,
                    'overdue_days' => $overdueDays,
                    'bucket' => $overdueDays <= 0 ? 'current' : ($overdueDays <= 30 ? '1-30' : ($overdueDays <= 60 ? '31-60' : ($overdueDays <= 90 ? '61-90' : '90+'))),
                ];
            });

        return view('reporting.salesman-outstanding', [
            'rows' => $invoices,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'salespeople' => User::orderBy('name')->get(),
        ]);
    }

    public function stockLedger(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $movements = StockMovement::query()
            ->with(['product', 'uom'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('reporting.stock-ledger', [
            'movements' => $movements,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function purchaseRegister(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $purchases = Purchase::query()
            ->with(['items.product', 'creator'])
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->latest('purchase_date')
            ->paginate(30)
            ->withQueryString();

        return view('reporting.purchase-register', compact('purchases', 'dateFrom', 'dateTo'));
    }

    public function margin(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $invoices = Invoice::query()
            ->with(['customer', 'items.product', 'deal.expenses'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->latest('invoice_date')
            ->get()
            ->map(function (Invoice $invoice) {
                $sale = (float) $invoice->grand_total;
                $cost = (float) $invoice->items->sum(function ($item) {
                    return (float) $item->quantity * (float) ($item->product?->purchase_price ?? 0);
                });
                $dealExpense = (float) ($invoice->deal?->expenses?->where('status', 'approved')->sum('amount') ?? 0);
                $gross = $sale - $cost;
                $net = $gross - $dealExpense;

                return [
                    'invoice' => $invoice,
                    'sale' => $sale,
                    'cost' => $cost,
                    'deal_expense' => $dealExpense,
                    'gross' => $gross,
                    'net' => $net,
                    'gross_pct' => $sale > 0 ? round(($gross / $sale) * 100, 2) : 0,
                    'net_pct' => $sale > 0 ? round(($net / $sale) * 100, 2) : 0,
                ];
            });

        return view('reporting.margin', [
            'rows' => $invoices,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function aging(Request $request): View
    {
        $rows = Invoice::query()
            ->with(['customer', 'salesperson'])
            ->whereRaw('grand_total > paid_amount')
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->orderBy('invoice_date')
            ->get()
            ->map(function (Invoice $invoice) {
                $outstanding = (float) $invoice->grand_total - (float) $invoice->paid_amount;
                $due = $invoice->due_date ? $invoice->due_date->copy() : $invoice->invoice_date->copy()->addDays((int) ($invoice->credit_days ?? 0));
                $overdueDays = max(0, now()->startOfDay()->diffInDays($due->copy()->startOfDay(), false) * -1);
                $bucket = $overdueDays <= 0 ? 'Current' : ($overdueDays <= 30 ? '1-30' : ($overdueDays <= 60 ? '31-60' : ($overdueDays <= 90 ? '61-90' : ($overdueDays <= 180 ? '91-180' : '180+'))));

                return compact('invoice', 'outstanding', 'due', 'overdueDays', 'bucket') + [
                    'due_date' => $due,
                    'overdue_days' => $overdueDays,
                ];
            })
            ->groupBy('bucket');

        return view('reporting.aging', [
            'groups' => $rows,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
