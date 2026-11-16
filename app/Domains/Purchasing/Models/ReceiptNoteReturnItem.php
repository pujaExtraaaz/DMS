<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptNoteReturnItem extends Model
{
    protected $fillable = [
        'receipt_note_return_id',
        'receipt_note_item_id',
        'product_id',
        'uom_id',
        'quantity',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function receiptNoteReturn(): BelongsTo
    {
        return $this->belongsTo(ReceiptNoteReturn::class);
    }

    public function receiptNoteItem(): BelongsTo
    {
        return $this->belongsTo(ReceiptNoteItem::class);
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
