<?php

namespace App\Domains\Payment\Models;

use App\Domains\Master\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChequeBounce extends Model
{
    protected $fillable = [
        'cheque_id',
        'customer_id',
        'bounced_on',
        'reason',
        'charges',
        'bounce_number',
        'triggered_freeze',
        'recorded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bounced_on' => 'date',
            'charges' => 'decimal:2',
            'triggered_freeze' => 'boolean',
        ];
    }

    public function cheque(): BelongsTo
    {
        return $this->belongsTo(Cheque::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
