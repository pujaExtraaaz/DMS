<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewaySettlement extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'financial_year_id',
        'merchant_profile_id',
        'bank_account_id',
        'voucher_id',
        'reference',
        'settlement_date',
        'gross_amount',
        'charges',
        'net_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'settlement_date' => 'date',
            'gross_amount' => 'decimal:2',
            'charges' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class, 'merchant_profile_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }
}
