<?php

namespace App\Domains\Settlement\Models;

use App\Domains\Master\Models\Customer;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementLine extends Model
{
    protected $fillable = [
        'settlement_id',
        'customer_id',
        'invoice_id',
        'cash_amount',
        'upi_amount',
        'outstanding_amount',
    ];

    protected function casts(): array
    {
        return [
            'cash_amount' => 'decimal:2',
            'upi_amount' => 'decimal:2',
            'outstanding_amount' => 'decimal:2',
        ];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
