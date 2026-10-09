<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantProfile extends AccountingModel
{
    protected $fillable = ['company_id', 'branch_id', 'name', 'legal_name', 'merchant_code', 'settlement_ledger_id', 'bank_account_id', 'notes'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function settlementLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'settlement_ledger_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
