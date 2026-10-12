<?php

namespace Tally\Models;

use Tally\Tax\GstRegistrationType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'ledger_id',
        'branch_id',
        'type',
        'legal_name',
        'contact_person',
        'phone',
        'mobile',
        'email',
        'billing_address',
        'shipping_address',
        'state',
        'country',
        'gstin',
        'pan',
        'gst_registration_type',
        'credit_limit',
        'credit_days',
        'payment_term_id',
        'default_discount_percent',
        'salesperson_name',
        'commission_percent',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'credit_days' => 'integer',
            'default_discount_percent' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'gst_registration_type' => GstRegistrationType::class,
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PartyAddress::class);
    }

    public function priceLists(): HasMany
    {
        return $this->hasMany(PartyPriceList::class);
    }

    public function isCustomer(): bool
    {
        return $this->type === 'customer';
    }

    public function defaultBillingAddress(): ?PartyAddress
    {
        return $this->addresses->firstWhere('is_default_billing', true)
            ?? $this->addresses->first(fn (PartyAddress $a) => in_array($a->kind, ['billing', 'both'], true));
    }

    public function defaultShippingAddress(): ?PartyAddress
    {
        return $this->addresses->firstWhere('is_default_shipping', true)
            ?? $this->addresses->first(fn (PartyAddress $a) => in_array($a->kind, ['shipping', 'both'], true));
    }
}
