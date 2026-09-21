<?php

namespace App\Domains\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EWayBill extends Model
{
    protected static function booted(): void
    {
        static::creating(function (EWayBill $eWayBill) {
            if (empty($eWayBill->qr_token)) {
                $eWayBill->qr_token = (string) Str::uuid();
            }
        });
    }
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
        'qr_token',
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
