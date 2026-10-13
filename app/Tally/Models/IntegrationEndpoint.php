<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationEndpoint extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'name',
        'kind',
        'url',
        'bank_account_id',
        'auth_header',
        'config',
        'is_active',
        'last_synced_at',
        'last_error',
        'retry_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'retry_count' => 'integer',
            'config' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
