<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsInTransit extends AccountingModel
{
    protected $table = 'acct_goods_in_transit';

    protected $fillable = [
        'company_id',
        'from_godown_id',
        'to_godown_id',
        'product_id',
        'quantity',
        'dispatch_date',
        'expected_receipt_date',
        'received_date',
        'status',
        'reference',
        'is_consignment',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'dispatch_date' => 'date',
            'expected_receipt_date' => 'date',
            'received_date' => 'date',
            'is_consignment' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fromGodown(): BelongsTo
    {
        return $this->belongsTo(Godown::class, 'from_godown_id');
    }

    public function toGodown(): BelongsTo
    {
        return $this->belongsTo(Godown::class, 'to_godown_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'in_transit';
    }
}
