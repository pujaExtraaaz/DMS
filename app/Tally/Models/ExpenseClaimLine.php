<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseClaimLine extends AccountingModel
{
    protected $fillable = [
        'expense_claim_id',
        'line_number',
        'expense_date',
        'category',
        'expense_ledger_id',
        'description',
        'amount',
        'bill_attached',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'bill_attached' => 'boolean',
        ];
    }

    public function expenseClaim(): BelongsTo
    {
        return $this->belongsTo(ExpenseClaim::class);
    }

    public function expenseLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'expense_ledger_id');
    }
}
