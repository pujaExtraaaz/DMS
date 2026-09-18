<?php

namespace App\Domains\Target\Models;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PartyTarget extends Model
{
    protected $fillable = [
        'target_period_id',
        'customer_id',
        'salesperson_id',
        'brand_id',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TargetPeriod::class, 'target_period_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function achievement(): HasOne
    {
        return $this->hasOne(TargetAchievement::class);
    }
}
