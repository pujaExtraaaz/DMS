<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollLine extends AccountingModel
{
    protected $fillable = ['payroll_run_id', 'employee_id', 'earnings', 'deductions', 'pf_amount', 'esi_amount', 'employer_pf_amount', 'employer_esi_amount', 'payable_days', 'net'];

    protected function casts(): array
    {
        return [
            'earnings' => 'decimal:2',
            'deductions' => 'decimal:2',
            'net' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }
}
