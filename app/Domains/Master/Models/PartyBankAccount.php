<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyBankAccount extends Model
{
    protected $fillable = [
        'customer_id',
        'bank_name',
        'account_holder_name',
        'account_number',
        'account_type',
        'ifsc_code',
        'branch_name',
        'branch_address',
        'upi_id',
        'is_primary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}