<?php

namespace App\Domains\Scheme\Models;

use App\Domains\Master\Models\Customer;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchemeAchievement extends Model
{
    protected $fillable = [
        'scheme_id',
        'customer_id',
        'invoice_id',
        'qualified_value',
        'benefit_amount',
        'status',
        'snapshot',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'qualified_value' => 'decimal:2',
            'benefit_amount' => 'decimal:2',
            'snapshot' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(SchemeSettlement::class);
    }
}
