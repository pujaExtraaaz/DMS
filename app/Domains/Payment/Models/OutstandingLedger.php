<?php

namespace App\Domains\Payment\Models;

use App\Domains\Master\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OutstandingLedger extends Model
{
    protected $table = 'outstanding_ledger';

    protected $fillable = [
        'customer_id',
        'type',
        'reference_type',
        'reference_id',
        'debit',
        'credit',
        'balance',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
