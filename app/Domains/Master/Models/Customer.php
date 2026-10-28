<?php

namespace App\Domains\Master\Models;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasUppercaseNames, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'business_group_id',
        'customer_type_id',
        'area_id',
        'route_id',
        'salesperson_id',
        'sales_manager_id',
        'name',
        'trade_name',
        'code',
        'party_type',
        'phone',
        'email',
        'opened_on',
        'reference_by',
        'udyam_number',
        'gem_registered',
        'gem_authorization_code',
        'gem_valid_until',
        'website',
        'location_link',
        'address',
        'gstin',
        'state',
        'pincode',
        'credit_limit',
        'credit_days',
        'credit_status',
        'credit_period_basis',
        'interest_rate',
        'payment_terms',
        'credit_notes',
        'shipping_name',
        'is_active',
    ];

    public const PARTY_TYPE_SUNDRY_DEBTORS = 'sundry_debtors';
    public const PARTY_TYPE_SUNDRY_CREDITORS = 'sundry_creditors';
    public const PARTY_TYPE_BOTH = 'both';

    public const PARTY_TYPES = [
        self::PARTY_TYPE_SUNDRY_DEBTORS => 'Sundry Debtors',
        self::PARTY_TYPE_SUNDRY_CREDITORS => 'Sundry Creditors',
        self::PARTY_TYPE_BOTH => 'Both',
    ];

    protected $appends = ['pan', 'party_type_label', 'party_type_key'];

    public function getPanAttribute(): ?string
    {
        return ($this->gstin && strlen($this->gstin) >= 12) ? substr($this->gstin, 2, 10) : null;
    }

    public function getPartyTypeKeyAttribute(): string
    {
        return match ($this->party_type) {
            'sundry_creditors', 'supplier' => self::PARTY_TYPE_SUNDRY_CREDITORS,
            'both' => self::PARTY_TYPE_BOTH,
            default => self::PARTY_TYPE_SUNDRY_DEBTORS,
        };
    }

    public function getPartyTypeLabelAttribute(): string
    {
        return self::PARTY_TYPES[$this->party_type_key] ?? 'Sundry Debtors';
    }

    public function isSupplier(): bool
    {
        return in_array($this->party_type, [self::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier', self::PARTY_TYPE_BOTH], true);
    }

    public function isCustomerParty(): bool
    {
        return in_array($this->party_type, [self::PARTY_TYPE_SUNDRY_DEBTORS, 'customer', 'dealer', self::PARTY_TYPE_BOTH], true);
    }

    public function isBoth(): bool
    {
        return $this->party_type === self::PARTY_TYPE_BOTH;
    }

    public function isFrozen(): bool
    {
        return $this->credit_status === 'frozen';
    }

    public function blocksNewCredit(): bool
    {
        return in_array($this->credit_status, ['hold', 'blocked', 'frozen', 'restricted'], true);
    }

    public function creditStatusLabel(): string
    {
        return match ($this->credit_status) {
            'hold' => 'On Hold',
            'blocked' => 'Blocked',
            'frozen' => 'Frozen',
            'restricted' => 'Restricted',
            'active', 'open', null, '' => 'Open',
            default => ucfirst(str_replace('_', ' ', (string) $this->credit_status)),
        };
    }

    public function creditPeriodBasisLabel(): string
    {
        return match ($this->credit_period_basis) {
            'invoice_date' => 'Invoice Date',
            'inward_date' => 'Inward Date',
            'receipt_date' => 'Receipt Date',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            'cumulative', null, '' => 'Cumulative / Balance',
            default => ucfirst(str_replace('_', ' ', (string) $this->credit_period_basis)),
        };
    }

    public function scopeSuppliers($query)
    {
        return $query->whereIn('party_type', [self::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier', self::PARTY_TYPE_BOTH]);
    }

    public function scopeDebtors($query)
    {
        return $query->whereIn('party_type', [self::PARTY_TYPE_SUNDRY_DEBTORS, 'customer', 'dealer', self::PARTY_TYPE_BOTH]);
    }

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'credit_days' => 'integer',
            'interest_rate' => 'decimal:2',
            'opened_on' => 'date',
            'is_active' => 'boolean',
            'gem_registered' => 'boolean',
            'gem_valid_until' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Organization\Models\BusinessGroup::class);
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

    public function gemAuthorizations(): HasMany
    {
        return $this->hasMany(PartyGemAuthorization::class);
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

    public function orders(): HasMany
    {
        return $this->hasMany(\App\Domains\Order\Models\Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\App\Domains\Sales\Models\Invoice::class);
    }

    public function salesTargets(): HasMany
    {
        return $this->hasMany(\App\Domains\Target\Models\SalesTarget::class, 'customer_id');
    }

    public function activeSalesTarget(): ?\App\Domains\Target\Models\SalesTarget
    {
        return $this->salesTargets()
            ->where('status', 'active')
            ->whereDate('ends_on', '>=', now())
            ->latest('starts_on')
            ->first();
    }

    public function latestSalesTarget(): ?\App\Domains\Target\Models\SalesTarget
    {
        return $this->salesTargets()
            ->latest('starts_on')
            ->first();
    }

    public function defaultAddress(): ?PartyAddress
    {
        return $this->defaultBillingAddress() ?? $this->defaultDeliveryAddress() ?? $this->addresses()->first();
    }

    public function activeAddresses(): HasMany
    {
        return $this->hasMany(PartyAddress::class)->where('is_active', true);
    }

    public function defaultBillingAddress(): ?PartyAddress
    {
        return $this->addresses()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_default_billing', true)
                  ->orWhere('is_default', true);
            })
            ->first()
            ?? $this->addresses()->where('is_active', true)->whereIn('type', ['billing', 'both'])->first()
            ?? $this->addresses()->where('is_active', true)->first();
    }

    public function defaultDeliveryAddress(): ?PartyAddress
    {
        return $this->addresses()
            ->where('is_active', true)
            ->where('is_default_delivery', true)
            ->first()
            ?? $this->addresses()->where('is_active', true)->whereIn('type', ['delivery', 'shipping', 'both'])->first()
            ?? $this->defaultBillingAddress();
    }
}