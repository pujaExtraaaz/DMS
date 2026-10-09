<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integration extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'webhook_endpoint_id',
        'name',
        'type',
        'direction',
        'external_system',
        'credentials',
        'is_active',
        'last_status',
        'last_synced_at',
        'last_error',
    ];

    protected $hidden = [
        'credentials',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    public function syncs(): HasMany
    {
        return $this->hasMany(IntegrationSync::class);
    }

    public function hasCredentials(): bool
    {
        return $this->credentials !== null && $this->credentials !== [];
    }
}
