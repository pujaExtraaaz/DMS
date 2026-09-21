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
        'invoice_no',
        'customer_id',
        'order_id',
        'salesperson_id',
        'invoice_date',
        'due_date',
        'due_date_basis',
        'due_date_source_date',
        'payment_terms',
        'credit_days',
        'status',
        'subtotal',
        'discount_amount',
        'universal_discount_type',
        'universal_discount_value',
        'item_discount_total',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'notes',
        'terms_and_conditions',
        'vehicle_no',
        'transport_mode',
        'reference_no',
        'delivery_state',
        'qr_token',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'due_date_source_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'universal_discount_value' => 'decimal:2',
            'item_discount_total' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
}
