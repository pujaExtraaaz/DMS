<?php

namespace App\Domains\Service\Models;

use App\Domains\Hrms\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceVisit extends Model
{
    protected $fillable = [
        'service_ticket_id', 'employee_id', 'visited_at', 'customer_complaint', 'problem_identified',
        'action_taken', 'parts_replaced', 'before_photo_path', 'after_photo_path',
        'customer_signature', 'next_visit_required', 'next_visit_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'next_visit_at' => 'datetime',
            'next_visit_required' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
