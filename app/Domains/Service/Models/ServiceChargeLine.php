<?php

namespace App\Domains\Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceChargeLine extends Model
{
    protected $fillable = [
        'service_charge_id', 'line_type', 'item_name', 'quantity', 'rate', 'amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(ServiceCharge::class, 'service_charge_id');
    }
}
