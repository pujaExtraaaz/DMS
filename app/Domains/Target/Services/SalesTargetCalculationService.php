<?php

namespace App\Domains\Target\Services;

use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\CreditNoteItem;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Target\Models\SalesTarget;
use App\Domains\Target\Models\SalesTargetAchievement;
use App\Domains\Target\Models\SalesTargetAlert;
use App\Domains\Target\Models\SalesTargetAudit;
use App\Domains\Target\Models\SalesTargetItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesTargetCalculationService
{
    /**
     * Calculate achievement for a specific sales target.
     */
    public function calculateTarget(SalesTarget $target): SalesTargetAchievement
    {
        return DB::transaction(function () use ($target) {
            $startDate = $target->starts_on->toDateString();
            $endDate = $target->ends_on->toDateString();

            // 1. Identify relevant salesperson user IDs based on owner type
            $salespersonIds = $this->resolveSalespersonIds($target);

            // 2. Base Invoice Query
            $invoiceQuery = Invoice::query()
                ->whereNotIn('status', ['cancelled', 'draft', 'void'])
                ->whereBetween('invoice_date', [$startDate, $endDate]);

            if ($target->owner_type === 'party' || $target->customer_id) {
                $invoiceQuery->where('customer_id', $target->customer_id);
            } else {
                if ($salespersonIds->isNotEmpty()) {
                    $invoiceQuery->whereIn('salesperson_id', $salespersonIds);
                }

                // Also check branch match if branch-owned and no specific users
                if ($target->owner_type === 'branch' && $target->branch_id && $salespersonIds->isEmpty()) {
                    $invoiceQuery->whereHas('customer', function ($q) use ($target) {
                        $q->where('branch_id', $target->branch_id);
                    });
                }
            }

            $matchedInvoices = (clone $invoiceQuery)->with(['items.product'])->get();
            $invoiceIds = $matchedInvoices->pluck('id')->all();

            // 3. Credit Notes Query (Posted credit notes for these invoices or salesperson or customer)
            $creditNoteQuery = CreditNote::query()
                ->where('status', 'posted')
                ->whereBetween('credit_note_date', [$startDate, $endDate]);

            if ($target->owner_type === 'party' || $target->customer_id) {
                $creditNoteQuery->where('customer_id', $target->customer_id);
            } elseif (!empty($invoiceIds)) {
                $creditNoteQuery->where(function ($q) use ($invoiceIds, $salespersonIds) {
                    $q->whereIn('invoice_id', $invoiceIds);
                    if ($salespersonIds->isNotEmpty()) {
                        $q->orWhereHas('invoice', fn ($iq) => $iq->whereIn('salesperson_id', $salespersonIds));
                    }
                });
            } elseif ($salespersonIds->isNotEmpty()) {
                $creditNoteQuery->whereHas('invoice', fn ($iq) => $iq->whereIn('salesperson_id', $salespersonIds));
            } else {
                $creditNoteQuery->whereRaw('1 = 0');
            }

            $matchedCreditNotes = $creditNoteQuery->with(['items.product'])->get();

            // 4. Calculate Achievement based on criteria
            $targetType = $target->target_type;
            $achievedAmount = 0.0;
            $achievedQuantity = 0.0;
            $invoicesCount = 0;
            $contributingInvoices = [];

            $hasCriteriaLines = $target->items()->count() > 0;

            if ($hasCriteriaLines) {
                // Multi-criteria target calculation
                foreach ($target->items as $item) {
                    $itemResult = $this->calculateCriteriaAchievement($item, $matchedInvoices, $matchedCreditNotes);
                    $item->update([
                        'achieved_amount' => $itemResult['amount'],
                        'achieved_quantity' => $itemResult['quantity'],
                    ]);

                    $achievedAmount += $itemResult['amount'];
                    $achievedQuantity += $itemResult['quantity'];
                }

                // If combined target has overall target amount, use sum of items or overall invoice total
                if ($targetType === 'combined' && (float) $target->target_amount > 0) {
                    $generalInvoiceTotal = (float) $matchedInvoices->sum('grand_total');
                    $generalCreditNoteTotal = (float) $matchedCreditNotes->sum('grand_total');
                    $achievedAmount = max(0, $generalInvoiceTotal - $generalCreditNoteTotal);
                }

                $invoicesCount = $matchedInvoices->count();
            } else {
                // Single criteria or general value target
                $result = $this->calculateSingleCriteriaAchievement($target, $matchedInvoices, $matchedCreditNotes);
                $achievedAmount = $result['amount'];
                $achievedQuantity = $result['quantity'];
                $invoicesCount = $result['invoices_count'];
                $contributingInvoices = $result['invoice_ids'];
            }

            // Decimal safe rounding
            $achievedAmount = round(max(0, $achievedAmount), 2);
            $achievedQuantity = round(max(0, $achievedQuantity), 4);

            $targetAmount = (float) $target->target_amount;
            $targetQuantity = (float) $target->target_quantity;

            $pendingAmount = max(0, $targetAmount - $achievedAmount);
            $pendingQuantity = max(0, $targetQuantity - $achievedQuantity);

            $percent = $targetAmount > 0
                ? round(($achievedAmount / $targetAmount) * 100, 2)
                : 0.0;

            $qtyPercent = $targetQuantity > 0
                ? round(($achievedQuantity / $targetQuantity) * 100, 2)
                : 0.0;

            $isAchieved = false;
            if ($targetAmount > 0 && $targetQuantity > 0) {
                $isAchieved = ($achievedAmount >= $targetAmount) && ($achievedQuantity >= $targetQuantity);
            } elseif ($targetAmount > 0) {
                $isAchieved = ($achievedAmount >= $targetAmount);
            } elseif ($targetQuantity > 0) {
                $isAchieved = ($achievedQuantity >= $targetQuantity);
            }

            // 5. Update Cached Achievement Record
            $achievement = SalesTargetAchievement::updateOrCreate(
                ['sales_target_id' => $target->id],
                [
                    'achieved_amount' => $achievedAmount,
                    'achieved_quantity' => $achievedQuantity,
                    'pending_amount' => $pendingAmount,
                    'pending_quantity' => $pendingQuantity,
                    'achievement_percent' => $percent,
                    'quantity_achievement_percent' => $qtyPercent,
                    'is_achieved' => $isAchieved,
                    'invoices_count' => $invoicesCount,
                    'last_calculated_at' => now(),
                    'breakdown_snapshot' => [
                        'invoice_ids' => array_values(array_unique($invoiceIds)),
                        'calculated_at' => now()->toIso8601String(),
                    ],
                ]
            );

            // 6. Check & Trigger Threshold Alerts
            $this->checkThresholdAlerts($target, $achievement);

            return $achievement;
        });
    }

    /**
     * Resolve all relevant salesperson user IDs for a target owner.
     */
    public function resolveSalespersonIds(SalesTarget $target): Collection
    {
        if ($target->owner_type === 'individual') {
            return $target->salesperson_id ? collect([(int) $target->salesperson_id]) : collect();
        }

        if ($target->owner_type === 'team') {
            if ($target->sales_team_id) {
                $team = $target->salesTeam ?? \App\Domains\Target\Models\SalesTeam::with('members')->find($target->sales_team_id);
                if ($team) {
                    $ids = $team->members->pluck('id')->all();
                    if ($team->leader_id) {
                        $ids[] = (int) $team->leader_id;
                    }
                    return collect(array_values(array_unique(array_filter($ids))));
                }
            }
            return collect();
        }

        if ($target->owner_type === 'branch') {
            if ($target->branch_id) {
                // Salespeople assigned to this branch
                $userIds = User::where('branch_id', $target->branch_id)->pluck('id')->all();
                return collect($userIds);
            }
            return collect();
        }

        return collect();
    }

    /**
     * Calculate achievement for a single target or general target without line items.
     */
    protected function calculateSingleCriteriaAchievement(SalesTarget $target, Collection $invoices, Collection $creditNotes): array
    {
        $targetType = $target->target_type;
        $matchedInvoiceIds = [];

        // Case A: Pure Value Target (no specific brand/product restriction)
        if ($targetType === 'value' && !$target->brand_id && !$target->product_id && !$target->sku && !$target->category_id && !$target->sub_category_id) {
            $invTotal = (float) $invoices->sum('grand_total');
            $cnTotal = (float) $creditNotes->sum('grand_total');
            return [
                'amount' => max(0, $invTotal - $cnTotal),
                'quantity' => 0.0,
                'invoices_count' => $invoices->count(),
                'invoice_ids' => $invoices->pluck('id')->all(),
            ];
        }

        // Case A2: Pure Quantity Target (no specific brand/product restriction)
        if ($targetType === 'quantity' && !$target->brand_id && !$target->product_id && !$target->sku && !$target->category_id && !$target->sub_category_id) {
            $totalQty = 0.0;
            $invTotal = (float) $invoices->sum('grand_total');
            $cnTotal = (float) $creditNotes->sum('grand_total');
            foreach ($invoices as $inv) {
                foreach ($inv->items as $item) {
                    $totalQty += (float) ($item->quantity ?? 0);
                }
            }
            foreach ($creditNotes as $cn) {
                foreach ($cn->items as $cnItem) {
                    $totalQty -= (float) ($cnItem->quantity ?? 0);
                }
            }
            return [
                'amount' => max(0, $invTotal - $cnTotal),
                'quantity' => max(0, $totalQty),
                'invoices_count' => $invoices->count(),
                'invoice_ids' => $invoices->pluck('id')->all(),
            ];
        }

        // Case B: Filter by Brand, Product, SKU, Category, or Subcategory
        $totalAmount = 0.0;
        $totalQuantity = 0.0;

        foreach ($invoices as $inv) {
            $invoiceMatched = false;
            foreach ($inv->items as $item) {
                if ($this->matchesCriteria($item, $target)) {
                    $totalAmount += (float) $item->line_total;
                    $totalQuantity += (float) $item->quantity;
                    $invoiceMatched = true;
                }
            }
            if ($invoiceMatched) {
                $matchedInvoiceIds[] = $inv->id;
            }
        }

        // Subtract matching credit note items
        foreach ($creditNotes as $cn) {
            foreach ($cn->items as $cnItem) {
                if ($this->matchesCriteria($cnItem, $target)) {
                    $totalAmount -= (float) $cnItem->line_total;
                    $totalQuantity -= (float) $cnItem->quantity;
                }
            }
        }

        return [
            'amount' => max(0, $totalAmount),
            'quantity' => max(0, $totalQuantity),
            'invoices_count' => count(array_unique($matchedInvoiceIds)),
            'invoice_ids' => array_values(array_unique($matchedInvoiceIds)),
        ];
    }

    /**
     * Calculate achievement for a specific multi-criteria line item.
     */
    protected function calculateCriteriaAchievement(SalesTargetItem $criteriaItem, Collection $invoices, Collection $creditNotes): array
    {
        $totalAmount = 0.0;
        $totalQuantity = 0.0;

        foreach ($invoices as $inv) {
            foreach ($inv->items as $item) {
                if ($this->matchesCriteriaItem($item, $criteriaItem)) {
                    $totalAmount += (float) $item->line_total;
                    $totalQuantity += (float) $item->quantity;
                }
            }
        }

        foreach ($creditNotes as $cn) {
            foreach ($cn->items as $cnItem) {
                if ($this->matchesCriteriaItem($cnItem, $criteriaItem)) {
                    $totalAmount -= (float) $cnItem->line_total;
                    $totalQuantity -= (float) $cnItem->quantity;
                }
            }
        }

        return [
            'amount' => max(0, $totalAmount),
            'quantity' => max(0, $totalQuantity),
        ];
    }

    /**
     * Check if an InvoiceItem or CreditNoteItem matches a SalesTarget criteria.
     */
    protected function matchesCriteria($item, SalesTarget $target): bool
    {
        $product = $item->product;
        if (!$product) return false;

        if ($target->product_id && (int) $item->product_id !== (int) $target->product_id) {
            return false;
        }

        if ($target->brand_id && (int) $product->brand_id !== (int) $target->brand_id) {
            return false;
        }

        if ($target->sku && strcasecmp(trim($product->sku ?? ''), trim($target->sku)) !== 0) {
            return false;
        }

        if ($target->category_id && (int) $product->category_id !== (int) $target->category_id) {
            return false;
        }

        if ($target->sub_category_id && (int) $product->sub_category_id !== (int) $target->sub_category_id) {
            return false;
        }

        return true;
    }

    /**
     * Check if an InvoiceItem matches a SalesTargetItem criteria.
     */
    protected function matchesCriteriaItem($item, SalesTargetItem $criteriaItem): bool
    {
        $product = $item->product;
        if (!$product) return false;

        if ($criteriaItem->product_id && (int) $item->product_id !== (int) $criteriaItem->product_id) {
            return false;
        }

        if ($criteriaItem->brand_id && (int) $product->brand_id !== (int) $criteriaItem->brand_id) {
            return false;
        }

        if ($criteriaItem->sku && strcasecmp(trim($product->sku ?? ''), trim($criteriaItem->sku)) !== 0) {
            return false;
        }

        if ($criteriaItem->category_id && (int) $product->category_id !== (int) $criteriaItem->category_id) {
            return false;
        }

        if ($criteriaItem->sub_category_id && (int) $product->sub_category_id !== (int) $criteriaItem->sub_category_id) {
            return false;
        }

        return true;
    }

    /**
     * Check achievement percentages and trigger threshold & underperformance alerts.
     */
    public function checkThresholdAlerts(SalesTarget $target, SalesTargetAchievement $achievement): void
    {
        $percent = (float) $achievement->achievement_percent;
        $targetName = $target->name;
        $ownerName = $target->owner_name;

        // Threshold checks (50%, 75%, 90%, 100%)
        $thresholds = [
            50 => 'threshold_50',
            75 => 'threshold_75',
            90 => 'threshold_90',
            100 => 'threshold_100',
        ];

        foreach ($thresholds as $threshold => $alertType) {
            if ($percent >= $threshold) {
                $alreadyAlerted = SalesTargetAlert::where('sales_target_id', $target->id)
                    ->where('alert_type', $alertType)
                    ->exists();

                if (!$alreadyAlerted) {
                    SalesTargetAlert::create([
                        'sales_target_id' => $target->id,
                        'user_id' => $target->salesperson_id,
                        'alert_type' => $alertType,
                        'threshold_percent' => $threshold,
                        'title' => "Target Threshold Reached: {$threshold}%",
                        'message' => "Target '{$targetName}' for {$ownerName} has reached {$percent}% achievement (₹ " . number_format($achievement->achieved_amount, 2) . ").",
                    ]);
                }
            }
        }

        // Underperformance pacing alert:
        // If elapsed days / total days > achievement percent / 100 + 0.20 (20% behind expected pace)
        $now = Carbon::now();
        if ($now->greaterThanOrEqualTo($target->starts_on) && $now->lessThanOrEqualTo($target->ends_on)) {
            $totalDays = max(1, $target->starts_on->diffInDays($target->ends_on));
            $elapsedDays = $target->starts_on->diffInDays($now);
            $elapsedFraction = $elapsedDays / $totalDays;
            $achievedFraction = $percent / 100;

            // Only trigger if elapsed is at least 7 days and at least 30% into the period
            if ($elapsedDays >= 7 && $elapsedFraction >= 0.30 && ($elapsedFraction - $achievedFraction) >= 0.20) {
                // Ensure not repeated within last 7 days
                $recentUnderperfAlert = SalesTargetAlert::where('sales_target_id', $target->id)
                    ->where('alert_type', 'underperformance')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->exists();

                if (!$recentUnderperfAlert) {
                    $expectedPercent = round($elapsedFraction * 100, 1);
                    SalesTargetAlert::create([
                        'sales_target_id' => $target->id,
                        'user_id' => $target->salesperson_id,
                        'alert_type' => 'underperformance',
                        'threshold_percent' => $percent,
                        'title' => 'Underperformance Warning: Behind Expected Pace',
                        'message' => "Target '{$targetName}' for {$ownerName} is at {$percent}%, whereas expected progress for elapsed time is {$expectedPercent}%.",
                    ]);
                }
            }
        }
    }

    /**
     * Recalculate all active targets affected by an invoice.
     */
    public function recalculateForInvoice(Invoice $invoice): void
    {
        $invoiceDate = $invoice->invoice_date?->toDateString() ?? now()->toDateString();
        $salespersonId = $invoice->salesperson_id;
        $customerId = $invoice->customer_id;

        $targets = SalesTarget::query()
            ->where('status', 'active')
            ->where('starts_on', '<=', $invoiceDate)
            ->where('ends_on', '>=', $invoiceDate)
            ->get();

        foreach ($targets as $target) {
            if ($target->owner_type === 'party') {
                if ($customerId && (int) $target->customer_id === (int) $customerId) {
                    $this->calculateTarget($target);
                }
                continue;
            }

            $salespersonIds = $this->resolveSalespersonIds($target);
            if ($salespersonIds->isEmpty() || $salespersonIds->contains($salespersonId)) {
                $this->calculateTarget($target);
            }
        }
    }

    /**
     * Recalculate all active targets affected by a credit note.
     */
    public function recalculateForCreditNote(CreditNote $creditNote): void
    {
        $cnDate = $creditNote->credit_note_date?->toDateString() ?? now()->toDateString();
        $customerId = $creditNote->customer_id;
        $invoice = $creditNote->invoice;

        if ($invoice) {
            $this->recalculateForInvoice($invoice);
            return;
        }

        $targets = SalesTarget::query()
            ->where('status', 'active')
            ->where('starts_on', '<=', $cnDate)
            ->where('ends_on', '>=', $cnDate)
            ->get();

        foreach ($targets as $target) {
            if ($target->owner_type === 'party') {
                if ($customerId && (int) $target->customer_id === (int) $customerId) {
                    $this->calculateTarget($target);
                }
                continue;
            }

            $salespersonIds = $this->resolveSalespersonIds($target);
            if ($salespersonIds->isEmpty()) {
                $this->calculateTarget($target);
            }
        }
    }

    /**
     * Recalculate party targets for a specific customer and date.
     */
    public function recalculateForCustomerIdAndDate(?int $customerId, $date): void
    {
        if (! $customerId || ! $date) {
            return;
        }

        $dateStr = $date instanceof \Carbon\CarbonInterface ? $date->toDateString() : (string) $date;

        $targets = SalesTarget::query()
            ->whereIn('status', ['active', 'completed'])
            ->where('owner_type', 'party')
            ->where('customer_id', $customerId)
            ->where('starts_on', '<=', $dateStr)
            ->where('ends_on', '>=', $dateStr)
            ->get();

        foreach ($targets as $target) {
            $this->calculateTarget($target);
        }
    }

    /**
     * Recalculate all targets across the system.
     */
    public function recalculateAll(bool $onlyActive = true): int
    {
        $query = SalesTarget::query();
        if ($onlyActive) {
            $query->whereIn('status', ['active', 'pending_approval']);
        }

        $targets = $query->get();
        $count = 0;

        foreach ($targets as $target) {
            $this->calculateTarget($target);
            $count++;
        }

        return $count;
    }
}

