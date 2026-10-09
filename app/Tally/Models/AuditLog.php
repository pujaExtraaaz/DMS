<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends AccountingModel
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'action', 'module', 'auditable_type', 'auditable_id',
        'company_id', 'branch_id', 'financial_year_id', 'description',
        'previous_values', 'new_values', 'ip', 'user_agent', 'source',
    ];

    protected function casts(): array
    {
        return [
            'previous_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function entityName(): string
    {
        if (! $this->auditable_type) {
            return '—';
        }

        return class_basename($this->auditable_type).($this->auditable_id ? ' #'.$this->auditable_id : '');
    }
}
