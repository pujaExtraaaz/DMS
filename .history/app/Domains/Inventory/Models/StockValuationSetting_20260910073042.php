<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockValuationSetting extends Model
{
    protected $fillable = [
        'company_id',
        'financial_year_id',
        'method',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }
}
