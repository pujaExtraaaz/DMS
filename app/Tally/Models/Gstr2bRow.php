<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gstr2bRow extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'return_period',
        'supplier_gstin',
        'supplier_name',
        'invoice_number',
        'invoice_date',
        'taxable_value',
        'igst',
        'cgst',
        'sgst',
        'cess',
        'matched_invoice_id',
        'match_status',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'taxable_value' => 'decimal:2',
            'igst' => 'decimal:2',
            'cgst' => 'decimal:2',
            'sgst' => 'decimal:2',
            'cess' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function matchedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'matched_invoice_id');
    }

    public function totalTax(): string
    {
        return number_format(
            (float) $this->igst + (float) $this->cgst + (float) $this->sgst + (float) $this->cess,
            2,
            '.',
            ''
        );
    }
}
