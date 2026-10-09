<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankInstrument extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'financial_year_id',
        'bank_account_id',
        'voucher_id',
        'party_ledger_id',
        'number',
        'instrument_date',
        'amount',
        'favouring',
        'status',
        'narration',
    ];

    protected function casts(): array
    {
        return [
            'instrument_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'party_ledger_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
