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
        'external_lead_id', 'name', 'mobile', 'email', 'organization', 'city', 'state',
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

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function source(): BelongsTo { return $this->belongsTo(LeadSource::class, 'lead_source_id'); }
    public function campaign(): BelongsTo { return $this->belongsTo(LeadCampaign::class, 'lead_campaign_id'); }
    public function metaForm(): BelongsTo { return $this->belongsTo(MetaLeadForm::class, 'meta_lead_form_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function convertedCustomer(): BelongsTo { return $this->belongsTo(Customer::class, 'converted_customer_id'); }
    public function activities(): HasMany { return $this->hasMany(LeadActivity::class); }
    public function assignments(): HasMany { return $this->hasMany(LeadAssignment::class); }
    public function followups(): HasMany { return $this->hasMany(LeadFollowup::class); }
    public function conversion(): HasOne { return $this->hasOne(LeadConversion::class); }
}
