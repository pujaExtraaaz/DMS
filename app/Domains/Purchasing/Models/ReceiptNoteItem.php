<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Inventory\Models\ProductSerial;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ReceiptNoteItem extends Model
{
    protected $fillable = [
        'receipt_note_id',
        'purchase_order_item_id',
        'product_id',
        'uom_id',
        'ordered_qty',
        'challan_qty',
        'received_qty',
        'invoiced_qty',
        'returned_qty',
        'unit_cost',
        'batch_no',
        'expiry_date',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'ordered_qty' => 'decimal:4',
            'challan_qty' => 'decimal:4',
            'received_qty' => 'decimal:4',
            'invoiced_qty' => 'decimal:4',
            'returned_qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'expiry_date' => 'date',
        ];
    }

    public function receiptNote(): BelongsTo
    {
        return $this->belongsTo(ReceiptNote::class);
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

    public function returnItems(): HasMany
    {
        return $this->hasMany(ReceiptNoteReturnItem::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function serials(): MorphMany
    {
        return $this->morphMany(ProductSerial::class, 'source');
    }

    public function getPendingReturnQtyAttribute(): float
    {
        return max(0, (float) $this->received_qty - (float) $this->returned_qty);
    }

    public function getPendingInvoiceQtyAttribute(): float
    {
        $available = max(0, (float) $this->received_qty - (float) $this->returned_qty);
        return max(0, $available - (float) $this->invoiced_qty);
    }
}
