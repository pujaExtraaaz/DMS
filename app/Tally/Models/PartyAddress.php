<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyAddress extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'party_id',
        'label',
        'kind',
        'line1',
        'line2',
        'city',
        'state',
        'country',
        'pincode',
        'gstin',
        'place_of_supply',
        'is_default_billing',
        'is_default_shipping',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default_billing' => 'boolean',
            'is_default_shipping' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function singleLine(): string
    {
        return collect([$this->line1, $this->line2, $this->city, $this->state, $this->pincode, $this->country])
            ->filter()
            ->implode(', ');
    }
}
