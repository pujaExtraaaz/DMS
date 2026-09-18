<?php

namespace App\Domains\Hrms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeKpi extends Model
{
    protected $fillable = [
        'employee_id', 'kpi_name', 'period_label', 'target_value',
        'achievement_value', 'rating', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'achievement_value' => 'decimal:2',
            'rating' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
