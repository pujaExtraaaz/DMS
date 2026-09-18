<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInwardItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductSerial extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'serial_number',
        'status',
        'source_type',
        'source_id',
        'purchase_inward_item_id',
        'sold_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function inwardItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseInwardItem::class, 'purchase_inward_item_id');
    }
}
