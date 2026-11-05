<?php

namespace App\Domains\Organization\Models;

use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDeliveryAddress extends Model
{
    use HasUppercaseNames;

    protected $fillable = [
        'company_id',
        'address_type',
        'mailing_name',
        'address',
        'state',
        'country',
        'pincode',
        'gstin',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'display_text',
        'snapshot',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getDisplayTextAttribute(): string
    {
        $type = $this->address_type ?: 'Delivery Address';
        $addr = trim((string) $this->address);

        return $addr !== '' ? "{$type} — {$addr}" : $type;
    }

    public function getSnapshotAttribute(): string
    {
        return $this->formatSnapshot();
    }

    public function formatSnapshot(): string
    {
        $parts = [];
        if ($this->mailing_name) {
            $parts[] = $this->mailing_name;
        }
        if ($this->address) {
            $parts[] = trim((string) $this->address);
        }
        $location = array_filter([$this->state, $this->country, $this->pincode]);
        if ($location) {
            $parts[] = implode(', ', $location);
        }
        if ($this->gstin) {
            $parts[] = 'GSTIN: '.$this->gstin;
        }

        return implode("\n", $parts);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'address_type' => $this->address_type,
            'mailing_name' => $this->mailing_name,
            'address' => $this->address,
            'state' => $this->state,
            'country' => $this->country ?: 'India',
            'pincode' => $this->pincode,
            'gstin' => $this->gstin,
            'is_default' => (bool) $this->is_default,
            'display_text' => $this->display_text,
            'snapshot' => $this->formatSnapshot(),
        ];
    }
}
