<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'uom_id',
        'quantity',
        'received_qty',
        'unit_cost',
        'purchase_price_inclusive',
        'purchase_discount_percent',
        'tax_percent',
        'line_total',
        'other_vendor_rate',
        'batch_no',
        'batch_selling_price',
        'batch_mrp',
        'expiry_date',
        'cgst_percent',
        'sgst_percent',
        'cgst_amount',
        'sgst_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'received_qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'purchase_price_inclusive' => 'decimal:4',
            'purchase_discount_percent' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'line_total' => 'decimal:2',
            'other_vendor_rate' => 'decimal:4',
            'batch_selling_price' => 'decimal:2',
            'batch_mrp' => 'decimal:2',
            'expiry_date' => 'date',
            'cgst_percent' => 'decimal:2',
            'sgst_percent' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
        ];
    }

    public function remainingQty(): float
    {
        return max(0, (float) $this->quantity - (float) $this->received_qty);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
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
