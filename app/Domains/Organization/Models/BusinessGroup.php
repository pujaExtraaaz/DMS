<?php

namespace App\Domains\Organization\Models;

use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessGroup extends Model
{
    use HasUppercaseNames;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_group_links')->withTimestamps();
    }

    public function links(): HasMany
    {
        return $this->hasMany(CompanyGroupLink::class);
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
