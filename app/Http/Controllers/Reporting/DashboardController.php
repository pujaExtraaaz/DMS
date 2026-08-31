<?php

namespace App\Http\Controllers\Reporting;

use App\Domains\Delivery\Models\Delivery;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $lastMonthStart = now()->subMonth()->startOfMonth()->toDateString();
        $lastMonthEnd = now()->subMonth()->endOfMonth()->toDateString();

        $todaySales = (float) Invoice::whereDate('invoice_date', $today)->sum('grand_total');
        $monthSales = (float) Invoice::whereDate('invoice_date', '>=', $startOfMonth)->sum('grand_total');
        $lastMonthSales = (float) Invoice::whereBetween('invoice_date', [$lastMonthStart, $lastMonthEnd])->sum('grand_total');
        $salesGrowth = $lastMonthSales > 0
            ? round((($monthSales - $lastMonthSales) / $lastMonthSales) * 100, 1)
            : ($monthSales > 0 ? 100 : 0);

        $todayOrders = Order::whereDate('order_date', $today)->count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $approvedOrders = Order::where('status', 'approved')->count();
        $pendingDeliveries = Delivery::whereIn('status', ['pending', 'out_for_delivery'])->count();
        $todayCollections = (float) Payment::whereDate('paid_at', $today)->where('status', 'completed')->sum('amount');
        $lowStock = StockLevel::where('quantity', '<', 10)->count();

        $outstanding = OutstandingLedger::query()
            ->selectRaw('customer_id, MAX(id) as latest_id')
            ->groupBy('customer_id')
            ->get()
            ->sum(fn ($row) => max(0, (float) OutstandingLedger::find($row->latest_id)?->balance));

        $stats = [
            'today_sales' => $todaySales,
            'month_sales' => $monthSales,
            'sales_growth' => $salesGrowth,
            'today_orders' => $todayOrders,
            'pending_orders' => $pendingOrders,
            'approved_orders' => $approvedOrders,
            'pending_deliveries' => $pendingDeliveries,
            'today_collections' => $todayCollections,
            'low_stock' => $lowStock,
            'outstanding' => $outstanding,
            'total_invoices' => Invoice::whereDate('invoice_date', '>=', $startOfMonth)->count(),
            'delivered_today' => Delivery::where('status', 'delivered')->whereDate('updated_at', $today)->count(),
        ];

        // Last 7 days sales chart
        $salesTrend = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);

            return [
                'label' => $date->format('D'),
                'date' => $date->toDateString(),
                'sales' => (float) Invoice::whereDate('invoice_date', $date)->sum('grand_total'),
                'orders' => Order::whereDate('order_date', $date)->count(),
            ];
        });

        // Order status breakdown
        $orderStatusBreakdown = Order::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Payment methods this month
        $paymentMethods = Payment::query()
            ->where('status', 'completed')
            ->whereDate('paid_at', '>=', $startOfMonth)
            ->select('method', DB::raw('sum(amount) as total'))
            ->groupBy('method')
            ->pluck('total', 'method')
            ->toArray();

        // Top customers by sales this month
        $topCustomers = Invoice::query()
            ->whereDate('invoice_date', '>=', $startOfMonth)
            ->select('customer_id', DB::raw('SUM(grand_total) as total'))
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->load('customer:id,name');

        $recentOrders = Order::with('customer')
            ->latest('order_date')
            ->limit(8)
            ->get();

        $recentInvoices = Invoice::with('customer')
            ->latest('invoice_date')
            ->limit(5)
            ->get();

        return view('reporting.dashboard', compact(
            'stats',
            'salesTrend',
            'orderStatusBreakdown',
            'paymentMethods',
            'topCustomers',
            'recentOrders',
            'recentInvoices',
        ));
    }
}
