<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdsCertificate extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'party_ledger_id',
        'certificate_number',
        'form_type',
        'financial_year',
        'quarter',
        'tds_amount',
        'issue_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tds_amount' => 'decimal:2',
            'issue_date' => 'date',
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
