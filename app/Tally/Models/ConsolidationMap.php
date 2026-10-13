<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsolidationMap extends AccountingModel
{
    protected $fillable = [
        'parent_company_id',
        'subsidiary_company_id',
        'ownership_percent',
        'reporting_currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ownership_percent' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function subsidiaryCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'subsidiary_company_id');
    }
}
