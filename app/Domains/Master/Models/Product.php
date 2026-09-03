<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'hsn_code',
        'description',
        'base_uom_id',
        'tax_rate',
        'selling_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'base_uom_id');
    }

    public function productUoms(): HasMany
    {
        return $this->hasMany(ProductUom::class);
    }

    public function uoms(): BelongsToMany
    {
        return $this->belongsToMany(
            Uom::class,
            'product_uoms'
        )->withPivot([
            'conversion_factor',
            'is_base',
        ]);
    }

    public function priceMasters(): HasMany
    {
        return $this->hasMany(PriceMaster::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }
}