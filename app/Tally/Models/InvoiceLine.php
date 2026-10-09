<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends AccountingModel
{
    /**
     * Stock is posted only when the invoice is posted and the line has a product.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'line_number',
        'item_name',
        'product_id',
        'godown_id',
        'tax_rate_id',
        'hsn_sac_id',
        'quantity',
        'rate',
        'discount',
        'taxable_amount',
        'tax_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'cess_amount',
        'line_total',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'product_id' => 'integer',
            'quantity' => 'decimal:4',
            'rate' => 'decimal:2',
            'discount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'cess_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function godown(): BelongsTo
    {
        return $this->belongsTo(Godown::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function hsnSac(): BelongsTo
    {
        return $this->belongsTo(HsnSac::class);
    }
}
