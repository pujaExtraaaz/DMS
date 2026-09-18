<?php

namespace App\Domains\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessGroup extends Model
{
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
}
