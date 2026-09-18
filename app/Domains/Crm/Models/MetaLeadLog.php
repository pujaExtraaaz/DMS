<?php

namespace App\Domains\Crm\Models;

use Illuminate\Database\Eloquent\Model;

class MetaLeadLog extends Model
{
    protected $fillable = [
        'external_lead_id', 'status', 'attempts', 'payload', 'result', 'last_error', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
