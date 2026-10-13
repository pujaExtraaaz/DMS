<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'supplier_id',
        'po_no',
        'po_date',
        'expected_date',
        'credit_days',
        'due_date',
        'status',
        'is_freight_applicable',
        'freight_amount',
        'freight_tax_rate',
        'freight_tax_amount',
        'freight_total',
        'freight_notes',
        'subtotal',
        'tax_amount',
        'grand_total',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'billing_address_id',
        'shipping_address_id',
        'billing_address',
        'shipping_address',
    ];

    protected function casts(): array
    {
        return [
            'po_date' => 'date',
            'expected_date' => 'date',
            'due_date' => 'date',
            'credit_days' => 'integer',
            'approved_at' => 'datetime',
            'is_freight_applicable' => 'boolean',
            'freight_amount' => 'decimal:2',
            'freight_tax_rate' => 'decimal:2',
            'freight_tax_amount' => 'decimal:2',
            'freight_total' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    protected $appends = [
        'po_number',
        'order_number',
    ];

    public function getPoNumberAttribute(): ?string
    {
        return $this->po_no;
    }

    public function getOrderNumberAttribute(): ?string
    {
        return $this->po_no;
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function inwards(): HasMany
    {
        return $this->hasMany(PurchaseInward::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function freightAllocations(): MorphMany
    {
        return $this->morphMany(FreightBillAllocation::class, 'allocatable');
    }

    public function isReceivable(): bool
    {
        return in_array($this->status, ['approved', 'partially_received'], true);
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Master\Models\PartyAddress::class, 'billing_address_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Master\Models\PartyAddress::class, 'shipping_address_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'pending_approval'], true)
            && ! $this->inwards()->exists()
            && ! $this->invoices()->exists();
    }

    public function canBeDeleted(): bool
    {
        return $this->getDeleteBlockReason() === null;
    }

    public function getDeleteBlockReason(): ?string
    {
        if (in_array($this->status, ['partially_received', 'closed'], true)) {
            return 'Cannot delete purchase order with status "' . str_replace('_', ' ', $this->status) . '".';
        }

        if ($this->inwards()->exists()) {
            return 'Cannot delete purchase order because goods receipts (GRN) exist for it.';
        }

        if ($this->invoices()->exists()) {
            return 'Cannot delete purchase order because purchase invoices exist for it.';
        }

        if ((float) $this->items()->sum('received_qty') > 0) {
            return 'Cannot delete purchase order because goods have already been received.';
        }

        return null;
    }
}
