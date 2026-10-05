<?php

namespace App\Domains\Master\Models;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_type_id',
        'area_id',
        'route_id',
        'salesperson_id',
        'sales_manager_id',
        'name',
        'code',
        'party_type',
        'phone',
        'email',
        'address',
        'gstin',
        'state',
        'pincode',
        'credit_limit',
        'credit_days',
        'credit_status',
        'credit_period_basis',
        'interest_rate',
        'is_active',
    ];

    protected $appends = ['pan'];

    public function getPanAttribute(): ?string
    {
        return ($this->gstin && strlen($this->gstin) >= 12) ? substr($this->gstin, 2, 10) : null;
    }

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'credit_days' => 'integer',
            'interest_rate' => 'decimal:2',
            'is_active' => 'boolean',
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

    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function salesManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_manager_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PartyContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PartyAddress::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(PartyBankAccount::class);
    }

    public function creditCheques(): HasMany
    {
        return $this->hasMany(PartyCreditCheque::class);
    }

    public function defaultAddress(): ?PartyAddress
    {
        return $this->addresses()->where('is_default', true)->first()
            ?? $this->addresses()->first();
    }
}