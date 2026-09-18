<?php

namespace App\Domains\Interest\Models;

use App\Domains\Master\Models\Customer;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterestLedger extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_id',
        'interest_rule_id',
        'as_of_date',
        'overdue_balance',
        'overdue_days',
        'annual_rate',
        'interest_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'as_of_date' => 'date',
            'overdue_balance' => 'decimal:2',
            'annual_rate' => 'decimal:2',
            'interest_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(InterestRule::class, 'interest_rule_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(InterestDocument::class);
    }
}
