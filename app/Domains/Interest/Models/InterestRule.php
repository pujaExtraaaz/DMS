<?php

namespace App\Domains\Interest\Models;

use App\Domains\Master\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterestRule extends Model
{
    protected $fillable = [
        'name',
        'customer_id',
        'annual_rate',
        'grace_days',
        'is_active',
        'is_default',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'annual_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(InterestLedger::class);
    }
}
