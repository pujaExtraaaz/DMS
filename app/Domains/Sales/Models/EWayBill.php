<?php

namespace App\Domains\Sales\Models;

use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EWayBill extends Model
{
    use HasUppercaseNames;
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
        'valid_until',
        'ewb_date',
        'distance_km',
        'transporter_id',
        'transporter_name',
        'vehicle_no',
        'vehicle_type',
        'transport_mode',
        'doc_no',
        'doc_date',
        'from_pincode',
        'to_pincode',
        'from_state',
        'to_state',
        'last_error',
        'payload',
        'qr_token',
        'cancel_reason',
        'cancel_remarks',
        'cancelled_at',
        'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'valid_upto' => 'datetime',
            'ewb_date' => 'datetime',
            'doc_date' => 'date',
            'cancelled_at' => 'datetime',
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

    public function getValidUntilAttribute(): ?\Carbon\Carbon
    {
        return $this->valid_upto;
    }

    public function setValidUntilAttribute($value): void
    {
        $this->attributes['valid_upto'] = $value;
    }
}
