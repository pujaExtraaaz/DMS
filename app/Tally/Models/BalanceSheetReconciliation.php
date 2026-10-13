<?php

namespace Tally\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceSheetReconciliation extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'ledger_id',
        'as_of_date',
        'gl_balance',
        'substantiated_balance',
        'difference',
        'status',
        'evidence_note',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'as_of_date' => 'date',
            'gl_balance' => 'decimal:2',
            'substantiated_balance' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
