<?php

namespace App\Domains\Hrms\Models;

use App\Domains\Master\Models\PartyContact;
use App\Domains\Organization\Models\Company;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    use HasUppercaseNames;

    protected $fillable = ['company_id', 'name', 'code', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function partyContacts(): HasMany
    {
        return $this->hasMany(PartyContact::class, 'designation_id');
    }
}
