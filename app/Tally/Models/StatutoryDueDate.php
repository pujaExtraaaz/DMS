<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatutoryDueDate extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'obligation',
        'category',
        'due_date',
        'period_label',
        'status',
        'alert_sent',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'alert_sent' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
