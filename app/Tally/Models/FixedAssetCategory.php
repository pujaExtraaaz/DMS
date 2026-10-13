<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAssetCategory extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'name',
        'depreciation_method',
        'useful_life_years',
        'residual_percent',
        'asset_ledger_id',
        'accum_depr_ledger_id',
        'depr_expense_ledger_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'useful_life_years' => 'decimal:2',
            'residual_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(FixedAsset::class, 'category_id');
    }

    public function assetLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'asset_ledger_id');
    }

    public function accumDeprLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'accum_depr_ledger_id');
    }

    public function deprExpenseLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'depr_expense_ledger_id');
    }

    public function lifeMonths(): int
    {
        return max(1, (int) round((float) $this->useful_life_years * 12));
    }

    public function isStraightLine(): bool
    {
        return ($this->depreciation_method ?? 'straight_line') === 'straight_line';
    }

    public function isWdv(): bool
    {
        return ($this->depreciation_method ?? '') === 'wdv';
    }
}
