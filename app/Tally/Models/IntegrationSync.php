<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationSync extends AccountingModel
{
    protected $fillable = [
        'integration_id',
        'status',
        'message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }
}
