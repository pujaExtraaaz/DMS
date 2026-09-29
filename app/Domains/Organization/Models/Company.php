<?php

namespace App\Domains\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    protected $fillable = [
        'business_group_id',
        'name',
        'code',
        'logo_path',
        'legal_name',
        'gstin',
        'pan',
        'cin',
        'tan',
        'udyam_registration_no',
        'msme_category',
        'msme_registration_no',
        'address',
        'city',
        'state',
        'pincode',
        'country',
        'phone',
        'alternate_phone',
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

    /**
     * Get the accessible public URL for the company's uploaded logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        // If the path is already a full external URL
        if (filter_var($this->logo_path, FILTER_VALIDATE_URL)) {
            return $this->logo_path;
        }

        // Dedicated streaming endpoint with cache-busting
        if (Route::has('organization.companies.logo')) {
            return route('organization.companies.logo', [
                'company' => $this->id,
                'v' => optional($this->updated_at)->timestamp ?: time(),
            ]);
        }

        $cleanPath = ltrim($this->logo_path, '/');
        if (str_starts_with($cleanPath, 'public/')) {
            $cleanPath = substr($cleanPath, 7);
        }
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }

        return url('storage/'.$cleanPath);
    }

    public function getContactsAttribute(): array
    {
        $primary = [
            'phone' => $this->phone,
            'alternate_phone' => $this->alternate_phone,
            'email' => $this->email,
            'website' => $this->website,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'country' => $this->country,
        ];
        $additional = $this->additional_details['contacts'] ?? [];

        return array_merge([$primary], $additional);
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
}