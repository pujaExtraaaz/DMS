<?php

namespace App\Domains\Payment\Models;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\ChequeBook;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cheque extends Model
{
    use HasUppercaseNames;

    public const STATUS_UNUSED = 'unused';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_DEPOSITED = 'deposited';
    public const STATUS_CLEARED = 'cleared';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_STALE = 'stale';

    public const TYPE_ISSUED = 'issued';
    public const TYPE_RECEIVED = 'received';

    protected $fillable = [
        'company_id',
        'bank_account_no',
        'cheque_book_id',
        'cheque_no',
        'customer_id',
        'purpose',
        'direction',
        'cheque_type',
        'amount',
        'bank_name',
        'branch_name',
        'cheque_date',
        'deposit_date',
        'clearance_date',
        'stale_date',
        'status',
        'payment_id',
        'reference_voucher',
        'voucher_type',
        'voucher_id',
        'bounce_count',
        'bounced_at',
        'bounce_reason',
        'cancelled_at',
        'cancelled_reason',
        'notes',
        'recorded_by',
    ];

    protected $appends = [
        'status_label',
        'type_label',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'cheque_date' => 'date',
            'deposit_date' => 'date',
            'clearance_date' => 'date',
            'stale_date' => 'date',
            'bounced_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function chequeBook(): BelongsTo
    {
        return $this->belongsTo(ChequeBook::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function bounces(): HasMany
    {
        return $this->hasMany(ChequeBounce::class);
    }

    public function isIssued(): bool
    {
        return $this->cheque_type === self::TYPE_ISSUED
            || $this->direction === 'issued_to_vendor';
    }

    public function isReceived(): bool
    {
        return ! $this->isIssued();
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->isIssued() ? 'Issued' : 'Received';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => $this->isIssued() ? 'Issued' : 'Received',
            'unused' => 'Unused',
            'issued' => 'Issued',
            'received' => 'Received',
            'deposited' => 'Deposited',
            'cleared' => 'Cleared',
            'bounced' => 'Bounced',
            'cancelled' => 'Cancelled',
            'stale', 'expired' => 'Stale / Expired',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'cleared' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'deposited' => 'bg-blue-100 text-blue-800 border-blue-200',
            'bounced' => 'bg-rose-100 text-rose-800 border-rose-200',
            'cancelled' => 'bg-slate-200 text-slate-800 border-slate-300',
            'stale', 'expired' => 'bg-amber-100 text-amber-800 border-amber-200',
            'issued' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            'unused' => 'bg-gray-100 text-gray-700 border-gray-200',
            default => 'bg-amber-100 text-amber-800 border-amber-200',
        };
    }
}
