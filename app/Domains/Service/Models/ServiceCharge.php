<?php

namespace App\Domains\Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCharge extends Model
{
    protected $fillable = [
        'service_ticket_id', 'is_paid_service', 'technician_charges', 'transportation_charges',
        'parts_total', 'grand_total', 'customer_approved', 'customer_approved_at',
        'payment_status', 'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'is_paid_service' => 'boolean',
            'technician_charges' => 'decimal:2',
            'transportation_charges' => 'decimal:2',
            'parts_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'customer_approved' => 'boolean',
            'customer_approved_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ServiceChargeLine::class);
    }

    public function outstanding(): float
    {
        return max(0, (float) $this->grand_total - (float) $this->paid_amount);
    }

    public function recalculateTotals(): void
    {
        $parts = (float) $this->lines()->sum('amount');
        $grand = round((float) $this->technician_charges + (float) $this->transportation_charges + $parts, 2);
        $this->update([
            'parts_total' => $parts,
            'grand_total' => $grand,
        ]);
    }
}
