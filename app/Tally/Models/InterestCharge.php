<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterestCharge extends AccountingModel
{
    /**
     * One posted interest amount for a bill on an as-on date.
     * A second post for the same bill and date is refused.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'bill_id',
        'ledger_id',
        'voucher_id',
        'as_on',
        'kind',
        'amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'as_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}