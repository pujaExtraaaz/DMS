<?php

namespace App\Domains\Hrms\Models;

use Illuminate\Database\Eloquent\Model;

class EsslSyncLog extends Model
{
    protected $fillable = [
        'started_at', 'finished_at', 'fetched', 'imported', 'unmatched', 'status', 'message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
