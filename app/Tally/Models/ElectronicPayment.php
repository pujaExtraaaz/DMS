<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectronicPayment extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'bank_account_id',
        'voucher_id',
        'payment_batch_id',
        'channel',
        'reference',
        'utr',
        'payment_date',
        'amount',
        'beneficiary',
        'beneficiary_account',
        'beneficiary_ifsc',
        'status',
        'cleared_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'cleared_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
