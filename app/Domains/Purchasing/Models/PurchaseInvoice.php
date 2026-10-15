<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Inventory\Models\StockMovement;
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
        'od_account_id',
        'supplier_id',
        'purchase_order_id',
        'purchase_inward_id',
        'created_by',
        'invoice_number',
        'invoice_no',
        'supplier_invoice_number',
        'supplier_invoice_no',
        'invoice_date',
        'purchase_receive_date',
        'payment_order_date',
        'status',
        'subtotal',
        'tax_amount',
        'freight_charge',
        'other_charges',
        'total_amount',
        'grand_total',
        'qr_token',
        'notes',
        'credit_days',
        'due_date',
        'due_date_basis',
        'due_date_source_date',
        'rate_override_reason',
        'terms_and_conditions',
        'freight_allocation_method',
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
            'purchase_receive_date' => 'date',
            'payment_order_date' => 'date',
            'due_date' => 'date',
            'posted_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'freight_charge' => 'decimal:2',
            'other_charges' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'credit_days' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PurchaseInvoice $invoice) {
            if (empty($invoice->qr_token)) {
                $invoice->qr_token = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) ($this->attributes['grand_total'] ?? 0);
    }

    public function setTotalAmountAttribute($value): void
    {
        $this->attributes['grand_total'] = $value;
    }

    public function getInvoiceNumberAttribute(): ?string
    {
        return $this->attributes['invoice_no'] ?? null;
    }

    public function setInvoiceNumberAttribute(?string $value): void
    {
        $this->attributes['invoice_no'] = $value;
    }

    public function getSupplierInvoiceNumberAttribute(): ?string
    {
        return $this->attributes['supplier_invoice_no'] ?? null;
    }

    public function setSupplierInvoiceNumberAttribute(?string $value): void
    {
        $this->attributes['supplier_invoice_no'] = $value;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class)->withTrashed();
    }

    public function odAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Banking\Models\OdAccount::class, 'od_account_id');
    }

    public function bankAccountTransactions(): MorphMany
    {
        return $this->morphMany(\App\Domains\Banking\Models\BankAccountTransaction::class, 'reference');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'supplier_id')->withTrashed();
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
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Master\Models\PartyAddress::class, 'billing_address_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Master\Models\PartyAddress::class, 'shipping_address_id');
    }

    public function landedCosts(): HasMany
    {
        return $this->hasMany(LandedCost::class);
    }

    public function isEditable(): bool
    {
        return ! in_array($this->status, ['paid', 'cancelled'], true);
    }

    public function getDeleteBlockReason(): ?string
    {
        if ($this->status === 'paid') {
            return 'Paid purchase invoices cannot be deleted. Please reverse the payment first.';
        }

        if ($this->landedCosts()->where('status', 'posted')->exists()) {
            return 'Cannot delete purchase invoice linked to posted landed costs. Please cancel the landed cost first.';
        }

        return null;
    }

    public function canBeDeleted(): bool
    {
        return $this->getDeleteBlockReason() === null;
    }
}