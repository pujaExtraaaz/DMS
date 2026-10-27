<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VoucherEntry extends AccountingModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'voucher_id',
        'ledger_id',
        'cost_centre_id',
        'business_unit_id',
        'department_id',
        'line_number',
        'debit',
        'credit',
        'base_debit',
        'base_credit',
        'narration',
        'reference',
        'bank_transaction_type',
        'instrument_number',
        'instrument_date',
        'payment_favouring',
        'bank_name',
        'bank_account_number',
        'bank_ifsc',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'instrument_date' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'base_debit' => 'decimal:2',
            'base_credit' => 'decimal:2',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function costCentre(): BelongsTo
    {
        return $this->belongsTo(CostCentre::class);
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function reconciliation(): HasOne
    {
        return $this->hasOne(BankReconciliation::class);
    }
}
