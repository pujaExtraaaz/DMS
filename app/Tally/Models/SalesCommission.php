<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesCommission extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'invoice_id',
        'party_id',
        'salesperson_name',
        'base_amount',
        'percent',
        'commission_amount',
        'accrual_voucher_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'percent' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
