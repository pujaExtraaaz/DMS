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
     * Get target progress status
     */
    public function getProgressStatusAttribute(): string
    {
        $achievement = $this->achievement;
        $isAchieved = $achievement?->is_achieved ?? false;
        if ($isAchieved) {
            return 'Achieved';
        }

        if ($this->starts_on && $this->starts_on->isFuture()) {
            return 'Not Started';
        }

        if ($this->ends_on && $this->ends_on->isPast()) {
            return 'Overdue';
        }

        return 'In Progress';
    }
}

