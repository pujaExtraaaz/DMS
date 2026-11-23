<?php

namespace App\Domains\Sales\Models;

use App\Domains\Master\Models\Customer;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Company;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Quotation extends Model
{
    use HasUppercaseNames {
        isUppercaseNameAttribute as protected traitIsUppercaseNameAttribute;
    }

    protected $fillable = [
        'company_id',
        'quotation_no',
        'customer_id',
        'salesperson_id',
        'quotation_date',
        'valid_until',
        'title',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'estimated_profit',
        'notes',
        'special_instructions',
        'terms_conditions',
        'created_by_name',
        'updated_by_name',
        'converted_order_id',
    ];

    protected function casts(): array
    {
        return [
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'estimated_profit' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function items(): HasMany
    {
        $relation = $this->hasMany(QuotationItem::class);

        if (Schema::hasColumn('quotation_items', 'sort_order')) {
            return $relation->orderBy('sort_order')->orderBy('id');
        }

        return $relation->orderBy('id');
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    public function hasConvertedOrder(): bool
    {
        return (bool) ($this->converted_order_id || Order::where('quotation_id', $this->id)->exists());
    }

    public function getConvertedOrder(): ?Order
    {
        if ($this->relationLoaded('convertedOrder') && $this->convertedOrder) {
            return $this->convertedOrder;
        }

        if ($this->converted_order_id) {
            return $this->convertedOrder()->first();
        }

        return Order::where('quotation_id', $this->id)->first();
    }

    public function hasConvertedInvoice(): bool
    {
        $order = $this->getConvertedOrder();
        if ($order && ($order->invoice()->exists() || Invoice::where('order_id', $order->id)->exists())) {
            return true;
        }

        return false;
    }

    public function getConvertedInvoice(): ?Invoice
    {
        $order = $this->getConvertedOrder();
        if ($order) {
            return $order->invoice ?? Invoice::where('order_id', $order->id)->first();
        }

        return null;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent'], true);
    }

    public function isDeletable(): bool
    {
        if ($this->hasConvertedOrder() || $this->hasConvertedInvoice() || $this->status === 'converted') {
            return false;
        }

        return in_array($this->status, ['draft', 'sent', 'rejected', 'cancelled'], true);
    }

    public function resolveCompany(): ?Company
    {
        if ($this->relationLoaded('company') && $this->company) {
            return $this->company;
        }

        if ($this->company_id) {
            return $this->company()->first() ?: Company::defaultCompany();
        }

        return Company::defaultCompany();
    }

    public function isUppercaseNameAttribute(string $key): bool
    {
        if (in_array($key, ['title', 'notes', 'special_instructions', 'terms_conditions', 'quotation_no'], true)) {
            return false;
        }

        return $this->traitIsUppercaseNameAttribute($key);
    }
}
