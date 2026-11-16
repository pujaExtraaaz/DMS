<?php

namespace App\Domains\Organization\Models;

use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessGroup extends Model
{
    use HasUppercaseNames;

    protected $fillable = [
        'name',
        'code',
        'proprietor_name',
        'proprietor_relationship',
        'proprietor_email',
        'proprietor_phone',
        'proprietor_pan',
        'parent_company_id',
        'incorporation_date',
        'headquarters',
        'country',
        'industry',
        'website',
        'email',
        'phone',
        'logo_emoji',
        'description',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'incorporation_date' => 'date:Y-m-d',
        ];
    }

    public function getProprietorTitleAttribute(): string
    {
        if (filled($this->proprietor_name)) {
            return $this->proprietor_name;
        }

        return $this->parentCompany?->legal_name ?: ($this->name ?: 'Vivek Gupta');
    }

    public function getProprietorRelationAttribute(): string
    {
        return $this->proprietor_relationship ?: 'Common ownership and business oversight';
    }

    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_group_links')->withTimestamps();
    }

    public function links(): HasMany
    {
        return $this->hasMany(CompanyGroupLink::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(\App\Domains\Master\Models\Customer::class);
    }

    public function isDefault(): bool
    {
        return strcasecmp(trim($this->code ?? ''), 'DEFAULT') === 0
            || strcasecmp(trim($this->name ?? ''), 'Default Group') === 0;
    }

    public function getDeleteBlockReason(): ?string
    {
        if ($this->isDefault()) {
            return 'Cannot delete the Default Group as it is a required system group.';
        }

        $attachedCount = $this->companies()->count();
        if ($attachedCount === 0 && \Illuminate\Support\Facades\Schema::hasColumn('companies', 'business_group_id')) {
            $attachedCount = Company::where('business_group_id', $this->id)->count();
        }

        if ($attachedCount > 0) {
            return "Cannot delete sister concern group because {$attachedCount} company(ies) are currently attached to it. Please reassign or detach the companies before deleting this group.";
        }

        return null;
    }

    public function canBeDeleted(): bool
    {
        return $this->getDeleteBlockReason() === null;
    }
}
