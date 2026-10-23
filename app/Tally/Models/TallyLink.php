<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TallyLink extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'tally_type',
        'linkable_type',
        'linkable_id',
        'guid',
        'alter_id',
        'master_id',
        'remote_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}