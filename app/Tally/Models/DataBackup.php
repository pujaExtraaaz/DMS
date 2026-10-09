<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\DataExchange\DataOperationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataBackup extends AccountingModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'disk',
        'path',
        'filename',
        'checksum',
        'size_bytes',
        'status',
        'table_counts',
        'metadata',
        'error_message',
        'started_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'status' => DataOperationStatus::class,
            'table_counts' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restores(): HasMany
    {
        return $this->hasMany(DataRestore::class);
    }
}
