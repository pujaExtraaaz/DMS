<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashForecast extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'name',
        'as_of_date',
        'horizon_days',
        'opening_cash',
        'projected_inflows',
        'projected_outflows',
        'closing_cash',
        'scenario',
        'lines_json',
    ];

    protected function casts(): array
    {
        return [
            'as_of_date' => 'date',
            'horizon_days' => 'integer',
            'opening_cash' => 'decimal:2',
            'projected_inflows' => 'decimal:2',
            'projected_outflows' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'lines_json' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
