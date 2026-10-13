<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionLine extends AccountingModel
{
    protected $fillable = [
        'purchase_requisition_id',
        'line_number',
        'product_id',
        'item_name',
        'quantity',
        'unit',
        'required_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'required_by' => 'date',
        ];
    }

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
