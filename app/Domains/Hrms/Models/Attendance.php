<?php

namespace App\Domains\Hrms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id', 'shift_id', 'attendance_date', 'check_in', 'check_out',
        'status', 'hours_worked', 'late_minutes', 'early_minutes', 'ot_minutes',
        'source', 'is_regularised', 'notes', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'hours_worked' => 'decimal:2',
            'is_regularised' => 'boolean',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function shift(): BelongsTo { return $this->belongsTo(Shift::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
