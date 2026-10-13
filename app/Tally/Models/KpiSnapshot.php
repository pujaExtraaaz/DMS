<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiSnapshot extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'as_of_date',
        'dso',
        'dpo',
        'inventory_turnover',
        'cash_runway_days',
        'working_capital',
        'gross_margin_percent',
        'extras_json',
    ];

    protected function casts(): array
    {
        return [
            'as_of_date' => 'date',
            'dso' => 'decimal:2',
            'dpo' => 'decimal:2',
            'inventory_turnover' => 'decimal:4',
            'cash_runway_days' => 'decimal:2',
            'working_capital' => 'decimal:2',
            'gross_margin_percent' => 'decimal:4',
            'extras_json' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
