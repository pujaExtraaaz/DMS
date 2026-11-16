<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Services\ConsolidatedReportService;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Support\ReportExporter;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SisterConcernReportController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected ConsolidatedReportService $reportService,
        protected SisterConcernController $sisterConcernController
    ) {}

    /**
     * Consolidated Sister Concern Sales & Purchase Reports.
     */
    public function index(Request $request): View
    {
        $tabCounts = $this->sisterConcernController->getTabCounts();
        $user = $request->user();

        // 1. Authorized Business Groups
        $businessGroups = $this->reportService->resolveAuthorizedBusinessGroups($user);
        $selectedGroupId = $request->input('business_group_id')
            ? (int) $request->input('business_group_id')
            : ($businessGroups->first()?->id ?? 0);

        $selectedGroup = $businessGroups->firstWhere('id', $selectedGroupId) ?? $businessGroups->first();

        // 2. Financial Years & Dates
        $fyData = $this->reportService->resolveFinancialYears();
        $selectedFy = $request->input('financial_year') ?: ($fyData['current']['name'] ?? '2026-27');
        $matchedFy = collect($fyData['years'])->firstWhere('name', $selectedFy) ?? $fyData['current'];

        $defaultFromDate = $matchedFy['starts_on'] ?: now()->startOfYear()->toDateString();
        $defaultToDate = $matchedFy['ends_on'] ?: now()->endOfYear()->toDateString();

        $fromDate = $request->input('from_date') ?: $defaultFromDate;
        $toDate = $request->input('to_date') ?: $defaultToDate;

        // If user changed financial_year query param without manual custom dates, sync with FY dates
        if ($request->has('financial_year') && ! $request->has('custom_dates')) {
            $fromDate = $matchedFy['starts_on'] ?: $fromDate;
            $toDate = $matchedFy['ends_on'] ?: $toDate;
        }

        // 3. Resolve Companies for Selected Business Group
        $groupCompanies = $selectedGroup
            ? $this->reportService->resolveGroupCompanies($selectedGroup)
            : collect();

        $selectedCompanyId = $request->filled('company_id') && $request->input('company_id') !== 'all'
            ? (int) $request->input('company_id')
            : null;

        $targetCompanies = $selectedCompanyId
            ? $groupCompanies->where('id', $selectedCompanyId)
            : $groupCompanies;

        $companyIds = $targetCompanies->pluck('id')->all();

        // 4. Report Type & Summary Calculations
        $reportType = $request->input('report_type', 'all');
        if (! in_array($reportType, ['all', 'sales', 'purchases'], true)) {
            $reportType = 'all';
        }

        $salesSummary = $this->reportService->computeSalesSummary($companyIds, $fromDate, $toDate);
        $purchasesSummary = $this->reportService->computePurchasesSummary($companyIds, $fromDate, $toDate);
        $expensesSummary = $this->reportService->computeExpensesSummary($companyIds, $fromDate, $toDate);
        $interCompany = $this->reportService->detectInterCompanyTransactions($companyIds, $fromDate, $toDate);

        // Comparison Matrix & Profitability
        $comparison = $this->reportService->getComparisonMatrix($targetCompanies, $fromDate, $toDate);
        $comparisonCompanies = $comparison['companies'];
        $combined = $comparison['combined'];

        // Net Group Position
        $netContribution = $salesSummary['net_sales'] - $purchasesSummary['total_purchases'];

        // Sorting Parameters
        $sort = $request->input('sort', 'invoice_date');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = $request->string('search');
        $status = $request->input('status');

        $salesItems = null;
        $purchaseItems = null;
        $salesByCompany = collect();
        $purchasesByCompany = collect();
        $monthlyTrends = [];
        $recentSales = collect();
        $recentPurchases = collect();

        if ($reportType === 'all') {
            $salesByCompany = $this->reportService->getSalesBySisterConcern($groupCompanies, $fromDate, $toDate);
            $purchasesByCompany = $this->reportService->getPurchasesBySisterConcern($groupCompanies, $fromDate, $toDate);
            $monthlyTrends = $this->reportService->getMonthlyTrends($companyIds, $fromDate, $toDate);

            $recentSales = $this->reportService->buildSalesQuery($companyIds, $fromDate, $toDate, null, null, true)
                ->orderByDesc('invoice_date')
                ->limit(5)
                ->get();

            $recentPurchases = $this->reportService->buildPurchasesQuery($companyIds, $fromDate, $toDate, null, null, true)
                ->orderByDesc('invoice_date')
                ->limit(5)
                ->get();
        } elseif ($reportType === 'sales') {
            $salesQuery = $this->reportService->buildSalesQuery($companyIds, $fromDate, $toDate, $search, $status);

            if ($sort === 'company') {
                $salesQuery->leftJoin('companies', 'invoices.company_id', '=', 'companies.id')
                    ->orderBy('companies.name', $direction)
                    ->select('invoices.*');
            } elseif (in_array($sort, ['invoice_no', 'invoice_date', 'grand_total', 'paid_amount', 'status'], true)) {
                $salesQuery->orderBy($sort, $direction);
            } else {
                $salesQuery->orderByDesc('invoice_date');
            }

            $salesItems = $salesQuery->paginate(15)->withQueryString();
        } elseif ($reportType === 'purchases') {
            $purchasesQuery = $this->reportService->buildPurchasesQuery($companyIds, $fromDate, $toDate, $search, $status);

            if ($sort === 'company') {
                $purchasesQuery->leftJoin('companies', 'purchase_invoices.company_id', '=', 'companies.id')
                    ->orderBy('companies.name', $direction)
                    ->select('purchase_invoices.*');
            } elseif (in_array($sort, ['invoice_no', 'invoice_date', 'grand_total', 'status'], true)) {
                $purchasesQuery->orderBy($sort, $direction);
            } else {
                $purchasesQuery->orderByDesc('invoice_date');
            }

            $purchaseItems = $purchasesQuery->paginate(15)->withQueryString();
        }

        return view('organization.sister-concerns.reports', [
            'tabCounts' => $tabCounts,
            'businessGroups' => $businessGroups,
            'selectedGroup' => $selectedGroup,
            'groupCompanies' => $groupCompanies,
            'selectedCompanyId' => $selectedCompanyId,
            'financialYears' => $fyData['years'],
            'selectedFy' => $selectedFy,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'reportType' => $reportType,
            'salesSummary' => $salesSummary,
            'purchasesSummary' => $purchasesSummary,
            'expensesSummary' => $expensesSummary,
            'comparison' => $comparison,
            'combined' => $combined,
            'comparisonCompanies' => $comparisonCompanies,
            'interCompany' => $interCompany,
            'netContribution' => $netContribution,
            'salesByCompany' => $salesByCompany,
            'purchasesByCompany' => $purchasesByCompany,
            'monthlyTrends' => $monthlyTrends,
            'recentSales' => $recentSales,
            'recentPurchases' => $recentPurchases,
            'salesItems' => $salesItems,
            'purchaseItems' => $purchaseItems,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'selectedStatus' => $status,
        ]);
    }

    /**
     * Export consolidated sales or purchases to CSV/Excel.
     */
    public function export(Request $request): StreamedResponse|Response
    {
        $user = $request->user();
        $businessGroups = $this->reportService->resolveAuthorizedBusinessGroups($user);
        $selectedGroupId = (int) $request->input('business_group_id', $businessGroups->first()?->id ?? 0);
        $selectedGroup = $businessGroups->firstWhere('id', $selectedGroupId) ?? $businessGroups->first();

        $groupCompanies = $selectedGroup
            ? $this->reportService->resolveGroupCompanies($selectedGroup)
            : collect();

        $selectedCompanyId = $request->filled('company_id') && $request->input('company_id') !== 'all'
            ? (int) $request->input('company_id')
            : null;

        $targetCompanies = $selectedCompanyId
            ? $groupCompanies->where('id', $selectedCompanyId)
            : $groupCompanies;

        $companyIds = $targetCompanies->pluck('id')->all();

        $fromDate = $request->input('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->input('to_date', now()->endOfYear()->toDateString());
        $reportType = $request->input('report_type', 'all');
        $search = $request->string('search');
        $status = $request->input('status');
        $format = strtolower((string) $request->input('format', 'csv'));

        $groupName = $selectedGroup?->name ? strtolower(str_replace(' ', '-', $selectedGroup->name)) : 'group';
        $proprietorName = $selectedGroup?->proprietor_title ?: 'Mr. Vivek Gupta';
        $meta = [
            'Proprietor' => $proprietorName,
            'Business Group' => $selectedGroup?->name ?: 'All Groups',
            'Period' => "{$fromDate} to {$toDate}",
            'Scope' => $selectedCompanyId ? ($targetCompanies->first()?->name ?? 'Single Company') : 'All Sister Concerns (Consolidated)',
        ];

        // 1. Comparison / Consolidated Financial Performance Export
        if ($reportType === 'all' || $reportType === 'comparison') {
            $headers = [
                'Particulars / Business',
                'Business Type',
                'Main Work',
                'Gross Sales (₹)',
                'Net Sales (₹)',
                'Purchases (₹)',
                'Expenses (₹)',
                'Gross Profit (₹)',
                'Net Profit (₹)',
                'Receivables (₹)',
                'Payables (₹)',
            ];

            $matrix = $this->reportService->getComparisonMatrix($targetCompanies, $fromDate, $toDate);
            $rows = [];

            foreach ($matrix['companies'] as $coRow) {
                $rows[] = [
                    $coRow['name'] . ' (' . $coRow['code'] . ')',
                    $coRow['business_type'],
                    $coRow['main_work'],
                    number_format($coRow['sales'], 2, '.', ''),
                    number_format($coRow['net_sales'], 2, '.', ''),
                    number_format($coRow['purchases'], 2, '.', ''),
                    number_format($coRow['expenses'], 2, '.', ''),
                    number_format($coRow['gross_profit'], 2, '.', ''),
                    number_format($coRow['net_profit'], 2, '.', ''),
                    number_format($coRow['receivables'], 2, '.', ''),
                    number_format($coRow['payables'], 2, '.', ''),
                ];
            }

            // Append Combined Row
            $comb = $matrix['combined'];
            $rows[] = [
                'COMBINED TOTAL (Adjusted for Inter-Company)',
                'Group Total',
                'Consolidated Performance',
                number_format($comb['sales'], 2, '.', ''),
                number_format($comb['net_sales'], 2, '.', ''),
                number_format($comb['purchases'], 2, '.', ''),
                number_format($comb['expenses'], 2, '.', ''),
                number_format($comb['gross_profit'], 2, '.', ''),
                number_format($comb['net_profit'], 2, '.', ''),
                number_format($comb['receivables'], 2, '.', ''),
                number_format($comb['payables'], 2, '.', ''),
            ];

            $basename = 'sister-concerns-consolidated-' . $groupName . '-' . $fromDate . '-to-' . $toDate;

            if ($format === 'pdf' || $format === 'excel' || $format === 'xlsx') {
                return \App\Support\DocumentExporter::exportTabular($basename, 'Sister Concerns Consolidated Performance', $headers, $rows, $format);
            }

            return ReportExporter::csv($basename . '.csv', $headers, $rows);
        }

        if ($reportType === 'purchases') {
            $headers = [
                'Purchase Date',
                'Purchase Invoice No',
                'Supplier Invoice No',
                'Purchasing Company',
                'Supplier Name',
                'GSTIN',
                'Item / Products',
                'Quantity',
                'Taxable Amount',
                'GST Amount',
                'Purchase Total',
                'Paid Amount',
                'Outstanding Amount',
                'Status',
            ];

            $purchases = $this->reportService->buildPurchasesQuery($companyIds, $fromDate, $toDate, $search, $status)
                ->orderByDesc('invoice_date')
                ->get();

            $rows = $purchases->map(function (PurchaseInvoice $pi) {
                $totalQty = $pi->items->sum('quantity');
                $itemsSummary = $pi->items->map(fn ($it) => $it->product?->name)->filter()->unique()->join(', ');
                $paid = $pi->status === 'paid' ? (float) $pi->grand_total : (float) ($pi->paid_amount ?? 0);
                $outstanding = max(0.0, (float) $pi->grand_total - $paid);

                return [
                    $pi->invoice_date?->format('Y-m-d') ?? '-',
                    $pi->invoice_no,
                    $pi->supplier_invoice_no ?: '-',
                    $pi->company?->name ?? $pi->warehouse?->company?->name ?? 'Sister Concern',
                    $pi->supplier?->name ?: '-',
                    $pi->supplier?->gstin ?: '-',
                    $itemsSummary ?: 'Standard Items',
                    number_format($totalQty, 2, '.', ''),
                    number_format((float) $pi->subtotal, 2, '.', ''),
                    number_format((float) $pi->tax_amount, 2, '.', ''),
                    number_format((float) $pi->grand_total, 2, '.', ''),
                    number_format($paid, 2, '.', ''),
                    number_format($outstanding, 2, '.', ''),
                    strtoupper($pi->status),
                ];
            })->all();

            $basename = 'consolidated-purchases-' . $groupName . '-' . $fromDate . '-to-' . $toDate;

            if ($format === 'pdf') {
                return ReportExporter::pdf($basename . '.pdf', 'Consolidated Purchases Report', $headers, $rows, $meta);
            }
            if ($format === 'excel' || $format === 'xlsx') {
                return \App\Support\DocumentExporter::exportTabular('xlsx', $basename, 'Consolidated Purchases Report', $headers, $rows);
            }

            return ReportExporter::csv($basename . '.csv', $headers, $rows);
        }

        // Default to Consolidated Sales
        $headers = [
            'Invoice Date',
            'Invoice Number',
            'Selling Company',
            'Customer / Party Name',
            'GSTIN',
            'Item / Products',
            'Quantity',
            'Taxable Amount',
            'GST Amount',
            'Invoice Total',
            'Paid Amount',
            'Outstanding Amount',
            'Status',
        ];

        $invoices = $this->reportService->buildSalesQuery($companyIds, $fromDate, $toDate, $search, $status)
            ->orderByDesc('invoice_date')
            ->get();

        $rows = $invoices->map(function (Invoice $inv) {
            $totalQty = $inv->items->sum('quantity');
            $itemsSummary = $inv->items->map(fn ($it) => $it->product?->name)->filter()->unique()->join(', ');
            $outstanding = max(0.0, (float) $inv->grand_total - (float) $inv->paid_amount);

            return [
                $inv->invoice_date?->format('Y-m-d') ?? '-',
                $inv->invoice_no,
                $inv->company?->name ?? $inv->selling_company?->name ?? 'Sister Concern',
                $inv->customer?->name ?: '-',
                $inv->customer?->gstin ?: '-',
                $itemsSummary ?: 'Standard Products',
                number_format($totalQty, 2, '.', ''),
                number_format((float) $inv->subtotal, 2, '.', ''),
                number_format((float) $inv->tax_amount, 2, '.', ''),
                number_format((float) $inv->grand_total, 2, '.', ''),
                number_format((float) $inv->paid_amount, 2, '.', ''),
                number_format($outstanding, 2, '.', ''),
                strtoupper($inv->status),
            ];
        })->all();

        $basename = 'consolidated-sales-' . $groupName . '-' . $fromDate . '-to-' . $toDate;

        if ($format === 'pdf') {
            return ReportExporter::pdf($basename . '.pdf', 'Consolidated Sales Report', $headers, $rows, $meta);
        }
        if ($format === 'excel' || $format === 'xlsx') {
            return \App\Support\DocumentExporter::exportTabular('xlsx', $basename, 'Consolidated Sales Report', $headers, $rows);
        }

        return ReportExporter::csv($basename . '.csv', $headers, $rows);
    }
}

