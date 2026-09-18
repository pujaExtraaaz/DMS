<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandedCostItem extends Model
{
    protected $fillable = [
        'landed_cost_id',
        'product_id',
        'uom_id',
        'quantity',
        'base_value',
        'weight',
        'volume',
        'allocated_cost',
        'landed_unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'base_value' => 'decimal:2',
            'weight' => 'decimal:4',
            'volume' => 'decimal:4',
            'allocated_cost' => 'decimal:2',
            'landed_unit_cost' => 'decimal:4',
        ];
    }

    public function landedCost(): BelongsTo
    {
        return $this->belongsTo(LandedCost::class);
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
