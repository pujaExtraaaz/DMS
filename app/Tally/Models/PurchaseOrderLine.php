<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends AccountingModel
{
    protected $fillable = [
        'purchase_order_id',
        'line_number',
        'item_name',
        'product_id',
        'tax_rate_id',
        'hsn_sac_id',
        'quantity',
        'rate',
        'discount',
        'taxable_amount',
        'tax_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'cess_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'decimal:4',
            'rate' => 'decimal:2',
            'discount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'cess_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hsnSac(): BelongsTo
    {
        return $this->belongsTo(HsnSac::class);
    }
}
