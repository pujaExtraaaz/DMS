<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'uom_id',
        'quantity',
        'received_qty',
        'unit_cost',
        'tax_percent',
        'line_total',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'received_qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'tax_percent' => 'decimal:2',
            'line_total' => 'decimal:2',
            'weight' => 'decimal:4',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function remainingQty(): float
    {
        return max(0, (float) $this->quantity - (float) $this->received_qty);
    }
}
