<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\InterCompanyTransaction;
use App\Domains\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SisterConcernController extends Controller
{
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

        // 1. Total Business Groups
        $businessGroupsCount = BusinessGroup::count();

        // 2. Total Sister Concerns
        $companiesCount = Company::count();

        // 3. Total Head Offices
        $headOfficesCount = Company::where('is_default', true)->count();
        if ($headOfficesCount === 0 && $companiesCount > 0) {
            $headOfficesCount = 1;
        }

        // 4. Total Turnover
        $totalTurnover = 0.0;
        if (Schema::hasTable('invoices')) {
            $totalTurnover = (float) DB::table('invoices')->where('status', '!=', 'cancelled')->sum('grand_total');
        }
        if ($totalTurnover <= 0 && Schema::hasTable('orders')) {
            $totalTurnover = (float) DB::table('orders')->where('status', '!=', 'cancelled')->sum('grand_total');
        }

        // Format turnover
        $turnoverInLakhs = round($totalTurnover / 100000, 2);
        $turnoverFormatted = $turnoverInLakhs >= 1
            ? '₹' . number_format($turnoverInLakhs, 2) . ' L'
            : '₹' . number_format($totalTurnover, 2);

        // 5. Total Employees
        $employeesCount = 0;
        if (class_exists(Employee::class) && Schema::hasTable('employees')) {
            $employeesCount = Employee::count();
        }

        // 6. Active Transactions
        $activeTransactions = 0;
        if (Schema::hasTable('inter_company_transactions') && InterCompanyTransaction::count() > 0) {
            $activeTransactions = InterCompanyTransaction::whereIn('status', ['Pending', 'Approved'])->count();
        } elseif (Schema::hasTable('orders')) {
            $activeTransactions = DB::table('orders')->whereIn('status', ['pending', 'approved', 'processing'])->count();
        }
        if ($activeTransactions === 0 && Schema::hasTable('invoices')) {
            $activeTransactions = DB::table('invoices')->whereIn('status', ['issued', 'partial'])->count();
        }

        // 7. Total Transaction Value
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

        // 8. Settled Transactions
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

        // Chart 1: Group-wise Turnover (₹ Lakhs)
        $groups = BusinessGroup::with(['companies.warehouses'])->get();
        $groupTurnoverLabels = [];
        $groupTurnoverValues = [];

        foreach ($groups as $group) {
            $groupTurnoverLabels[] = $group->name;
            $companyIds = $group->companies->pluck('id')->toArray();
            $turnover = 0.0;

            if (!empty($companyIds) && Schema::hasTable('warehouses') && Schema::hasTable('orders')) {
                $whIds = Warehouse::whereIn('company_id', $companyIds)->pluck('id')->toArray();
                if (!empty($whIds)) {
                    $turnover = (float) DB::table('orders')->whereIn('warehouse_id', $whIds)->where('status', '!=', 'cancelled')->sum('grand_total');
                }
            }

            if ($turnover <= 0 && $group->isDefault()) {
                $turnover = $totalTurnover;
            }

            $groupTurnoverValues[] = round($turnover / 100000, 2);
        }

        // Fallback for empty groups
        if (empty($groupTurnoverLabels)) {
            $groupTurnoverLabels = ['Default Group'];
            $groupTurnoverValues = [round($totalTurnover / 100000, 2)];
        }

        // Chart 2: Entity Type Distribution
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
            'active_transactions_count' => $activeTransactions,
            'total_transaction_value' => $totalTransactionValue,
            'transaction_value_formatted' => $transactionValueFormatted,
            'settled_transactions_count' => $settledTransactions,
        ];

        return view('organization.sister-concerns.dashboard', [
            'tabCounts' => $tabCounts,
            'stats' => $stats,
            'groupTurnoverLabels' => $groupTurnoverLabels,
            'groupTurnoverValues' => $groupTurnoverValues,
            'entityDistribution' => $entityDistribution,
        ]);
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
