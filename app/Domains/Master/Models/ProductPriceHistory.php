<?php

namespace App\Domains\Master\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    protected $fillable = [
        'product_id',
        'uom_id',
        'trade_price',
        'selling_price',
        'purchase_price',
        'mrp',
        'effective_from',
        'effective_to',
        'change_reason',
        'source',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'trade_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'mrp' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
