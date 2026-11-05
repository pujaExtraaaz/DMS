<?php

namespace App\Domains\Organization\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\InterCompanyTransaction;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConsolidatedReportService
{
    /**
     * Resolve business groups accessible to the given user based on role and tenant isolation.
     *
     * @return EloquentCollection<int, BusinessGroup>
     */
    public function resolveAuthorizedBusinessGroups(?User $user = null): EloquentCollection
    {
        $query = BusinessGroup::query()
            ->with(['parentCompany', 'companies'])
            ->where('is_active', true);

        // If the user is restricted to a specific company and is not super-admin/client-admin
        if ($user && $user->company_id && ! $user->hasAnyRole(['super-admin', 'client-admin', 'owner'])) {
            $companyId = (int) $user->company_id;
            $query->where(function ($q) use ($companyId) {
                $q->where('parent_company_id', $companyId)
                    ->orWhereHas('companies', fn ($cq) => $cq->where('companies.id', $companyId))
                    ->orWhereHas('customers', fn ($cu) => $cu->where('company_id', $companyId));
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Resolve all sister concerns / companies for the given business group.
     *
     * @return Collection<int, Company>
     */
    public function resolveGroupCompanies(BusinessGroup $group, ?int $specificCompanyId = null): Collection
    {
        $companies = collect();

        // 1. Parent company
        if ($group->parentCompany && $group->parentCompany->is_active) {
            $companies->put($group->parentCompany->id, $group->parentCompany);
        } elseif ($group->parent_company_id) {
            $parent = Company::find($group->parent_company_id);
            if ($parent && $parent->is_active) {
                $companies->put($parent->id, $parent);
            }
        }

        // 2. Pivot linked companies
        foreach ($group->companies()->where('companies.is_active', true)->get() as $co) {
            $companies->put($co->id, $co);
        }

        // 3. Direct business_group_id on companies
        $directCompanies = Company::where('business_group_id', $group->id)
            ->where('is_active', true)
            ->get();
        foreach ($directCompanies as $co) {
            $companies->put($co->id, $co);
        }

        // 4. Mapped parties (Customers) that represent sister concerns
        $groupCustomers = Customer::where('business_group_id', $group->id)
            ->whereNotNull('company_id')
            ->pluck('company_id')
            ->unique();
        if ($groupCustomers->isNotEmpty()) {
            $mappedCompanies = Company::whereIn('id', $groupCustomers)
                ->where('is_active', true)
                ->get();
            foreach ($mappedCompanies as $co) {
                $companies->put($co->id, $co);
            }
        }

        // Fallback for standalone/default groups
        if ($companies->isEmpty()) {
            $defaultCo = Company::where('is_default', true)->first() ?? Company::first();
            if ($defaultCo) {
                $companies->put($defaultCo->id, $defaultCo);
            }
        }

        if ($specificCompanyId && $companies->has($specificCompanyId)) {
            return collect([$specificCompanyId => $companies->get($specificCompanyId)]);
        }

        return $companies->values();
    }

    /**
     * Retrieve all available financial years and the resolved active year.
     *
     * @return array{years: array<int, array{name: string, starts_on: string, ends_on: string, is_current: bool}>, current: array{name: string, starts_on: string, ends_on: string}}
     */
    public function resolveFinancialYears(): array
    {
        $dbYears = FinancialYear::orderByDesc('starts_on')->get();
        $yearsList = [];

        foreach ($dbYears as $fy) {
            $name = trim($fy->name);
            if (! isset($yearsList[$name])) {
                $yearsList[$name] = [
                    'name' => $name,
                    'starts_on' => $fy->starts_on ? $fy->starts_on->toDateString() : '',
                    'ends_on' => $fy->ends_on ? $fy->ends_on->toDateString() : '',
                    'is_current' => (bool) $fy->is_current,
                ];
            } elseif ($fy->is_current) {
                $yearsList[$name]['is_current'] = true;
            }
        }

        // Standard Indian financial year fallback if empty or missing current
        $currentDate = now();
        $fyStartYear = $currentDate->month >= 4 ? $currentDate->year : $currentDate->year - 1;
        $defaultFyName = $fyStartYear . '-' . substr((string) ($fyStartYear + 1), -2);

        if (! isset($yearsList[$defaultFyName])) {
            $yearsList[$defaultFyName] = [
                'name' => $defaultFyName,
                'starts_on' => "{$fyStartYear}-04-01",
                'ends_on' => ($fyStartYear + 1) . "-03-31",
                'is_current' => true,
            ];
        }

        // Add 2 preceding financial years if missing
        for ($i = 1; $i <= 2; $i++) {
            $prevStart = $fyStartYear - $i;
            $prevName = $prevStart . '-' . substr((string) ($prevStart + 1), -2);
            if (! isset($yearsList[$prevName])) {
                $yearsList[$prevName] = [
                    'name' => $prevName,
                    'starts_on' => "{$prevStart}-04-01",
                    'ends_on' => ($prevStart + 1) . "-03-31",
                    'is_current' => false,
                ];
            }
        }

        // Find current
        $currentYear = collect($yearsList)->firstWhere('is_current', true) ?? reset($yearsList);

        return [
            'years' => array_values($yearsList),
            'current' => $currentYear,
        ];
    }

    /**
     * Build base query for consolidated Sales Invoices.
     */
    public function buildSalesQuery(
        array $companyIds,
        string $fromDate,
        string $toDate,
        ?string $search = null,
        ?string $status = null,
        bool $excludeCancelled = false
    ): Builder {
        $query = Invoice::query()
            ->with([
                'company',
                'customer',
                'items.product',
                'order.warehouse.company',
                'salesperson',
            ])
            ->whereBetween('invoice_date', [$fromDate, $toDate]);

        // Filter by company IDs
        $hasCompanyColumn = Schema::hasColumn('invoices', 'company_id');
        $query->where(function (Builder $q) use ($companyIds, $hasCompanyColumn) {
            if ($hasCompanyColumn) {
                $q->whereIn('invoices.company_id', $companyIds);
            }
            $q->orWhereHas('order.warehouse', fn ($wq) => $wq->whereIn('company_id', $companyIds))
                ->orWhereHas('customer', fn ($cq) => $cq->whereIn('company_id', $companyIds));
        });

        if ($excludeCancelled) {
            $query->where('status', '!=', 'cancelled');
        } elseif ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if (filled($search)) {
            $term = trim($search);
            $query->where(function (Builder $q) use ($term) {
                $q->where('invoice_no', 'like', "%{$term}%")
                    ->orWhere('reference_no', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$term}%")->orWhere('gstin', 'like', "%{$term}%"))
                    ->orWhereHas('company', fn ($cq) => $cq->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('items.product', fn ($pq) => $pq->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"));
            });
        }

        return $query;
    }

    /**
     * Build base query for consolidated Purchase Invoices.
     */
    public function buildPurchasesQuery(
        array $companyIds,
        string $fromDate,
        string $toDate,
        ?string $search = null,
        ?string $status = null,
        bool $excludeCancelled = false
    ): Builder {
        $query = PurchaseInvoice::query()
            ->with([
                'company',
                'supplier',
                'items.product',
                'warehouse.company',
                'creator',
            ])
            ->whereBetween('invoice_date', [$fromDate, $toDate]);

        // Filter by company IDs
        $hasCompanyColumn = Schema::hasColumn('purchase_invoices', 'company_id');
        $query->where(function (Builder $q) use ($companyIds, $hasCompanyColumn) {
            if ($hasCompanyColumn) {
                $q->whereIn('purchase_invoices.company_id', $companyIds);
            }
            $q->orWhereHas('warehouse', fn ($wq) => $wq->whereIn('company_id', $companyIds));
        });

        if ($excludeCancelled) {
            $query->where('status', '!=', 'cancelled');
        } elseif ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if (filled($search)) {
            $term = trim($search);
            $query->where(function (Builder $q) use ($term) {
                $q->where('invoice_no', 'like', "%{$term}%")
                    ->orWhere('supplier_invoice_no', 'like', "%{$term}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$term}%")->orWhere('gstin', 'like', "%{$term}%"))
                    ->orWhereHas('company', fn ($cq) => $cq->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('items.product', fn ($pq) => $pq->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"));
            });
        }

        return $query;
    }

    /**
     * Compute Sales Summary metrics.
     */
    public function computeSalesSummary(array $companyIds, string $fromDate, string $toDate): array
    {
        $salesQuery = $this->buildSalesQuery($companyIds, $fromDate, $toDate, null, null, true);
        $invoices = $salesQuery->get();

        $grossSales = (float) $invoices->sum('grand_total');
        $taxable = (float) $invoices->sum('subtotal');
        $taxAmount = (float) $invoices->sum('tax_amount');
        $collected = (float) $invoices->sum('paid_amount');
        $invoiceCount = $invoices->count();

        // Calculate Credit Notes (Sales Returns) in this period
        $creditNotesTotal = 0.0;
        if (Schema::hasTable('credit_notes')) {
            $creditNotesTotal = (float) CreditNote::query()
                ->where('status', 'posted')
                ->whereBetween('credit_note_date', [$fromDate, $toDate])
                ->whereIn('invoice_id', $invoices->pluck('id')->filter())
                ->sum('grand_total');
        }

        $netSales = max(0.0, $grossSales - $creditNotesTotal);
        $outstanding = max(0.0, $grossSales - $collected - $creditNotesTotal);

        return [
            'total_sales' => $grossSales,
            'net_sales' => $netSales,
            'total_invoices' => $invoiceCount,
            'taxable_amount' => $taxable,
            'tax_amount' => $taxAmount,
            'collected_amount' => $collected,
            'outstanding_amount' => $outstanding,
            'credit_notes_total' => $creditNotesTotal,
            'collection_percentage' => $grossSales > 0 ? round(($collected / $grossSales) * 100, 2) : 0,
        ];
    }

    /**
     * Compute Purchases Summary metrics.
     */
    public function computePurchasesSummary(array $companyIds, string $fromDate, string $toDate): array
    {
        $purchaseQuery = $this->buildPurchasesQuery($companyIds, $fromDate, $toDate, null, null, true);
        $purchases = $purchaseQuery->get();

        $totalPurchases = (float) $purchases->sum('grand_total');
        $taxable = (float) $purchases->sum('subtotal');
        $taxAmount = (float) $purchases->sum('tax_amount');
        $invoiceCount = $purchases->count();

        // Calculate actual paid payments linked to these purchase invoices if tracking exists
        $paidAmount = 0.0;
        foreach ($purchases as $pi) {
            if ($pi->status === 'paid') {
                $paidAmount += (float) $pi->grand_total;
            } elseif (isset($pi->paid_amount)) {
                $paidAmount += (float) $pi->paid_amount;
            }
        }

        $outstandingPayables = max(0.0, $totalPurchases - $paidAmount);

        return [
            'total_purchases' => $totalPurchases,
            'total_invoices' => $invoiceCount,
            'taxable_amount' => $taxable,
            'tax_amount' => $taxAmount,
            'paid_amount' => $paidAmount,
            'outstanding_amount' => $outstandingPayables,
            'payment_percentage' => $totalPurchases > 0 ? round(($paidAmount / $totalPurchases) * 100, 2) : 0,
        ];
    }

    /**
     * Group-wise Breakdown: Sales by Sister Concern.
     *
     * @param Collection<int, Company> $companies
     * @return Collection<int, array>
     */
    public function getSalesBySisterConcern(Collection $companies, string $fromDate, string $toDate): Collection
    {
        $companyIds = $companies->pluck('id')->all();
        $invoices = $this->buildSalesQuery($companyIds, $fromDate, $toDate, null, null, true)->get();
        $totalSales = (float) $invoices->sum('grand_total');

        return $companies->map(function (Company $company) use ($invoices, $totalSales) {
            $companyInvoices = $invoices->filter(function (Invoice $inv) use ($company) {
                return (int) ($inv->company_id ?? $inv->selling_company?->id) === (int) $company->id;
            });

            $salesVal = (float) $companyInvoices->sum('grand_total');
            $taxable = (float) $companyInvoices->sum('subtotal');
            $tax = (float) $companyInvoices->sum('tax_amount');
            $collected = (float) $companyInvoices->sum('paid_amount');
            $outstanding = max(0.0, $salesVal - $collected);
            $share = $totalSales > 0 ? round(($salesVal / $totalSales) * 100, 2) : 0;

            return [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'code' => $company->code,
                'gstin' => $company->gstin,
                'state' => $company->state,
                'invoices_count' => $companyInvoices->count(),
                'taxable_amount' => $taxable,
                'tax_amount' => $tax,
                'total_sales' => $salesVal,
                'collected_amount' => $collected,
                'outstanding_amount' => $outstanding,
                'sales_share' => $share,
            ];
        })->sortByDesc('total_sales')->values();
    }

    /**
     * Group-wise Breakdown: Purchases by Sister Concern.
     *
     * @param Collection<int, Company> $companies
     * @return Collection<int, array>
     */
    public function getPurchasesBySisterConcern(Collection $companies, string $fromDate, string $toDate): Collection
    {
        $companyIds = $companies->pluck('id')->all();
        $purchases = $this->buildPurchasesQuery($companyIds, $fromDate, $toDate, null, null, true)->get();
        $totalPurchases = (float) $purchases->sum('grand_total');

        return $companies->map(function (Company $company) use ($purchases, $totalPurchases) {
            $companyPurchases = $purchases->filter(function (PurchaseInvoice $pi) use ($company) {
                return (int) ($pi->company_id ?? $pi->warehouse?->company_id) === (int) $company->id;
            });

            $purchaseVal = (float) $companyPurchases->sum('grand_total');
            $taxable = (float) $companyPurchases->sum('subtotal');
            $tax = (float) $companyPurchases->sum('tax_amount');

            $paid = 0.0;
            foreach ($companyPurchases as $pi) {
                if ($pi->status === 'paid') {
                    $paid += (float) $pi->grand_total;
                } elseif (isset($pi->paid_amount)) {
                    $paid += (float) $pi->paid_amount;
                }
            }
            $outstanding = max(0.0, $purchaseVal - $paid);
            $share = $totalPurchases > 0 ? round(($purchaseVal / $totalPurchases) * 100, 2) : 0;

            return [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'code' => $company->code,
                'gstin' => $company->gstin,
                'state' => $company->state,
                'invoices_count' => $companyPurchases->count(),
                'taxable_amount' => $taxable,
                'tax_amount' => $tax,
                'total_purchases' => $purchaseVal,
                'paid_amount' => $paid,
                'outstanding_amount' => $outstanding,
                'purchase_share' => $share,
            ];
        })->sortByDesc('total_purchases')->values();
    }

    /**
     * Compute Monthly Sales and Purchase Trends across the selected period.
     *
     * @return array<int, array{month_key: string, month_label: string, sales: float, purchases: float, net: float}>
     */
    public function getMonthlyTrends(array $companyIds, string $fromDate, string $toDate): array
    {
        $start = Carbon::parse($fromDate)->startOfMonth();
        $end = Carbon::parse($toDate)->endOfMonth();

        $invoices = $this->buildSalesQuery($companyIds, $fromDate, $toDate, null, null, true)
            ->get(['id', 'invoice_date', 'grand_total']);

        $purchases = $this->buildPurchasesQuery($companyIds, $fromDate, $toDate, null, null, true)
            ->get(['id', 'invoice_date', 'grand_total']);

        $trends = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $monthKey = $current->format('Y-m');
            $monthLabel = $current->format('M Y');

            $monthlySales = (float) $invoices->filter(fn ($i) => $i->invoice_date && $i->invoice_date->format('Y-m') === $monthKey)->sum('grand_total');
            $monthlyPurchases = (float) $purchases->filter(fn ($p) => $p->invoice_date && $p->invoice_date->format('Y-m') === $monthKey)->sum('grand_total');

            $trends[] = [
                'month_key' => $monthKey,
                'month_label' => $monthLabel,
                'sales' => $monthlySales,
                'purchases' => $monthlyPurchases,
                'net' => $monthlySales - $monthlyPurchases,
            ];

            $current->addMonth();
        }

        return $trends;
    }

    /**
     * Detect inter-company transactions between the selected sister concerns.
     */
    public function detectInterCompanyTransactions(array $companyIds, string $fromDate, string $toDate): array
    {
        $interCompanyVolume = 0.0;
        $interCompanyTxCount = 0;

        if (Schema::hasTable('inter_company_transactions') && count($companyIds) > 1) {
            $txs = InterCompanyTransaction::query()
                ->whereIn('from_company_id', $companyIds)
                ->whereIn('to_company_id', $companyIds)
                ->whereBetween('transaction_date', [$fromDate, $toDate])
                ->where('status', '!=', 'Cancelled')
                ->get();

            $interCompanyVolume = (float) $txs->sum('net_amount');
            $interCompanyTxCount = $txs->count();
        }

        return [
            'volume' => $interCompanyVolume,
            'count' => $interCompanyTxCount,
        ];
    }

    /**
     * Compute Expenses Summary for the given company IDs within the date range.
     *
     * @param array<int> $companyIds
     * @return array{total_expenses: float, expense_count: int, breakdown: array<string, array{label: string, amount: float, count: int}>}
     */
    public function computeExpensesSummary(array $companyIds, string $fromDate, string $toDate): array
    {
        $totalExpenses = 0.0;
        $expenseCount = 0;
        $breakdown = [];

        // 1. Freight Bills (direct logistics & transport expenses)
        if (Schema::hasTable('freight_bills')) {
            $freightQ = DB::table('freight_bills')
                ->whereBetween('bill_date', [$fromDate, $toDate])
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
                });

            if (Schema::hasColumn('freight_bills', 'company_id')) {
                $freightQ->whereIn('company_id', $companyIds);
            }

            $freightTotal = (float) $freightQ->sum('total_amount');
            $freightCount = (int) $freightQ->count();

            if ($freightTotal > 0) {
                $totalExpenses += $freightTotal;
                $expenseCount += $freightCount;
                $breakdown['freight'] = [
                    'label' => 'Freight & Logistics',
                    'amount' => $freightTotal,
                    'count' => $freightCount,
                ];
            }
        }

        // 2. Accounting Expense Claims (acct_expense_claims)
        if (Schema::hasTable('acct_expense_claims')) {
            $claimQ = DB::table('acct_expense_claims')
                ->whereIn('company_id', $companyIds)
                ->whereBetween('claim_date', [$fromDate, $toDate])
                ->whereNotIn('status', ['rejected', 'cancelled']);

            $claimTotal = (float) $claimQ->sum('amount');
            $claimCount = (int) $claimQ->count();

            if ($claimTotal > 0) {
                $totalExpenses += $claimTotal;
                $expenseCount += $claimCount;
                $breakdown['acct_claims'] = [
                    'label' => 'Operating Expense Claims',
                    'amount' => $claimTotal,
                    'count' => $claimCount,
                ];
            }
        }

        // 3. HRMS Expense Claims (expense_claims)
        if (Schema::hasTable('expense_claims') && Schema::hasTable('employees')) {
            $hrmsClaimQ = DB::table('expense_claims')
                ->join('employees', 'expense_claims.employee_id', '=', 'employees.id')
                ->whereIn('employees.company_id', $companyIds)
                ->whereBetween('expense_claims.claim_date', [$fromDate, $toDate])
                ->whereNotIn('expense_claims.status', ['rejected', 'cancelled']);

            $hrmsTotal = (float) $hrmsClaimQ->sum('expense_claims.amount');
            $hrmsCount = (int) $hrmsClaimQ->count();

            if ($hrmsTotal > 0) {
                $totalExpenses += $hrmsTotal;
                $expenseCount += $hrmsCount;
                $breakdown['hrms_claims'] = [
                    'label' => 'Staff Expense Reimbursements',
                    'amount' => $hrmsTotal,
                    'count' => $hrmsCount,
                ];
            }
        }

        // 4. Deal Expenses (deal_expenses)
        if (Schema::hasTable('deal_expenses')) {
            $dealQ = DB::table('deal_expenses')
                ->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                ->where(function ($q) {
                    $q->whereNull('status')->orWhereNotIn('status', ['rejected', 'cancelled']);
                });

            if (Schema::hasColumn('deal_expenses', 'company_id')) {
                $dealQ->whereIn('company_id', $companyIds);
            }

            $dealTotal = (float) $dealQ->sum('amount');
            $dealCount = (int) $dealQ->count();

            if ($dealTotal > 0) {
                $totalExpenses += $dealTotal;
                $expenseCount += $dealCount;
                $breakdown['deal_expenses'] = [
                    'label' => 'Deal & Project Expenses',
                    'amount' => $dealTotal,
                    'count' => $dealCount,
                ];
            }
        }

        return [
            'total_expenses' => $totalExpenses,
            'expense_count' => $expenseCount,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Compute Group-wide or Business-wise Comparison Matrix with Profitability.
     *
     * @param Collection<int, Company> $companies
     * @return array{
     *     companies: array<int, array>,
     *     combined: array,
     *     inter_company: array{volume: float, count: int}
     * }
     */
    public function getComparisonMatrix(Collection $companies, string $fromDate, string $toDate): array
    {
        $companyRows = [];
        $sumGrossSales = 0.0;
        $sumCreditNotes = 0.0;
        $sumNetSales = 0.0;
        $sumPurchases = 0.0;
        $sumExpenses = 0.0;
        $sumReceivables = 0.0;
        $sumPayables = 0.0;

        foreach ($companies as $co) {
            $sales = $this->computeSalesSummary([$co->id], $fromDate, $toDate);
            $purchases = $this->computePurchasesSummary([$co->id], $fromDate, $toDate);
            $expenses = $this->computeExpensesSummary([$co->id], $fromDate, $toDate);

            $grossSales = (float) $sales['total_sales'];
            $creditNotes = (float) $sales['credit_notes_total'];
            $netSales = (float) $sales['net_sales'];
            $purchaseVal = (float) $purchases['total_purchases'];
            $expenseVal = (float) $expenses['total_expenses'];

            $grossProfit = $netSales - $purchaseVal;
            $netProfit = $grossProfit - $expenseVal;

            $receivables = (float) $sales['outstanding_amount'];
            $payables = (float) $purchases['outstanding_amount'];

            $grossMargin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0.0;
            $netMargin = $netSales > 0 ? round(($netProfit / $netSales) * 100, 2) : 0.0;

            $sumGrossSales += $grossSales;
            $sumCreditNotes += $creditNotes;
            $sumNetSales += $netSales;
            $sumPurchases += $purchaseVal;
            $sumExpenses += $expenseVal;
            $sumReceivables += $receivables;
            $sumPayables += $payables;

            $companyRows[] = [
                'company_id' => $co->id,
                'name' => $co->name,
                'code' => $co->code,
                'business_type' => $co->business_type,
                'main_work' => $co->main_work,
                'gstin' => $co->gstin,
                'state' => $co->state,
                'sales' => $grossSales,
                'credit_notes' => $creditNotes,
                'net_sales' => $netSales,
                'purchases' => $purchaseVal,
                'expenses' => $expenseVal,
                'gross_profit' => $grossProfit,
                'gross_margin' => $grossMargin,
                'net_profit' => $netProfit,
                'net_margin' => $netMargin,
                'receivables' => $receivables,
                'payables' => $payables,
                'invoices_count' => $sales['total_invoices'],
                'purchases_count' => $purchases['total_invoices'],
            ];
        }

        // Inter-company elimination
        $interCompany = $this->detectInterCompanyTransactions($companies->pluck('id')->all(), $fromDate, $toDate);
        $eliminatedAmount = (float) ($interCompany['volume'] ?? 0.0);

        // Combined Net External Positions
        $combinedGrossSales = max(0.0, $sumGrossSales - $eliminatedAmount);
        $combinedNetSales = max(0.0, $sumNetSales - $eliminatedAmount);
        $combinedPurchases = max(0.0, $sumPurchases - $eliminatedAmount);
        $combinedGrossProfit = $combinedNetSales - $combinedPurchases;
        $combinedNetProfit = $combinedGrossProfit - $sumExpenses;

        $combinedGrossMargin = $combinedNetSales > 0 ? round(($combinedGrossProfit / $combinedNetSales) * 100, 2) : 0.0;
        $combinedNetMargin = $combinedNetSales > 0 ? round(($combinedNetProfit / $combinedNetSales) * 100, 2) : 0.0;

        $combinedRow = [
            'name' => 'Combined Total',
            'code' => 'ALL',
            'sales' => $combinedGrossSales,
            'unadjusted_sales' => $sumGrossSales,
            'credit_notes' => $sumCreditNotes,
            'net_sales' => $combinedNetSales,
            'purchases' => $combinedPurchases,
            'unadjusted_purchases' => $sumPurchases,
            'expenses' => $sumExpenses,
            'gross_profit' => $combinedGrossProfit,
            'gross_margin' => $combinedGrossMargin,
            'net_profit' => $combinedNetProfit,
            'net_margin' => $combinedNetMargin,
            'receivables' => $sumReceivables,
            'payables' => $sumPayables,
            'inter_company_eliminated' => $eliminatedAmount,
        ];

        return [
            'companies' => $companyRows,
            'combined' => $combinedRow,
            'inter_company' => $interCompany,
        ];
    }

    /**
     * Resolve Filter Dates by Period Type (FY, Monthly, Quarterly, Yearly, Custom).
     */
    public function resolveDateRange(\Illuminate\Http\Request $request, array $fyData): array
    {
        $periodType = $request->input('period_type', 'current_fy');
        $currentFy = $fyData['current'];
        $years = $fyData['years'];

        $today = now();
        $currentYear = $today->year;

        // Default FY
        $fromDate = $currentFy['starts_on'] ?: Carbon::create($currentYear, 4, 1)->toDateString();
        $toDate = $currentFy['ends_on'] ?: Carbon::create($currentYear + 1, 3, 31)->toDateString();
        $periodLabel = "Current FY ({$currentFy['name']})";
        $selectedFy = $currentFy['name'];

        switch ($periodType) {
            case 'prev_fy':
                $prevFy = collect($years)->where('is_current', false)->first() ?? [
                    'name' => ($currentYear - 1) . '-' . substr((string) $currentYear, -2),
                    'starts_on' => Carbon::create($currentYear - 1, 4, 1)->toDateString(),
                    'ends_on' => Carbon::create($currentYear, 3, 31)->toDateString(),
                ];
                $fromDate = $prevFy['starts_on'];
                $toDate = $prevFy['ends_on'];
                $periodLabel = "Previous FY ({$prevFy['name']})";
                $selectedFy = $prevFy['name'];
                break;

            case 'monthly':
                $targetMonth = $request->input('month');
                if ($targetMonth && preg_match('/^\d{4}-\d{2}$/', $targetMonth)) {
                    $monthCarbon = Carbon::createFromFormat('Y-m', $targetMonth);
                } else {
                    $monthCarbon = $today->copy();
                }
                $fromDate = $monthCarbon->copy()->startOfMonth()->toDateString();
                $toDate = $monthCarbon->copy()->endOfMonth()->toDateString();
                $periodLabel = "Monthly (" . $monthCarbon->format('F Y') . ")";
                break;

            case 'quarterly':
                $q = strtoupper((string) $request->input('quarter', ''));
                if (! in_array($q, ['Q1', 'Q2', 'Q3', 'Q4'], true)) {
                    // Determine current Indian FY quarter
                    $m = $today->month;
                    if ($m >= 4 && $m <= 6) $q = 'Q1';
                    elseif ($m >= 7 && $m <= 9) $q = 'Q2';
                    elseif ($m >= 10 && $m <= 12) $q = 'Q3';
                    else $q = 'Q4';
                }

                $fyStartYear = $today->month >= 4 ? $today->year : $today->year - 1;
                switch ($q) {
                    case 'Q1':
                        $fromDate = Carbon::create($fyStartYear, 4, 1)->toDateString();
                        $toDate = Carbon::create($fyStartYear, 6, 30)->toDateString();
                        $periodLabel = "Quarter 1 (Apr - Jun {$fyStartYear})";
                        break;
                    case 'Q2':
                        $fromDate = Carbon::create($fyStartYear, 7, 1)->toDateString();
                        $toDate = Carbon::create($fyStartYear, 9, 30)->toDateString();
                        $periodLabel = "Quarter 2 (Jul - Sep {$fyStartYear})";
                        break;
                    case 'Q3':
                        $fromDate = Carbon::create($fyStartYear, 10, 1)->toDateString();
                        $toDate = Carbon::create($fyStartYear, 12, 31)->toDateString();
                        $periodLabel = "Quarter 3 (Oct - Dec {$fyStartYear})";
                        break;
                    case 'Q4':
                        $fromDate = Carbon::create($fyStartYear + 1, 1, 1)->toDateString();
                        $toDate = Carbon::create($fyStartYear + 1, 3, 31)->toDateString();
                        $periodLabel = "Quarter 4 (Jan - Mar " . ($fyStartYear + 1) . ")";
                        break;
                }
                break;

            case 'yearly':
                $fromDate = Carbon::create($today->year, 1, 1)->toDateString();
                $toDate = Carbon::create($today->year, 12, 31)->toDateString();
                $periodLabel = "Calendar Year ({$today->year})";
                break;

            case 'custom':
            default:
                if ($request->filled('from_date') && $request->filled('to_date')) {
                    $fromDate = (string) $request->input('from_date');
                    $toDate = (string) $request->input('to_date');
                    $periodType = 'custom';
                    $periodLabel = Carbon::parse($fromDate)->format('d/m/Y') . ' to ' . Carbon::parse($toDate)->format('d/m/Y');
                } elseif ($request->filled('financial_year')) {
                    $fyReq = $request->input('financial_year');
                    $matched = collect($years)->firstWhere('name', $fyReq);
                    if ($matched) {
                        $fromDate = $matched['starts_on'];
                        $toDate = $matched['ends_on'];
                        $selectedFy = $matched['name'];
                        $periodLabel = "FY {$selectedFy}";
                        $periodType = $selectedFy === $currentFy['name'] ? 'current_fy' : 'prev_fy';
                    }
                }
                break;
        }

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'period_type' => $periodType,
            'period_label' => $periodLabel,
            'financial_year' => $selectedFy,
        ];
    }
}

