<?php

namespace App\Domains\Hrms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeExit extends Model
{
    protected $fillable = [
        'employee_id', 'resignation_date', 'last_working_date', 'notice_period_days',
        'exit_type', 'clearance_status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'resignation_date' => 'date',
            'last_working_date' => 'date',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
