<?php

namespace App\Domains\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EWayBill extends Model
{
    protected $fillable = [
        'invoice_id',
        'status',
        'provider',
        'eway_bill_no',
        'valid_upto',
        'ewb_date',
        'distance_km',
        'transporter_id',
        'transporter_name',
        'vehicle_no',
        'transport_mode',
        'last_error',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'valid_upto' => 'datetime',
            'ewb_date' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isReal(): bool
    {
        return in_array($this->status, ['generated', 'active'], true) && ! str_starts_with((string) $this->eway_bill_no, 'STUB');
    }
}
