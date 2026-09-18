<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInwardItem extends Model
{
    protected $fillable = [
        'purchase_inward_id',
        'purchase_order_item_id',
        'product_id',
        'uom_id',
        'ordered_qty',
        'received_qty',
        'accepted_qty',
        'rejected_qty',
        'unit_cost',
        'batch_no',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'ordered_qty' => 'decimal:4',
            'received_qty' => 'decimal:4',
            'accepted_qty' => 'decimal:4',
            'rejected_qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'expiry_date' => 'date',
        ];
    }

    public function inward(): BelongsTo
    {
        return $this->belongsTo(PurchaseInward::class, 'purchase_inward_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }
}
