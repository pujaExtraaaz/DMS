<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepositSlip extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'financial_year_id',
        'bank_account_id',
        'slip_number',
        'slip_date',
        'amount',
        'narration',
    ];

    protected function casts(): array
    {
        return [
            'slip_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DepositSlipLine::class);
    }
}
