<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends AccountingModel
{
    protected $fillable = [
        'project_id',
        'name',
        'percent',
        'amount',
        'due_date',
        'billed_invoice_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'percent' => 'decimal:2',
            'amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function billedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'billed_invoice_id');
    }
}
