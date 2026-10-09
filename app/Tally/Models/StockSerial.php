<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockSerial extends AccountingModel
{
    protected $fillable = ['company_id', 'product_id', 'serial_number', 'status'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}
