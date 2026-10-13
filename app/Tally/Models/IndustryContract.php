<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryContract extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'industry',
        'contract_number',
        'name',
        'party_ledger_id',
        'start_date',
        'end_date',
        'contract_value',
        'retention_percent',
        'recognized_revenue',
        'billing_frequency',
        'status',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'contract_value' => 'decimal:2',
            'retention_percent' => 'decimal:2',
            'recognized_revenue' => 'decimal:2',
            'meta_json' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function partyLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'party_ledger_id');
    }
}
