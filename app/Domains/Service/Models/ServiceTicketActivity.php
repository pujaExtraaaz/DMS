<?php

namespace App\Domains\Service\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTicketActivity extends Model
{
    protected $fillable = [
        'service_ticket_id', 'event', 'from_status', 'to_status', 'notes', 'user_id',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
