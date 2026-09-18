<?php

namespace App\Domains\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
