<?php

namespace App\Domains\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FreightBillAllocation extends Model
{
    protected $fillable = [
        'freight_bill_id',
        'allocatable_type',
        'allocatable_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function freightBill(): BelongsTo
    {
        return $this->belongsTo(FreightBill::class);
    }

    public function allocatable(): MorphTo
    {
        return $this->morphTo();
    }
}
