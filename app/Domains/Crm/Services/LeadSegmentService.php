<?php

namespace App\Domains\Crm\Services;

use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\LeadCampaign;
use App\Domains\Crm\Models\LeadSource;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Branch;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use App\Support\IndianStates;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LeadSegmentService
{
    /**
     * Build the Eloquent query for leads matching the criteria.
     */
    public function query(array $criteria, ?int $companyId = null, ?int $branchId = null): Builder
    {
        $query = Lead::query()
            ->with(['source', 'campaign', 'assignee', 'subCategory', 'convertedCustomer'])
            ->when($companyId, fn ($q) => $q->where('leads.company_id', $companyId))
            ->when($branchId, fn ($q) => $q->where('leads.branch_id', $branchId));

        $this->applyIndustryCriteria($query, $criteria);
        $this->applyRegionCriteria($query, $criteria);
        $this->applyCrmClassificationCriteria($query, $criteria);
        $this->applyPurchaseHistoryCriteria($query, $criteria);
        $this->applyEngagementCriteria($query, $criteria);

        return $query;
    }

    /**
     * Get count of matching leads.
     */
    public function count(array $criteria, ?int $companyId = null, ?int $branchId = null): int
    {
        return $this->query($criteria, $companyId, $branchId)->count();
    }

    /**
     * Preview matching leads with channel statistics.
     */
    public function preview(array $criteria, ?int $companyId = null, ?int $branchId = null, int $limit = 10): array
    {
        $baseQuery = $this->query($criteria, $companyId, $branchId);

        $totalCount = (clone $baseQuery)->count();
        $withMobileCount = (clone $baseQuery)->where(function ($q) {
            $q->whereNotNull('mobile')->where('mobile', '!=', '')
              ->orWhereNotNull('secondary_mobile')->where('secondary_mobile', '!=', '')
              ->orWhereNotNull('phone')->where('phone', '!=', '');
        })->count();

        $withEmailCount = (clone $baseQuery)->where(function ($q) {
            $q->whereNotNull('email')->where('email', '!=', '')
              ->orWhereNotNull('secondary_email')->where('secondary_email', '!=', '');
        })->count();

        $sampleLeads = (clone $baseQuery)
            ->latest('leads.created_at')
            ->limit($limit)
            ->get();

        return [
            'total_count' => $totalCount,
            'with_mobile_count' => $withMobileCount,
            'with_email_count' => $withEmailCount,
            'sample_leads' => $sampleLeads,
        ];
    }

    /**
     * Apply Industry criteria.
     */
    protected function applyIndustryCriteria(Builder $query, array $criteria): void
    {
        if (empty($criteria['industry'])) {
            return;
        }

        $industries = is_array($criteria['industry'])
            ? array_filter($criteria['industry'])
            : array_filter(array_map('trim', explode(',', (string) $criteria['industry'])));

        if (empty($industries)) {
            return;
        }

        $query->where(function (Builder $sub) use ($industries) {
            foreach ($industries as $idx => $ind) {
                $method = $idx === 0 ? 'where' : 'orWhere';
                $sub->{$method}(function ($q) use ($ind) {
                    $q->where('leads.industry', 'LIKE', "%{$ind}%")
                      ->orWhere('leads.tag', 'LIKE', "%{$ind}%")
                      ->orWhere('leads.organization', 'LIKE', "%{$ind}%");
                });
            }
        });
    }

    /**
     * Apply Region criteria (State, City, Branch).
     */
    protected function applyRegionCriteria(Builder $query, array $criteria): void
    {
        // States
        if (! empty($criteria['state'])) {
            $states = is_array($criteria['state'])
                ? array_filter($criteria['state'])
                : array_filter(array_map('trim', explode(',', (string) $criteria['state'])));

            if (! empty($states)) {
                $query->where(function ($q) use ($states) {
                    foreach ($states as $st) {
                        $q->orWhereRaw('LOWER(leads.state) = ?', [strtolower($st)]);
                    }
                });
            }
        }

        // Cities
        if (! empty($criteria['city'])) {
            $cities = is_array($criteria['city'])
                ? array_filter($criteria['city'])
                : array_filter(array_map('trim', explode(',', (string) $criteria['city'])));

            if (! empty($cities)) {
                $query->where(function ($q) use ($cities) {
                    foreach ($cities as $c) {
                        $q->orWhereRaw('LOWER(leads.city) = ?', [strtolower($c)]);
                    }
                });
            }
        }

        // Branch
        if (! empty($criteria['branch_id'])) {
            $branchIds = (array) $criteria['branch_id'];
            $query->whereIn('leads.branch_id', $branchIds);
        }
    }

    /**
     * Apply CRM classification criteria (Category, Status, Priority, Source, Campaign, Assignee).
     */
    protected function applyCrmClassificationCriteria(Builder $query, array $criteria): void
    {
        // Category
        if (! empty($criteria['category'])) {
            $cat = $criteria['category'];
            if (is_array($cat)) {
                $hasUncat = in_array('uncategorized', $cat, true);
                $namedCats = array_filter($cat, fn ($c) => $c !== 'uncategorized');

                $query->where(function ($q) use ($namedCats, $hasUncat) {
                    if (! empty($namedCats)) {
                        $q->whereIn('leads.category', $namedCats);
                    }
                    if ($hasUncat) {
                        $method = ! empty($namedCats) ? 'orWhere' : 'where';
                        $q->{$method}(function ($sub) {
                            $sub->whereNull('leads.category')->orWhere('leads.category', '');
                        });
                    }
                });
            } elseif ($cat === 'uncategorized') {
                $query->where(function ($sub) {
                    $sub->whereNull('leads.category')->orWhere('leads.category', '');
                });
            } else {
                $query->where('leads.category', strtolower(trim((string) $cat)));
            }
        }

        // Status
        if (! empty($criteria['status'])) {
            $status = $criteria['status'];
            if (is_array($status)) {
                $statuses = [];
                foreach ($status as $s) {
                    if (in_array($s, ['follow_up', 'followup', 'follow-up'], true)) {
                        $statuses = array_merge($statuses, ['follow_up', 'followup', 'follow-up']);
                    } else {
                        $statuses[] = $s;
                    }
                }
                $query->whereIn('leads.status', array_unique($statuses));
            } else {
                if (in_array($status, ['follow_up', 'followup', 'follow-up'], true)) {
                    $query->whereIn('leads.status', ['follow_up', 'followup', 'follow-up']);
                } else {
                    $query->where('leads.status', $status);
                }
            }
        }

        // Priority
        if (! empty($criteria['priority'])) {
            $priorities = (array) $criteria['priority'];
            $query->whereIn('leads.priority', $priorities);
        }

        // Assigned to
        if (! empty($criteria['assigned_to'])) {
            $query->whereIn('leads.assigned_to', (array) $criteria['assigned_to']);
        }

        // Source
        if (! empty($criteria['lead_source_id'])) {
            $query->whereIn('leads.lead_source_id', (array) $criteria['lead_source_id']);
        }

        // Campaign
        if (! empty($criteria['lead_campaign_id'])) {
            $query->whereIn('leads.lead_campaign_id', (array) $criteria['lead_campaign_id']);
        }
    }

    /**
     * Apply Purchase History criteria based on actual Sales Orders and Invoices.
     */
    protected function applyPurchaseHistoryCriteria(Builder $query, array $criteria): void
    {
        $purchaseStatus = $criteria['purchase_status'] ?? null;

        // 1. Status: Unavailable (Lead is not linked to any customer Party)
        if ($purchaseStatus === 'unavailable') {
            $query->whereNull('leads.converted_customer_id');
            return;
        }

        // 2. Status: Never Purchased (Lead is linked to customer Party, but has 0 eligible orders and 0 invoices)
        if ($purchaseStatus === 'never_purchased') {
            $query->whereNotNull('leads.converted_customer_id')
                ->whereDoesntHave('convertedCustomer.orders', fn ($o) => $o->where('status', '!=', 'cancelled'))
                ->whereDoesntHave('convertedCustomer.invoices', fn ($i) => $i->where('status', '!=', 'cancelled'));
            return;
        }

        // 3. Status: Has Purchased (Lead is linked to customer Party and has >= 1 eligible order or invoice)
        if ($purchaseStatus === 'has_purchased') {
            $query->whereNotNull('leads.converted_customer_id')
                ->where(function (Builder $sub) {
                    $sub->whereHas('convertedCustomer.orders', fn ($o) => $o->where('status', '!=', 'cancelled'))
                        ->orWhereHas('convertedCustomer.invoices', fn ($i) => $i->where('status', '!=', 'cancelled'));
                });
        }

        // 4. Repeat Customers (>= 2 orders or invoices)
        if ($purchaseStatus === 'repeat_customer' || ! empty($criteria['repeat_customer'])) {
            $repeatCustomerIds = Order::select('customer_id')
                ->where('status', '!=', 'cancelled')
                ->groupBy('customer_id')
                ->havingRaw('COUNT(*) >= 2');

            $query->whereIn('leads.converted_customer_id', $repeatCustomerIds);
        }

        // 5. Inactive Customers (Has purchased in past, but no purchase in the last X days)
        $noPurchaseDays = ! empty($criteria['last_purchase_before_days'])
            ? (int) $criteria['last_purchase_before_days']
            : (in_array($purchaseStatus, ['no_purchase_in_days', 'inactive_days'], true)
                ? (int) ($criteria['no_purchase_days'] ?? $criteria['purchase_inactive_days'] ?? 0)
                : null);

        if ($noPurchaseDays !== null && $noPurchaseDays > 0) {
            $cutoff = Carbon::now()->subDays($noPurchaseDays)->toDateString();

            // Customer must have purchase history, and their latest purchase date <= cutoff
            $inactiveCustomerIds = Order::select('customer_id')
                ->where('status', '!=', 'cancelled')
                ->groupBy('customer_id')
                ->havingRaw('MAX(order_date) <= ?', [$cutoff]);

            $query->whereIn('leads.converted_customer_id', $inactiveCustomerIds);
        }

        // 6. Active Customers (Placed purchase within last X days)
        if (! empty($criteria['last_purchase_within_days'])) {
            $cutoff = Carbon::now()->subDays((int) $criteria['last_purchase_within_days'])->toDateString();

            $activeCustomerIds = Order::select('customer_id')
                ->where('status', '!=', 'cancelled')
                ->where('order_date', '>=', $cutoff);

            $query->whereIn('leads.converted_customer_id', $activeCustomerIds);
        }

        // 7. Minimum Orders count
        if (! empty($criteria['min_orders'])) {
            $minOrders = (int) $criteria['min_orders'];
            $customerIds = Order::select('customer_id')
                ->where('status', '!=', 'cancelled')
                ->groupBy('customer_id')
                ->havingRaw('COUNT(*) >= ' . $minOrders);

            $query->whereIn('leads.converted_customer_id', $customerIds);
        }

        // 8. Total Purchase Value range
        $minVal = $criteria['min_total_value'] ?? $criteria['min_total_spent'] ?? $criteria['min_spend'] ?? null;
        $maxVal = $criteria['max_total_value'] ?? $criteria['max_total_spent'] ?? $criteria['max_spend'] ?? null;
        if (! empty($minVal) || ! empty($maxVal)) {
            $valQuery = Order::select('customer_id')
                ->where('status', '!=', 'cancelled')
                ->groupBy('customer_id');

            if (! empty($minVal)) {
                $valQuery->havingRaw('SUM(grand_total) >= ' . (float) $minVal);
            }
            if (! empty($maxVal)) {
                $valQuery->havingRaw('SUM(grand_total) <= ' . (float) $maxVal);
            }

            $query->whereIn('leads.converted_customer_id', $valQuery);
        }

        // 9. Specific Purchase Date Range
        if (! empty($criteria['purchase_date_from']) || ! empty($criteria['purchase_date_to'])) {
            $dateQuery = Order::select('customer_id')
                ->where('status', '!=', 'cancelled');

            if (! empty($criteria['purchase_date_from'])) {
                $dateQuery->where('order_date', '>=', $criteria['purchase_date_from']);
            }
            if (! empty($criteria['purchase_date_to'])) {
                $dateQuery->where('order_date', '<=', $criteria['purchase_date_to']);
            }

            $query->whereIn('leads.converted_customer_id', $dateQuery);
        }
    }

    /**
     * Apply Engagement Level criteria based on actual followups, activities, and contact dates.
     */
    protected function applyEngagementCriteria(Builder $query, array $criteria): void
    {
        $engagementLevel = $criteria['engagement_level'] ?? null;

        // 1. Highly Engaged: >= 3 interactions or active within the last 14 days
        if ($engagementLevel === 'high') {
            $cutoff = Carbon::now()->subDays(14);
            $query->where(function (Builder $sub) use ($cutoff) {
                $sub->whereHas('followups', null, '>=', 3)
                    ->orWhereHas('activities', null, '>=', 3)
                    ->orWhere('leads.last_contacted_at', '>=', $cutoff)
                    ->orWhereHas('followups', fn ($f) => $f->where('created_at', '>=', $cutoff))
                    ->orWhereHas('activities', fn ($a) => $a->where('created_at', '>=', $cutoff));
            });
        }

        // 2. Moderately Engaged: >= 1 interaction or active within the last 30 days
        if ($engagementLevel === 'medium') {
            $cutoff = Carbon::now()->subDays(30);
            $query->where(function (Builder $sub) use ($cutoff) {
                $sub->whereHas('followups')
                    ->orWhereHas('activities')
                    ->orWhere('leads.last_contacted_at', '>=', $cutoff)
                    ->orWhereHas('followups', fn ($f) => $f->where('created_at', '>=', $cutoff))
                    ->orWhereHas('activities', fn ($a) => $a->where('created_at', '>=', $cutoff));
            });
        }

        // 3. Low Engagement: Has interaction recorded in past, but NO activity in the last 30 days
        if ($engagementLevel === 'low') {
            $cutoff = Carbon::now()->subDays(30);
            $query->where(function (Builder $sub) {
                $sub->whereHas('followups')
                    ->orWhereHas('activities')
                    ->orWhereNotNull('leads.last_contacted_at');
            })->where(function (Builder $sub) use ($cutoff) {
                $sub->whereNull('leads.last_contacted_at')
                    ->orWhere('leads.last_contacted_at', '<', $cutoff);
            })->whereDoesntHave('followups', fn ($f) => $f->where('created_at', '>=', $cutoff))
              ->whereDoesntHave('activities', fn ($a) => $a->where('created_at', '>=', $cutoff));
        }

        // 4. No Recent Activity: No followups or activities recorded in the last X days
        $noActivityDays = ! empty($criteria['no_activity_within_days'])
            ? (int) $criteria['no_activity_within_days']
            : ($engagementLevel === 'no_recent_activity' ? (int) ($criteria['no_activity_days'] ?? 30) : null);

        if ($noActivityDays !== null && $noActivityDays > 0) {
            $cutoff = Carbon::now()->subDays($noActivityDays);
            $query->whereDoesntHave('followups', fn ($f) => $f->where('created_at', '>=', $cutoff))
                ->whereDoesntHave('activities', fn ($a) => $a->where('created_at', '>=', $cutoff))
                ->where(function (Builder $sub) use ($cutoff) {
                    $sub->whereNull('leads.last_contacted_at')
                        ->orWhere('leads.last_contacted_at', '<', $cutoff);
                });
        }

        // 5. Activity within last X days
        if (! empty($criteria['last_activity_within_days'])) {
            $cutoff = Carbon::now()->subDays((int) $criteria['last_activity_within_days']);
            $query->where(function (Builder $sub) use ($cutoff) {
                $sub->where('leads.last_contacted_at', '>=', $cutoff)
                    ->orWhereHas('followups', fn ($f) => $f->where('created_at', '>=', $cutoff))
                    ->orWhereHas('activities', fn ($a) => $a->where('created_at', '>=', $cutoff));
            });
        }

        // 6. Unknown / No Activity Ever Recorded
        if ($engagementLevel === 'unknown' || $engagementLevel === 'none') {
            $query->whereDoesntHave('followups')
                ->whereDoesntHave('activities')
                ->whereNull('leads.last_contacted_at');
        }

        // 7. Followup Count threshold
        if (! empty($criteria['min_followups'])) {
            $query->whereHas('followups', null, '>=', (int) $criteria['min_followups']);
        }
        if (! empty($criteria['max_followups'])) {
            $query->whereDoesntHave('followups', null, '>', (int) $criteria['max_followups']);
        }

        // 8. Follow-up Channel
        if (! empty($criteria['followup_channel'])) {
            $channel = strtolower(trim((string) $criteria['followup_channel']));
            $query->whereHas('followups', fn ($f) => $f->where('channel', $channel));
        }
    }

    /**
     * Generate human-readable summaries of the segment criteria.
     */
    public function summarizeCriteria(array $criteria): array
    {
        $summary = [];

        // Industry & Region
        if (! empty($criteria['industry'])) {
            $inds = is_array($criteria['industry']) ? implode(', ', $criteria['industry']) : $criteria['industry'];
            $summary[] = "Industry: {$inds}";
        }
        if (! empty($criteria['state'])) {
            $states = is_array($criteria['state']) ? implode(', ', $criteria['state']) : $criteria['state'];
            $summary[] = "State: {$states}";
        }
        if (! empty($criteria['city'])) {
            $cities = is_array($criteria['city']) ? implode(', ', $criteria['city']) : $criteria['city'];
            $summary[] = "City: {$cities}";
        }
        if (! empty($criteria['branch_id'])) {
            $branchNames = Branch::whereIn('id', (array) $criteria['branch_id'])->pluck('name')->implode(', ');
            if ($branchNames) {
                $summary[] = "Branch: {$branchNames}";
            }
        }

        // Classification
        if (! empty($criteria['category'])) {
            $cats = is_array($criteria['category']) ? implode(', ', array_map('ucfirst', $criteria['category'])) : ucfirst($criteria['category']);
            $summary[] = "Category: {$cats}";
        }
        if (! empty($criteria['status'])) {
            $stats = is_array($criteria['status']) ? implode(', ', array_map('ucfirst', $criteria['status'])) : ucfirst($criteria['status']);
            $summary[] = "Status: {$stats}";
        }
        if (! empty($criteria['priority'])) {
            $pri = is_array($criteria['priority']) ? implode(', ', array_map('ucfirst', $criteria['priority'])) : ucfirst($criteria['priority']);
            $summary[] = "Priority: {$pri}";
        }

        // Purchase History
        if (! empty($criteria['purchase_status'])) {
            $labels = [
                'has_purchased' => 'Has Purchased',
                'never_purchased' => 'Never Purchased',
                'unavailable' => 'Purchase History Unavailable',
                'repeat_customer' => 'Repeat Customers (≥2 Orders)',
                'no_purchase_in_days' => 'No Purchase in last ' . ($criteria['no_purchase_days'] ?? 90) . ' days',
            ];
            $summary[] = "Purchase: " . ($labels[$criteria['purchase_status']] ?? ucfirst($criteria['purchase_status']));
        }
        if (! empty($criteria['last_purchase_before_days'])) {
            $summary[] = "No Purchase in last {$criteria['last_purchase_before_days']} days";
        }
        if (! empty($criteria['min_orders'])) {
            $summary[] = "Min Orders: {$criteria['min_orders']}";
        }
        if (! empty($criteria['min_total_value'])) {
            $summary[] = "Min Total Value: ₹" . number_format($criteria['min_total_value'], 2);
        }

        // Engagement
        if (! empty($criteria['engagement_level'])) {
            $engLabels = [
                'high' => 'Highly Engaged (≥3 interactions or active in 14d)',
                'medium' => 'Moderately Engaged (active in 30d)',
                'low' => 'Low Engagement (inactive >30d)',
                'no_recent_activity' => 'No Activity in last ' . ($criteria['no_activity_days'] ?? 30) . ' days',
                'unknown' => 'No Recorded Activity',
            ];
            $summary[] = "Engagement: " . ($engLabels[$criteria['engagement_level']] ?? ucfirst($criteria['engagement_level']));
        }
        if (! empty($criteria['no_activity_within_days'])) {
            $summary[] = "No follow-up in last {$criteria['no_activity_within_days']} days";
        }
        if (! empty($criteria['min_followups'])) {
            $summary[] = "Min Follow-ups: {$criteria['min_followups']}";
        }
        if (! empty($criteria['followup_channel'])) {
            $summary[] = "Channel: " . ucfirst($criteria['followup_channel']);
        }

        return $summary ?: ['All Leads (No criteria)'];
    }

    /**
     * Get available dropdown options based on existing database records.
     */
    public function getFilterOptions(?int $companyId = null): array
    {
        $leadQuery = Lead::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        // Distinct Industries / Tags from existing data
        $industriesFromColumn = (clone $leadQuery)
            ->whereNotNull('industry')
            ->where('industry', '!=', '')
            ->distinct()
            ->pluck('industry')
            ->toArray();

        $tagsFromColumn = (clone $leadQuery)
            ->whereNotNull('tag')
            ->where('tag', '!=', '')
            ->distinct()
            ->pluck('tag')
            ->toArray();

        $mergedIndustries = array_values(array_unique(array_filter(array_merge($industriesFromColumn, $tagsFromColumn))));
        sort($mergedIndustries);

        // Distinct States from leads merged with IndianStates master list
        $statesFromDb = (clone $leadQuery)
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->distinct()
            ->pluck('state')
            ->toArray();

        $masterStates = IndianStates::all();
        $mergedStates = array_values(array_unique(array_filter(array_merge($statesFromDb, $masterStates))));
        sort($mergedStates);

        // Distinct Cities from leads
        $citiesFromDb = (clone $leadQuery)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->pluck('city')
            ->toArray();
        sort($citiesFromDb);

        // Branches
        $branches = Branch::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        // Users / Salespersons
        $users = User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        // Sources and Campaigns
        $sources = LeadSource::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $campaigns = LeadCampaign::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return [
            'industries' => $mergedIndustries,
            'states' => $mergedStates,
            'cities' => $citiesFromDb,
            'branches' => $branches,
            'users' => $users,
            'sources' => $sources,
            'campaigns' => $campaigns,
            'categories' => Lead::CATEGORIES,
            'statuses' => ['new', 'contacted', 'follow_up', 'qualified', 'unqualified', 'converted', 'lost'],
            'priorities' => ['low', 'normal', 'high', 'urgent'],
            'purchase_statuses' => [
                'has_purchased' => 'Has Purchased (Linked Party with Orders)',
                'never_purchased' => 'Never Purchased (Linked Party with 0 Orders)',
                'unavailable' => 'Purchase History Unavailable (Not Linked to Party)',
                'repeat_customer' => 'Repeat Customers (≥ 2 Orders)',
                'no_purchase_in_days' => 'No Purchase in last X days',
            ],
            'engagement_levels' => [
                'high' => 'Highly Engaged (≥3 interactions or active within 14 days)',
                'medium' => 'Moderately Engaged (active within 30 days)',
                'low' => 'Low Engagement (inactive > 30 days)',
                'no_recent_activity' => 'No Recent Activity (no activity in last X days)',
                'unknown' => 'No Activity Recorded',
            ],
            'channels' => ['call', 'whatsapp', 'email', 'meeting', 'note'],
        ];
    }
}

