<?php

namespace App\Domains\Banking\Models;

use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankAccountTransaction extends Model
{
    protected $fillable = [
        'company_id',
        'account_number',
        'od_account_id',
        'transaction_date',
        'transaction_no',
        'description',
        'transaction_type',
        'debit',
        'credit',
        'reference_type',
        'reference_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function odAccount(): BelongsTo
    {
        return $this->belongsTo(OdAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForAccount(Builder $query, string $accountNumber, int $companyId): Builder
    {
        return $query->where('company_id', $companyId)
            ->where('account_number', $accountNumber);
    }

    public function scopeBetweenDates(Builder $query, $from, $to): Builder
    {
        return $query->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to);
    }
}

