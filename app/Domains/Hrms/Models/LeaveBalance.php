<?php

namespace App\Domains\Hrms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'employee_id', 'leave_type_id', 'year',
        'opening_balance', 'used_balance', 'closing_balance',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'used_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); }
}
