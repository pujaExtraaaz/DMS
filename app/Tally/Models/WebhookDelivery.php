<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends AccountingModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'webhook_endpoint_id',
        'event',
        'payload',
        'status',
        'attempts',
        'response_status',
        'last_error',
        'next_attempt_at',
        'delivered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'next_attempt_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    public function canRetry(): bool
    {
        return $this->status !== 'delivered' && $this->attempts < 5;
    }
}
