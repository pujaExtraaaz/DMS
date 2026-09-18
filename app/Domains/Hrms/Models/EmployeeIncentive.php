<?php

namespace App\Domains\Hrms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeIncentive extends Model
{
    protected $fillable = [
        'employee_id', 'target_id', 'period_label', 'target_amount',
        'achievement_amount', 'incentive_amount', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'achievement_amount' => 'decimal:2',
            'incentive_amount' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
