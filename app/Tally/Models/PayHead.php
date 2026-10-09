<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayHead extends AccountingModel
{
    protected $fillable = ['company_id', 'name', 'nature', 'calculation', 'rate_or_amount', 'ledger_id', 'employee_id'];

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }
}
