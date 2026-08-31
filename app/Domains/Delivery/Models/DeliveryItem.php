<?php

namespace App\Domains\Delivery\Models;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryItem extends Model
{
    protected $fillable = [
        'delivery_id',
        'product_id',
        'uom_id',
        'loaded_qty',
        'delivered_qty',
        'short_qty',
        'returned_qty',
    ];

    protected function casts(): array
    {
        return [
            'loaded_qty' => 'decimal:4',
            'delivered_qty' => 'decimal:4',
            'short_qty' => 'decimal:4',
            'returned_qty' => 'decimal:4',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
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
