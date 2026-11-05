<?php

namespace App\Domains\Target\Models;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesTarget extends Model
{
    use HasUppercaseNames;

    protected $table = 'sales_targets';

    protected $fillable = [
        'name',
        'target_type',
        'owner_type',
        'customer_id',
        'salesperson_id',
        'sales_team_id',
        'branch_id',
        'company_id',
        'financial_year_id',
        'period_type',
        'starts_on',
        'ends_on',
        'target_amount',
        'target_quantity',
        'brand_id',
        'product_id',
        'sku',
        'category_id',
        'sub_category_id',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'revision_number',
        'revised_from_id',
        'revision_reason',
        'carried_forward_from_id',
        'carried_forward_amount',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'approved_at' => 'datetime',
            'target_amount' => 'decimal:2',
            'target_quantity' => 'decimal:4',
            'carried_forward_amount' => 'decimal:2',
            'revision_number' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Master\Models\Customer::class, 'customer_id');
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function salesTeam(): BelongsTo
    {
        return $this->belongsTo(SalesTeam::class, 'sales_team_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id')->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function revisedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'revised_from_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'revised_from_id');
    }

    public function carriedForwardFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'carried_forward_from_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesTargetItem::class, 'sales_target_id');
    }

    public function achievement(): HasOne
    {
        return $this->hasOne(SalesTargetAchievement::class, 'sales_target_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(SalesTargetAudit::class, 'sales_target_id')->latest();
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(SalesTargetAlert::class, 'sales_target_id')->latest();
    }

    /**
     * Get owner display name
     */
    public function getOwnerNameAttribute(): string
    {
        return match ($this->owner_type) {
            'team' => $this->salesTeam?->name ?? 'Team',
            'branch' => $this->branch?->name ?? 'Branch',
            'party' => $this->customer?->name ?? 'Party',
            default => $this->salesperson?->name ?? 'Individual Salesman',
        };
    }

    /**
     * Get calculated target progress status based on priority rules:
     * 1. Achieved (if achieved >= target)
     * 2. Period Expired (if end date has passed and not achieved)
     * 3. Not Started (if start date is in future)
     * 4. In Progress (otherwise)
     */
    public function getProgressStatusAttribute(): string
    {
        $achievement = $this->achievement;
        $achievedAmount = (float) ($achievement?->achieved_amount ?? 0);
        $targetAmount = (float) $this->target_amount;
        $isAchieved = ($achievement?->is_achieved ?? false) || ($targetAmount > 0 && $achievedAmount >= $targetAmount);

        // Priority 1: Achieved (including when achievement happens before end date)
        if ($isAchieved) {
            return 'Achieved';
        }

        $today = \Carbon\Carbon::today();
        $startDate = $this->starts_on ? $this->starts_on->copy()->startOfDay() : null;
        $endDate = $this->ends_on ? $this->ends_on->copy()->startOfDay() : null;

        // Priority 2: Period Expired
        if ($endDate && $today->greaterThan($endDate)) {
            return 'Period Expired';
        }

        // Priority 3: Not Started
        if ($startDate && $today->lessThan($startDate)) {
            return 'Not Started';
        }

        // Priority 4: In Progress
        return 'In Progress';
    }

    /**
     * Get numeric remaining days to end date (or days to start if Not Started, 0 if achieved, null if expired)
     */
    public function getRemainingDaysAttribute(): ?int
    {
        $status = $this->progress_status;

        if ($status === 'Achieved') {
            return 0;
        }

        if ($status === 'Period Expired') {
            return null;
        }

        $today = \Carbon\Carbon::today();

        if ($status === 'Not Started') {
            $startDate = $this->starts_on ? $this->starts_on->copy()->startOfDay() : null;
            return $startDate ? max(0, $today->diffInDays($startDate, false)) : null;
        }

        // In Progress
        $endDate = $this->ends_on ? $this->ends_on->copy()->startOfDay() : null;
        return $endDate ? max(0, $today->diffInDays($endDate, false)) : null;
    }

    /**
     * Get human-readable remaining days label
     */
    public function getRemainingDaysLabelAttribute(): string
    {
        $status = $this->progress_status;

        if ($status === 'Achieved') {
            return '0 days needed';
        }

        if ($status === 'Period Expired') {
            return 'Period Expired';
        }

        $today = \Carbon\Carbon::today();
        $startDate = $this->starts_on ? $this->starts_on->copy()->startOfDay() : null;
        $endDate = $this->ends_on ? $this->ends_on->copy()->startOfDay() : null;

        if ($status === 'Not Started') {
            if (! $startDate) return 'Not Started';
            $daysToStart = $today->diffInDays($startDate, false);
            return $daysToStart === 1 ? 'Starts in 1 day' : "Starts in {$daysToStart} days";
        }

        // In Progress
        if ($endDate) {
            $daysRemaining = $today->diffInDays($endDate, false);
            if ($daysRemaining <= 0) {
                return 'Ends today';
            }
            return $daysRemaining === 1 ? '1 day remaining' : "{$daysRemaining} days remaining";
        }

        return '—';
    }

    /**
     * Get calculated shortfall amount: MAX(Target Amount - Achieved Target, 0)
     */
    public function getShortfallAmountAttribute(): float
    {
        $achievement = $this->achievement;
        $achieved = $achievement ? (float) $achievement->achieved_amount : 0.0;
        return max(0.0, (float) $this->target_amount - $achieved);
    }

    /**
     * Get calculated achievement percentage
     */
    public function getCalculatedAchievementPercentAttribute(): float
    {
        $targetAmt = (float) $this->target_amount;
        if ($targetAmt <= 0) {
            return 0.0;
        }
        $achievement = $this->achievement;
        $achieved = $achievement ? (float) $achievement->achieved_amount : 0.0;
        return round(($achieved / $targetAmt) * 100, 2);
    }

    /**
     * Query scope to filter targets by calculated progress status
     */
    public function scopeProgressStatus($query, string $status)
    {
        $today = \Carbon\Carbon::today()->toDateString();
        return match ($status) {
            'Achieved' => $query->whereHas('achievement', function ($q) {
                $q->where('is_achieved', true)
                  ->orWhereRaw('sales_target_achievements.achieved_amount >= sales_targets.target_amount');
            }),
            'Period Expired' => $query->where('ends_on', '<', $today)
                ->where(function ($q) {
                    $q->whereDoesntHave('achievement')
                      ->orWhereHas('achievement', function ($sq) {
                          $sq->where('is_achieved', false)
                             ->whereRaw('sales_target_achievements.achieved_amount < sales_targets.target_amount');
                      });
                }),
            'Not Started' => $query->where('starts_on', '>', $today)
                ->where(function ($q) {
                    $q->whereDoesntHave('achievement')
                      ->orWhereHas('achievement', function ($sq) {
                          $sq->where('is_achieved', false)
                             ->whereRaw('sales_target_achievements.achieved_amount < sales_targets.target_amount');
                      });
                }),
            'In Progress' => $query->where('starts_on', '<=', $today)
                ->where('ends_on', '>=', $today)
                ->where(function ($q) {
                    $q->whereDoesntHave('achievement')
                      ->orWhereHas('achievement', function ($sq) {
                          $sq->where('is_achieved', false)
                             ->whereRaw('sales_target_achievements.achieved_amount < sales_targets.target_amount');
                      });
                }),
            default => $query,
        };
    }
}

