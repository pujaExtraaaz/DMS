<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'category_id',
        'asset_code',
        'name',
        'acquisition_date',
        'capitalization_date',
        'gross_value',
        'residual_value',
        'accumulated_depreciation',
        'book_value',
        'location',
        'department',
        'custodian',
        'is_cip',
        'status',
        'disposal_date',
        'disposal_proceeds',
        'disposal_gain_loss',
        'parent_asset_id',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'capitalization_date' => 'date',
            'disposal_date' => 'date',
            'gross_value' => 'decimal:2',
            'residual_value' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
            'book_value' => 'decimal:2',
            'disposal_proceeds' => 'decimal:2',
            'disposal_gain_loss' => 'decimal:2',
            'is_cip' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FixedAssetCategory::class, 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_asset_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_asset_id');
    }

    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(DepreciationEntry::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(AssetVerification::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ! $this->is_cip;
    }

    public function isDisposed(): bool
    {
        return $this->status === 'disposed';
    }
}
