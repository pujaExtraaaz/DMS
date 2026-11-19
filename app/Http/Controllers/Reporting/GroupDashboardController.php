<?php

namespace App\Http\Controllers\Reporting;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Services\ConsolidatedReportService;
use App\Http\Controllers\Controller;
use App\Support\DocumentExporter;
use App\Support\ReportExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GroupDashboardController extends Controller
{
    public function __construct(
        protected ConsolidatedReportService $reportService
    ) {}

    /**
     * Group Dashboard landing page for Sister Concerns.
     */
    public function index(Request $request): View
    {
        $this->authorizeAccess($request);

        $user = $request->user();

        // 1. Resolve registered sister companies
        $allRegisteredCompanies = Company::where('is_active', true)->orderBy('name')->get();
        if ($allRegisteredCompanies->isEmpty()) {
            $allRegisteredCompanies = Company::orderBy('name')->get();
        }

        // Apply tenant restrictions if user is not super/client admin or owner
        $isGlobalAdmin = $user && ($user->hasAnyRole(['super-admin', 'client-admin', 'owner']) || $user->hasPermissionTo('group_dashboard.combined.view'));
        if (! $isGlobalAdmin && $user?->company_id) {
            $allowedCompanies = $allRegisteredCompanies->where('id', $user->company_id);
        } else {
            $allowedCompanies = $allRegisteredCompanies;
        }

        // 2. Company Filter Selection (Both / All Companies vs Specific Company)
        $selectedCompanyId = $request->filled('company_id') && $request->input('company_id') !== 'all'
            ? (int) $request->input('company_id')
            : null;

        // If user is restricted to a company, force that company
        if (! $isGlobalAdmin && $user?->company_id && $selectedCompanyId !== $user->company_id) {
            $selectedCompanyId = $user->company_id;
        }

        $activeCompanies = $selectedCompanyId
            ? $allowedCompanies->where('id', $selectedCompanyId)
            : $allowedCompanies;

        // 3. Date Range & Period Resolution
        $fyData = $this->reportService->resolveFinancialYears();
        $dateRange = $this->reportService->resolveDateRange($request, $fyData);
        $fromDate = $dateRange['from_date'];
        $toDate = $dateRange['to_date'];
        $periodType = $dateRange['period_type'];
        $periodLabel = $dateRange['period_label'];
        $selectedFy = $dateRange['financial_year'];

        // 4. Consolidation & Elimination Mode
        $eliminationMode = strtolower((string) $request->input('elimination_mode', 'after'));
        if (! in_array($eliminationMode, ['after', 'before'], true)) {
            $eliminationMode = 'after';
        }

        // 5. Consolidated Financial Metrics & Performance Matrix
        $comparison = $this->reportService->getComparisonMatrix($activeCompanies, $fromDate, $toDate, $eliminationMode);
        $comparisonCompanies = $comparison['companies'];
        $combined = $comparison['combined'];
        $interCompany = $comparison['inter_company'];

        // If single company selected, update combined row label and metrics
        $isCombinedView = empty($selectedCompanyId);
        $activeCompanyRecord = $selectedCompanyId ? $allowedCompanies->firstWhere('id', $selectedCompanyId) : null;
        $activeReportingStatus = $isCombinedView
            ? 'Both Companies (Combined' . ($eliminationMode === 'after' ? ' · Consolidated After Elimination' : ' · Before Elimination') . ')'
            : ($activeCompanyRecord?->name ?? 'Single Company');

        // 6. Multi-Period Comparisons
        $monthlyTrends = $this->reportService->getMonthlyTrendsWithCompanies($activeCompanies, $fromDate, $toDate);
        $currentFyStartYear = (int) substr((string) $selectedFy, 0, 4);
        $quarterlyTrends = $this->reportService->getQuarterlyComparison($activeCompanies, $currentFyStartYear ?: null);
        $yearlyTrends = $this->reportService->getYearlyComparison($activeCompanies, $fyData['years']);

        // 7. Expense Breakdown
        $expensesSummary = $this->reportService->computeExpensesSummary($activeCompanies->pluck('id')->all(), $fromDate, $toDate);

        // 8. Visual Chart Payloads
        $chartData = $this->buildChartPayloads($activeCompanies, $monthlyTrends, $comparisonCompanies, $expensesSummary, $interCompany);

        return view('reporting.group-dashboard.index', [
            'allCompanies' => $allowedCompanies,
            'selectedCompanyId' => $selectedCompanyId,
            'activeCompanies' => $activeCompanies,
            'activeCompanyRecord' => $activeCompanyRecord,
            'isCombinedView' => $isCombinedView,
            'activeReportingStatus' => $activeReportingStatus,
            'eliminationMode' => $eliminationMode,
            'fyData' => $fyData,
            'selectedFy' => $selectedFy,
            'periodType' => $periodType,
            'periodLabel' => $periodLabel,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'comparison' => $comparison,
            'comparisonCompanies' => $comparisonCompanies,
            'combined' => $combined,
            'interCompany' => $interCompany,
            'expensesSummary' => $expensesSummary,
            'monthlyTrends' => $monthlyTrends,
            'quarterlyTrends' => $quarterlyTrends,
            'yearlyTrends' => $yearlyTrends,
            'chartData' => $chartData,
            'isGlobalAdmin' => $isGlobalAdmin,
        ]);
    }

    /**
     * Async Drill-down endpoint for KPI cards and chart clicks.
     */
    public function drilldown(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $type = (string) $request->input('type', 'sales');
        $companyId = $request->input('company_id');
        $fromDate = $request->input('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->input('to_date', now()->endOfYear()->toDateString());
        $search = $request->input('search');

        $allCompanies = Company::where('is_active', true)->get();
        if ($companyId && $companyId !== 'all') {
            $companies = $allCompanies->where('id', (int) $companyId);
        } else {
            $companies = $allCompanies;
        }

        $rows = $this->reportService->getDrilldownData($companies, $type, $fromDate, $toDate, $search);

        return response()->json([
            'success' => true,
            'type' => $type,
            'count' => count($rows),
            'rows' => $rows,
        ]);
    }

    /**
     * Export Consolidated Group Reports.
     */
    public function export(Request $request): Response|StreamedResponse
    {
        $this->authorizeAccess($request);

        $reportType = (string) $request->input('report_type', 'summary');
        $format = strtolower((string) $request->input('format', 'csv'));
        $companyId = $request->input('company_id');
        $fromDate = $request->input('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->input('to_date', now()->endOfYear()->toDateString());
        $eliminationMode = $request->input('elimination_mode', 'after');

        $allCompanies = Company::where('is_active', true)->get();
        $targetCompanies = ($companyId && $companyId !== 'all')
            ? $allCompanies->where('id', (int) $companyId)
            : $allCompanies;

        $matrix = $this->reportService->getComparisonMatrix($targetCompanies, $fromDate, $toDate, $eliminationMode);
        $basename = 'group-' . $reportType . '-' . $fromDate . '-to-' . $toDate;

        switch ($reportType) {
            case 'sales':
                $headers = ['Date', 'Invoice No', 'Company', 'Customer', 'GSTIN', 'Taxable (₹)', 'Tax (₹)', 'Total (₹)', 'Status'];
                $data = $this->reportService->getDrilldownData($targetCompanies, 'sales', $fromDate, $toDate);
                $rows = array_map(fn ($r) => [
                    $r['date'], $r['number'], $r['company'], $r['party'], $r['gstin'],
                    number_format($r['taxable'], 2, '.', ''),
                    number_format($r['tax'], 2, '.', ''),
                    number_format($r['total'], 2, '.', ''),
                    $r['status'],
                ], $data);
                return DocumentExporter::exportTabular($basename, 'Consolidated Sales Register', $headers, $rows, $format);

            case 'purchases':
                $headers = ['Date', 'Invoice No', 'Supplier Inv No', 'Company', 'Supplier', 'GSTIN', 'Taxable (₹)', 'Tax (₹)', 'Total (₹)', 'Status'];
                $data = $this->reportService->getDrilldownData($targetCompanies, 'purchases', $fromDate, $toDate);
                $rows = array_map(fn ($r) => [
                    $r['date'], $r['number'], $r['supplier_number'], $r['company'], $r['party'], $r['gstin'],
                    number_format($r['taxable'], 2, '.', ''),
                    number_format($r['tax'], 2, '.', ''),
                    number_format($r['total'], 2, '.', ''),
                    $r['status'],
                ], $data);
                return DocumentExporter::exportTabular($basename, 'Consolidated Purchase Register', $headers, $rows, $format);

            case 'profit-loss':
                $headers = ['Business / Line Item', 'Sales (₹)', 'Sales Returns (₹)', 'Net Sales (₹)', 'Purchases (₹)', 'Purchase Returns (₹)', 'Net Purchases (₹)', 'Gross Profit (₹)', 'Expenses (₹)', 'Net Profit (₹)', 'Gross Margin %', 'Net Margin %'];
                $rows = [];
                foreach ($matrix['companies'] as $c) {
                    $rows[] = [
                        $c['name'] . ' (' . $c['code'] . ')',
                        number_format($c['sales'], 2, '.', ''),
                        number_format($c['credit_notes'], 2, '.', ''),
                        number_format($c['net_sales'], 2, '.', ''),
                        number_format($c['purchases'], 2, '.', ''),
                        number_format($c['debit_notes'] ?? 0, 2, '.', ''),
                        number_format($c['net_purchases'] ?? $c['purchases'], 2, '.', ''),
                        number_format($c['gross_profit'], 2, '.', ''),
                        number_format($c['expenses'], 2, '.', ''),
                        number_format($c['net_profit'], 2, '.', ''),
                        $c['gross_margin'] . '%',
                        $c['net_margin'] . '%',
                    ];
                }
                $comb = $matrix['combined'];
                $rows[] = [
                    'COMBINED TOTAL (' . strtoupper($eliminationMode) . ' ELIMINATION)',
                    number_format($comb['sales'], 2, '.', ''),
                    number_format($comb['credit_notes'], 2, '.', ''),
                    number_format($comb['net_sales'], 2, '.', ''),
                    number_format($comb['purchases'], 2, '.', ''),
                    number_format($comb['debit_notes'] ?? 0, 2, '.', ''),
                    number_format($comb['net_purchases'] ?? $comb['purchases'], 2, '.', ''),
                    number_format($comb['gross_profit'], 2, '.', ''),
                    number_format($comb['expenses'], 2, '.', ''),
                    number_format($comb['net_profit'], 2, '.', ''),
                    $comb['gross_margin'] . '%',
                    $comb['net_margin'] . '%',
                ];
                return DocumentExporter::exportTabular($basename, 'Profit and Loss Consolidation Report', $headers, $rows, $format);

            case 'expenses':
                $headers = ['Expense Category', 'Transactions Count', 'Total Amount (₹)'];
                $summary = $this->reportService->computeExpensesSummary($targetCompanies->pluck('id')->all(), $fromDate, $toDate);
                $rows = [];
                foreach ($summary['breakdown'] as $cat) {
                    $rows[] = [$cat['label'], $cat['count'], number_format($cat['amount'], 2, '.', '')];
                }
                $rows[] = ['TOTAL EXPENSES', $summary['expense_count'], number_format($summary['total_expenses'], 2, '.', '')];
                return DocumentExporter::exportTabular($basename, 'Consolidated Expense Report', $headers, $rows, $format);

            case 'inter-company':
                $headers = ['Transaction Date', 'Ref / Inv No', 'Type', 'Selling Company', 'Purchasing Company', 'Amount (₹)', 'Outstanding (₹)', 'Match Status', 'Status'];
                $ic = $this->reportService->detectInterCompanyTransactions($targetCompanies->pluck('id')->all(), $fromDate, $toDate);
                $rows = array_map(fn ($t) => [
                    $t['transaction_date'], $t['transaction_no'], $t['transaction_type'],
                    $t['selling_company_name'], $t['purchasing_company_name'],
                    number_format($t['net_amount'], 2, '.', ''),
                    number_format($t['outstanding_amount'], 2, '.', ''),
                    $t['match_status'], $t['status'],
                ], $ic['all_transactions']);
                return DocumentExporter::exportTabular($basename, 'Inter-Company Transactions Report', $headers, $rows, $format);

            case 'outstanding':
                $headers = ['Company', 'Gross Sales (₹)', 'Receivables Outstanding (₹)', 'Total Purchases (₹)', 'Payables Outstanding (₹)'];
                $rows = [];
                foreach ($matrix['companies'] as $c) {
                    $rows[] = [
                        $c['name'] . ' (' . $c['code'] . ')',
                        number_format($c['sales'], 2, '.', ''),
                        number_format($c['receivables'], 2, '.', ''),
                        number_format($c['purchases'], 2, '.', ''),
                        number_format($c['payables'], 2, '.', ''),
                    ];
                }
                $comb = $matrix['combined'];
                $rows[] = [
                    'COMBINED TOTAL',
                    number_format($comb['sales'], 2, '.', ''),
                    number_format($comb['receivables'], 2, '.', ''),
                    number_format($comb['purchases'], 2, '.', ''),
                    number_format($comb['payables'], 2, '.', ''),
                ];
                return DocumentExporter::exportTabular($basename, 'Outstanding Balances Report', $headers, $rows, $format);

            case 'summary':
            default:
                $headers = ['Metric Particulars', 'Amount / Value (₹)'];
                $comb = $matrix['combined'];
                $rows = [
                    ['Total Sales (Gross)', number_format($comb['sales'], 2, '.', '')],
                    ['Sales Returns (Credit Notes)', number_format($comb['credit_notes'], 2, '.', '')],
                    ['Net Sales (Revenue)', number_format($comb['net_sales'], 2, '.', '')],
                    ['Total Purchases (Gross)', number_format($comb['purchases'], 2, '.', '')],
                    ['Purchase Returns (Debit Notes)', number_format($comb['debit_notes'] ?? 0, 2, '.', '')],
                    ['Net Purchases (Procurement)', number_format($comb['net_purchases'] ?? $comb['purchases'], 2, '.', '')],
                    ['Gross Profit', number_format($comb['gross_profit'], 2, '.', '')],
                    ['Gross Profit Margin', $comb['gross_margin'] . '%'],
                    ['Total Operating Expenses', number_format($comb['expenses'], 2, '.', '')],
                    ['Net Profit', number_format($comb['net_profit'], 2, '.', '')],
                    ['Net Profit Margin', $comb['net_margin'] . '%'],
                    ['Receivables Outstanding', number_format($comb['receivables'], 2, '.', '')],
                    ['Payables Outstanding', number_format($comb['payables'], 2, '.', '')],
                    ['Inter-Company Eliminated Volume', number_format($comb['inter_company_eliminated'] ?? 0, 2, '.', '')],
                ];
                return DocumentExporter::exportTabular($basename, 'Group Dashboard Financial Summary', $headers, $rows, $format);
        }
    }

    /**
     * Verify user authorization.
     */
    protected function authorizeAccess(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        if ($user->hasAnyRole(['super-admin', 'client-admin', 'owner'])) {
            return;
        }

        if ($user->hasAnyPermission(['group_dashboard.view', 'reports.view', 'organization.view'])) {
            return;
        }

        abort(403, 'Unauthorized access to Group Dashboard.');
    }

    /**
     * Build chart datasets for visualization.
     */
    protected function buildChartPayloads(
        Collection $companies,
        array $monthlyTrends,
        array $comparisonCompanies,
        array $expensesSummary,
        array $interCompany
    ): array {
        $months = array_column($monthlyTrends, 'month_label');

        // 1. Monthly Sales Comparison (Company A vs Company B vs Combined)
        $salesDatasets = [];
        $palette = ['#6366f1', '#a855f7', '#ec4899', '#3b82f6', '#14b8a6', '#f59e0b'];
        $idx = 0;

        foreach ($companies as $co) {
            $coSales = [];
            foreach ($monthlyTrends as $m) {
                $coSales[] = round($m['company_breakdown'][$co->id]['sales'] ?? 0.0, 2);
            }
            $color = $palette[$idx % count($palette)];
            $salesDatasets[] = [
                'label' => $co->name,
                'data' => $coSales,
                'backgroundColor' => $color,
                'borderColor' => $color,
            ];
            $idx++;
        }

        // 2. Monthly Purchases Comparison
        $purchasesDatasets = [];
        $idx = 0;
        foreach ($companies as $co) {
            $coPurchases = [];
            foreach ($monthlyTrends as $m) {
                $coPurchases[] = round($m['company_breakdown'][$co->id]['purchases'] ?? 0.0, 2);
            }
            $color = $palette[$idx % count($palette)];
            $purchasesDatasets[] = [
                'label' => $co->name,
                'data' => $coPurchases,
                'backgroundColor' => $color,
                'borderColor' => $color,
            ];
            $idx++;
        }

        // 3. Sales vs Purchases (Group Level)
        $groupSales = array_map(fn ($m) => round($m['sales'], 2), $monthlyTrends);
        $groupPurchases = array_map(fn ($m) => round($m['purchases'], 2), $monthlyTrends);

        // 4. Profitability Comparison (Gross vs Net Profit)
        $profitLabels = [];
        $grossProfits = [];
        $netProfits = [];
        foreach ($comparisonCompanies as $c) {
            $profitLabels[] = $c['name'];
            $grossProfits[] = round($c['gross_profit'], 2);
            $netProfits[] = round($c['net_profit'], 2);
        }

        // 5. Expense Breakdown (Donut)
        $expenseLabels = [];
        $expenseValues = [];
        foreach ($expensesSummary['breakdown'] as $cat) {
            $expenseLabels[] = $cat['label'];
            $expenseValues[] = round($cat['amount'], 2);
        }
        if (empty($expenseLabels)) {
            $expenseLabels = ['Operating Expenses'];
            $expenseValues = [0];
        }

        return [
            'months' => $months,
            'sales_datasets' => $salesDatasets,
            'purchases_datasets' => $purchasesDatasets,
            'group_sales' => $groupSales,
            'group_purchases' => $groupPurchases,
            'profit_labels' => $profitLabels,
            'gross_profits' => $grossProfits,
            'net_profits' => $netProfits,
            'expense_labels' => $expenseLabels,
            'expense_values' => $expenseValues,
            'inter_company_summary' => [
                'matched' => round($interCompany['volume'] ?? 0.0, 2),
                'unmatched' => round($interCompany['unmatched_volume'] ?? 0.0, 2),
                'receivables' => round($interCompany['receivables'] ?? 0.0, 2),
                'payables' => round($interCompany['payables'] ?? 0.0, 2),
            ],
        ];
    }
}
