<?php

namespace App\Domains\Order\Models;

use App\Domains\Inventory\Models\StockReservation;
use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_no',
        'customer_id',
        'warehouse_id',
        'quotation_id',
        'salesperson_id',

        'created_by_name',
        'updated_by_name',

        'order_date',
        'due_date',
        'fulfilment_mode',
        'back_order',
        'credit_check_status',
        'blocked_reason',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'notes',

        'approved_by',
        'approved_by_name',
        'approved_at',

        'converted_by_name',
        'cancelled_by_name',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'due_date' => 'date',
            'back_order' => 'boolean',

            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',

            'approved_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'salesperson_id'
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function pendingQty(): float
    {
        return (float) $this->items->sum(function (OrderItem $item) {
            return max(
                0,
                (float) $item->quantity - (float) $item->delivered_qty
            );
        });
    }
}
