<?php

namespace App\Domains\Hrms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseClaim extends Model
{
    protected $fillable = [
        'employee_id', 'claim_date', 'claim_type', 'amount', 'description', 'receipt_path',
        'status', 'approved_by', 'approved_at', 'approval_notes',
        'settlement_amount', 'settlement_reference', 'settlement_notes', 'settled_by', 'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'amount' => 'decimal:2',
            'settlement_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function settler(): BelongsTo { return $this->belongsTo(User::class, 'settled_by'); }
}
