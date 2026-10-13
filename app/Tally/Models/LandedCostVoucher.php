<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandedCostVoucher extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'financial_year_id',
        'number',
        'voucher_date',
        'purchase_invoice_id',
        'goods_receipt_id',
        'freight',
        'duty',
        'other_charges',
        'total_charges',
        'allocation_basis',
        'status',
        'voucher_id',
        'posted_at',
        'narration',
    ];

    protected function casts(): array
    {
        return [
            'voucher_date' => 'date',
            'freight' => 'decimal:2',
            'duty' => 'decimal:2',
            'other_charges' => 'decimal:2',
            'total_charges' => 'decimal:2',
            'posted_at' => 'datetime',
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

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'purchase_invoice_id');
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(LandedCostAllocation::class)->orderBy('id');
    }
}
