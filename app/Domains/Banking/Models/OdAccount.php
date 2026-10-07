<?php

namespace App\Domains\Banking\Models;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OdAccount extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'account_number',
        'bank_name',
        'ifsc_code',
        'od_limit',
        'interest_rate',
        'interest_calculation_method',
        'effective_from',
        'effective_to',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'od_limit' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankAccountTransaction::class, 'account_number', 'account_number')
            ->where('company_id', $this->company_id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Get the current net utilized OD amount (Total Debits - Total Credits).
     */
    public function getCurrentUtilization(): float
    {
        $debits = (float) BankAccountTransaction::query()
            ->where('company_id', $this->company_id)
            ->where('account_number', $this->account_number)
            ->sum('debit');

        $credits = (float) BankAccountTransaction::query()
            ->where('company_id', $this->company_id)
            ->where('account_number', $this->account_number)
            ->sum('credit');

        return round($debits - $credits, 2);
    }

    /**
     * Get available OD limit.
     * Guaranteed never to become negative as per requirements.
     */
    public function getAvailableLimit(): float
    {
        $utilized = $this->getCurrentUtilization();
        $limit = (float) $this->od_limit;

        return max(0.0, round($limit - $utilized, 2));
    }

    /**
     * Check if OD limit is currently exceeded.
     */
    public function isExceeded(): bool
    {
        return $this->getCurrentUtilization() > (float) $this->od_limit;
    }

    /**
     * Amount by which OD is exceeded.
     */
    public function getExceededAmount(): float
    {
        $utilized = $this->getCurrentUtilization();
        $limit = (float) $this->od_limit;

        return max(0.0, round($utilized - $limit, 2));
    }

    /**
     * Percentage of OD limit utilized.
     */
    public function getUtilizationPercentage(): float
    {
        $limit = (float) $this->od_limit;
        if ($limit <= 0) {
            return 0.0;
        }

        $utilized = $this->getCurrentUtilization();

        return round(($utilized / $limit) * 100, 2);
    }

    /**
     * Daily interest for the current utilized balance.
     * Simple Daily = Utilized * Rate / 365 / 100
     */
    public function getDailyInterest(): float
    {
        $utilized = $this->getCurrentUtilization();
        if ($utilized <= 0) {
            return 0.0;
        }

        return round($utilized * ((float) $this->interest_rate) / 365 / 100, 4);
    }
}

