<?php

namespace App\Domains\Service\Models;

use App\Domains\Organization\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOemCentre extends Model
{
    protected $fillable = [
        'company_id', 'name', 'brand', 'contact_person', 'mobile', 'email', 'address', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(ServiceTicket::class, 'oem_centre_id');
    }
}
