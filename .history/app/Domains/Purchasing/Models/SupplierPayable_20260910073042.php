<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Master\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupplierPayable extends Model
{
    protected $fillable = [
        'supplier_id',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'supplier_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
