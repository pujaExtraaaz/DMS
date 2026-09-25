<?php

namespace App\Domains\Tally\Models;

use Illuminate\Database\Eloquent\Model;

class TallySyncQueue extends Model
{
    protected $table = 'tally_sync_queues';

    protected $fillable = [
        'document_type',
        'document_id',
        'payload',
        'status',
        'attempts',
        'last_error',
        'last_response',
        'sent_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}
