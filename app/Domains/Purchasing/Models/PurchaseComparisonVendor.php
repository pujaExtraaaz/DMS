<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Master\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseComparisonVendor extends Model
{
    protected $fillable = [
        'purchase_comparison_id',
        'supplier_id',
        'sort_order',
        'billing_address',
        'dispatch_address',
        'contact_person',
        'mobile',
        'email',
        'gstin',
        'quotation_number',
        'quotation_date',
        'valid_until',
        'credit_period',
        'advance_payment',
        'payment_mode',
        'credit_days',
        'payment_due_date',
        'pdc_details',
        'cdc_details',
        'cash_discount_percent',
        'cash_discount_days',
        'bank_interest_percent',
        'bank_interest_days',
        'financing_notes',
        'credit_limit_note',
        'replacement_policy',
        'return_policy',
        'installation_support',
        'transporter',
        'freight_paid_by',
        'delivery_location',
        'partial_delivery',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'payment_due_date' => 'date',
        'partial_delivery' => 'boolean',
        'cash_discount_percent' => 'decimal:2',
        'bank_interest_percent' => 'decimal:2',
    ];

    public function comparison(): BelongsTo
    {
        return $this->belongsTo(PurchaseComparison::class, 'purchase_comparison_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'supplier_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(PurchaseComparisonQuote::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(PurchaseComparisonFile::class);
    }
}
