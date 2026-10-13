<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLoan extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'loan_date',
        'principal',
        'interest_rate',
        'tenure_months',
        'emi_amount',
        'outstanding',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'principal' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'emi_amount' => 'decimal:2',
            'outstanding' => 'decimal:2',
            'tenure_months' => 'integer',
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
}
