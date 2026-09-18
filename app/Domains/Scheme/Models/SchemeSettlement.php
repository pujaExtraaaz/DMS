<?php

namespace App\Domains\Scheme\Models;

use App\Domains\Master\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemeSettlement extends Model
{
    protected $fillable = [
        'scheme_id',
        'scheme_achievement_id',
        'customer_id',
        'amount',
        'status',
        'settlement_ref',
        'settled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(SchemeAchievement::class, 'scheme_achievement_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
