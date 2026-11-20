<?php

namespace App\Domains\Hrms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRunLine extends Model
{
    protected $fillable = [
        'payroll_run_id', 'employee_id', 'payable_days', 'present_days',
        'leave_days', 'lwp_days', 'ot_minutes', 'gross_earnings',
        'total_deductions', 'net_payable', 'earnings_json', 'deductions_json',
    ];

    protected function casts(): array
    {
        return [
            'gross_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_payable' => 'decimal:2',
            'earnings_json' => 'array',
            'deductions_json' => 'array',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
