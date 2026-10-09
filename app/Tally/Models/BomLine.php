<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomLine extends AccountingModel
{
    protected $fillable = ['bill_of_material_id', 'product_id', 'unit_id', 'quantity', 'wastage_percent'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'wastage_percent' => 'decimal:4',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bill_of_material_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
