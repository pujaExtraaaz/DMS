<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'label',
        'name',
        'contact_person',
        'contact_phone',
        'address_line_1',
        'address_line_2',
        'city',
        'address',
        'state',
        'pincode',
        'gstin',
        'is_default',
        'is_default_billing',
        'is_default_delivery',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_default_billing' => 'boolean',
        'is_default_delivery' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'full_address',
        'display_text',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getFullAddressAttribute(): string
    {
        $lines = array_filter([$this->address_line_1, $this->address_line_2]);
        $lineStr = !empty($lines) ? implode(', ', $lines) : ($this->address ?? '');

        $cityState = array_filter([$this->city, $this->state]);
        $cityStateStr = !empty($cityState) ? implode(', ', $cityState) : '';

        $parts = array_filter([$lineStr, $cityStateStr]);
        $full = implode(', ', $parts);

        if ($this->pincode) {
            $full .= ($full ? ' - ' : '') . $this->pincode;
        }

        return $full ?: ($this->address ?? '');
    }

    public function getDisplayTextAttribute(): string
    {
        $label = $this->label ?: 'Address';
        $full = $this->full_address;
        return $full ? "{$label} — {$full}" : $label;
    }

    public function formatSnapshot(): string
    {
        $parts = [];
        if ($this->label) {
            $parts[] = "Label: {$this->label}";
        }
        $contact = $this->contact_person ?: $this->name;
        if ($contact) {
            if ($this->contact_phone) {
                $contact .= " (Ph: {$this->contact_phone})";
            }
            $parts[] = "Contact: {$contact}";
        }
        if ($this->full_address) {
            $parts[] = $this->full_address;
        }
        if ($this->gstin) {
            $parts[] = "GSTIN: {$this->gstin}";
        }

        return implode("\n", $parts);
    }

    public function isReferencedInTransactions(): bool
    {
        return \App\Domains\Sales\Models\Invoice::where('billing_address_id', $this->id)->orWhere('shipping_address_id', $this->id)->exists()
            || \App\Domains\Order\Models\Order::where('billing_address_id', $this->id)->orWhere('shipping_address_id', $this->id)->exists()
            || \App\Domains\Purchasing\Models\PurchaseOrder::where('billing_address_id', $this->id)->orWhere('shipping_address_id', $this->id)->exists()
            || \App\Domains\Purchasing\Models\PurchaseInvoice::where('billing_address_id', $this->id)->orWhere('shipping_address_id', $this->id)->exists();
    }
}
