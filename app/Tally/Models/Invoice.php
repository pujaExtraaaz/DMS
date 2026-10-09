<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Accounting\VoucherStatus;
use Tally\Invoicing\InvoiceKind;
use Tally\Tax\SupplyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends AccountingModel
{
    /**
     * Commercial sales or purchase document.
     * party_ledger_id is the customer or supplier until a Party Master exists.
     * Accounting is the linked voucher, written only when this document is posted.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'branch_id',
        'financial_year_id',
        'kind',
        'invoice_number',
        'invoice_date',
        'party_ledger_id',
        'account_ledger_id',
        'reference_number',
        'narration',
        'supply_type',
        'place_of_supply',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'voucher_id',
        'sales_order_id',
        'gst_registration_id',
        'reverse_charge',
        'created_by',
        'posted_at',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => InvoiceKind::class,
            'invoice_date' => 'date',
            'supply_type' => SupplyType::class,
            'status' => VoucherStatus::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'reverse_charge' => 'boolean',
            'posted_at' => 'datetime',
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

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'party_ledger_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'account_ledger_id');
    }

    public function gstRegistration(): BelongsTo
    {
        return $this->belongsTo(GstRegistration::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('line_number');
    }

    public function isDraft(): bool
    {
        return $this->status === VoucherStatus::Draft;
    }

    public function isPosted(): bool
    {
        return $this->status === VoucherStatus::Posted;
    }

    public function isCancelled(): bool
    {
        return $this->status === VoucherStatus::Cancelled;
    }
}
