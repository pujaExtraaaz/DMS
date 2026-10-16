<?php

namespace App\Domains\Master\Models;

use App\Domains\Logistics\Models\LoadSheet;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryPerson extends Model
{
    use HasUppercaseNames;

    protected $table = 'delivery_persons';

    protected $fillable = [
        'name',
        'phone',
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
