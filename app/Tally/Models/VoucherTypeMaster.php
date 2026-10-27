<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherTypeMaster extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'name',
        'abbreviation',
        'category',
        'numbering_method',
        'prefix',
        'suffix',
        'next_number',
        'padding',
        'restart',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
