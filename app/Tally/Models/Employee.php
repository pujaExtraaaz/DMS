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
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'monthly_earnings' => 'decimal:2',
            'monthly_deductions' => 'decimal:2',
        ];
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function payrollLines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }
}
