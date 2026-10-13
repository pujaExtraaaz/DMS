<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetVerification extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'fixed_asset_id',
        'verification_date',
        'verified_by',
        'condition',
        'found',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'verification_date' => 'date',
            'found' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }
}
