<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'purchase_inward_id',
        'supplier_id',
        'warehouse_id',
        'invoice_no',
        'supplier_invoice_no',
        'invoice_date',
        'due_date',
        'due_date_basis',
        'due_date_source_date',
        'status',
        'subtotal',
        'tax_amount',
        'grand_total',
        'rate_override_reason',
        'notes',
        'terms_and_conditions',
        'freight_allocation_method',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'due_date_source_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inward(): BelongsTo
    {
        return $this->belongsTo(PurchaseInward::class, 'purchase_inward_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }
}
