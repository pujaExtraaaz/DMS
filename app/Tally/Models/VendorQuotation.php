<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorQuotation extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'request_for_quotation_id',
        'supplier_ledger_id',
        'quote_date',
        'valid_until',
        'subtotal',
        'tax_total',
        'grand_total',
        'lead_days',
        'payment_terms_note',
        'is_selected',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quote_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'lead_days' => 'integer',
            'is_selected' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requestForQuotation(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'supplier_ledger_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VendorQuotationLine::class)->orderBy('line_number');
    }
}
