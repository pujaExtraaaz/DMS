<?php

namespace App\Domains\Organization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
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

