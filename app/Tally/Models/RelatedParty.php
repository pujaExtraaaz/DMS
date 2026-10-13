<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelatedParty extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'party_ledger_id',
        'relationship',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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
