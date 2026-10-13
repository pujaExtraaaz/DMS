<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollProvision extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'kind',
        'period',
        'amount',
        'expense_ledger_id',
        'liability_ledger_id',
        'voucher_id',
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

    public function expenseLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'expense_ledger_id');
    }

    public function liabilityLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'liability_ledger_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
