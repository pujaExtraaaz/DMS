<?php

namespace App\Domains\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseComparisonFile extends Model
{
    protected $fillable = [
        'purchase_comparison_vendor_id',
        'path',
        'original_name',
        'mime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(PurchaseComparisonVendor::class, 'purchase_comparison_vendor_id');
    }
}
