<?php

namespace App\Domains\Sales\Models;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'is_general',
        'description',
        'uom_id',
        'quantity',
        'price_type',
        'entered_unit_price',
        'unit_price',
        'discount_amount',
        'discount_type',
        'discount_value',
        'taxable_amount',
        'tax_rate',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'hsn_code',
        'batch_no',
        'serial_numbers',
        'tax_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'is_general' => 'boolean',
            'quantity' => 'decimal:4',
            'entered_unit_price' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'cgst_rate' => 'decimal:2',
            'sgst_rate' => 'decimal:2',
            'igst_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'serial_numbers' => 'array',
        ];
    }

    public function isGeneralEntry(): bool
    {
        return (bool) $this->is_general || ($this->product_id === null && filled($this->description));
    }

    public function getItemNameAttribute(): ?string
    {
        if ($this->isGeneralEntry()) {
            return $this->description;
        }

        return $this->product?->name;
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }
}
