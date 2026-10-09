<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'financial_year_id',
        'name',
        'period_start',
        'period_end',
        'ledger_id',
        'account_group_id',
        'cost_centre_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'amount' => 'decimal:2',
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

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class);
    }

    public function costCentre(): BelongsTo
    {
        return $this->belongsTo(CostCentre::class);
    }
}
