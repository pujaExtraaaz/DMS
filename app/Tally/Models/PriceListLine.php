<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceListLine extends AccountingModel
{
    protected $fillable = [
        'price_list_id',
        'product_id',
        'rate',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
        ];
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
