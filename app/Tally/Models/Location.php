<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends AccountingModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'branch_id',
        'code',
        'name',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function gstRegistrations(): HasMany
    {
        return $this->hasMany(GstRegistration::class);
    }
}
