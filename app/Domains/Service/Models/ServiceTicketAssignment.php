<?php

namespace App\Domains\Service\Models;

use App\Domains\Hrms\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTicketAssignment extends Model
{
    protected $fillable = [
        'service_ticket_id', 'employee_id', 'oem_centre_id', 'notes', 'assigned_by',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function oemCentre(): BelongsTo
    {
        return $this->belongsTo(ServiceOemCentre::class, 'oem_centre_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
