<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'loan_number',
        'lender_name',
        'loan_type',
        'is_long_term',
        'disbursement_date',
        'principal',
        'interest_rate',
        'tenure_months',
        'emi_amount',
        'outstanding_principal',
        'loan_ledger_id',
        'interest_ledger_id',
        'bank_account_id',
        'overdraft_limit',
        'disbursement_voucher_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'disbursement_date' => 'date',
            'principal' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'emi_amount' => 'decimal:2',
            'outstanding_principal' => 'decimal:2',
            'overdraft_limit' => 'decimal:2',
            'is_long_term' => 'boolean',
            'tenure_months' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(LoanSchedule::class)->orderBy('installment_no');
    }

    public function loanLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'loan_ledger_id');
    }

    public function interestLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'interest_ledger_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function disbursementVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'disbursement_voucher_id');
    }
}
