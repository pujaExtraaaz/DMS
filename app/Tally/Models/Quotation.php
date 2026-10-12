<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'financial_year_id',
        'number',
        'quote_date',
        'valid_until',
        'customer_ledger_id',
        'billing_address_id',
        'shipping_address_id',
        'reference_number',
        'narration',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'sales_order_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quote_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
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

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'customer_ledger_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuotationLine::class)->orderBy('line_number');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(PartyAddress::class, 'billing_address_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(PartyAddress::class, 'shipping_address_id');
    }

    public function canConvert(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'accepted'], true) && ! $this->sales_order_id;
    }
}
