<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransactionLine extends AccountingModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'stock_transaction_id',
        'line_number',
        'product_id',
        'godown_id',
        'quantity',
        'rate',
        'value',
        'batch_number',
        'manufactured_on',
        'expires_on',
        'serial_number',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'decimal:4',
            'rate' => 'decimal:2',
            'value' => 'decimal:2',
            'manufactured_on' => 'date',
            'expires_on' => 'date',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class, 'stock_transaction_id');
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
