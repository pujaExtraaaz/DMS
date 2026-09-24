<?php

namespace App\Domains\Tally\Models;

use Illuminate\Database\Eloquent\Model;

class TallySyncMapping extends Model
{
    protected $table = 'tally_sync_mappings';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'tally_type',
        'tally_guid',
        'tally_name',
        'sync_status',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }
}