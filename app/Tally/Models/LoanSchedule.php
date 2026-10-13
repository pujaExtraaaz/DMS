<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanSchedule extends AccountingModel
{
    protected $fillable = [
        'loan_id',
        'installment_no',
        'due_date',
        'principal_component',
        'interest_component',
        'emi_amount',
        'outstanding_after',
        'is_paid',
        'paid_date',
        'payment_voucher_id',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_date' => 'date',
            'principal_component' => 'decimal:2',
            'interest_component' => 'decimal:2',
            'emi_amount' => 'decimal:2',
            'outstanding_after' => 'decimal:2',
            'is_paid' => 'boolean',
            'installment_no' => 'integer',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'payment_voucher_id');
    }
}
