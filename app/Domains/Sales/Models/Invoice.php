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

    public function creditNotes(): HasMany
    {
        return $this->hasMany(\App\Domains\Payment\Models\CreditNote::class);
    }

    public function getCreditNotesTotalAttribute(): float
    {
        if ($this->relationLoaded('creditNotes')) {
            return (float) $this->creditNotes
                ->where('status', 'posted')
                ->sum('grand_total');
        }

        return (float) $this->creditNotes()
            ->where('status', 'posted')
            ->sum('grand_total');
    }

    public function getEligibleAmountAttribute(): float
    {
        return max(0, (float) $this->grand_total - $this->credit_notes_total);
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

    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Domains\Organization\Models\ActivityLog::class, 'subject')->latest();
    }

    public function isEInvoiceGenerated(): bool
    {
        if (in_array(strtolower((string) $this->einvoice_status), ['generated', 'active'], true)) {
            return true;
        }

        return $this->eInvoice !== null
            && in_array($this->eInvoice->status, ['generated', 'active'], true)
            && filled($this->eInvoice->irn)
            && strtolower((string) $this->einvoice_status) !== 'cancelled';
    }

    public function isEWayBillGenerated(): bool
    {
        if (in_array(strtolower((string) $this->eway_bill_status), ['generated', 'active'], true)) {
            return true;
        }

        if (filled($this->eway_bill_no) && strtolower((string) $this->eway_bill_status) !== 'cancelled') {
            return true;
        }

        return $this->eWayBill !== null
            && in_array($this->eWayBill->status, ['generated', 'active'], true)
            && filled($this->eWayBill->eway_bill_no)
            && strtolower((string) $this->eway_bill_status) !== 'cancelled';
    }

    public function isStatutoryLocked(): bool
    {
        return $this->isEInvoiceGenerated() || $this->isEWayBillGenerated();
    }

    /**
     * Resolve remaining credit days and countdown metadata.
     *
     * @param \Carbon\CarbonInterface|string|null $asOfDate
     * @return array{
     *     status: 'paid'|'cancelled'|'na'|'ok'|'warning'|'due_today'|'overdue',
     *     badge_variant: 'success'|'warning'|'danger'|'neutral',
     *     credit_days: int|null,
     *     credit_period_label: string,
     *     remaining_days: int|null,
     *     remaining_label: string,
     *     display_text: string,
     * }
     */
    public function getRemainingDaysInfo($asOfDate = null): array
    {
        $statusStr = strtolower((string) $this->status);

        // Cancelled invoice rule
        if (in_array($statusStr, ['cancelled', 'canceled'], true)) {
            $creditDays = $this->resolveCreditDays();
            return [
                'status' => 'cancelled',
                'badge_variant' => 'neutral',
                'credit_days' => $creditDays > 0 ? $creditDays : null,
                'credit_period_label' => $creditDays > 0 ? ($creditDays === 1 ? '1 Day' : "{$creditDays} Days") : 'N/A',
                'remaining_days' => null,
                'remaining_label' => 'Cancelled',
                'display_text' => 'Cancelled',
            ];
        }

        // Fully paid invoice rule
        $isPaid = $statusStr === 'paid' || ((float) $this->paid_amount >= (float) $this->grand_total && (float) $this->grand_total > 0);
        if ($isPaid) {
            $creditDays = $this->resolveCreditDays();
            return [
                'status' => 'paid',
                'badge_variant' => 'success',
                'credit_days' => $creditDays > 0 ? $creditDays : null,
                'credit_period_label' => $creditDays > 0 ? ($creditDays === 1 ? '1 Day' : "{$creditDays} Days") : 'N/A',
                'remaining_days' => null,
                'remaining_label' => 'Paid',
                'display_text' => 'Paid',
            ];
        }

        // Credit period and due date
        $creditDays = $this->resolveCreditDays();
        $dueDate = $this->due_date ? \Illuminate\Support\Carbon::parse($this->due_date)->startOfDay() : null;

        if (! $dueDate && $creditDays > 0 && $this->invoice_date) {
            $dueDate = \Illuminate\Support\Carbon::parse($this->invoice_date)->startOfDay()->addDays($creditDays);
        }

        // Invoices without credit period / due date
        if (! $dueDate || ($creditDays <= 0 && (! $this->due_date || ($this->invoice_date && $this->due_date->isSameDay($this->invoice_date))))) {
            return [
                'status' => 'na',
                'badge_variant' => 'neutral',
                'credit_days' => $creditDays > 0 ? $creditDays : null,
                'credit_period_label' => $creditDays > 0 ? ($creditDays === 1 ? '1 Day' : "{$creditDays} Days") : 'N/A',
                'remaining_days' => null,
                'remaining_label' => 'N/A',
                'display_text' => 'N/A',
            ];
        }

        // Live daily countdown: Due Date - Current Date
        $today = ($asOfDate ? \Illuminate\Support\Carbon::parse($asOfDate) : \Illuminate\Support\Carbon::today())->startOfDay();
        $diff = (int) $today->diffInDays($dueDate, false);

        $creditPeriodLabel = $creditDays > 0 ? ($creditDays === 1 ? '1 Day' : "{$creditDays} Days") : 'N/A';

        if ($diff > 15) {
            return [
                'status' => 'ok',
                'badge_variant' => 'success',
                'credit_days' => $creditDays,
                'credit_period_label' => $creditPeriodLabel,
                'remaining_days' => $diff,
                'remaining_label' => "{$diff} Days Left",
                'display_text' => "{$diff} Days Left",
            ];
        }

        if ($diff >= 1) {
            return [
                'status' => 'warning',
                'badge_variant' => 'warning',
                'credit_days' => $creditDays,
                'credit_period_label' => $creditPeriodLabel,
                'remaining_days' => $diff,
                'remaining_label' => $diff === 1 ? '1 Day Left' : "{$diff} Days Left",
                'display_text' => $diff === 1 ? '1 Day Left' : "{$diff} Days Left",
            ];
        }

        if ($diff === 0) {
            return [
                'status' => 'due_today',
                'badge_variant' => 'danger',
                'credit_days' => $creditDays,
                'credit_period_label' => $creditPeriodLabel,
                'remaining_days' => 0,
                'remaining_label' => 'Due Today',
                'display_text' => 'Due Today',
            ];
        }

        // Overdue ($diff < 0)
        $overdueDays = abs($diff);
        return [
            'status' => 'overdue',
            'badge_variant' => 'danger',
            'credit_days' => $creditDays,
            'credit_period_label' => $creditPeriodLabel,
            'remaining_days' => $diff,
            'remaining_label' => $overdueDays === 1 ? '1 Day Overdue' : "{$overdueDays} Days Overdue",
            'display_text' => $overdueDays === 1 ? '1 Day Overdue' : "{$overdueDays} Days Overdue",
        ];
    }

    public function resolveCreditDays(): int
    {
        if ($this->credit_days !== null && (int) $this->credit_days > 0) {
            return (int) $this->credit_days;
        }

        if ($this->customer && $this->customer->credit_days !== null && (int) $this->customer->credit_days > 0) {
            return (int) $this->customer->credit_days;
        }

        if ($this->due_date && $this->invoice_date) {
            $days = (int) $this->invoice_date->diffInDays($this->due_date, false);
            if ($days > 0) {
                return $days;
            }
        }

        return (int) ($this->credit_days ?? 0);
    }
}
