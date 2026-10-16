<?php

namespace App\Domains\Master\Models;

use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyCreditCheque extends Model
{
    use HasUppercaseNames;

    protected $fillable = [
        'customer_id',
        'cheque_number',
        'bank_name',
        'branch_name',
        'cheque_date',
        'amount',
        'cheque_type',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cheque_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}