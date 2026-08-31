<?php

namespace App\Domains\Master\Models;

use App\Domains\Logistics\Models\LoadSheet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'name',
        'registration_no',
        'type',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function loadSheets(): HasMany
    {
        return $this->hasMany(LoadSheet::class);
    }
}
