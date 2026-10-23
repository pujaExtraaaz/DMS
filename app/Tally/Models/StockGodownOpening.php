<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockGodownOpening extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'product_id',
        'godown_id',
        'batch_name',
        'quantity',
        'rate',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'rate' => 'decimal:2',
            'value' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function godown(): BelongsTo
    {
        return $this->belongsTo(Godown::class);
    }
}