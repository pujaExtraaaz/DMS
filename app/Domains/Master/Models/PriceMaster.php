<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceMaster extends Model
{
    protected $fillable = [
        'customer_type_id',
        'product_id',
        'uom_id',
        'rate',
        'min_qty',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'min_qty' => 'decimal:2',
        ];
    }

    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
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
