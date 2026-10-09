<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCentre extends AccountingModel
{
    protected $fillable = ['company_id', 'cost_category_id', 'name', 'code', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CostCategory::class, 'cost_category_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(VoucherEntry::class);
    }

    public function canBeDeleted(): bool
    {
        return ! $this->entries()->exists() && ! $this->budgets()->exists();
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }
}
