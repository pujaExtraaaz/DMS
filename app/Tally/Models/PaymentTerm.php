<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTerm extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'name',
        'days',
        'due_on_receipt',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'integer',
            'due_on_receipt' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parties(): HasMany
    {
        return $this->hasMany(Party::class);
    }

    public function daysForDue(): int
    {
        return $this->due_on_receipt ? 0 : max(0, (int) $this->days);
    }
}
