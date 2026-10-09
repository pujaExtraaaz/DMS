<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillOfMaterial extends AccountingModel
{
    protected $table = 'bills_of_materials';

    protected $fillable = ['company_id', 'finished_product_id', 'name', 'wastage_percent', 'is_active'];

    protected function casts(): array
    {
        return [
            'wastage_percent' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function finishedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'finished_product_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BomLine::class);
    }

    public function byproducts(): HasMany
    {
        return $this->hasMany(BomByproduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ManufacturingOrder::class);
    }

    public function canBeDeleted(): bool
    {
        return ! $this->orders()->exists();
    }
}
