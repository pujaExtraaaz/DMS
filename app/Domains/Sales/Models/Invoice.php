<?php

namespace App\Domains\Sales\Models;

use App\Domains\Communication\Models\CommunicationLog;
use App\Domains\Deal\Models\Deal;
use App\Domains\Master\Models\Customer;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\PaymentLink;
use App\Models\User;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->qr_token)) {
                $invoice->qr_token = (string) Str::uuid();
            }
        });
    }
    protected $fillable = [
        'company_id',
        'invoice_no',
        'customer_id',
        'scheme_id',
        'order_id',
        'salesperson_id',
        'invoice_date',
        'due_date',
        'due_date_basis',
        'due_date_source_date',
        'payment_terms',
        'credit_days',
        'price_type',
        'tax_type',
        'status',
        'subtotal',
        'taxable_amount',
        'discount_amount',
        'universal_discount_type',
        'universal_discount_value',
        'item_discount_total',
        'tax_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'round_off',
        'grand_total',
        'scheme_benefit_amount',
        'paid_amount',
        'notes',
        'terms_and_conditions',
        'vehicle_no',
        'transport_mode',
        'reference_no',
        'delivery_note_no',
        'delivery_note_na',
        'dispatch_doc_no',
        'dispatched_through',
        'destination',
        'carrier_name',
        'lr_rr_no',
        'lr_rr_date',
        'order_no',
        'other_references',
        'terms_of_delivery',
        'delivery_state',
        'place_of_supply_state',
        'bill_to_place',
        'ship_to_place',
        'billing_address_id',
        'shipping_address_id',
        'billing_address',
        'shipping_address',
        'qr_token',
        'einvoice_status',
        'eway_bill_status',
        'eway_bill_required',
        'transporter_id',
        'transporter_name',
        'distance_km',
        'vehicle_type',
        'eway_bill_no',
        'eway_bill_date',
        'eway_sub_type',
        'eway_doc_type',
        'eway_supply_type',
        'consignor_name',
        'consignor_gstin',
        'consignor_state',
        'consignor_address_type',
        'consignor_address_line_1',
        'consignor_address_line_2',
        'consignor_pincode',
        'consignor_city',
        'consignor_actual_state',
        'consignee_name',
        'consignee_gstin',
        'consignee_state',
        'consignee_address_type',
        'consignee_address_line_1',
        'consignee_address_line_2',
        'consignee_pincode',
        'consignee_city',
        'consignee_actual_state',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'due_date_source_date' => 'date',
            'lr_rr_date' => 'date',
            'eway_bill_date' => 'date',
            'delivery_note_na' => 'boolean',
            'subtotal' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'universal_discount_value' => 'decimal:2',
            'item_discount_total' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'round_off' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'scheme_benefit_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Organization\Models\Company::class);
    }

    public function getSellingCompanyAttribute(): ?\App\Domains\Organization\Models\Company
    {
        return $this->company
            ?? $this->order?->warehouse?->company
            ?? $this->customer?->company
            ?? $this->salesperson?->company
            ?? \App\Domains\Organization\Models\Company::defaultCompany();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function eInvoice(): HasOne
    {
        return $this->hasOne(EInvoice::class);
    }

    public function eWayBill(): HasOne
    {
        return $this->hasOne(EWayBill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class);
    }

    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class);
    }

    public function deal(): HasOne
    {
        return $this->hasOne(Deal::class);
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Master\Models\PartyAddress::class, 'billing_address_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Master\Models\PartyAddress::class, 'shipping_address_id');
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Scheme\Models\Scheme::class);
    }

    public function schemeAchievement(): HasOne
    {
        return $this->hasOne(\App\Domains\Scheme\Models\SchemeAchievement::class);
    }
}
