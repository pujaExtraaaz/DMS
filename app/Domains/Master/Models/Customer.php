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
        'pan',
        'gst_registration_type',
        'state',
        'pincode',
        'credit_limit',
        'credit_days',
        'credit_status',
        'risk_status',
        'cheque_bounce_count',
        'credit_period_basis',
        'interest_rate',
        'payment_terms',
        'credit_notes',
        'shipping_name',
        'target_amount',
        'target_start_date',
        'target_end_date',
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

    public const GST_REGISTRATION_TYPES = [
        'Regular',
        'Composition',
        'Casual Taxable Person',
        'Non-Resident Taxable Person',
        'Input Service Distributor (ISD)',
        'SEZ Unit',
        'SEZ Developer',
        'TDS Deductor',
        'TCS Collector',
        'UIN Holder',
        'Unregistered',
    ];

    /**
     * Normalize raw or API registration type strings into standard Party Master categories.
     */
    public static function normalizeGstRegistrationType(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $trimmed = trim((string) $value);
        $upper = mb_strtoupper($trimmed);

        foreach (self::GST_REGISTRATION_TYPES as $type) {
            if (mb_strtoupper($type) === $upper) {
                return $type;
            }
        }

        if (str_contains($upper, 'COMPOSITION')) {
            return 'Composition';
        }
        if (str_contains($upper, 'CASUAL')) {
            return 'Casual Taxable Person';
        }
        if (str_contains($upper, 'NON-RESIDENT') || str_contains($upper, 'NON RESIDENT')) {
            return 'Non-Resident Taxable Person';
        }
        if (str_contains($upper, 'ISD') || str_contains($upper, 'INPUT SERVICE DISTRIBUTOR')) {
            return 'Input Service Distributor (ISD)';
        }
        if (str_contains($upper, 'SEZ DEVELOPER')) {
            return 'SEZ Developer';
        }
        if (str_contains($upper, 'SEZ')) {
            return 'SEZ Unit';
        }
        if (str_contains($upper, 'TDS')) {
            return 'TDS Deductor';
        }
        if (str_contains($upper, 'TCS')) {
            return 'TCS Collector';
        }
        if (str_contains($upper, 'UIN') || str_contains($upper, 'EMBASSY') || str_contains($upper, 'UN BODY')) {
            return 'UIN Holder';
        }
        if (str_contains($upper, 'UNREGISTERED')) {
            return 'Unregistered';
        }
        if (str_contains($upper, 'REGULAR')) {
            return 'Regular';
        }

        return $trimmed;
    }

    protected $appends = ['pan', 'party_type_label', 'party_type_key'];

    public function getPanAttribute(?string $value = null): ?string
    {
        $stored = $value ?? ($this->attributes['pan'] ?? null);
        if (filled($stored)) {
            return strtoupper(trim((string) $stored));
        }

        $gstin = $this->attributes['gstin'] ?? null;

        return ($gstin && strlen((string) $gstin) >= 12) ? substr((string) $gstin, 2, 10) : null;
    }

    public function getPartyTypeKeyAttribute(): string
    {
        return $this->canonicalPartyType();
    }

    public function getPartyTypeLabelAttribute(): string
    {
        return self::PARTY_TYPES[$this->canonicalPartyType()] ?? 'Sundry Debtors';
    }

    /**
     * Stored party type as the form slug. Reads the raw column so title-casing
     * cannot turn "sundry_creditors" into a value the select does not recognise.
     */
    public function canonicalPartyType(): string
    {
        $raw = (string) ($this->attributes['party_type'] ?? '');

        return match ($raw) {
            'sundry_creditors', 'supplier' => self::PARTY_TYPE_SUNDRY_CREDITORS,
            'both' => self::PARTY_TYPE_BOTH,
            default => self::PARTY_TYPE_SUNDRY_DEBTORS,
        };
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
            'target_amount' => 'decimal:2',
            'target_start_date' => 'date',
            'target_end_date' => 'date',
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

    public function phoneDirectoryContacts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Domains\PhoneDirectory\Models\PhoneDirectoryContact::class, 'party_contacts', 'customer_id', 'phone_directory_contact_id')
            ->withPivot(['id', 'is_primary', 'level', 'role', 'designation_id', 'phone', 'email'])
            ->withTimestamps();
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

    public function getEffectiveSalesTargetAttribute(): ?\App\Domains\Target\Models\SalesTarget
    {
        return $this->activeSalesTarget() ?? $this->latestSalesTarget();
    }

    public function getTargetProgressStatusAttribute(): string
    {
        $target = $this->effective_sales_target;
        if (! $target) {
            return 'No Target';
        }

        return $target->progress_status;
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