<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandedCostAllocation extends AccountingModel
{
    protected $fillable = [
        'landed_cost_voucher_id',
        'product_id',
        'item_name',
        'quantity',
        'base_value',
        'allocated_charges',
        'landed_unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'base_value' => 'decimal:2',
            'allocated_charges' => 'decimal:2',
            'landed_unit_cost' => 'decimal:4',
        ];
    }

    public function landedCostVoucher(): BelongsTo
    {
        return $this->belongsTo(LandedCostVoucher::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
