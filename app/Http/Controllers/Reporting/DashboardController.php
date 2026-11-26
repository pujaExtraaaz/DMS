<?php

namespace App\Http\Controllers\Reporting;

use App\Domains\Delivery\Models\Delivery;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Master\Models\Customer;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\SupplierPayable;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Target\Models\TargetAchievement;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $companyId = auth()->user()?->company_id;

        [$from, $to, $canonicalRange, $dateRangeLabel, $showingText] = $this->resolveDateRange($request, $companyId);

        $dateRangeOptions = [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'quarter' => 'This Quarter',
            'this_financial_year' => 'This Financial Year',
            'last_financial_year' => 'Last Financial Year',
            'all_time' => 'All Time',
            'custom' => 'Custom Date Range',
        ];

        // Helper to apply robust date boundaries across date & datetime fields in both MySQL and SQLite
        $filterDate = function ($query, string $column) use ($from, $to) {
            if ($from && $to) {
                if ($from === $to) {
                    $query->whereDate($column, $from);
                } else {
                    $query->whereDate($column, '>=', $from)->whereDate($column, '<=', $to);
                }
            } elseif ($from) {
                $query->whereDate($column, '>=', $from);
            } elseif ($to) {
                $query->whereDate($column, '<=', $to);
            }
        };

        // 1. Sales metrics (Filtered by business transaction date: invoice_date)
        $salesQuery = Invoice::query()
            ->where('status', '!=', 'cancelled')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->tap(fn ($q) => $filterDate($q, 'invoice_date'));

        $rangeSales = (float) (clone $salesQuery)->sum('grand_total');
        $salesInvoiceCount = (int) (clone $salesQuery)->count();

        $todayDate = now()->toDateString();
        $todaySales = (float) Invoice::query()
            ->where('status', '!=', 'cancelled')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('invoice_date', $todayDate)
            ->sum('grand_total');

        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();
        $monthSales = (float) Invoice::query()
            ->where('status', '!=', 'cancelled')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('invoice_date', '>=', $startOfMonth)
            ->whereDate('invoice_date', '<=', $endOfMonth)
            ->sum('grand_total');

        // 2. Purchase metrics (Filtered by business transaction date: invoice_date)
        $purchasesQuery = PurchaseInvoice::query()
            ->where('status', '!=', 'cancelled')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->tap(fn ($q) => $filterDate($q, 'invoice_date'));

        $purchaseAmount = (float) (clone $purchasesQuery)->sum('grand_total');
        $purchaseInvoiceCount = (int) (clone $purchasesQuery)->count();

        // 3. Growth calculation vs previous equivalent period
        $prevSales = 0.0;
        if ($from && $to) {
            $daysDiff = max(1, Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1);
            $prevFrom = Carbon::parse($from)->subDays($daysDiff)->toDateString();
            $prevTo = Carbon::parse($from)->subDay()->toDateString();
            $prevSales = (float) Invoice::query()
                ->where('status', '!=', 'cancelled')
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->when($prevFrom === $prevTo, fn ($q) => $q->whereDate('invoice_date', $prevFrom), fn ($q) => $q->whereDate('invoice_date', '>=', $prevFrom)->whereDate('invoice_date', '<=', $prevTo))
                ->sum('grand_total');
        }
        $salesGrowth = $prevSales > 0
            ? round((($rangeSales - $prevSales) / $prevSales) * 100, 1)
            : ($rangeSales > 0 ? 100.0 : 0.0);
        $salesGrowthLabel = ($salesGrowth >= 0 ? '+' : '').$salesGrowth.'% vs prev period';
        $salesGrowthType = $salesGrowth >= 0 ? 'positive' : 'negative';

        // 4. Order metrics (Filtered by business transaction date: order_date)
        $ordersBase = Order::query()
            ->when($companyId, fn ($q) => $q->whereHas('customer', fn ($cq) => $cq->where('company_id', $companyId)))
            ->tap(fn ($q) => $filterDate($q, 'order_date'));

        $rangeOrders = (int) (clone $ordersBase)->count();
        $pendingOrders = (int) (clone $ordersBase)->where('status', 'pending')->count();
        $approvedOrders = (int) (clone $ordersBase)->where('status', 'approved')->count();

        // 5. Collections (Filtered by business payment datetime: paid_at)
        $paymentsBase = Payment::query()
            ->where('status', 'completed')
            ->when($companyId, fn ($q) => $q->whereHas('customer', fn ($cq) => $cq->where('company_id', $companyId)))
            ->when($from && $to, fn ($q) => $q->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59']))
            ->when($from && ! $to, fn ($q) => $q->where('paid_at', '>=', $from.' 00:00:00'))
            ->when(! $from && $to, fn ($q) => $q->where('paid_at', '<=', $to.' 23:59:59'));

        $rangeCollections = (float) (clone $paymentsBase)->sum('amount');
        $collectionsCount = (int) (clone $paymentsBase)->count();

        // 6. Receivables & Payables (as of date boundary)
        $asOf = $to ?? now()->toDateString();

        $receivablesQuery = OutstandingLedger::query();
        if ($companyId) {
            $receivablesQuery->whereHas('customer', fn ($cq) => $cq->where('company_id', $companyId));
        }
        if ($to) {
            $receivablesQuery->whereDate('outstanding_ledger.created_at', '<=', $to);
        }
        $outstanding = (float) (clone $receivablesQuery)
            ->joinSub(
                OutstandingLedger::query()
                    ->select('customer_id')
                    ->selectRaw('MAX(id) as latest_id')
                    ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                    ->groupBy('customer_id'),
                'latest',
                function ($join) {
                    $join->on('outstanding_ledger.id', '=', 'latest.latest_id');
                }
            )
            ->where('outstanding_ledger.balance', '>', 0)
            ->sum('outstanding_ledger.balance');

        if ($outstanding <= 0) {
            $outstanding = (float) Invoice::query()
                ->whereNotIn('status', ['cancelled', 'paid'])
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->when($to, fn ($q) => $q->whereDate('invoice_date', '<=', $to))
                ->selectRaw('COALESCE(SUM(grand_total - paid_amount), 0) as balance')
                ->value('balance');
        }

        $payablesQuery = SupplierPayable::query();
        if ($companyId) {
            $payablesQuery->whereHas('supplier', fn ($sq) => $sq->where('company_id', $companyId));
        }
        if ($to) {
            $payablesQuery->whereDate('supplier_payables.created_at', '<=', $to);
        }
        $payables = (float) (clone $payablesQuery)
            ->joinSub(
                SupplierPayable::query()
                    ->select('supplier_id')
                    ->selectRaw('MAX(id) as latest_id')
                    ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                    ->groupBy('supplier_id'),
                'latest',
                function ($join) {
                    $join->on('supplier_payables.id', '=', 'latest.latest_id');
                }
            )
            ->where('supplier_payables.balance', '>', 0)
            ->sum('supplier_payables.balance');

        if ($payables <= 0) {
            $payables = (float) PurchaseInvoice::query()
                ->where('status', '!=', 'cancelled')
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->when($to, fn ($q) => $q->whereDate('invoice_date', '<=', $to))
                ->where(function ($q) {
                    $q->whereNull('due_date')->orWhere('status', '!=', 'paid');
                })
                ->selectRaw('COALESCE(SUM(grand_total), 0) as balance')
                ->value('balance');
        }

        // 7. Deliveries, stock, risk & targets
        $pendingDeliveries = Delivery::query()
            ->whereIn('status', ['pending', 'out_for_delivery'])
            ->when($from && $to, fn ($q) => $q->whereBetween('updated_at', [$from.' 00:00:00', $to.' 23:59:59']))
            ->count();

        $deliveredPeriod = Delivery::query()
            ->where('status', 'delivered')
            ->when($from && $to, fn ($q) => $q->whereBetween('updated_at', [$from.' 00:00:00', $to.' 23:59:59']))
            ->count();

        $lowStock = StockLevel::query()->where('quantity', '<', 10)->count();

        $overdueAmount = (float) Invoice::query()
            ->whereNotIn('status', ['cancelled', 'paid', 'draft'])
            ->whereColumn('paid_amount', '<', 'grand_total')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $asOf)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('COALESCE(SUM(grand_total - paid_amount), 0) as due_total')
            ->value('due_total');

        $frozenParties = Customer::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('credit_status', 'frozen')
            ->count();

        $bounceRisk = Customer::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('cheque_bounce_count', '>=', 2)
            ->count()
            + Cheque::query()
                ->where('status', 'bounced')
                ->when($from, fn ($q) => $q->whereDate('updated_at', '>=', $from))
                ->count();

        $marginStub = round($rangeSales * 0.12, 2);
        $deadStockCount = StockLevel::query()->where('quantity', '>', 0)->where('quantity', '<', 2)->count();

        $targetsAchievement = (float) TargetAchievement::query()
            ->when($from && $to, fn ($q) => $q->whereBetween('calculated_at', [$from.' 00:00:00', $to.' 23:59:59']))
            ->avg('achievement_percent') ?: 0;

        // Card Labels based on chosen date range
        $cardLabels = [
            'sales' => match ($canonicalRange) {
                'today' => "Today's Sales",
                'yesterday' => "Yesterday's Sales",
                'this_week' => "This Week's Sales",
                'last_week' => "Last Week's Sales",
                'this_month' => "This Month's Sales",
                'last_month' => "Last Month's Sales",
                'quarter' => "This Quarter's Sales",
                'this_financial_year' => "This FY Sales",
                'last_financial_year' => "Last FY Sales",
                'all_time' => "Total Sales (All Time)",
                default => "Sales Amount",
            },
            'purchases' => match ($canonicalRange) {
                'today' => "Today's Purchases",
                'yesterday' => "Yesterday's Purchases",
                'this_week' => "This Week's Purchases",
                'last_week' => "Last Week's Purchases",
                'this_month' => "This Month's Purchases",
                'last_month' => "Last Month's Purchases",
                'quarter' => "This Quarter's Purchases",
                'this_financial_year' => "This FY Purchases",
                'last_financial_year' => "Last FY Purchases",
                'all_time' => "Total Purchases (All Time)",
                default => "Purchase Amount",
            },
            'revenue' => match ($canonicalRange) {
                'this_month' => "Month Revenue",
                'quarter' => "Quarter Revenue",
                default => "Period Revenue",
            },
            'collections' => match ($canonicalRange) {
                'today' => "Collections Today",
                'yesterday' => "Collections Yesterday",
                default => "Period Collections",
            },
        ];

        $stats = [
            'today_sales' => $todaySales,
            'month_sales' => $monthSales,
            'range_sales' => $rangeSales,
            'total_sales' => $rangeSales,
            'sales_growth' => $salesGrowth,
            'sales_invoice_count' => $salesInvoiceCount,
            'purchase_amount' => $purchaseAmount,
            'purchase_invoice_count' => $purchaseInvoiceCount,
            'today_orders' => $rangeOrders,
            'range_orders' => $rangeOrders,
            'pending_orders' => $pendingOrders,
            'approved_orders' => $approvedOrders,
            'pending_deliveries' => $pendingDeliveries,
            'today_collections' => $rangeCollections,
            'range_collections' => $rangeCollections,
            'collections_count' => $collectionsCount,
            'low_stock' => $lowStock,
            'outstanding' => $outstanding,
            'receivables' => $outstanding,
            'payables' => $payables,
            'total_invoices' => $salesInvoiceCount,
            'delivered_today' => $deliveredPeriod,
            'overdue' => $overdueAmount,
            'frozen_parties' => $frozenParties,
            'bounce_risk' => $bounceRisk,
            'margin_stub' => $marginStub,
            'dead_stock' => $deadStockCount,
            'targets_achievement' => round($targetsAchievement, 1),
        ];

        // 8. Sales, Purchases & Orders trend chart data
        $salesTrend = $this->buildSalesTrend($from, $to, $companyId);

        // 9. Order Pipeline breakdown
        $orderStatusBreakdown = Order::query()
            ->when($companyId, fn ($q) => $q->whereHas('customer', fn ($cq) => $cq->where('company_id', $companyId)))
            ->tap(fn ($q) => $filterDate($q, 'order_date'))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // 10. Payment methods breakdown
        $paymentMethods = Payment::query()
            ->where('status', 'completed')
            ->when($companyId, fn ($q) => $q->whereHas('customer', fn ($cq) => $cq->where('company_id', $companyId)))
            ->when($from && $to, fn ($q) => $q->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59']))
            ->select('method', DB::raw('sum(amount) as total'))
            ->groupBy('method')
            ->pluck('total', 'method')
            ->toArray();

        // 11. Top Customers
        $topCustomers = Invoice::query()
            ->where('status', '!=', 'cancelled')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->tap(fn ($q) => $filterDate($q, 'invoice_date'))
            ->select('customer_id', DB::raw('SUM(grand_total) as total'))
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->load('customer:id,name');

        $maxCustomerTotal = $topCustomers->max('total') ?: 1;
        $formattedTopCustomers = $topCustomers->values()->map(function ($row, $index) use ($maxCustomerTotal) {
            $total = (float) $row->total;
            return [
                'index' => $index + 1,
                'customer_id' => $row->customer_id,
                'name' => $row->customer?->name ?? 'Unknown',
                'total' => $total,
                'total_formatted' => '₹'.number_format($total, 0),
                'percentage' => min(100, round(($total / $maxCustomerTotal) * 100)),
            ];
        })->toArray();

        // 12. Recent Orders (strictly within selected period)
        $recentOrders = Order::with('customer')
            ->when($companyId, fn ($q) => $q->whereHas('customer', fn ($cq) => $cq->where('company_id', $companyId)))
            ->tap(fn ($q) => $filterDate($q, 'order_date'))
            ->latest('order_date')
            ->limit(8)
            ->get();

        $formattedRecentOrders = $recentOrders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'customer_name' => $order->customer?->name ?? '—',
                'date_formatted' => $order->order_date ? Carbon::parse($order->order_date)->format('M d, Y') : '—',
                'status' => $order->status,
                'status_color' => match ($order->status) {
                    'pending' => 'warning',
                    'approved' => 'primary',
                    'converted' => 'success',
                    'cancelled' => 'danger',
                    default => 'neutral',
                },
                'amount_formatted' => '₹'.number_format((float) $order->grand_total, 2),
                'url' => route('orders.show', $order),
            ];
        })->toArray();

        // 13. Recent Invoices (strictly within selected period)
        $recentInvoices = Invoice::with('customer')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->tap(fn ($q) => $filterDate($q, 'invoice_date'))
            ->latest('invoice_date')
            ->limit(5)
            ->get();

        $formattedRecentInvoices = $recentInvoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'customer_name' => $invoice->customer?->name ?? '—',
                'date_formatted' => $invoice->invoice_date ? Carbon::parse($invoice->invoice_date)->format('M d, Y') : '—',
                'status' => $invoice->status,
                'status_color' => match ($invoice->status) {
                    'paid' => 'success',
                    'partial' => 'warning',
                    default => 'primary',
                },
                'amount_formatted' => '₹'.number_format((float) $invoice->grand_total, 2),
                'url' => route('invoices.show', $invoice),
            ];
        })->toArray();

        $dateRange = $canonicalRange;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'stats' => $stats,
                'cardLabels' => $cardLabels,
                'salesGrowthLabel' => $salesGrowthLabel,
                'salesGrowthType' => $salesGrowthType,
                'salesTrend' => $salesTrend,
                'orderStatusBreakdown' => $orderStatusBreakdown,
                'paymentMethods' => $paymentMethods,
                'topCustomers' => $formattedTopCustomers,
                'recentOrders' => $formattedRecentOrders,
                'recentInvoices' => $formattedRecentInvoices,
                'dateRange' => $dateRange,
                'dateRangeLabel' => $dateRangeLabel,
                'from' => $from,
                'to' => $to,
                'showingText' => $showingText,
            ]);
        }

        return view('reporting.dashboard', compact(
            'stats',
            'cardLabels',
            'salesGrowthLabel',
            'salesGrowthType',
            'salesTrend',
            'orderStatusBreakdown',
            'paymentMethods',
            'topCustomers',
            'formattedTopCustomers',
            'recentOrders',
            'formattedRecentOrders',
            'recentInvoices',
            'formattedRecentInvoices',
            'dateRange',
            'dateRangeLabel',
            'from',
            'to',
            'showingText',
            'dateRangeOptions'
        ));
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: string, 3: string, 4: string}
     */
    protected function resolveDateRange(Request $request, ?int $companyId): array
    {
        $rawRange = strtolower(trim((string) $request->input('date_range', 'this_financial_year')));
        $rawRange = str_replace(['-', ' '], '_', $rawRange);

        $canonical = match ($rawRange) {
            'today' => 'today',
            'yesterday' => 'yesterday',
            'this_week', 'week' => 'this_week',
            'last_week' => 'last_week',
            'this_month', 'month' => 'this_month',
            'last_month' => 'last_month',
            'quarter', 'this_quarter' => 'quarter',
            'this_financial_year', 'financial_year', 'year' => 'this_financial_year',
            'last_financial_year' => 'last_financial_year',
            'all', 'all_time' => 'all_time',
            'custom' => 'custom',
            default => 'this_financial_year',
        };

        $from = null;
        $to = null;
        $label = 'This Month';
        $showing = '';

        switch ($canonical) {
            case 'today':
                $from = now()->toDateString();
                $to = now()->toDateString();
                $label = 'Today';
                $showing = "Showing: {$from} → {$to}";
                break;

            case 'yesterday':
                $from = now()->subDay()->toDateString();
                $to = now()->subDay()->toDateString();
                $label = 'Yesterday';
                $showing = "Showing: {$from} → {$to}";
                break;

            case 'this_week':
                $from = now()->startOfWeek()->toDateString();
                $to = now()->endOfWeek()->toDateString();
                $label = 'This Week';
                $showing = "Showing: {$from} → {$to}";
                break;

            case 'last_week':
                $from = now()->subWeek()->startOfWeek()->toDateString();
                $to = now()->subWeek()->endOfWeek()->toDateString();
                $label = 'Last Week';
                $showing = "Showing: {$from} → {$to}";
                break;

            case 'this_month':
                $from = now()->startOfMonth()->toDateString();
                $to = now()->endOfMonth()->toDateString();
                $label = 'This Month';
                $showing = "Showing: {$from} → {$to}";
                break;

            case 'last_month':
                $from = now()->subMonth()->startOfMonth()->toDateString();
                $to = now()->subMonth()->endOfMonth()->toDateString();
                $label = 'Last Month';
                $showing = "Showing: {$from} → {$to}";
                break;

            case 'quarter':
                // India FY quarters (Apr–Mar): Q1 Apr–Jun, Q2 Jul–Sep, Q3 Oct–Dec, Q4 Jan–Mar
                [$from, $to] = $this->currentFinancialQuarterBounds();
                $label = 'This Quarter';
                $showing = "Showing: {$from} → {$to}";
                break;

            case 'this_financial_year':
                $fy = FinancialYear::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->where('is_current', true)
                    ->first();
                if ($fy && $fy->starts_on && $fy->ends_on) {
                    $from = Carbon::parse($fy->starts_on)->toDateString();
                    $to = Carbon::parse($fy->ends_on)->toDateString();
                } else {
                    $now = now();
                    $startYear = $now->month >= 4 ? $now->year : $now->year - 1;
                    $from = Carbon::create($startYear, 4, 1)->toDateString();
                    $to = Carbon::create($startYear + 1, 3, 31)->toDateString();
                }
                $label = 'This Financial Year';
                $showing = "Showing: {$from} → {$to}";
                break;

            case 'last_financial_year':
                $now = now();
                $currStartYear = $now->month >= 4 ? $now->year : $now->year - 1;
                $currStartsOn = Carbon::create($currStartYear, 4, 1)->toDateString();
                $prevFy = FinancialYear::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereDate('ends_on', '<=', $currStartsOn)
                    ->orderByDesc('ends_on')
                    ->first();
                if ($prevFy && $prevFy->starts_on && $prevFy->ends_on) {
                    $from = Carbon::parse($prevFy->starts_on)->toDateString();
                    $to = Carbon::parse($prevFy->ends_on)->toDateString();
                } else {
                    $prevStartYear = $currStartYear - 1;
                    $from = Carbon::create($prevStartYear, 4, 1)->toDateString();
                    $to = Carbon::create($prevStartYear + 1, 3, 31)->toDateString();
                }
                $label = 'Last Financial Year';
                $showing = "Showing: {$from} → {$to}";
                break;

            case 'all_time':
                $from = null;
                $to = null;
                $label = 'All Time';
                $showing = 'Showing: All Time';
                break;

            case 'custom':
                $from = $request->input('date_from', now()->startOfMonth()->toDateString());
                $to = $request->input('date_to', now()->toDateString());
                if ($from && $to && Carbon::parse($from)->gt(Carbon::parse($to))) {
                    [$from, $to] = [$to, $from];
                }
                $label = 'Custom Date Range';
                $showing = "Showing: {$from} → {$to}";
                break;
        }

        return [$from, $to, $canonical, $label, $showing];
    }

    /**
     * Current financial-year quarter bounds (Apr–Mar FY).
     *
     * @return array{0: string, 1: string}
     */
    protected function currentFinancialQuarterBounds(): array
    {
        $now = now();
        $month = (int) $now->month;
        $year = (int) $now->year;

        // Q1 Apr–Jun, Q2 Jul–Sep, Q3 Oct–Dec, Q4 Jan–Mar (of the FY that started the previous April)
        if ($month >= 4 && $month <= 6) {
            return [
                Carbon::create($year, 4, 1)->toDateString(),
                Carbon::create($year, 6, 30)->toDateString(),
            ];
        }

        if ($month >= 7 && $month <= 9) {
            return [
                Carbon::create($year, 7, 1)->toDateString(),
                Carbon::create($year, 9, 30)->toDateString(),
            ];
        }

        if ($month >= 10 && $month <= 12) {
            return [
                Carbon::create($year, 10, 1)->toDateString(),
                Carbon::create($year, 12, 31)->toDateString(),
            ];
        }

        // Jan–Mar = Q4 of FY that began last April
        return [
            Carbon::create($year, 1, 1)->toDateString(),
            Carbon::create($year, 3, 31)->toDateString(),
        ];
    }

    /**
     * Build daily or monthly sales, purchases, and orders trend data.
     */
    protected function buildSalesTrend(?string $from, ?string $to, ?int $companyId): array
    {
        // 1. Single day (Today / Yesterday): Show 7-day trend ending on $to for rich context
        if ($from && $to && $from === $to) {
            $dates = [];
            for ($i = 6; $i >= 0; $i--) {
                $dates[] = Carbon::parse($to)->subDays($i)->toDateString();
            }
            return $this->buildDailyTrend($dates, $companyId);
        }

        // 2. Range <= 31 days (This Week, Last Week, This Month, Last Month, Custom <= 31 days)
        if ($from && $to) {
            $diffDays = Carbon::parse($from)->diffInDays(Carbon::parse($to));
            if ($diffDays <= 31) {
                $dates = [];
                $curr = Carbon::parse($from);
                $end = Carbon::parse($to);
                while ($curr->lte($end)) {
                    $dates[] = $curr->toDateString();
                    $curr->addDay();
                }
                return $this->buildDailyTrend($dates, $companyId);
            }

            // 3. Multi-month range (Quarter, Financial Year, Custom > 31 days)
            $months = [];
            $curr = Carbon::parse($from)->startOfMonth();
            $end = Carbon::parse($to)->endOfMonth();
            while ($curr->lte($end)) {
                $months[] = [
                    'label' => $curr->format('M Y'),
                    'ym' => $curr->format('Y-m'),
                    'start' => $curr->copy()->startOfMonth()->toDateString(),
                    'end' => $curr->copy()->endOfMonth()->toDateString(),
                ];
                $curr->addMonth();
            }
            return $this->buildMonthlyTrend($months, $companyId);
        }

        // 4. All Time: Up to past 12 months ending on current month
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $dt = now()->subMonths($i);
            $months[] = [
                'label' => $dt->format('M Y'),
                'ym' => $dt->format('Y-m'),
                'start' => $dt->copy()->startOfMonth()->toDateString(),
                'end' => $dt->copy()->endOfMonth()->toDateString(),
            ];
        }
        return $this->buildMonthlyTrend($months, $companyId);
    }

    /**
     * @param array<int, string> $dates
     */
    protected function buildDailyTrend(array $dates, ?int $companyId): array
    {
        if (empty($dates)) {
            return [];
        }

        $minDate = reset($dates);
        $maxDate = end($dates);

        $salesQuery = Invoice::query()
            ->where('status', '!=', 'cancelled')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $purchasesQuery = PurchaseInvoice::query()
            ->where('status', '!=', 'cancelled')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $ordersQuery = Order::query()
            ->when($companyId, fn ($q) => $q->whereHas('customer', fn ($cq) => $cq->where('company_id', $companyId)));

        if ($minDate === $maxDate) {
            $salesQuery->whereDate('invoice_date', $minDate);
            $purchasesQuery->whereDate('invoice_date', $minDate);
            $ordersQuery->whereDate('order_date', $minDate);
        } else {
            $salesQuery->whereDate('invoice_date', '>=', $minDate)->whereDate('invoice_date', '<=', $maxDate);
            $purchasesQuery->whereDate('invoice_date', '>=', $minDate)->whereDate('invoice_date', '<=', $maxDate);
            $ordersQuery->whereDate('order_date', '>=', $minDate)->whereDate('order_date', '<=', $maxDate);
        }

        $salesMap = $salesQuery->get(['invoice_date', 'grand_total'])
            ->groupBy(fn ($inv) => Carbon::parse($inv->invoice_date)->toDateString())
            ->map(fn ($group) => (float) $group->sum('grand_total'))
            ->toArray();

        $purchasesMap = $purchasesQuery->get(['invoice_date', 'grand_total'])
            ->groupBy(fn ($pi) => Carbon::parse($pi->invoice_date)->toDateString())
            ->map(fn ($group) => (float) $group->sum('grand_total'))
            ->toArray();

        $ordersMap = $ordersQuery->get(['order_date'])
            ->groupBy(fn ($ord) => Carbon::parse($ord->order_date)->toDateString())
            ->map(fn ($group) => (int) $group->count())
            ->toArray();

        return collect($dates)->map(function ($d) use ($salesMap, $purchasesMap, $ordersMap) {
            $dt = Carbon::parse($d);
            return [
                'label' => $dt->format('d M'),
                'date' => $d,
                'sales' => (float) ($salesMap[$d] ?? 0),
                'purchases' => (float) ($purchasesMap[$d] ?? 0),
                'orders' => (int) ($ordersMap[$d] ?? 0),
            ];
        })->values()->toArray();
    }

    /**
     * @param array<int, array{label: string, ym: string, start: string, end: string}> $months
     */
    protected function buildMonthlyTrend(array $months, ?int $companyId): array
    {
        if (empty($months)) {
            return [];
        }

        $minDate = $months[0]['start'];
        $maxDate = end($months)['end'];

        $salesDaily = Invoice::query()
            ->where('status', '!=', 'cancelled')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('invoice_date', '>=', $minDate)
            ->whereDate('invoice_date', '<=', $maxDate)
            ->get(['invoice_date', 'grand_total'])
            ->groupBy(fn ($inv) => Carbon::parse($inv->invoice_date)->toDateString())
            ->map(fn ($group) => (float) $group->sum('grand_total'))
            ->toArray();

        $purchasesDaily = PurchaseInvoice::query()
            ->where('status', '!=', 'cancelled')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('invoice_date', '>=', $minDate)
            ->whereDate('invoice_date', '<=', $maxDate)
            ->get(['invoice_date', 'grand_total'])
            ->groupBy(fn ($pi) => Carbon::parse($pi->invoice_date)->toDateString())
            ->map(fn ($group) => (float) $group->sum('grand_total'))
            ->toArray();

        $ordersDaily = Order::query()
            ->when($companyId, fn ($q) => $q->whereHas('customer', fn ($cq) => $cq->where('company_id', $companyId)))
            ->whereDate('order_date', '>=', $minDate)
            ->whereDate('order_date', '<=', $maxDate)
            ->get(['order_date'])
            ->groupBy(fn ($ord) => Carbon::parse($ord->order_date)->toDateString())
            ->map(fn ($group) => (int) $group->count())
            ->toArray();

        return collect($months)->map(function ($m) use ($salesDaily, $purchasesDaily, $ordersDaily) {
            $ym = $m['ym'];

            $mSales = 0.0;
            foreach ($salesDaily as $d => $amt) {
                if (str_starts_with($d, $ym)) {
                    $mSales += (float) $amt;
                }
            }

            $mPurchases = 0.0;
            foreach ($purchasesDaily as $d => $amt) {
                if (str_starts_with($d, $ym)) {
                    $mPurchases += (float) $amt;
                }
            }

            $mOrders = 0;
            foreach ($ordersDaily as $d => $cnt) {
                if (str_starts_with($d, $ym)) {
                    $mOrders += (int) $cnt;
                }
            }

            return [
                'label' => $m['label'],
                'date' => $m['start'],
                'sales' => $mSales,
                'purchases' => $mPurchases,
                'orders' => $mOrders,
            ];
        })->values()->toArray();
    }
}
