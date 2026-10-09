<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherClass extends AccountingModel
{
    protected $fillable = ['company_id', 'voucher_type', 'name', 'default_ledger_id'];

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'default_ledger_id');
    }
}
