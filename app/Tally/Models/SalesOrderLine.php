<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderLine extends AccountingModel
{
    protected $fillable = [
        'sales_order_id', 'line_number', 'item_name', 'product_id', 'tax_rate_id', 'hsn_sac_id',
        'quantity', 'fulfilled_quantity', 'cancelled_quantity', 'rate', 'discount', 'taxable_amount', 'tax_amount', 'cgst_amount',
        'sgst_amount', 'igst_amount', 'cess_amount', 'line_total',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }
}
