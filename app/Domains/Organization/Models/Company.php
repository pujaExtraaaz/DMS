<?php

namespace App\Domains\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
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
        'additional_details',
        'purchase_terms_and_conditions',
        'selling_terms_and_conditions',
        'due_date_basis',
        'logo_path',
        'is_active',  
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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
        $primary = [
            'bank_name' => $this->bank_name,
            'bank_account_no' => $this->bank_account_no,
            'bank_ifsc' => $this->bank_ifsc,
            'upi_id' => $this->upi_id,
        ];
        $additional = $this->additional_details['bank_accounts'] ?? [];

        return array_merge([$primary], $additional);
    }

    /**
     * Normalized collection of non-empty company bank accounts configured in Company Profile.
     */
    public function getCompanyBankAccounts(): \Illuminate\Support\Collection
    {
        return collect($this->bank_accounts)
            ->filter(fn ($b) => is_array($b) && filled($b['bank_account_no'] ?? null))
            ->map(function ($b) {
                $accNo = trim((string) ($b['bank_account_no'] ?? ''));
                $bankName = trim((string) ($b['bank_name'] ?? 'Bank Account'));
                $ifsc = trim((string) ($b['bank_ifsc'] ?? ''));
                $upi = trim((string) ($b['upi_id'] ?? ''));

                return [
                    'account_number' => $accNo,
                    'bank_name' => $bankName,
                    'ifsc' => $ifsc,
                    'upi_id' => $upi,
                    'label' => "{$bankName} - {$accNo}",
                ];
            })
            ->unique('account_number')
            ->values();
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (blank($this->logo_path)) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        return asset('storage/' . $this->logo_path);
    }

    public function hasLogo(): bool
    {
        return ! blank($this->logo_path) && Storage::disk('public')->exists($this->logo_path);
    }
}