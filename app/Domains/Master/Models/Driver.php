<?php

namespace App\Domains\Master\Models;

use App\Domains\Logistics\Models\LoadSheet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'license_no',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function loadSheets(): HasMany
    {
        return $this->hasMany(LoadSheet::class);
    }
}
