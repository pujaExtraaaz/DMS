<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccruedExpense extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'accrual_date',
        'expense_ledger_id',
        'accrual_ledger_id',
        'amount',
        'goods_receipt_id',
        'is_uninvoiced_receipt',
        'is_reversed',
        'accrual_voucher_id',
        'reversal_voucher_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'accrual_date' => 'date',
            'amount' => 'decimal:2',
            'is_uninvoiced_receipt' => 'boolean',
            'is_reversed' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function expenseLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'expense_ledger_id');
    }

    public function accrualLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'accrual_ledger_id');
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function accrualVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'accrual_voucher_id');
    }

    public function reversalVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'reversal_voucher_id');
    }
}
