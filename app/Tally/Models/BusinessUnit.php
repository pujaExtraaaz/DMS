<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessUnit extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
