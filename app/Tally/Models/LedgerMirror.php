<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerMirror extends AccountingModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'source_company_id',
        'source_ledger_id',
        'counterparty_company_id',
        'counterparty_ledger_id',
    ];

    public function sourceCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'source_company_id');
    }

    public function sourceLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'source_ledger_id');
    }

    public function counterpartyCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'counterparty_company_id');
    }

    public function counterpartyLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'counterparty_ledger_id');
    }
}
