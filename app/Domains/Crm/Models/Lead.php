<?php

namespace App\Domains\Crm\Models;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use App\Support\Concerns\ScopeByBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    use ScopeByBranch;

    protected $fillable = [
        'company_id', 'branch_id', 'lead_source_id', 'lead_campaign_id', 'meta_lead_form_id',
        'external_lead_id', 'name', 'title', 'company_name', 'organization', 'mobile', 'secondary_mobile',
        'phone', 'landline', 'email', 'secondary_email', 'street', 'city', 'state', 'zip',
        'tag', 'sub_category_id', 'sub_category',
        'interested_product', 'priority', 'status', 'assigned_to', 'last_contacted_at',
        'next_followup_at', 'lead_score', 'lost_reason', 'converted_customer_id',
        'converted_at', 'meta_payload', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_contacted_at' => 'datetime',
            'next_followup_at' => 'datetime',
            'converted_at' => 'datetime',
            'meta_payload' => 'array',
            'lead_score' => 'decimal:2',
        ];
    }

    public function setCompanyNameAttribute($value): void
    {
        $this->attributes['company_name'] = $value;
        if (blank($this->attributes['organization'] ?? null) && filled($value)) {
            $this->attributes['organization'] = $value;
        }
    }

    public function setOrganizationAttribute($value): void
    {
        $this->attributes['organization'] = $value;
        if (blank($this->attributes['company_name'] ?? null) && filled($value)) {
            $this->attributes['company_name'] = $value;
        }
    }

    public function getCompanyNameAttribute(): ?string
    {
        return $this->attributes['company_name'] ?? $this->attributes['organization'] ?? null;
    }

    public function getOrganizationAttribute(): ?string
    {
        return $this->attributes['organization'] ?? $this->attributes['company_name'] ?? null;
    }

    public function getContactNameAttribute(): ?string
    {
        return $this->name;
    }

    public function setContactNameAttribute($value): void
    {
        $this->name = $value;
    }

    public function getMailingStreetAttribute(): ?string
    {
        return $this->street;
    }

    public function setMailingStreetAttribute($value): void
    {
        $this->street = $value;
    }

    public function getMailingCityAttribute(): ?string
    {
        return $this->city;
    }

    public function setMailingCityAttribute($value): void
    {
        $this->city = $value;
    }

    public function getMailingStateAttribute(): ?string
    {
        return $this->state;
    }

    public function setMailingStateAttribute($value): void
    {
        $this->state = $value;
    }

    public function getMailingZipAttribute(): ?string
    {
        return $this->zip;
    }

    public function setMailingZipAttribute($value): void
    {
        $this->zip = $value;
    }

    public function getSecondMobileNumberAttribute(): ?string
    {
        return $this->secondary_mobile;
    }

    public function setSecondMobileNumberAttribute($value): void
    {
        $this->secondary_mobile = $value;
    }

    public function getSecndMobAttribute(): ?string
    {
        return $this->secondary_mobile;
    }

    public function setSecndMobAttribute($value): void
    {
        $this->secondary_mobile = $value;
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function source(): BelongsTo { return $this->belongsTo(LeadSource::class, 'lead_source_id'); }
    public function campaign(): BelongsTo { return $this->belongsTo(LeadCampaign::class, 'lead_campaign_id'); }
    public function metaForm(): BelongsTo { return $this->belongsTo(MetaLeadForm::class, 'meta_lead_form_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function salesperson(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function subCategory(): BelongsTo { return $this->belongsTo(\App\Domains\Catalog\Models\SubCategory::class, 'sub_category_id'); }
    public function convertedCustomer(): BelongsTo { return $this->belongsTo(Customer::class, 'converted_customer_id'); }
    public function activities(): HasMany { return $this->hasMany(LeadActivity::class); }
    public function assignments(): HasMany { return $this->hasMany(LeadAssignment::class); }
    public function followups(): HasMany { return $this->hasMany(LeadFollowup::class); }
    public function conversion(): HasOne { return $this->hasOne(LeadConversion::class); }
}
