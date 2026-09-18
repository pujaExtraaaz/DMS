<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Master\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockCostConsumption extends Model
{
    protected $fillable = [
        'stock_cost_layer_id',
        'stock_movement_id',
        'product_id',
        'quantity',
        'unit_cost',
        'reference_type',
        'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function layer(): BelongsTo
    {
        return $this->belongsTo(StockCostLayer::class, 'stock_cost_layer_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
