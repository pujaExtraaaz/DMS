<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdsChallan extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'return_period',
        'section',
        'challan_number',
        'bsr_code',
        'deposit_date',
        'amount',
        'interest',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'deposit_date' => 'date',
            'amount' => 'decimal:2',
            'interest' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
