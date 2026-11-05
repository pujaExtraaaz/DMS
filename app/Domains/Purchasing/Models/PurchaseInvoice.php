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
        'material_dispatch_date',
        'expected_delivery_date',
        'actual_material_receipt_date',
        'payment_order_date',
        'status',
        'material_receipt_status',
        'price_type',
        'tax_type',
        'subtotal',
        'taxable_amount',
        'tax_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'freight_charge',
        'is_freight_applicable',
        'freight_mode',
        'freight_percent',
        'freight_amount',
        'freight_tax_rate',
        'freight_tax_amount',
        'freight_total',
        'freight_notes',
        'receipt_note_nos',
        'receipt_doc_no',
        'dispatched_through',
        'carrier_name',
        'lr_number',
        'lr_date',
        'vehicle_number',
        'order_nos',
        'payment_terms',
        'other_references',
        'terms_of_delivery',
        'other_charges',
        'round_off',
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
            'material_dispatch_date' => 'date',
            'expected_delivery_date' => 'date',
            'actual_material_receipt_date' => 'date',
            'payment_order_date' => 'date',
            'lr_date' => 'date',
            'due_date' => 'date',
            'is_freight_applicable' => 'boolean',
            'posted_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'freight_charge' => 'decimal:2',
            'freight_percent' => 'decimal:4',
            'freight_amount' => 'decimal:2',
            'freight_tax_rate' => 'decimal:2',
            'freight_tax_amount' => 'decimal:2',
            'freight_total' => 'decimal:2',
            'other_charges' => 'decimal:2',
            'round_off' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'credit_days' => 'integer',
        ];
    }

    public const MATERIAL_IN_TRANSIT = 'in_transit';

    public const MATERIAL_PARTIALLY_RECEIVED = 'partially_received';

    public const MATERIAL_RECEIVED = 'received';

    public function materialReceiptLabel(): string
    {
        return match ($this->material_receipt_status) {
            self::MATERIAL_RECEIVED => 'Received',
            self::MATERIAL_PARTIALLY_RECEIVED => 'Partially Received',
            default => 'In Transit',
        };
    }

    public function daysInTransit(): ?int
    {
        $start = $this->material_dispatch_date ?? $this->invoice_date;
        if (! $start) {
            return null;
        }

        $end = $this->actual_material_receipt_date ?? now();

        return (int) $start->diffInDays($end);
    }

    public function isDeliveryOverdue(): bool
    {
        if (in_array($this->material_receipt_status, [self::MATERIAL_RECEIVED], true)) {
            return false;
        }

        if (! $this->expected_delivery_date) {
            return false;
        }

        return $this->expected_delivery_date->isPast();
    }

    public function paymentStatusLabel(): string
    {
        if ($this->status === 'cancelled') {
            return 'Cancelled';
        }

        if (in_array($this->status, ['paid'], true)) {
            return 'Paid';
        }

        if ($this->due_date && $this->due_date->isPast()) {
            return 'Overdue';
        }

        if ($this->due_date) {
            return 'Payment Due';
        }

        return 'Outstanding';
    }

    public function canReceiveMaterial(): bool
    {
        return $this->status !== 'cancelled'
            && $this->material_receipt_status !== self::MATERIAL_RECEIVED;
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