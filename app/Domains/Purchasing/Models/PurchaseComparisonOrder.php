<?php

namespace App\Domains\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseComparisonOrder extends Model
{
    protected $fillable = [
        'purchase_comparison_id',
        'purchase_comparison_vendor_id',
        'purchase_order_id',
    ];

    public function comparison(): BelongsTo
    {
        return $this->belongsTo(PurchaseComparison::class, 'purchase_comparison_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(PurchaseComparisonVendor::class, 'purchase_comparison_vendor_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
