<?php

namespace App\Domains\Crm\Models;

use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadCampaign extends Model
{
    use HasUppercaseNames;

    protected $fillable = ['name', 'external_campaign_id', 'platform', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function forms(): HasMany
    {
        return $this->hasMany(MetaLeadForm::class);
    }
}
