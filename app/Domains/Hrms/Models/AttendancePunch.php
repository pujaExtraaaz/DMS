<?php

namespace App\Domains\Hrms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePunch extends Model
{
    protected $fillable = [
        'employee_id', 'biometric_code', 'punched_at', 'direction',
        'source', 'device_id', 'external_id',
    ];

    protected function casts(): array
    {
        return ['punched_at' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
