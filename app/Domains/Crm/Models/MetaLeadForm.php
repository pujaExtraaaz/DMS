<?php

namespace App\Domains\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaLeadForm extends Model
{
    protected $fillable = ['form_id', 'form_name', 'page_id', 'lead_campaign_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(LeadCampaign::class, 'lead_campaign_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
