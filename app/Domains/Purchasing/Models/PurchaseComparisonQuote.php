<?php

namespace App\Domains\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseComparisonQuote extends Model
{
    protected $fillable = [
        'purchase_comparison_item_id',
        'purchase_comparison_vendor_id',
        'is_quoted',
        'quoted_quantity',
        'basic_rate',
        'discount_type',
        'discount_value',
        'gst_rate',
        'freight_type',
        'freight_mode',
        'freight_value',
        'packing_charges',
        'insurance_charges',
        'other_charges',
        'warranty_period',
        'warranty_type',
        'stock_availability',
        'delivery_time',
        'delivery_date',
        'payment_terms',
        'price_validity',
        'country_of_origin',
        'remarks',
    ];

    protected $casts = [
        'is_quoted' => 'boolean',
        'quoted_quantity' => 'decimal:4',
        'basic_rate' => 'decimal:4',
        'discount_value' => 'decimal:4',
        'gst_rate' => 'decimal:2',
        'freight_value' => 'decimal:2',
        'packing_charges' => 'decimal:2',
        'insurance_charges' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'delivery_date' => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PurchaseComparisonItem::class, 'purchase_comparison_item_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(PurchaseComparisonVendor::class, 'purchase_comparison_vendor_id');
    }
}
