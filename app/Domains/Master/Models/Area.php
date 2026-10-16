<?php

namespace App\Domains\Master\Models;

use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use HasUppercaseNames;

    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
