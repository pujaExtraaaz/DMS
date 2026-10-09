<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\DataExchange\DataOperationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataRestore extends AccountingModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'data_backup_id',
        'user_id',
        'safety_backup_id',
        'status',
        'confirmation',
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
            'status' => DataOperationStatus::class,
            'table_counts' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function backup(): BelongsTo
    {
        return $this->belongsTo(DataBackup::class, 'data_backup_id');
    }

    public function safetyBackup(): BelongsTo
    {
        return $this->belongsTo(DataBackup::class, 'safety_backup_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
