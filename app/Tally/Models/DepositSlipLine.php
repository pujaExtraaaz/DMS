<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositSlipLine extends AccountingModel
{
    protected $fillable = [
        'deposit_slip_id',
        'bank_instrument_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(BankInstrument::class, 'bank_instrument_id');
    }
}
