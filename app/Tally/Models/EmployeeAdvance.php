<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAdvance extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'employee_ledger_id',
        'advance_date',
        'amount',
        'settled_amount',
        'purpose',
        'status',
        'payment_voucher_id',
    ];

    protected function casts(): array
    {
        return [
            'advance_date' => 'date',
            'amount' => 'decimal:2',
            'settled_amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function employeeLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'employee_ledger_id');
    }

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'payment_voucher_id');
    }
}
