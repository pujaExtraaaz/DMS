<?php

namespace App\Domains\Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceInstallation extends Model
{
    protected $fillable = [
        'service_ticket_id', 'installation_type', 'project_name', 'site_address', 'quotation_amount',
        'customer_approved', 'customer_approved_at', 'installation_charges', 'material_consumed',
        'completion_certificate_path', 'customer_acknowledged', 'customer_acknowledged_at',
        'customer_signature',
    ];

    protected function casts(): array
    {
        return [
            'quotation_amount' => 'decimal:2',
            'installation_charges' => 'decimal:2',
            'customer_approved' => 'boolean',
            'customer_approved_at' => 'datetime',
            'customer_acknowledged' => 'boolean',
            'customer_acknowledged_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }
}
