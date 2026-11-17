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

class ReceiptNote extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_PARTIALLY_INVOICED = 'partially_invoiced';
    public const STATUS_FULLY_INVOICED = 'fully_invoiced';
    public const STATUS_PARTIALLY_RETURNED = 'partially_returned';
    public const STATUS_FULLY_RETURNED = 'fully_returned';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'branch_id',
        'receipt_note_no',
        'receipt_date',
        'supplier_id',
        'warehouse_id',
        'purchase_order_id',
        'supplier_challan_no',
        'supplier_challan_date',
        'received_by',
        'reference_no',
        'remarks',
        'status',
        'attachment_path',
        'confirmed_at',
        'confirmed_by',
        'cancelled_at',
        'cancelled_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'supplier_challan_date' => 'date',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReceiptNoteItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReceiptNoteReturn::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isReceived(): bool
    {
        return in_array($this->status, [
            self::STATUS_RECEIVED,
            self::STATUS_PARTIALLY_INVOICED,
            self::STATUS_PARTIALLY_RETURNED,
            self::STATUS_FULLY_INVOICED,
            self::STATUS_FULLY_RETURNED,
        ], true);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeConfirmed(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeReturned(): bool
    {
        return $this->isReceived() && ! $this->isCancelled() && $this->pending_return_qty > 0.0001;
    }

    public function canBeInvoiced(): bool
    {
        return $this->isReceived() && ! $this->isCancelled() && $this->pending_invoice_qty > 0.0001;
    }

    public function getTotalReceivedQtyAttribute(): float
    {
        return (float) $this->items->sum('received_qty');
    }

    public function getTotalInvoicedQtyAttribute(): float
    {
        return (float) $this->items->sum('invoiced_qty');
    }

    public function getTotalReturnedQtyAttribute(): float
    {
        return (float) $this->items->sum('returned_qty');
    }

    public function getPendingInvoiceQtyAttribute(): float
    {
        return (float) $this->items->sum(function (ReceiptNoteItem $item) {
            // Pending invoiceable quantity is received minus returned minus already invoiced
            $available = max(0, (float) $item->received_qty - (float) $item->returned_qty);
            return max(0, $available - (float) $item->invoiced_qty);
        });
    }

    public function getPendingReturnQtyAttribute(): float
    {
        return (float) $this->items->sum(function (ReceiptNoteItem $item) {
            return max(0, (float) $item->received_qty - (float) $item->returned_qty);
        });
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_RECEIVED => 'Received (In Stock)',
            self::STATUS_PARTIALLY_INVOICED => 'Partially Invoiced',
            self::STATUS_FULLY_INVOICED => 'Fully Invoiced',
            self::STATUS_PARTIALLY_RETURNED => 'Partially Returned',
            self::STATUS_FULLY_RETURNED => 'Fully Returned',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20',
            self::STATUS_RECEIVED => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20',
            self::STATUS_PARTIALLY_INVOICED => 'bg-sky-50 text-sky-700 ring-1 ring-sky-600/20',
            self::STATUS_FULLY_INVOICED => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20',
            self::STATUS_PARTIALLY_RETURNED => 'bg-orange-50 text-orange-700 ring-1 ring-orange-600/20',
            self::STATUS_FULLY_RETURNED => 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20',
            self::STATUS_CANCELLED => 'bg-slate-100 text-slate-600 ring-1 ring-slate-500/20',
            default => 'bg-slate-100 text-slate-700',
        };
    }
}
