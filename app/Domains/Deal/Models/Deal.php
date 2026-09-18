<?php

namespace App\Domains\Deal\Models;

use App\Domains\Master\Models\Customer;
use App\Domains\Order\Models\Order;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deal extends Model
{
    protected $fillable = [
        'reference',
        'customer_id',
        'invoice_id',
        'order_id',
        'site_name',
        'status',
        'sale_amount',
        'landed_cost',
        'discount_amount',
        'deal_cost_total',
        'net_margin',
        'notes',
        'created_by_name',
    ];

    protected function casts(): array
    {
        return [
            'sale_amount' => 'decimal:2',
            'landed_cost' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'deal_cost_total' => 'decimal:2',
            'net_margin' => 'decimal:2',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(DealExpense::class);
    }
}
