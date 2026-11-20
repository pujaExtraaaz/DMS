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

        // Filter by company IDs: prioritize direct company_id, fallback to warehouse/customer only when company_id is null
        $hasCompanyColumn = Schema::hasColumn('invoices', 'company_id');
        $query->where(function (Builder $q) use ($companyIds, $hasCompanyColumn) {
            if ($hasCompanyColumn) {
                $q->whereIn('invoices.company_id', $companyIds)
                    ->orWhere(function ($fallback) use ($companyIds) {
                        $fallback->whereNull('invoices.company_id')
                            ->where(function ($sub) use ($companyIds) {
                                $sub->whereHas('order.warehouse', fn ($wq) => $wq->whereIn('company_id', $companyIds))
                                    ->orWhereHas('customer', fn ($cq) => $cq->whereIn('company_id', $companyIds));
                            });
                    });
            } else {
                $q->whereHas('order.warehouse', fn ($wq) => $wq->whereIn('company_id', $companyIds))
                    ->orWhereHas('customer', fn ($cq) => $cq->whereIn('company_id', $companyIds));
            }
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

        // Filter by company IDs: prioritize direct company_id, fallback to warehouse only when company_id is null
        $hasCompanyColumn = Schema::hasColumn('purchase_invoices', 'company_id');
        $query->where(function (Builder $q) use ($companyIds, $hasCompanyColumn) {
            if ($hasCompanyColumn) {
                $q->whereIn('purchase_invoices.company_id', $companyIds)
                    ->orWhere(function ($fallback) use ($companyIds) {
                        $fallback->whereNull('purchase_invoices.company_id')
                            ->whereHas('warehouse', fn ($wq) => $wq->whereIn('company_id', $companyIds));
                    });
            } else {
                $q->whereHas('warehouse', fn ($wq) => $wq->whereIn('company_id', $companyIds));
            }
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
            try {
                $creditNotesTotal = (float) CreditNote::query()
                    ->where('status', 'posted')
                    ->whereBetween('credit_note_date', [$fromDate, $toDate])
                    ->whereIn('invoice_id', $invoices->pluck('id')->filter())
                    ->sum('grand_total');
            } catch (\Throwable) {
                // Ignore schema mismatches
            }
        }

        if (Schema::hasTable('acct_vouchers')) {
            $voucherCreditNotes = (float) DB::table('acct_vouchers')
                ->whereIn('company_id', $companyIds)
                ->where('voucher_type', 'credit_note')
                ->where('status', 'posted')
                ->whereBetween('voucher_date', [$fromDate, $toDate])
                ->sum('total_credit');
            $creditNotesTotal = max($creditNotesTotal, $voucherCreditNotes);
        }

        $netSales = max(0.0, $grossSales - $creditNotesTotal);
        $outstanding = max(0.0, $grossSales - $collected - $creditNotesTotal);

        return [
            'total_sales' => $grossSales,
            'sales_returns' => $creditNotesTotal,
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

        // Calculate Debit Notes (Purchase Returns) in this period
        $debitNotesTotal = 0.0;
        if (Schema::hasTable('supplier_payables')) {
            try {
                $dpQuery = DB::table('supplier_payables')
                    ->where('type', 'debit_note')
                    ->whereDate('created_at', '>=', $fromDate)
                    ->whereDate('created_at', '<=', $toDate);

                if (Schema::hasColumn('supplier_payables', 'company_id')) {
                    $dpQuery->whereIn('company_id', $companyIds);
                } elseif (Schema::hasColumn('supplier_payables', 'supplier_id')) {
                    $supplierIds = Customer::query()->whereIn('company_id', $companyIds)->pluck('id');
                    $dpQuery->whereIn('supplier_id', $supplierIds);
                }

                if (Schema::hasColumn('supplier_payables', 'debit')) {
                    $debitNotesTotal += (float) $dpQuery->sum('debit');
                } elseif (Schema::hasColumn('supplier_payables', 'amount')) {
                    $debitNotesTotal += (float) $dpQuery->sum('amount');
                }
            } catch (\Throwable) {
                // Ignore any legacy/mismatched supplier_payables schema errors
            }
        }

        if (Schema::hasTable('acct_vouchers')) {
            $voucherDebitNotes = (float) DB::table('acct_vouchers')
                ->whereIn('company_id', $companyIds)
                ->where('voucher_type', 'debit_note')
                ->where('status', 'posted')
                ->whereBetween('voucher_date', [$fromDate, $toDate])
                ->sum('total_debit');
            $debitNotesTotal = max($debitNotesTotal, $voucherDebitNotes);
        }

        $netPurchases = max(0.0, $totalPurchases - $debitNotesTotal);
        $outstandingPayables = max(0.0, $totalPurchases - $paidAmount - $debitNotesTotal);

        return [
            'total_purchases' => $totalPurchases,
            'purchase_returns' => $debitNotesTotal,
            'debit_notes_total' => $debitNotesTotal,
            'net_purchases' => $netPurchases,
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
        $matchedCount = 0;
        $unmatchedCount = 0;
        $unmatchedVolume = 0.0;
        $internalReceivables = 0.0;
        $internalPayables = 0.0;
        $internalSales = 0.0;
        $internalPurchases = 0.0;
        $items = collect();

        if (count($companyIds) > 1) {
            $companies = Company::whereIn('id', $companyIds)->get()->keyBy('id');
            $companiesByGstin = $companies->filter(fn ($c) => filled($c->gstin))->keyBy('gstin');

            // 1. Direct InterCompanyTransaction table records
            if (Schema::hasTable('inter_company_transactions')) {
                $txs = InterCompanyTransaction::query()
                    ->with(['fromCompany', 'toCompany'])
                    ->whereIn('from_company_id', $companyIds)
                    ->whereIn('to_company_id', $companyIds)
                    ->whereBetween('transaction_date', [$fromDate, $toDate])
                    ->where('status', '!=', 'Cancelled')
                    ->orderByDesc('transaction_date')
                    ->get();

                foreach ($txs as $tx) {
                    $amount = (float) $tx->net_amount;
                    $isSettled = strtolower((string) $tx->status) === 'settled';
                    $isApproved = in_array(strtolower((string) $tx->status), ['approved', 'settled'], true);
                    $outstanding = $isSettled ? 0.0 : $amount;

                    $matchStatus = $isApproved ? 'Matched' : 'Pending Verification';

                    if ($isApproved) {
                        $interCompanyVolume += $amount;
                        $interCompanyTxCount++;
                        $matchedCount++;
                    } else {
                        $unmatchedCount++;
                        $unmatchedVolume += $amount;
                    }

                    $internalReceivables += $outstanding;
                    $internalPayables += $outstanding;
                    $internalSales += $amount;
                    $internalPurchases += $amount;

                    $items->push([
                        'id' => 'tx_' . $tx->id,
                        'source' => 'inter_company_register',
                        'transaction_no' => $tx->transaction_no,
                        'transaction_date' => $tx->transaction_date ? (is_string($tx->transaction_date) ? $tx->transaction_date : $tx->transaction_date->format('Y-m-d')) : $fromDate,
                        'transaction_type' => $tx->transaction_type ?: 'Inter-Company Transfer',
                        'selling_company_id' => $tx->from_company_id,
                        'selling_company_name' => $tx->fromCompany?->name ?? 'Company A',
                        'purchasing_company_id' => $tx->to_company_id,
                        'purchasing_company_name' => $tx->toCompany?->name ?? 'Company B',
                        'amount' => (float) $tx->amount,
                        'net_amount' => $amount,
                        'outstanding_amount' => $outstanding,
                        'status' => $tx->status ?: 'Pending',
                        'match_status' => $matchStatus,
                        'reference_invoice' => $tx->reference_invoice ?: '—',
                        'narration' => $tx->narration ?: 'Direct Inter-Company Transaction',
                    ]);
                }
            }

            // 2. Scan Sales Invoices where Customer matches a Sister Concern
            $knownTxInvoices = $items->pluck('reference_invoice')->filter()->all();

            $salesQuery = Invoice::query()
                ->with(['company', 'customer'])
                ->whereIn('company_id', $companyIds)
                ->whereBetween('invoice_date', [$fromDate, $toDate])
                ->where('status', '!=', 'cancelled');

            $invoices = $salesQuery->get();

            foreach ($invoices as $inv) {
                if (in_array($inv->invoice_no, $knownTxInvoices, true)) {
                    continue;
                }

                $customer = $inv->customer;
                if (! $customer) {
                    continue;
                }

                // Check if customer corresponds to another sister concern
                $targetCo = null;
                if (filled($customer->gstin) && isset($companiesByGstin[$customer->gstin]) && $companiesByGstin[$customer->gstin]->id !== $inv->company_id) {
                    $targetCo = $companiesByGstin[$customer->gstin];
                } elseif (filled($customer->name)) {
                    $cName = strtolower(trim($customer->name));
                    $targetCo = $companies->first(fn ($c) => $c->id !== $inv->company_id && strtolower(trim($c->name)) === $cName);
                }

                if ($targetCo) {
                    $amount = (float) $inv->grand_total;
                    $paid = (float) ($inv->paid_amount ?? 0.0);
                    $outstanding = max(0.0, $amount - $paid);

                    // Check if corresponding purchase invoice exists in target company
                    $matchingPurchase = PurchaseInvoice::query()
                        ->where('company_id', $targetCo->id)
                        ->where('status', '!=', 'cancelled')
                        ->where(function ($q) use ($inv, $amount) {
                            $q->where('supplier_invoice_no', $inv->invoice_no)
                                ->orWhere(function ($sub) use ($inv, $amount) {
                                    $sub->whereBetween('invoice_date', [
                                        $inv->invoice_date->copy()->subDays(3)->toDateString(),
                                        $inv->invoice_date->copy()->addDays(3)->toDateString(),
                                    ])->whereRaw('ABS(grand_total - ?) < 1.0', [$amount]);
                                });
                        })
                        ->first();

                    $matchStatus = $matchingPurchase ? 'Matched' : 'Unmatched';
                    if ($matchingPurchase) {
                        $interCompanyVolume += $amount;
                        $interCompanyTxCount++;
                        $matchedCount++;
                    } else {
                        $unmatchedCount++;
                        $unmatchedVolume += $amount;
                    }

                    $internalReceivables += $outstanding;
                    $internalPayables += $outstanding;
                    $internalSales += $amount;
                    $internalPurchases += $amount;

                    $items->push([
                        'id' => 'inv_' . $inv->id,
                        'source' => 'sales_invoice',
                        'transaction_no' => $inv->invoice_no,
                        'transaction_date' => $inv->invoice_date ? $inv->invoice_date->format('Y-m-d') : $fromDate,
                        'transaction_type' => 'Internal Sales',
                        'selling_company_id' => $inv->company_id,
                        'selling_company_name' => $inv->company?->name ?? 'Selling Company',
                        'purchasing_company_id' => $targetCo->id,
                        'purchasing_company_name' => $targetCo->name,
                        'amount' => (float) $inv->subtotal,
                        'net_amount' => $amount,
                        'outstanding_amount' => $outstanding,
                        'status' => ucfirst((string) $inv->status),
                        'match_status' => $matchStatus,
                        'reference_invoice' => $inv->invoice_no,
                        'narration' => 'Internal Sale to Sister Concern ' . $targetCo->name,
                    ]);
                }
            }
        }

        return [
            'volume' => $interCompanyVolume,
            'count' => $interCompanyTxCount,
            'sales_volume' => $internalSales,
            'purchases_volume' => $internalPurchases,
            'receivables' => $internalReceivables,
            'payables' => $internalPayables,
            'matched_count' => $matchedCount,
            'unmatched_count' => $unmatchedCount,
            'unmatched_volume' => $unmatchedVolume,
            'all_transactions' => $items->values()->all(),
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
     * @param string $eliminationMode 'after' or 'before'
     * @return array{
     *     companies: array<int, array>,
     *     combined: array,
     *     inter_company: array
     * }
     */
    public function getComparisonMatrix(Collection $companies, string $fromDate, string $toDate, string $eliminationMode = 'after'): array
    {
        $companyRows = [];
        $sumGrossSales = 0.0;
        $sumCreditNotes = 0.0;
        $sumNetSales = 0.0;
        $sumPurchases = 0.0;
        $sumDebitNotes = 0.0;
        $sumNetPurchases = 0.0;
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
            $debitNotes = (float) ($purchases['purchase_returns'] ?? 0.0);
            $netPurchases = (float) ($purchases['net_purchases'] ?? max(0.0, $purchaseVal - $debitNotes));
            $expenseVal = (float) $expenses['total_expenses'];

            $grossProfit = $netSales - $netPurchases;
            $netProfit = $grossProfit - $expenseVal;

            $receivables = (float) $sales['outstanding_amount'];
            $payables = (float) $purchases['outstanding_amount'];

            $grossMargin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0.0;
            $netMargin = $netSales > 0 ? round(($netProfit / $netSales) * 100, 2) : 0.0;

            $sumGrossSales += $grossSales;
            $sumCreditNotes += $creditNotes;
            $sumNetSales += $netSales;
            $sumPurchases += $purchaseVal;
            $sumDebitNotes += $debitNotes;
            $sumNetPurchases += $netPurchases;
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
                'debit_notes' => $debitNotes,
                'net_purchases' => $netPurchases,
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
        $interCompanyReceivables = (float) ($interCompany['receivables'] ?? 0.0);
        $interCompanyPayables = (float) ($interCompany['payables'] ?? 0.0);

        if ($eliminationMode === 'after') {
            $combinedGrossSales = max(0.0, $sumGrossSales - $eliminatedAmount);
            $combinedNetSales = max(0.0, $sumNetSales - $eliminatedAmount);
            $combinedPurchases = max(0.0, $sumPurchases - $eliminatedAmount);
            $combinedNetPurchases = max(0.0, $sumNetPurchases - $eliminatedAmount);
            $combinedReceivables = max(0.0, $sumReceivables - $interCompanyReceivables);
            $combinedPayables = max(0.0, $sumPayables - $interCompanyPayables);
        } else {
            // Before elimination: pure aggregation
            $combinedGrossSales = $sumGrossSales;
            $combinedNetSales = $sumNetSales;
            $combinedPurchases = $sumPurchases;
            $combinedNetPurchases = $sumNetPurchases;
            $combinedReceivables = $sumReceivables;
            $combinedPayables = $sumPayables;
        }

        $combinedGrossProfit = $combinedNetSales - $combinedNetPurchases;
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
            'unadjusted_net_sales' => $sumNetSales,
            'purchases' => $combinedPurchases,
            'unadjusted_purchases' => $sumPurchases,
            'debit_notes' => $sumDebitNotes,
            'net_purchases' => $combinedNetPurchases,
            'unadjusted_net_purchases' => $sumNetPurchases,
            'expenses' => $sumExpenses,
            'gross_profit' => $combinedGrossProfit,
            'gross_margin' => $combinedGrossMargin,
            'net_profit' => $combinedNetProfit,
            'net_margin' => $combinedNetMargin,
            'receivables' => $combinedReceivables,
            'unadjusted_receivables' => $sumReceivables,
            'payables' => $combinedPayables,
            'unadjusted_payables' => $sumPayables,
            'inter_company_eliminated' => $eliminatedAmount,
            'inter_company_receivables_eliminated' => $interCompanyReceivables,
            'inter_company_payables_eliminated' => $interCompanyPayables,
            'elimination_mode' => $eliminationMode,
        ];

        return [
            'companies' => $companyRows,
            'combined' => $combinedRow,
            'inter_company' => $interCompany,
        ];
    }

    /**
     * Get Quarterly Comparisons (Q1, Q2, Q3, Q4) for the companies.
     */
    public function getQuarterlyComparison(Collection $companies, ?int $fyStartYear = null): array
    {
        $today = now();
        $fyStartYear = $fyStartYear ?? ($today->month >= 4 ? $today->year : $today->year - 1);

        $quarters = [
            'Q1' => ['label' => 'Q1 (Apr-Jun)', 'start' => "{$fyStartYear}-04-01", 'end' => "{$fyStartYear}-06-30"],
            'Q2' => ['label' => 'Q2 (Jul-Sep)', 'start' => "{$fyStartYear}-07-01", 'end' => "{$fyStartYear}-09-30"],
            'Q3' => ['label' => 'Q3 (Oct-Dec)', 'start' => "{$fyStartYear}-10-01", 'end' => "{$fyStartYear}-12-31"],
            'Q4' => ['label' => 'Q4 (Jan-Mar)', 'start' => ($fyStartYear + 1) . "-01-01", 'end' => ($fyStartYear + 1) . "-03-31"],
        ];

        $results = [];
        foreach ($quarters as $key => $q) {
            $matrix = $this->getComparisonMatrix($companies, $q['start'], $q['end']);
            $results[$key] = [
                'quarter' => $key,
                'label' => $q['label'],
                'start_date' => $q['start'],
                'end_date' => $q['end'],
                'companies' => $matrix['companies'],
                'combined' => $matrix['combined'],
            ];
        }

        return $results;
    }

    /**
     * Get Year-Over-Year Comparison across configured Financial Years.
     */
    public function getYearlyComparison(Collection $companies, array $financialYears): array
    {
        $results = [];
        foreach (array_slice($financialYears, 0, 3) as $fy) {
            $startsOn = $fy['starts_on'] ?: '';
            $endsOn = $fy['ends_on'] ?: '';
            if (empty($startsOn) || empty($endsOn)) {
                continue;
            }

            $matrix = $this->getComparisonMatrix($companies, $startsOn, $endsOn);
            $results[$fy['name']] = [
                'name' => $fy['name'],
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'is_current' => $fy['is_current'] ?? false,
                'companies' => $matrix['companies'],
                'combined' => $matrix['combined'],
            ];
        }

        return $results;
    }

    /**
     * Compute Monthly Sales and Purchases comparing Company A vs Company B and Combined.
     */
    public function getMonthlyTrendsWithCompanies(Collection $companies, string $fromDate, string $toDate): array
    {
        $start = Carbon::parse($fromDate)->startOfMonth();
        $end = Carbon::parse($toDate)->endOfMonth();

        $companyIds = $companies->pluck('id')->all();
        $invoices = $this->buildSalesQuery($companyIds, $fromDate, $toDate, null, null, true)
            ->get(['id', 'company_id', 'invoice_date', 'grand_total']);

        $purchases = $this->buildPurchasesQuery($companyIds, $fromDate, $toDate, null, null, true)
            ->get(['id', 'company_id', 'invoice_date', 'grand_total']);

        $trends = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $monthKey = $current->format('Y-m');
            $monthLabel = $current->format('M Y');

            $mInvoices = $invoices->filter(fn ($i) => $i->invoice_date && $i->invoice_date->format('Y-m') === $monthKey);
            $mPurchases = $purchases->filter(fn ($p) => $p->invoice_date && $p->invoice_date->format('Y-m') === $monthKey);

            $companyData = [];
            foreach ($companies as $co) {
                $coSales = (float) $mInvoices->filter(fn ($i) => (int) $i->company_id === (int) $co->id)->sum('grand_total');
                $coPurchases = (float) $mPurchases->filter(fn ($p) => (int) $p->company_id === (int) $co->id)->sum('grand_total');
                $companyData[$co->id] = [
                    'company_name' => $co->name,
                    'code' => $co->code,
                    'sales' => $coSales,
                    'purchases' => $coPurchases,
                    'net' => $coSales - $coPurchases,
                ];
            }

            $totalSales = (float) $mInvoices->sum('grand_total');
            $totalPurchases = (float) $mPurchases->sum('grand_total');

            $trends[] = [
                'month_key' => $monthKey,
                'month_label' => $monthLabel,
                'sales' => $totalSales,
                'purchases' => $totalPurchases,
                'net' => $totalSales - $totalPurchases,
                'company_breakdown' => $companyData,
            ];

            $current->addMonth();
        }

        return $trends;
    }

    /**
     * Get Drilldown rows for a specific KPI type.
     */
    public function getDrilldownData(Collection $companies, string $type, string $fromDate, string $toDate, ?string $search = null): array
    {
        $companyIds = $companies->pluck('id')->all();

        switch ($type) {
            case 'sales':
                $query = $this->buildSalesQuery($companyIds, $fromDate, $toDate, $search, null, false)
                    ->orderByDesc('invoice_date');
                return $query->limit(200)->get()->map(function (Invoice $inv) {
                    $paid = (float) ($inv->paid_amount ?? 0.0);
                    $total = (float) $inv->grand_total;
                    return [
                        'company' => $inv->company?->name ?? 'Company',
                        'number' => $inv->invoice_no,
                        'date' => $inv->invoice_date?->format('d/m/Y') ?? '—',
                        'party' => $inv->customer?->name ?? '—',
                        'gstin' => $inv->customer?->gstin ?? '—',
                        'taxable' => (float) $inv->subtotal,
                        'tax' => (float) $inv->tax_amount,
                        'total' => $total,
                        'paid' => $paid,
                        'outstanding' => max(0.0, $total - $paid),
                        'status' => ucfirst((string) $inv->status),
                    ];
                })->all();

            case 'purchases':
                $query = $this->buildPurchasesQuery($companyIds, $fromDate, $toDate, $search, null, false)
                    ->orderByDesc('invoice_date');
                return $query->limit(200)->get()->map(function (PurchaseInvoice $pi) {
                    $total = (float) $pi->grand_total;
                    $paid = $pi->status === 'paid' ? $total : (float) ($pi->paid_amount ?? 0.0);
                    return [
                        'company' => $pi->company?->name ?? $pi->warehouse?->company?->name ?? 'Company',
                        'number' => $pi->invoice_no,
                        'supplier_number' => $pi->supplier_invoice_no ?: '—',
                        'date' => $pi->invoice_date?->format('d/m/Y') ?? '—',
                        'party' => $pi->supplier?->name ?? '—',
                        'gstin' => $pi->supplier?->gstin ?? '—',
                        'taxable' => (float) $pi->subtotal,
                        'tax' => (float) $pi->tax_amount,
                        'total' => $total,
                        'paid' => $paid,
                        'outstanding' => max(0.0, $total - $paid),
                        'status' => ucfirst((string) $pi->status),
                    ];
                })->all();

            case 'expenses':
                $summary = $this->computeExpensesSummary($companyIds, $fromDate, $toDate);
                $rows = [];
                foreach ($summary['breakdown'] as $key => $item) {
                    $rows[] = [
                        'category' => $item['label'],
                        'count' => $item['count'],
                        'amount' => $item['amount'],
                    ];
                }
                return $rows;

            case 'inter_company':
                $ic = $this->detectInterCompanyTransactions($companyIds, $fromDate, $toDate);
                return $ic['all_transactions'] ?? [];

            default:
                return [];
        }
    }

    /**
     * Resolve Filter Dates by Period Type (FY, Monthly, Quarterly, Yearly, Custom).
     */
    public function resolveDateRange(\Illuminate\Http\Request $request, array $fyData): array
    {
        $periodType = $request->input('period_type');
        if (! $periodType) {
            $periodType = ($request->filled('from_date') && $request->filled('to_date')) ? 'custom' : 'current_fy';
        }
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

