<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContingentLiability extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'kind',
        'description',
        'amount',
        'as_of_date',
        'counterparty',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'as_of_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
