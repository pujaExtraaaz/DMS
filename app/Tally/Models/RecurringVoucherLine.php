<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringVoucherLine extends AccountingModel
{
    protected $fillable = [
        'recurring_voucher_id',
        'ledger_id',
        'line_number',
        'debit',
        'credit',
        'cost_centre_id',
        'business_unit_id',
        'narration',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    public function recurringVoucher(): BelongsTo
    {
        return $this->belongsTo(RecurringVoucher::class);
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function costCentre(): BelongsTo
    {
        return $this->belongsTo(CostCentre::class);
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }
}
