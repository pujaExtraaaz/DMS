<?php

namespace App\Domains\Hrms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollInput extends Model
{
    protected $fillable = [
        'employee_id', 'year', 'month', 'basic', 'allowances', 'deductions',
        'incentives', 'advances', 'net_payable', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'basic' => 'decimal:2', 'allowances' => 'decimal:2', 'deductions' => 'decimal:2',
            'incentives' => 'decimal:2', 'advances' => 'decimal:2', 'net_payable' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
