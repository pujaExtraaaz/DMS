<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends AccountingModel
{
    protected $fillable = [
        'company_id', 'branch_id', 'financial_year_id', 'period_start', 'period_end',
        'status', 'salary_ledger_id', 'deduction_ledger_id', 'voucher_id', 'statutory_voucher_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }

    public function salaryLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'salary_ledger_id');
    }

    public function statutoryVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'statutory_voucher_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
