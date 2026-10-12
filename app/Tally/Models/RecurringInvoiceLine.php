<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringInvoiceLine extends AccountingModel
{
    protected $fillable = [
        'recurring_invoice_id',
        'line_number',
        'product_id',
        'item_name',
        'quantity',
        'rate',
        'discount',
        'tax_rate',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'rate' => 'decimal:4',
            'discount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
        ];
    }

    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }
}
