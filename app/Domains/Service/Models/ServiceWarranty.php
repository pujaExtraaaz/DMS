<?php

namespace App\Domains\Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceWarranty extends Model
{
    protected $fillable = [
        'service_ticket_id', 'oem_centre_id', 'warranty_claim_no', 'oem_ticket_no',
        'oem_status', 'parts_covered', 'chargeable', 'notes',
    ];

    protected function casts(): array
    {
        return ['chargeable' => 'boolean'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function oemCentre(): BelongsTo
    {
        return $this->belongsTo(ServiceOemCentre::class, 'oem_centre_id');
    }
}
