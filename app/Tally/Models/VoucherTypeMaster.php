<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherTypeMaster extends AccountingModel
{
    protected $fillable = ['company_id', 'name', 'abbreviation', 'category', 'prefix', 'next_number', 'padding'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
