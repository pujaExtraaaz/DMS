<?php

namespace App\Domains\Target\Models;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TargetPeriod extends Model
{
    use HasUppercaseNames;

    protected $fillable = [
        'name',
        'period_type',
        'starts_on',
        'ends_on',
        'status',
        'closed_at',
        'target_category',
        'brand_id',
        'product_id',
        'supplier_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'supplier_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(PartyTarget::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(TargetAchievement::class);
    }

    public function getTargetCategoryLabelAttribute(): ?string
    {
        return match ($this->target_category) {
            'brand', 'brands' => 'Brands',
            'product', 'products' => 'Products',
            'supplier', 'suppliers' => 'Suppliers',
            default => null,
        };
    }

    public function getTargetEntityNameAttribute(): ?string
    {
        return match ($this->target_category) {
            'brand', 'brands' => $this->brand?->name,
            'product', 'products' => $this->product?->name,
            'supplier', 'suppliers' => $this->supplier?->name,
            default => null,
        };
    }

    public function getTotalTargetAmountAttribute(): float
    {
        if (array_key_exists('total_target_amount', $this->attributes)) {
            return (float) ($this->attributes['total_target_amount'] ?? 0);
        }
        return (float) ($this->targets()->sum('amount') ?? 0);
    }

    public function getTotalAchievedAmountAttribute(): float
    {
        return (float) ($this->achievements()->sum('achieved_amount') ?? 0);
    }

    public function getOverallAchievementPercentAttribute(): float
    {
        $targetAmt = $this->total_target_amount;
        if ($targetAmt <= 0) {
            return 0.0;
        }
        return round(($this->total_achieved_amount / $targetAmt) * 100, 2);
    }
}