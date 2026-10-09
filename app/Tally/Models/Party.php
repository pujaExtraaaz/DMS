<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Tax\GstRegistrationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'credit_days' => 'integer',
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

    public function isCustomer(): bool
    {
        return $this->type === 'customer';
    }
}
