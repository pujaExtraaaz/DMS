<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Integration\SyncStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IntegrationReference extends AccountingModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'referenceable_type',
        'referenceable_id',
        'source',
        'external_reference_id',
        'sync_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sync_status' => SyncStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function referenceable(): MorphTo
    {
        return $this->morphTo();
    }
}
