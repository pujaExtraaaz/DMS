<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUom extends Model
{
    protected $fillable = [
        'product_id',
        'uom_id',
        'conversion_factor',
        'is_base',
        'label',
        'selling_price',
        'trade_price',
        'purchase_price',
        'mrp',
        'is_default_sales',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:4',
            'is_base' => 'boolean',
            'is_default_sales' => 'boolean',
            'is_active' => 'boolean',
            'selling_price' => 'decimal:2',
            'trade_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'mrp' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }
}
