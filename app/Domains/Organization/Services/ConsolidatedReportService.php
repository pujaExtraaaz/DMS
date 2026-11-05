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
}

