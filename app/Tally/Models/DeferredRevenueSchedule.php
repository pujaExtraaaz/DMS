<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeferredRevenueSchedule extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'invoice_id',
        'customer_ledger_id',
        'revenue_ledger_id',
        'deferred_ledger_id',
        'total_amount',
        'recognized_amount',
        'start_date',
        'end_date',
        'frequency',
        'next_recognize_on',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'recognized_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_recognize_on' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function remainingAmount(): string
    {
        return number_format((float) $this->total_amount - (float) $this->recognized_amount, 2, '.', '');
    }
}
