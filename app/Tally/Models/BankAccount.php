<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends AccountingModel
{
    /**
     * Bank details for a ledger in the Bank Accounts group.
     * The opening bank balance is the ledger opening balance.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'ledger_id',
        'bank_name',
        'account_number',
        'ifsc',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }
}
