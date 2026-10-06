<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'supplier_id',
        'purchase_order_id',
        'created_by',
        'invoice_number',
        'supplier_invoice_number',
        'invoice_date',
        'status',
        'subtotal',
        'tax_amount',
        'freight_charge',
        'other_charges',
        'total_amount',
        'notes',
        'credit_days',
        'due_date',
        'posted_at',
        'billing_address_id',
        'shipping_address_id',
        'billing_address',
        'shipping_address',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'posted_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'freight_charge' => 'decimal:2',
            'other_charges' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'credit_days' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'supplier_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Master\Models\PartyAddress::class, 'billing_address_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Master\Models\PartyAddress::class, 'shipping_address_id');
    }
}