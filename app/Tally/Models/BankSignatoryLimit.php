<?php

namespace Tally\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankSignatoryLimit extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'bank_account_id',
        'user_id',
        'single_limit',
        'daily_limit',
        'requires_dual',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'single_limit' => 'decimal:2',
            'daily_limit' => 'decimal:2',
            'requires_dual' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
