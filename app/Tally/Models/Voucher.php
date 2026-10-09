<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Integration\Concerns\HasExternalReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Voucher extends AccountingModel
{
    use HasExternalReference;
    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'branch_id',
        'financial_year_id',
        'voucher_type',
        'voucher_number',
        'voucher_date',
        'reference_number',
        'narration',
        'name_on_receipt',
        'is_post_dated',
        'is_optional',
        'is_memo',
        'voucher_class_id',
        'nature_of_payment',
        'reverses_on',
        'reversed_voucher_id',
        'currency_id',
        'exchange_rate',
        'foreign_total',
        'payment_request_id',
        'merchant_profile_id',
        'status',
        'total_debit',
        'total_credit',
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
            'voucher_type' => VoucherType::class,
            'voucher_date' => 'date',
            'status' => VoucherStatus::class,
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'foreign_total' => 'decimal:2',
            'is_post_dated' => 'boolean',
            'is_optional' => 'boolean',
            'is_memo' => 'boolean',
            'reverses_on' => 'date',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(VoucherEntry::class)->orderBy('line_number');
    }

    public function billAllocations(): HasMany
    {
        return $this->hasMany(BillAllocation::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function voucherClass(): BelongsTo
    {
        return $this->belongsTo(VoucherClass::class);
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

    public function ledgerSummary(): string
    {
        $names = $this->entries
            ->map(fn (VoucherEntry $entry) => $entry->ledger?->name)
            ->filter()
            ->unique()
            ->values();

        return $names->isEmpty() ? '—' : $names->join(', ');
    }
}
