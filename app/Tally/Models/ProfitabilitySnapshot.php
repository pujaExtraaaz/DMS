<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitabilitySnapshot extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'dimension',
        'dimension_key',
        'dimension_label',
        'from_date',
        'to_date',
        'revenue',
        'cost',
        'contribution',
        'margin_percent',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'revenue' => 'decimal:2',
            'cost' => 'decimal:2',
            'contribution' => 'decimal:2',
            'margin_percent' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
