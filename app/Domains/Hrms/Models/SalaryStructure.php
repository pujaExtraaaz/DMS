<?php

namespace App\Domains\Hrms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    protected $fillable = [
        'employee_id', 'basic', 'hra', 'allowances', 'deductions',
        'effective_from', 'effective_to', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'basic' => 'decimal:2', 'hra' => 'decimal:2',
            'allowances' => 'decimal:2', 'deductions' => 'decimal:2',
            'effective_from' => 'date', 'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
