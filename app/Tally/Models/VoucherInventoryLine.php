<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherInventoryLine extends AccountingModel
{
    protected $fillable = [
        'voucher_id',
        'line_number',
        'product_id',
        'godown_id',
        'destination_godown_id',
        'batch_name',
        'quantity',
        'rate',
        'amount',
        'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'decimal:4',
            'rate' => 'decimal:4',
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function godown(): BelongsTo
    {
        return $this->belongsTo(Godown::class);
    }

    public function destinationGodown(): BelongsTo
    {
        return $this->belongsTo(Godown::class, 'destination_godown_id');
    }
}