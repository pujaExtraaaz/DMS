<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrepaidExpense extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'prepaid_ledger_id',
        'expense_ledger_id',
        'start_date',
        'end_date',
        'total_amount',
        'recognized_amount',
        'frequency',
        'status',
        'source_voucher_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_amount' => 'decimal:2',
            'recognized_amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function prepaidLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'prepaid_ledger_id');
    }

    public function expenseLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'expense_ledger_id');
    }

    public function sourceVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'source_voucher_id');
    }

    public function remainingAmount(): string
    {
        return number_format(max(0, (float) $this->total_amount - (float) $this->recognized_amount), 2, '.', '');
    }
}
