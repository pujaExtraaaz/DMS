<?php

namespace App\Domains\Interest\Models;

use App\Domains\Master\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterestDocument extends Model
{
    protected $fillable = [
        'document_no',
        'customer_id',
        'interest_ledger_id',
        'document_date',
        'amount',
        'status',
        'notes',
        'created_by_name',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'amount' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(InterestLedger::class, 'interest_ledger_id');
    }
}
