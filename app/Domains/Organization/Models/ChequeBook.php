<?php

namespace App\Domains\Organization\Models;

use App\Domains\Payment\Models\Cheque;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChequeBook extends Model
{
    protected $fillable = [
        'company_id',
        'bank_account_no',
        'bank_name',
        'cheque_book_name',
        'from_number',
        'to_number',
        'from_numeric',
        'to_numeric',
        'total_cheques',
        'status',
        'issue_date',
        'remarks',
        'created_by',
    ];

    protected $appends = [
        'is_active',
        'used_count',
        'cancelled_count',
        'remaining_count',
        'utilization_percent',
        'next_available_cheque_number',
    ];

    protected function casts(): array
    {
        return [
            'from_numeric' => 'integer',
            'to_numeric' => 'integer',
            'total_cheques' => 'integer',
            'issue_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class, 'cheque_book_id');
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Query of issued/tracked cheques recorded for this bank account and company.
     */
    public function getRegisteredChequesQuery()
    {
        return Cheque::query()
            ->where(function ($q) {
                $q->where('cheque_book_id', $this->id)
                    ->orWhere(function ($sub) {
                        $sub->where('bank_account_no', trim($this->bank_account_no))
                            ->where('company_id', $this->company_id);
                    });
            })
            ->where(function ($q) {
                $q->where('cheque_type', 'issued')
                    ->orWhere('direction', 'issued_to_vendor');
            });
    }

    /**
     * Array of normalized cheque numbers that have been issued/used (non-cancelled).
     */
    public function usedChequeNumbers(): array
    {
        $allRegistered = $this->getRegisteredChequesQuery()->get(['cheque_no', 'status']);

        $used = [];
        foreach ($allRegistered as $chq) {
            $num = (int) $chq->cheque_no;
            if ($num >= $this->from_numeric && $num <= $this->to_numeric && $chq->status !== 'cancelled') {
                $used[] = $chq->cheque_no;
            }
        }

        return array_values(array_unique($used));
    }

    /**
     * Array of normalized cheque numbers that have been cancelled.
     */
    public function cancelledChequeNumbers(): array
    {
        $allRegistered = $this->getRegisteredChequesQuery()->get(['cheque_no', 'status']);

        $cancelled = [];
        foreach ($allRegistered as $chq) {
            $num = (int) $chq->cheque_no;
            if ($num >= $this->from_numeric && $num <= $this->to_numeric && $chq->status === 'cancelled') {
                $cancelled[] = $chq->cheque_no;
            }
        }

        return array_values(array_unique($cancelled));
    }

    public function getUsedCountAttribute(): int
    {
        return count($this->usedChequeNumbers());
    }

    public function getCancelledCountAttribute(): int
    {
        return count($this->cancelledChequeNumbers());
    }

    public function getRemainingCountAttribute(): int
    {
        $remaining = $this->total_cheques - ($this->used_count + $this->cancelled_count);

        return max(0, $remaining);
    }

    public function getUtilizationPercentAttribute(): float
    {
        if ($this->total_cheques <= 0) {
            return 0.0;
        }

        $utilized = $this->used_count + $this->cancelled_count;

        return round(($utilized / $this->total_cheques) * 100, 1);
    }

    /**
     * Format an integer into a padded cheque number matching the from_number string length.
     */
    public function formatChequeNumber(int $num): string
    {
        $padLength = max(strlen((string) $this->from_number), strlen((string) $this->to_number), 6);

        return str_pad((string) $num, $padLength, '0', STR_PAD_LEFT);
    }

    /**
     * Suggest the next available cheque number in this book range.
     */
    public function getNextAvailableChequeNumberAttribute(): ?string
    {
        $usedNumeric = array_map(fn ($n) => (int) $n, $this->usedChequeNumbers());
        $cancelledNumeric = array_map(fn ($n) => (int) $n, $this->cancelledChequeNumbers());
        $taken = array_flip(array_merge($usedNumeric, $cancelledNumeric));

        for ($i = $this->from_numeric; $i <= $this->to_numeric; $i++) {
            if (! isset($taken[$i])) {
                return $this->formatChequeNumber($i);
            }
        }

        return null;
    }

    /**
     * List all available (unused and uncancelled) cheque numbers in this book range.
     *
     * @return array<int, string>
     */
    public function availableChequeNumbers(): array
    {
        $usedNumeric = array_map(fn ($n) => (int) $n, $this->usedChequeNumbers());
        $cancelledNumeric = array_map(fn ($n) => (int) $n, $this->cancelledChequeNumbers());
        $taken = array_flip(array_merge($usedNumeric, $cancelledNumeric));

        $available = [];
        for ($i = $this->from_numeric; $i <= $this->to_numeric; $i++) {
            if (! isset($taken[$i])) {
                $available[] = $this->formatChequeNumber($i);
            }
        }

        return $available;
    }

    /**
     * Check if a given cheque number falls inside this cheque book's range.
     */
    public function containsNumber(string|int $chequeNo): bool
    {
        $numeric = (int) $chequeNo;

        return $numeric >= $this->from_numeric && $numeric <= $this->to_numeric;
    }

    /**
     * Check if a given cheque number is already used in this cheque book.
     */
    public function isNumberUsed(string|int $chequeNo): bool
    {
        $numeric = (int) $chequeNo;
        $usedNumeric = array_map(fn ($n) => (int) $n, $this->usedChequeNumbers());

        return in_array($numeric, $usedNumeric, true);
    }

    /**
     * Check if a given cheque number is cancelled in this cheque book.
     */
    public function isNumberCancelled(string|int $chequeNo): bool
    {
        $numeric = (int) $chequeNo;
        $cancelledNumeric = array_map(fn ($n) => (int) $n, $this->cancelledChequeNumbers());

        return in_array($numeric, $cancelledNumeric, true);
    }

    /**
     * Cancel an individual leaf directly from the cheque book.
     */
    public function cancelLeaf(string|int $chequeNo, ?string $reason = null, User|int|null $actor = null): Cheque
    {
        $formatted = $this->formatChequeNumber((int) $chequeNo);

        if (! $this->containsNumber($chequeNo)) {
            throw new \InvalidArgumentException("Cheque number {$chequeNo} is outside the range of this cheque book.");
        }

        if ($this->isNumberUsed($chequeNo)) {
            throw new \InvalidArgumentException("Cheque number {$chequeNo} has already been issued and cannot be cancelled as an unused leaf.");
        }

        $actorId = $actor instanceof User ? $actor->id : ($actor ?: auth()->id());

        return Cheque::updateOrCreate(
            [
                'company_id' => $this->company_id,
                'bank_account_no' => $this->bank_account_no,
                'cheque_no' => $formatted,
            ],
            [
                'cheque_book_id' => $this->id,
                'bank_name' => $this->bank_name,
                'cheque_type' => 'issued',
                'direction' => 'issued_to_vendor',
                'amount' => 0.00,
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_reason' => $reason ?: 'Void / Spoiled cheque leaf',
                'notes' => $reason ?: 'Void / Spoiled cheque leaf',
                'recorded_by' => $actorId,
            ]
        );
    }

    /**
     * Check if a cheque range overlaps with any existing cheque book for the given company and bank account.
     */
    public static function hasOverlap(int $companyId, string $bankAccountNo, int $fromNumeric, int $toNumeric, ?int $ignoreId = null): bool
    {
        return static::query()
            ->where('company_id', $companyId)
            ->where('bank_account_no', trim($bankAccountNo))
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($fromNumeric, $toNumeric) {
                $q->where('from_numeric', '<=', $toNumeric)
                  ->where('to_numeric', '>=', $fromNumeric);
            })
            ->exists();
    }
}
