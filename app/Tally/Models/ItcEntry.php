<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItcEntry extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'invoice_id',
        'return_period',
        'eligibility',
        'igst',
        'cgst',
        'sgst',
        'cess',
        'reason',
    ];

    protected function casts(): array
    {
        return [
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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
