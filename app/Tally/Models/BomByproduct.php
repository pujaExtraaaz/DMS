<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomByproduct extends AccountingModel
{
    protected $fillable = ['bill_of_material_id', 'product_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
