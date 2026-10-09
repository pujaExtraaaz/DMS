<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Accounting\OpeningBalanceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerOpening extends AccountingModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'ledger_id',
        'financial_year_id',
        'branch_id',
        'opening_balance',
        'opening_balance_type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'opening_balance_type' => OpeningBalanceType::class,
        ];
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
