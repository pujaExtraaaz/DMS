<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends AccountingModel
{
    protected $fillable = [
        'company_id', 'branch_id', 'name', 'employee_code', 'designation', 'group_name', 'pan',
        'joining_date', 'ledger_id', 'monthly_earnings', 'monthly_deductions',
        'salary_structure_id', 'basic_salary', 'uan', 'esi_number',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'monthly_earnings' => 'decimal:2',
            'monthly_deductions' => 'decimal:2',
            'basic_salary' => 'decimal:2',
        ];
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function payrollLines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }
}
