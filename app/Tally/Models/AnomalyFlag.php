<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnomalyFlag extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'flag_type',
        'document_type',
        'document_id',
        'amount',
        'severity',
        'message',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
