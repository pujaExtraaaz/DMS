<?php

namespace Tally\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloseChecklistTask extends AccountingModel
{
    protected $fillable = [
        'close_checklist_id',
        'task_key',
        'title',
        'sort_order',
        'status',
        'assigned_to',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(CloseChecklist::class, 'close_checklist_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
