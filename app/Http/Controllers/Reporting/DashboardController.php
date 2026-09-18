<?php

namespace App\Http\Controllers\Reporting;

use App\Domains\Delivery\Models\Delivery;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Master\Models\Customer;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Target\Models\TargetAchievement;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to, $dateRange] = $this->resolveDateRange($request);

        $rangeSales = (float) Invoice::whereBetween('invoice_date', [$from, $to])->sum('grand_total');
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

        $rangeOrders = Order::whereBetween('order_date', [$from, $to])->count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $approvedOrders = Order::where('status', 'approved')->count();
        $pendingDeliveries = Delivery::whereIn('status', ['pending', 'out_for_delivery'])->count();
        $rangeCollections = (float) Payment::whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->where('status', 'completed')
            ->sum('amount');
        $lowStock = StockLevel::where('quantity', '<', 10)->count();

        $outstanding = (float) OutstandingLedger::query()
            ->from('outstanding_ledger as ol')
            ->joinSub(
                OutstandingLedger::query()
                    ->select('customer_id')
                    ->selectRaw('MAX(id) as latest_id')
                    ->groupBy('customer_id'),
                'latest',
                function ($join) {
                    $join->on('ol.id', '=', 'latest.latest_id');
                }
            )
            ->where('ol.balance', '>', 0)
            ->sum('ol.balance');

        $overdueAmount = (float) Invoice::query()
            ->whereNotIn('status', ['cancelled', 'paid', 'draft'])
            ->whereColumn('paid_amount', '<', 'grand_total')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->selectRaw('COALESCE(SUM(grand_total - paid_amount), 0) as due_total')
            ->value('due_total');

        $frozenParties = Customer::query()->where('credit_status', 'frozen')->count();
        $bounceRisk = Customer::query()->where('cheque_bounce_count', '>=', 2)->count()
            + Cheque::query()->where('status', 'bounced')->whereDate('updated_at', '>=', $from)->count();

        $marginStub = round($rangeSales * 0.12, 2);
        $deadStockCount = StockLevel::query()->where('quantity', '>', 0)->where('quantity', '<', 2)->count();
        $targetsAchievement = (float) TargetAchievement::query()
            ->whereBetween('calculated_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->avg('achievement_percent') ?: 0;

        $stats = [
            'today_sales' => $todaySales,
            'month_sales' => $monthSales,
            'range_sales' => $rangeSales,
            'sales_growth' => $salesGrowth,
            'today_orders' => $rangeOrders,
            'pending_orders' => $pendingOrders,
            'approved_orders' => $approvedOrders,
            'pending_deliveries' => $pendingDeliveries,
            'today_collections' => $rangeCollections,
            'low_stock' => $lowStock,
            'outstanding' => $outstanding,
            'total_invoices' => Invoice::whereBetween('invoice_date', [$from, $to])->count(),
            'delivered_today' => Delivery::where('status', 'delivered')->whereBetween('updated_at', [$from.' 00:00:00', $to.' 23:59:59'])->count(),
            'overdue' => $overdueAmount,
            'frozen_parties' => $frozenParties,
            'bounce_risk' => $bounceRisk,
            'margin_stub' => $marginStub,
            'dead_stock' => $deadStockCount,
            'targets_achievement' => round($targetsAchievement, 1),
        ];

        $days = max(1, Carbon::parse($from)->diffInDays(Carbon::parse($to)));
        $trendDays = min($days, 13);
        $salesTrend = collect(range($trendDays, 0))->map(function ($daysAgo) use ($to) {
            $date = Carbon::parse($to)->subDays($daysAgo);

            return [
                'label' => $date->format('D'),
                'date' => $date->toDateString(),
                'sales' => (float) Invoice::whereDate('invoice_date', $date)->sum('grand_total'),
                'orders' => Order::whereDate('order_date', $date)->count(),
            ];
        });

        $orderStatusBreakdown = Order::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $paymentMethods = Payment::query()
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->select('method', DB::raw('sum(amount) as total'))
            ->groupBy('method')
            ->pluck('total', 'method')
            ->toArray();

        $topCustomers = Invoice::query()
            ->whereBetween('invoice_date', [$from, $to])
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
            'dateRange',
            'from',
            'to',
        ));
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    protected function resolveDateRange(Request $request): array
    {
        $range = $request->input('date_range', 'month');

        $map = match ($range) {
            'today' => [now()->toDateString(), now()->toDateString()],
            'yesterday' => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
            'week' => [now()->startOfWeek()->toDateString(), now()->toDateString()],
            'quarter' => [now()->firstOfQuarter()->toDateString(), now()->toDateString()],
            'year' => [now()->startOfYear()->toDateString(), now()->toDateString()],
            'custom' => [
                $request->input('date_from', now()->startOfMonth()->toDateString()),
                $request->input('date_to', now()->toDateString()),
            ],
            default => [now()->startOfMonth()->toDateString(), now()->toDateString()],
        };

        return [$map[0], $map[1], $range === 'custom' ? 'custom' : $range];
    }
}
