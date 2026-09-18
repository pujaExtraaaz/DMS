<?php

namespace App\Domains\Purchasing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandedCost extends Model
{
    protected $fillable = [
        'landed_no',
        'landed_date',
        'purchase_invoice_id',
        'freight_bill_id',
        'allocation_method',
        'total_additional_cost',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'landed_date' => 'date',
            'total_additional_cost' => 'decimal:2',
        ];
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function freightBill(): BelongsTo
    {
        return $this->belongsTo(FreightBill::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LandedCostItem::class);
    }
}
