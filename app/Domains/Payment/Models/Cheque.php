<?php

namespace App\Domains\Payment\Models;

use App\Domains\Master\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cheque extends Model
{
    protected $fillable = [
        'cheque_no',
        'customer_id',
        'purpose',
        'direction',
        'amount',
        'bank_name',
        'branch_name',
        'cheque_date',
        'deposit_date',
        'clearance_date',
        'status',
        'payment_id',
        'bounce_count',
        'bounced_at',
        'bounce_reason',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'cheque_date' => 'date',
            'deposit_date' => 'date',
            'clearance_date' => 'date',
            'bounced_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function bounces(): HasMany
    {
        return $this->hasMany(ChequeBounce::class);
    }
}
