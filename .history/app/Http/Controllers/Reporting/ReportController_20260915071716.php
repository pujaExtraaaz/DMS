<?php

namespace App\Http\Controllers\Reporting;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Delivery\Models\Delivery;
use App\Domains\Inventory\Models\Purchase;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ReportExporter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $invoices = Invoice::query()
            ->with(['customer', 'items.product', 'items.uom', 'salesperson'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('salesperson_id'), fn ($q) => $q->where('salesperson_id', $request->salesperson_id))
            ->when($request->filled('branch_id'), fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('branch_id', $request->branch_id)))
            ->when($request->filled('product_id') || $request->filled('brand_id') || $request->filled('category_id'), function ($q) use ($request) {
                $q->whereHas('items.product', function ($p) use ($request) {
                    $p->when($request->filled('product_id'), fn ($x) => $x->where('id', $request->product_id))
                        ->when($request->filled('brand_id'), fn ($x) => $x->where('brand_id', $request->brand_id))
                        ->when($request->filled('category_id'), fn ($x) => $x->where('category_id', $request->category_id));
                });
            })
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

        $export = $request->input('export');
        if (in_array($export, ['csv', 'pdf'], true)) {
            $headers = ['Date', 'Invoice No', 'Customer', 'Salesperson', 'Subtotal', 'Tax', 'Grand Total', 'Paid', 'Outstanding', 'Status'];
            $rows = $invoices->map(fn (Invoice $i) => [
                optional($i->invoice_date)->format('Y-m-d'),
                $i->invoice_no,
                $i->customer?->name,
                $i->salesperson?->name,
                number_format((float) $i->subtotal, 2, '.', ''),
                number_format((float) $i->tax_amount, 2, '.', ''),
                number_format((float) $i->grand_total, 2, '.', ''),
                number_format((float) $i->paid_amount, 2, '.', ''),
                number_format(((float) $i->grand_total) - ((float) $i->paid_amount), 2, '.', ''),
                $i->status,
            ])->all();
            $filename = 'sales-'.$dateFrom.'-to-'.$dateTo.'.'.$export;
            $meta = ['Period' => "{$dateFrom} → {$dateTo}", 'Invoices' => $summary['count'], 'Grand Total' => '₹ '.number_format($summary['total'], 2)];

            return $export === 'csv'
                ? ReportExporter::csv($filename, $headers, $rows)
                : ReportExporter::pdf($filename, 'Sales Report', $headers, $rows, $meta);
        }

        return view('reporting.sales', array_merge([
            'invoices' => $invoices,
            'summary' => $summary,
            'byStatus' => $byStatus,
            'byCustomer' => $byCustomer,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], $this->filterOptions()));
    }

    public function stock(Request $request)
    {
        $stockLevels = StockLevel::query()
            ->with(['product.brand', 'product.category', 'uom', 'warehouse'])
            ->when($request->boolean('low_only'), fn ($q) => $q->where('quantity', '<', 10))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('brand_id') || $request->filled('category_id'), function ($q) use ($request) {
                $q->whereHas('product', function ($p) use ($request) {
                    $p->when($request->filled('brand_id'), fn ($x) => $x->where('brand_id', $request->brand_id))
                        ->when($request->filled('category_id'), fn ($x) => $x->where('category_id', $request->category_id));
                });
            })
            ->orderBy('product_id')
            ->get();

        $export = $request->input('export');
        if (in_array($export, ['csv', 'pdf'], true)) {
            $headers = ['Product', 'Brand', 'Category', 'Warehouse', 'UOM', 'Quantity'];
            $rows = $stockLevels->map(fn ($l) => [
                $l->product?->name,
                $l->product?->brand?->name,
                $l->product?->category?->name,
                $l->warehouse?->name,
                $l->uom?->code,
                (float) $l->quantity,
            ])->all();
            $filename = 'stock-'.now()->format('Ymd-His').'.'.$export;

            return $export === 'csv'
                ? ReportExporter::csv($filename, $headers, $rows)
                : ReportExporter::pdf($filename, 'Stock Report', $headers, $rows, ['Generated' => now()->format('d M Y H:i')]);
        }

        return view('reporting.stock', array_merge(compact('stockLevels'), $this->filterOptions()));
    }

    public function payments(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $payments = Payment::query()
            ->with(['customer', 'invoice'])
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('salesperson_id'), fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('salesperson_id', $request->salesperson_id)))
            ->orderBy('paid_at')
            ->get();

        $byMethod = $payments->groupBy('method')->map->sum('amount');

        return view('reporting.payments', array_merge(
            compact('payments', 'byMethod', 'dateFrom', 'dateTo'),
            $this->filterOptions()
        ));
    }

    public function outstanding(Request $request)
    {
        $customers = Customer::where('is_active', true)
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('id', $request->customer_id))
            ->orderBy('name')
            ->get();

        $balances = $customers->map(function ($customer) {
            $latest = OutstandingLedger::where('customer_id', $customer->id)->orderByDesc('id')->first();

            return [
                'customer' => $customer,
                'balance' => (float) ($latest?->balance ?? 0),
            ];
        })->filter(fn ($row) => $row['balance'] != 0)->values();

        $export = $request->input('export');
        if (in_array($export, ['csv', 'pdf'], true)) {
            $headers = ['Customer', 'Balance'];
            $rows = $balances->map(fn ($r) => [$r['customer']->name, number_format((float) $r['balance'], 2, '.', '')])->all();
            $filename = 'outstanding-'.now()->format('Ymd-His').'.'.$export;

            return $export === 'csv'
                ? ReportExporter::csv($filename, $headers, $rows)
                : ReportExporter::pdf($filename, 'Outstanding Report', $headers, $rows, ['Total' => '₹ '.number_format($balances->sum('balance'), 2)]);
        }

        return view('reporting.outstanding', array_merge(compact('balances'), $this->filterOptions()));
    }

    public function delivery(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $deliveries = Delivery::query()
            ->with(['customer', 'invoice', 'loadSheet'])
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('salesperson_id'), fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('salesperson_id', $request->salesperson_id)))
            ->latest()
            ->get();

        $summary = $deliveries->groupBy('status')->map->count();

        return view('reporting.delivery', array_merge(
            compact('deliveries', 'summary', 'dateFrom', 'dateTo'),
            $this->filterOptions()
        ));
    }

    public function pendingOrders(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $orders = Order::query()
            ->with(['customer', 'items.product', 'items.uom', 'salesperson'])
            ->whereIn('status', ['pending', 'approved'])
            ->when($dateFrom, fn ($q) => $q->whereDate('order_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('order_date', '<=', $dateTo))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('salesperson_id'), fn ($q) => $q->where('salesperson_id', $request->salesperson_id))
            ->when($request->filled('branch_id'), fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('branch_id', $request->branch_id)))
            ->when($request->filled('product_id') || $request->filled('brand_id') || $request->filled('category_id'), function ($q) use ($request) {
                $q->whereHas('items.product', function ($p) use ($request) {
                    $p->when($request->filled('product_id'), fn ($x) => $x->where('id', $request->product_id))
                        ->when($request->filled('brand_id'), fn ($x) => $x->where('brand_id', $request->brand_id))
                        ->when($request->filled('category_id'), fn ($x) => $x->where('category_id', $request->category_id));
                });
            })
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

        $export = $request->input('export');
        if (in_array($export, ['csv', 'pdf'], true)) {co,
            $headers = ['Order No', 'Order Date', 'Customer', 'Mode', 'Due', 'Ordered', 'Reserved', 'Delivered', 'Pending', 'Back Order'];
            $exportRows = $rows->map(fn ($r) => [
                $r['order']->order_no,
                optional($r['order']->order_date)->format('Y-m-d'),
                $r['order']->customer?->name,
                $r['order']->fulfilment_mode,
                optional($r['order']->due_date)->format('Y-m-d'),
                $r['ordered'], $r['reserved'], $r['delivered'], $r['pending'], $r['back_order'],
            ])->all();
            $filename = 'pending-orders-'.now()->format('Ymd-His').'.'.$export;

            return $export === 'csv'
                ? ReportExporter::csv($filename, $headers, $exportRows)
                : ReportExporter::pdf($filename, 'Pending Orders', $headers, $exportRows, ['Period' => trim(($dateFrom ?? '')." → ".($dateTo ?? ''), ' →')]);
        }

        return view('reporting.pending-orders', array_merge([
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], $this->filterOptions()));
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

        return view('reporting.party-statement', array_merge([
            'customer' => $customer,
            'entries' => $entries,
            'opening' => $opening,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], $this->filterOptions()));
    }

    public function salesmanOutstanding(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $invoices = Invoice::query()
            ->with(['customer', 'salesperson'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->when($request->filled('salesperson_id'), fn ($q) => $q->where('salesperson_id', $request->salesperson_id))
            ->when($request->filled('branch_id'), fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('branch_id', $request->branch_id)))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
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

        return view('reporting.salesman-outstanding', array_merge([
            'rows' => $invoices,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], $this->filterOptions()));
    }

    public function stockLedger(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $movements = StockMovement::query()
            ->with(['product', 'uom', 'warehouse'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('brand_id') || $request->filled('category_id'), function ($q) use ($request) {
                $q->whereHas('product', function ($p) use ($request) {
                    $p->when($request->filled('brand_id'), fn ($x) => $x->where('brand_id', $request->brand_id))
                        ->when($request->filled('category_id'), fn ($x) => $x->where('category_id', $request->category_id));
                });
            })
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('reporting.stock-ledger', array_merge([
            'movements' => $movements,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], $this->filterOptions()));
    }

    public function purchaseRegister(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $purchases = Purchase::query()
            ->with(['items.product', 'creator', 'supplierParty', 'warehouse'])
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_party_id', $request->supplier_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('product_id') || $request->filled('brand_id') || $request->filled('category_id'), function ($q) use ($request) {
                $q->whereHas('items.product', function ($p) use ($request) {
                    $p->when($request->filled('product_id'), fn ($x) => $x->where('id', $request->product_id))
                        ->when($request->filled('brand_id'), fn ($x) => $x->where('brand_id', $request->brand_id))
                        ->when($request->filled('category_id'), fn ($x) => $x->where('category_id', $request->category_id));
                });
            })
            ->latest('purchase_date')
            ->paginate(30)
            ->withQueryString();

        return view('reporting.purchase-register', array_merge(
            compact('purchases', 'dateFrom', 'dateTo'),
            $this->filterOptions()
        ));
    }

    public function margin(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $invoices = Invoice::query()
            ->with(['customer', 'items.product', 'deal.expenses', 'salesperson'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('salesperson_id'), fn ($q) => $q->where('salesperson_id', $request->salesperson_id))
            ->when($request->filled('product_id') || $request->filled('brand_id') || $request->filled('category_id'), function ($q) use ($request) {
                $q->whereHas('items.product', function ($p) use ($request) {
                    $p->when($request->filled('product_id'), fn ($x) => $x->where('id', $request->product_id))
                        ->when($request->filled('brand_id'), fn ($x) => $x->where('brand_id', $request->brand_id))
                        ->when($request->filled('category_id'), fn ($x) => $x->where('category_id', $request->category_id));
                });
            })
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

        return view('reporting.margin', array_merge([
            'rows' => $invoices,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], $this->filterOptions()));
    }

    public function aging(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $rows = Invoice::query()
            ->with(['customer', 'salesperson'])
            ->whereRaw('grand_total > paid_amount')
            ->when($dateFrom, fn ($q) => $q->whereDate('invoice_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('invoice_date', '<=', $dateTo))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('salesperson_id'), fn ($q) => $q->where('salesperson_id', $request->salesperson_id))
            ->when($request->filled('branch_id'), fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('branch_id', $request->branch_id)))
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

        $export = $request->input('export');
        if (in_array($export, ['csv', 'pdf'], true)) {
            $headers = ['Bucket', 'Customer', 'Invoice', 'Due Date', 'Overdue Days', 'Outstanding'];
            $exportRows = [];
            foreach ($rows as $bucket => $bucketRows) {
                foreach ($bucketRows as $r) {
                    $exportRows[] = [
                        $bucket,
                        $r['invoice']->customer?->name,
                        $r['invoice']->invoice_no,
                        $r['due_date']->format('Y-m-d'),
                        $r['overdue_days'],
                        number_format((float) $r['outstanding'], 2, '.', ''),
                    ];
                }
            }
            $filename = 'aging-'.now()->format('Ymd-His').'.'.$export;

            return $export === 'csv'
                ? ReportExporter::csv($filename, $headers, $exportRows)
                : ReportExporter::pdf($filename, 'Outstanding Aging', $headers, $exportRows, ['Generated' => now()->format('d M Y H:i')]);
        }

        return view('reporting.aging', array_merge([
            'groups' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], $this->filterOptions()));
    }

    /**
     * Shared SRS filter option lists for report views.
     *
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        return [
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Customer::query()
                ->where('is_active', true)
                ->whereIn('party_type', ['supplier', 'both'])
                ->orderBy('name')
                ->get(),
            'salespeople' => User::orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->limit(500)->get(),
        ];
    }
}
