<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Tax\DeductionKind;
use Tally\Tax\PartyRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeductionSection extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'kind',
        'name',
        'section_code',
        'rate',
        'threshold_amount',
        'party_role',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'kind' => DeductionKind::class,
            'party_role' => PartyRole::class,
            'rate' => 'decimal:4',
            'threshold_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class);
    }

    public function canBeDeleted(): bool
    {
        return ! $this->ledgers()->exists();
    }
}
