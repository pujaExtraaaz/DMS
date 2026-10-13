<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptLine extends AccountingModel
{
    protected $fillable = [
        'goods_receipt_id',
        'line_number',
        'product_id',
        'item_name',
        'ordered_qty',
        'received_qty',
        'rate',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'ordered_qty' => 'decimal:4',
            'received_qty' => 'decimal:4',
            'rate' => 'decimal:4',
            'line_total' => 'decimal:2',
        ];
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
