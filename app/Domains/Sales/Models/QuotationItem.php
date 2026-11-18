<?php

namespace App\Domains\Sales\Models;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id',
        'sort_order',
        'product_id',
        'uom_id',
        'description',
        'hsn_code',
        'mrp',
        'tax_rate',
        'image_path',
        'quantity',
        'unit_price',
        'discount_amount',
        'line_total',
        'estimated_landed_cost',
        'estimated_profit',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'mrp' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'estimated_landed_cost' => 'decimal:2',
            'estimated_profit' => 'decimal:2',
        ];
    }

    /**
     * Tax-exclusive line amount for document preview (PRICE × Qty − line discount).
     */
    public function taxableDisplayTotal(): float
    {
        return round(max(0, ((float) $this->unit_price * (float) $this->quantity) - (float) $this->discount_amount), 2);
    }

    public function imageUrl(): ?string
    {
        $path = $this->image_path ?: $this->product?->image_path;
        if (! $path) {
            return null;
        }

        return asset('storage/'.$path);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
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
