<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseComparisonItem extends Model
{
    protected $fillable = [
        'purchase_comparison_id',
        'sort_order',
        'category_name',
        'subcategory_name',
        'brand_name',
        'product_id',
        'uom_id',
        'item_name',
        'model_sku',
        'description',
        'required_quantity',
        'selected_vendor_id',
        'selection_reason',
        'final_rate',
        'final_freight',
        'final_amount',
    ];

    protected $casts = [
        'required_quantity' => 'decimal:4',
        'final_rate' => 'decimal:4',
        'final_freight' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    public function comparison(): BelongsTo
    {
        return $this->belongsTo(PurchaseComparison::class, 'purchase_comparison_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function selectedVendor(): BelongsTo
    {
        return $this->belongsTo(PurchaseComparisonVendor::class, 'selected_vendor_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(PurchaseComparisonQuote::class);
    }
}
