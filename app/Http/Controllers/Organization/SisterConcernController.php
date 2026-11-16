<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\InterCompanyTransaction;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Organization\Services\ConsolidatedReportService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SisterConcernController extends Controller
{
    public function __construct(
        protected ?ConsolidatedReportService $reportService = null
    ) {
        $this->reportService = $this->reportService ?? app(ConsolidatedReportService::class);
    }
    /**
     * Real-time record counts for navigation tabs.
     */
    public function getTabCounts(): array
    {
        $transactionsCount = 0;
        if (Schema::hasTable('inter_company_transactions')) {
            $transactionsCount = InterCompanyTransaction::where('status', '!=', 'Cancelled')->count();
        }

        if ($transactionsCount === 0) {
            if (Schema::hasTable('orders')) {
                $transactionsCount = DB::table('orders')->where('status', '!=', 'cancelled')->count();
            } elseif (Schema::hasTable('invoices')) {
                $transactionsCount = DB::table('invoices')->where('status', '!=', 'cancelled')->count();
            }
        }

        return [
            'business_groups' => BusinessGroup::count(),
            'sister_concerns' => Company::count(),
            'transactions' => $transactionsCount,
        ];
    }

    /**
     * Sister Concern Dashboard landing page.
     */
    public function dashboard(Request $request): View
    {
        $tabCounts = $this->getTabCounts();
        $user = $request->user();

        // 1. Resolve Authorized Business Groups & Selected Group
        $businessGroups = $this->reportService->resolveAuthorizedBusinessGroups($user);
        if ($businessGroups->isEmpty()) {
            $businessGroups = BusinessGroup::where('is_active', true)->orderBy('name')->get();
        }

        $selectedGroupId = $request->input('business_group_id')
            ? (int) $request->input('business_group_id')
            : ($businessGroups->first()?->id ?? 0);

        $selectedGroup = $businessGroups->firstWhere('id', $selectedGroupId) ?? $businessGroups->first();

        // 2. Resolve Companies associated with this Group / Proprietor
        $groupCompanies = $selectedGroup
            ? $this->reportService->resolveGroupCompanies($selectedGroup)
            : Company::where('is_active', true)->orderBy('name')->get();

        // 3. Business Selector (All Sister Concerns vs Specific Company)
        $selectedCompanyId = $request->filled('company_id') && $request->input('company_id') !== 'all'
            ? (int) $request->input('company_id')
            : null;

        $activeCompanies = $selectedCompanyId
            ? $groupCompanies->where('id', $selectedCompanyId)
            : $groupCompanies;

        // 4. Financial Year & Period Resolution
        $fyData = $this->reportService->resolveFinancialYears();
        $dateRange = $this->reportService->resolveDateRange($request, $fyData);
        $fromDate = $dateRange['from_date'];
        $toDate = $dateRange['to_date'];
        $periodType = $dateRange['period_type'];
        $periodLabel = $dateRange['period_label'];
        $selectedFy = $dateRange['financial_year'];

        // 5. Consolidated Business Performance Calculations
        $comparison = $this->reportService->getComparisonMatrix($activeCompanies, $fromDate, $toDate);
        $combined = $comparison['combined'];
        $comparisonCompanies = $comparison['companies'];
        $interCompany = $comparison['inter_company'];

        // 6. Monthly Trends for Visual Performance Chart
        $monthlyTrends = $this->reportService->getMonthlyTrends($activeCompanies->pluck('id')->all(), $fromDate, $toDate);

        // 7. Legacy Counts and Metrics for Tab & Test Compatibility
        $businessGroupsCount = BusinessGroup::count();
        $companiesCount = Company::count();
        $headOfficesCount = Company::where('is_default', true)->count() ?: ($companiesCount > 0 ? 1 : 0);

        $totalTurnover = (float) $combined['sales'];
        $turnoverInLakhs = round($totalTurnover / 100000, 2);
        $turnoverFormatted = $turnoverInLakhs >= 1
            ? '₹' . number_format($turnoverInLakhs, 2) . ' L'
            : ($totalTurnover > 0 ? '₹' . number_format($totalTurnover, 2) : '₹0.00');

        $employeesCount = 0;
        if (class_exists(Employee::class) && Schema::hasTable('employees')) {
            $empQ = Employee::query();
            if ($activeCompanies->isNotEmpty()) {
                $empQ->whereIn('company_id', $activeCompanies->pluck('id')->all());
            }
            $employeesCount = $empQ->count();
        }

        $activeTransactions = 0;
        if (Schema::hasTable('inter_company_transactions') && InterCompanyTransaction::count() > 0) {
            $activeTransactions = InterCompanyTransaction::whereIn('status', ['Pending', 'Approved'])->count();
        } elseif (Schema::hasTable('orders')) {
            $activeTransactions = DB::table('orders')->whereIn('status', ['pending', 'approved', 'processing'])->count();
        }
        if ($activeTransactions === 0 && Schema::hasTable('invoices')) {
            $activeTransactions = DB::table('invoices')->whereIn('status', ['issued', 'partial'])->count();
        }

        $totalTransactionValue = 0.0;
        if (Schema::hasTable('inter_company_transactions') && InterCompanyTransaction::count() > 0) {
            $totalTransactionValue = (float) InterCompanyTransaction::where('status', '!=', 'Cancelled')->sum('net_amount');
        } elseif (Schema::hasTable('orders')) {
            $totalTransactionValue = (float) DB::table('orders')->where('status', '!=', 'cancelled')->sum('grand_total');
        }
        if ($totalTransactionValue <= 0 && Schema::hasTable('invoices')) {
            $totalTransactionValue = (float) DB::table('invoices')->where('status', '!=', 'cancelled')->sum('grand_total');
        }

        $transValInLakhs = round($totalTransactionValue / 100000, 2);
        $transactionValueFormatted = $transValInLakhs >= 1
            ? '₹' . number_format($transValInLakhs, 2) . ' L'
            : '₹' . number_format($totalTransactionValue, 2);

        $settledTransactions = 0;
        if (Schema::hasTable('inter_company_transactions') && InterCompanyTransaction::count() > 0) {
            $settledTransactions = InterCompanyTransaction::where('status', 'Settled')->count();
        } elseif (Schema::hasTable('orders')) {
            $settledTransactions += DB::table('orders')->whereIn('status', ['converted', 'completed'])->count();
        }
        if ($settledTransactions === 0 && Schema::hasTable('invoices')) {
            $paidInvoices = DB::table('invoices')->where('status', 'paid')->count();
            $settledTransactions = max($settledTransactions, $paidInvoices);
        }

        // Charts
        $groups = BusinessGroup::with(['companies.warehouses'])->get();
        $groupTurnoverLabels = [];
        $groupTurnoverValues = [];

        foreach ($groups as $grp) {
            $groupTurnoverLabels[] = $grp->name;
            $grpCoIds = $grp->companies->pluck('id')->toArray();
            $grpTurnover = 0.0;

            if (! empty($grpCoIds) && Schema::hasTable('warehouses') && Schema::hasTable('orders')) {
                $whIds = Warehouse::whereIn('company_id', $grpCoIds)->pluck('id')->toArray();
                if (! empty($whIds)) {
                    $grpTurnover = (float) DB::table('orders')->whereIn('warehouse_id', $whIds)->where('status', '!=', 'cancelled')->sum('grand_total');
                }
            }

            if ($grpTurnover <= 0 && $grp->isDefault()) {
                $grpTurnover = $totalTurnover;
            }

            $groupTurnoverValues[] = round($grpTurnover / 100000, 2);
        }

        if (empty($groupTurnoverLabels)) {
            $groupTurnoverLabels = ['Default Group'];
            $groupTurnoverValues = [round($totalTurnover / 100000, 2)];
        }

        $branchesCount = Branch::count();
        $warehousesCount = Warehouse::count();
        $entityDistribution = [
            'labels' => ['Head Office', 'Branches', 'Warehouses'],
            'data' => [$headOfficesCount, $branchesCount, $warehousesCount],
        ];

        $stats = [
            'business_groups_count' => $businessGroupsCount,
            'companies_count' => $companiesCount,
            'head_offices_count' => $headOfficesCount,
            'total_turnover' => $totalTurnover,
            'turnover_formatted' => $turnoverFormatted,
            'employees_count' => $employeesCount,
            'total_employees' => $employeesCount,
            'active_transactions_count' => $activeTransactions,
            'total_transaction_value' => $totalTransactionValue,
            'transaction_value_formatted' => $transactionValueFormatted,
            'settled_transactions_count' => $settledTransactions,
            // Consolidated Financial KPI metrics
            'combined_sales' => $combined['sales'],
            'combined_purchases' => $combined['purchases'],
            'combined_expenses' => $combined['expenses'],
            'combined_gross_profit' => $combined['gross_profit'],
            'combined_net_profit' => $combined['net_profit'],
            'combined_gross_margin' => $combined['gross_margin'],
            'combined_net_margin' => $combined['net_margin'],
            'total_receivables' => $combined['receivables'],
            'total_payables' => $combined['payables'],
        ];

        // Comparison Chart Datasets
        $comparisonChart = [
            'labels' => collect($comparisonCompanies)->pluck('name')->all(),
            'sales' => collect($comparisonCompanies)->pluck('sales')->all(),
            'purchases' => collect($comparisonCompanies)->pluck('purchases')->all(),
            'profits' => collect($comparisonCompanies)->pluck('net_profit')->all(),
            'expenses' => collect($comparisonCompanies)->pluck('expenses')->all(),
        ];

        return view('organization.sister-concerns.dashboard', [
            'tabCounts' => $tabCounts,
            'stats' => $stats,
            'businessGroups' => $businessGroups,
            'selectedGroup' => $selectedGroup,
            'groupCompanies' => $groupCompanies,
            'selectedCompanyId' => $selectedCompanyId,
            'activeCompanies' => $activeCompanies,
            'financialYears' => $fyData['years'],
            'selectedFy' => $selectedFy,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'periodType' => $periodType,
            'periodLabel' => $periodLabel,
            'comparison' => $comparison,
            'combined' => $combined,
            'comparisonCompanies' => $comparisonCompanies,
            'interCompany' => $interCompany,
            'monthlyTrends' => $monthlyTrends,
            'comparisonChart' => $comparisonChart,
            'groupTurnoverLabels' => $groupTurnoverLabels,
            'groupTurnoverValues' => $groupTurnoverValues,
            'entityDistribution' => $entityDistribution,
        ]);
    }

    /**
     * Update Common Proprietor Information for a Business Group.
     */
    public function updateProprietor(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'business_group_id' => 'required|exists:business_groups,id',
            'proprietor_name' => 'required|string|max:191',
            'proprietor_relationship' => 'nullable|string|max:191',
            'proprietor_email' => 'nullable|email|max:191',
            'proprietor_phone' => 'nullable|string|max:50',
            'proprietor_pan' => 'nullable|string|max:20',
        ]);

        $group = BusinessGroup::findOrFail($data['business_group_id']);
        $group->update([
            'proprietor_name' => $data['proprietor_name'],
            'proprietor_relationship' => $data['proprietor_relationship'] ?: 'Common ownership and business oversight',
            'proprietor_email' => $data['proprietor_email'] ?? $group->proprietor_email,
            'proprietor_phone' => $data['proprietor_phone'] ?? $group->proprietor_phone,
            'proprietor_pan' => $data['proprietor_pan'] ?? $group->proprietor_pan,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Proprietor details updated successfully.',
                'group' => $group,
            ]);
        }

        return redirect()->back()->with('success', 'Common Proprietor details updated successfully.');
    }

    /**
     * Associate an existing or newly created Business with the Common Proprietor / Business Group.
     */
    public function associateBusiness(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'business_group_id' => 'required|exists:business_groups,id',
            'company_id' => 'required|exists:companies,id',
            'business_type' => 'nullable|string|max:100',
            'main_work' => 'nullable|string',
        ]);

        $group = BusinessGroup::findOrFail($data['business_group_id']);
        $company = Company::findOrFail($data['company_id']);

        // Update company fields
        $companyUpdate = ['business_group_id' => $group->id];
        if (filled($data['business_type'])) {
            $companyUpdate['business_type'] = $data['business_type'];
        }
        if (filled($data['main_work'])) {
            $companyUpdate['main_work'] = $data['main_work'];
        }
        $company->update($companyUpdate);

        // Ensure pivot link exists
        if (! $group->companies()->where('companies.id', $company->id)->exists()) {
            $group->companies()->attach($company->id);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Business '{$company->name}' successfully associated with {$group->name}.",
                'company' => $company,
            ]);
        }

        return redirect()->back()->with('success', "Business '{$company->name}' associated with {$group->name} successfully.");
    }

    /**
     * Sister Concerns Listing Tab.
     */
    public function companies(Request $request): View
    {
        $tabCounts = $this->getTabCounts();

        $query = Company::query()
            ->with(['businessGroup', 'branches', 'warehouses']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('gstin', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%");
            });
        }

        if ($groupId = $request->input('business_group_id')) {
            $query->where('business_group_id', $groupId);
        }

        $items = $query->orderBy('name')->paginate(15)->withQueryString();
        $businessGroups = BusinessGroup::where('is_active', true)->orderBy('name')->get();
        $states = \App\Support\IndianStates::names();
        $financialYears = \App\Domains\Organization\Models\FinancialYear::orderByDesc('starts_on')->pluck('name')->unique()->values()->all();
        if (empty($financialYears)) {
            $financialYears = ['2026-2027', '2025-2026', '2024-2025'];
        }

        if ($request->wantsJson() && ! $request->hasHeader('X-DMS-Dynamic')) {
            return response()->json([
                'success' => true,
                'items' => $items,
            ]);
        }

        return view('organization.sister-concerns.companies', [
            'tabCounts' => $tabCounts,
            'items' => $items,
            'businessGroups' => $businessGroups,
            'states' => $states,
            'financialYears' => $financialYears,
            'search' => $request->string('search'),
            'selectedGroup' => $request->input('business_group_id'),
        ]);
    }

    /**
     * Inter-Company Transactions Listing Tab.
     */
    public function transactions(Request $request): View
    {
        $tabCounts = $this->getTabCounts();

        $hasDedicatedTransactions = Schema::hasTable('inter_company_transactions') && InterCompanyTransaction::count() > 0;

        if ($hasDedicatedTransactions) {
            $query = InterCompanyTransaction::query()
                ->with(['fromCompany', 'toCompany', 'approver']);

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_no', 'like', "%{$search}%")
                        ->orWhere('reference_invoice', 'like', "%{$search}%")
                        ->orWhere('reference_po', 'like', "%{$search}%")
                        ->orWhereHas('fromCompany', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('toCompany', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
                });
            }

            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            if ($type = $request->input('transaction_type')) {
                $query->where('transaction_type', $type);
            }

            if ($fromCo = $request->input('from_company_id')) {
                $query->where('from_company_id', $fromCo);
            }

            if ($toCo = $request->input('to_company_id')) {
                $query->where('to_company_id', $toCo);
            }

            // Summary cards statistics based on unfiltered or status-aware counts
            $summaryTotalCount = InterCompanyTransaction::count();
            $summaryTotalValue = (float) InterCompanyTransaction::where('status', '!=', 'Cancelled')->sum('net_amount');
            $summaryPendingCount = InterCompanyTransaction::where('status', 'Pending')->count();
            $summarySettledCount = InterCompanyTransaction::where('status', 'Settled')->count();

            $items = $query->orderByDesc('id')->paginate(15)->withQueryString();
        } else {
            // Backward compatible fallback for when no inter-company transactions exist yet
            $query = DB::table('orders')
                ->leftJoin('warehouses', 'orders.warehouse_id', '=', 'warehouses.id')
                ->leftJoin('companies', 'warehouses.company_id', '=', 'companies.id')
                ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
                ->select(
                    'orders.id',
                    'orders.order_no as reference_no',
                    'orders.order_date as transaction_date',
                    'orders.grand_total as amount',
                    'orders.status',
                    DB::raw("COALESCE(companies.name, 'Default Entity') as source_entity"),
                    DB::raw("COALESCE(customers.name, 'Internal Counterparty') as destination_entity"),
                    'orders.created_at'
                );

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('orders.order_no', 'like', "%{$search}%")
                        ->orWhere('companies.name', 'like', "%{$search}%")
                        ->orWhere('customers.name', 'like', "%{$search}%");
                });
            }

            if ($status = $request->input('status')) {
                $query->where('orders.status', $status);
            }

            $summaryTotalCount = DB::table('orders')->count();
            $summaryTotalValue = (float) DB::table('orders')->where('status', '!=', 'cancelled')->sum('grand_total');
            $summaryPendingCount = DB::table('orders')->whereIn('status', ['pending', 'processing'])->count();
            $summarySettledCount = DB::table('orders')->whereIn('status', ['converted', 'completed'])->count();

            $items = $query->orderByDesc('orders.id')->paginate(15);
        }

        $eligibleCompanies = Company::where('is_active', true)->orderBy('name')->get();
        $eligibleUsers = User::orderBy('name')->get(['id', 'name', 'email']);
        $transactionTypes = ['Sale', 'Purchase', 'Service', 'Loan Given', 'Loan Received'];
        $currencies = ['INR', 'USD', 'EUR', 'GBP', 'AED', 'SGD'];
        $tdsSections = [
            ['code' => '194C', 'name' => '194C - Contractor (1% / 2%)', 'default_rate' => 2.0],
            ['code' => '194J', 'name' => '194J - Professional / Technical Services (10% / 2%)', 'default_rate' => 10.0],
            ['code' => '194I', 'name' => '194I - Rent (Land/Building - 10%, Plant/Machinery - 2%)', 'default_rate' => 10.0],
            ['code' => '194Q', 'name' => '194Q - Purchase of Goods (0.1%)', 'default_rate' => 0.1],
            ['code' => '194A', 'name' => '194A - Interest other than securities (10%)', 'default_rate' => 10.0],
            ['code' => '194H', 'name' => '194H - Commission or Brokerage (5%)', 'default_rate' => 5.0],
            ['code' => 'Other', 'name' => 'Other TDS Section', 'default_rate' => 0.0],
        ];

        return view('organization.sister-concerns.transactions', [
            'tabCounts' => $tabCounts,
            'items' => $items,
            'isDedicated' => $hasDedicatedTransactions,
            'summary' => [
                'total_transactions' => $summaryTotalCount,
                'total_value' => $summaryTotalValue,
                'pending_count' => $summaryPendingCount,
                'settled_count' => $summarySettledCount,
            ],
            'eligibleCompanies' => $eligibleCompanies,
            'eligibleUsers' => $eligibleUsers,
            'transactionTypes' => $transactionTypes,
            'currencies' => $currencies,
            'tdsSections' => $tdsSections,
            'search' => $request->string('search'),
            'selectedStatus' => $request->input('status'),
            'selectedType' => $request->input('transaction_type'),
            'selectedFromCompany' => $request->input('from_company_id'),
            'selectedToCompany' => $request->input('to_company_id'),
        ]);
    }
}
