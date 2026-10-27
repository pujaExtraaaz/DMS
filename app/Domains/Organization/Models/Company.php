<?php

namespace App\Domains\Organization\Models;

use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasUppercaseNames;

    protected $fillable = [
        'business_group_id',
        'name',
        'code',
        'legal_name',
        'gstin',
        'pan',
        'cin',
        'tan',
        'udyam_registration_no',
        'msme_category',
        'msme_registration_no',
        'address',
        'state',
        'pincode',
        'phone',
        'email',
        'website',
        'bank_name',
        'bank_account_no',
        'bank_ifsc',
        'upi_id',
        'od_limit',
        'interest_rate',
        'additional_details',
        'purchase_terms_and_conditions',
        'selling_terms_and_conditions',
        'due_date_basis',
        'logo_path',
        'company_type',
        'incorporation_no',
        'incorporation_date',
        'logo_emoji',
        'iec_code',
        'city',
        'country',
        'nature_of_business',
        'authorized_capital',
        'paid_up_capital',
        'annual_turnover',
        'financial_year',
        'accounting_software',
        'branches_count_manual',
        'employees_count_manual',
        'relationship_type',
        'shareholding_percentage',
        'compliance_status',
        'is_active',
        'is_default',
        'cin_number',
        'msme_reg_no',
    ];

    protected $appends = [
        'cin_number',
        'msme_reg_no',
    ];

    public function getCinNumberAttribute(): ?string
    {
        return $this->attributes['cin'] ?? null;
    }

    public function setCinNumberAttribute(?string $value): void
    {
        $this->attributes['cin'] = $value;
    }

    public function getMsmeRegNoAttribute(): ?string
    {
        return $this->attributes['msme_registration_no'] ?? null;
    }

    public function setMsmeRegNoAttribute(?string $value): void
    {
        $this->attributes['msme_registration_no'] = $value;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'incorporation_date' => 'date:Y-m-d',
            'authorized_capital' => 'decimal:2',
            'paid_up_capital' => 'decimal:2',
            'annual_turnover' => 'decimal:2',
            'shareholding_percentage' => 'decimal:2',
            'branches_count_manual' => 'integer',
            'employees_count_manual' => 'integer',
            'od_limit' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'additional_details' => 'array',
        ];
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function financialYears(): HasMany
    {
        return $this->hasMany(FinancialYear::class);
    }

    public function currentFinancialYear(): ?FinancialYear
    {
        return $this->financialYears()->where('is_current', true)->first();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(\App\Domains\Purchasing\Models\PurchaseOrder::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(\App\Domains\Master\Models\Customer::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function canBeDeleted(): bool
    {
        return $this->getDeleteBlockReason() === null;
    }

    public function getDeleteBlockReason(): ?string
    {
        if ($this->is_default) {
            return 'Cannot delete the default company. Please set another company as default first.';
        }

        if ($this->branches()->exists()) {
            return 'Cannot delete company because branches are linked to it.';
        }

        if ($this->warehouses()->exists()) {
            return 'Cannot delete company because warehouses are linked to it.';
        }

        if ($this->financialYears()->exists()) {
            return 'Cannot delete company because financial years are linked to it.';
        }

        if ($this->users()->exists()) {
            return 'Cannot delete company because active users are associated with it.';
        }

        if ($this->customers()->exists()) {
            return 'Cannot delete company because customers or vendors are linked to it.';
        }

        if ($this->purchaseOrders()->exists()) {
            return 'Cannot delete company because purchase orders exist for it.';
        }

        if ($this->hasLinkedRecordsInTable('acct_sales_orders') || $this->hasLinkedRecordsInTable('sales_orders') || $this->hasLinkedRecordsInTable('orders')) {
            return 'Cannot delete company because sales orders exist for it.';
        }

        if ($this->hasLinkedRecordsInTable('acct_invoices') || $this->hasLinkedRecordsInTable('invoices')) {
            return 'Cannot delete company because sales invoices exist for it.';
        }

        if ($this->hasLinkedRecordsInTable('payments')) {
            return 'Cannot delete company because payment transactions exist for it.';
        }

        if ($this->hasLinkedRecordsInTable('leads')) {
            return 'Cannot delete company because CRM leads are linked to it.';
        }

        if ($this->hasLinkedRecordsInTable('acct_vouchers')) {
            return 'Cannot delete company because accounting vouchers exist for it.';
        }

        if ($this->hasLinkedRecordsInTable('acct_ledgers')) {
            return 'Cannot delete company because accounting ledgers exist for it.';
        }

        if ($this->hasLinkedRecordsInTable('employees')) {
            return 'Cannot delete company because employees are associated with it.';
        }

        if ($this->hasLinkedRecordsInTable('departments')) {
            return 'Cannot delete company because departments are linked to it.';
        }

        return null;
    }

    protected function hasLinkedRecordsInTable(string $table, string $column = 'company_id'): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
            return false;
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn($table, $column)) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::table($table)->where($column, $this->id)->exists();
    }

    public static function defaultCompany(): ?self
    {
        return static::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public static function defaultCompanyId(): ?int
    {
        return static::defaultCompany()?->id;
    }

    public static function resolveCurrent(): ?self
    {
        $user = auth()->user();
        if ($user && $user->company_id) {
            $userCompany = static::query()->where('is_active', true)->find($user->company_id);
            if ($userCompany) {
                return $userCompany;
            }
        }

        return static::defaultCompany();
    }

    public function setAsDefault(): void
    {
        if (! $this->is_active) {
            throw new \DomainException('Cannot set an inactive company as the default company.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () {
            static::query()
                ->where('is_default', true)
                ->where('id', '!=', $this->id)
                ->lockForUpdate()
                ->update(['is_default' => false]);

            $this->update(['is_default' => true]);
        });
    }

    public function getContactsAttribute(): array
    {
        $primary = [
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'address' => $this->address,
        ];
        $additional = $this->additional_details['contacts'] ?? [];

        return array_merge([$primary], $additional);
    }

    public function odAccounts(): HasMany
    {
        return $this->hasMany(\App\Domains\Banking\Models\OdAccount::class);
    }

    public function getBankAccountsAttribute(): array
    {
        $odByAcc = $this->relationLoaded('odAccounts')
            ? $this->odAccounts->keyBy('account_number')
            : $this->odAccounts()->get()->keyBy('account_number');

        $primaryOd = filled($this->bank_account_no) ? $odByAcc->get($this->bank_account_no) : null;

        $primary = [
            'bank_name' => $this->bank_name,
            'bank_account_no' => $this->bank_account_no,
            'bank_ifsc' => $this->bank_ifsc,
            'upi_id' => $this->upi_id,
            'od_limit' => $primaryOd ? (float) $primaryOd->od_limit : (float) ($this->attributes['od_limit'] ?? 0),
            'interest_rate' => $primaryOd ? (float) $primaryOd->interest_rate : (float) ($this->attributes['interest_rate'] ?? 0),
        ];

        $additional = $this->additional_details['bank_accounts'] ?? [];
        $additionalWithOd = array_map(function ($b) use ($odByAcc) {
            $accNo = $b['bank_account_no'] ?? '';
            $od = filled($accNo) ? $odByAcc->get($accNo) : null;
            if ($od) {
                $b['od_limit'] = (float) $od->od_limit;
                $b['interest_rate'] = (float) $od->interest_rate;
            } else {
                $b['od_limit'] = isset($b['od_limit']) ? (float) $b['od_limit'] : 0.0;
                $b['interest_rate'] = isset($b['interest_rate']) ? (float) $b['interest_rate'] : 0.0;
            }

            return $b;
        }, $additional);

        return array_merge([$primary], $additionalWithOd);
    }

    /**
     * Normalized collection of non-empty company bank accounts configured in Company Profile.
     */
    public function getCompanyBankAccounts(): \Illuminate\Support\Collection
    {
        $odByAcc = $this->relationLoaded('odAccounts')
            ? $this->odAccounts->keyBy('account_number')
            : $this->odAccounts()->get()->keyBy('account_number');

        return collect($this->bank_accounts)
            ->filter(fn ($b) => is_array($b) && filled($b['bank_account_no'] ?? null))
            ->map(function ($b) use ($odByAcc) {
                $accNo = trim((string) ($b['bank_account_no'] ?? ''));
                $bankName = trim((string) ($b['bank_name'] ?? 'Bank Account'));
                $ifsc = trim((string) ($b['bank_ifsc'] ?? ''));
                $upi = trim((string) ($b['upi_id'] ?? ''));
                $od = $odByAcc->get($accNo);
                $odLimit = $od ? (float) $od->od_limit : (float) ($b['od_limit'] ?? 0);
                $interestRate = $od ? (float) $od->interest_rate : (float) ($b['interest_rate'] ?? 0);

                $maskedAcc = strlen($accNo) > 4
                    ? str_repeat('•', max(4, strlen($accNo) - 4)) . substr($accNo, -4)
                    : $accNo;

                return [
                    'account_number' => $accNo,
                    'bank_name' => $bankName,
                    'ifsc' => $ifsc,
                    'upi_id' => $upi,
                    'od_limit' => $odLimit,
                    'interest_rate' => $interestRate,
                    'masked_account' => $maskedAcc,
                    'label' => "{$bankName} — {$maskedAcc}",
                ];
            })
            ->unique('account_number')
            ->values();
    }

    public function getCleanLogoPath(): ?string
    {
        if (blank($this->logo_path)) {
            return null;
        }

        $path = ltrim((string) $this->logo_path, '/');
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        } elseif (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        return ltrim($path, '/');
    }

    public function getLogoUrlAttribute(): ?string
    {
        $cleanPath = $this->getCleanLogoPath();

        if (blank($cleanPath)) {
            return null;
        }

        if (! Storage::disk('public')->exists($cleanPath)) {
            return null;
        }

        return asset('storage/' . $cleanPath);
    }

    public function hasLogo(): bool
    {
        $cleanPath = $this->getCleanLogoPath();

        return ! blank($cleanPath) && Storage::disk('public')->exists($cleanPath);
    }
}