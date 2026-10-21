<?php

namespace App\Domains\Master\Models;

use App\Domains\Organization\Models\Company;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductType extends Model
{
    use HasUppercaseNames;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'is_active',
        'track_serial',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'track_serial' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

