<?php

namespace App\Domains\Master\Models;

use App\Domains\Catalog\Models\Brand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartyGemAuthorization extends Model
{
    protected $fillable = [
        'customer_id',
        'brand_id',
        'gem_registered',
        'gem_authorized',
        'authorization_number',
    ];

    protected function casts(): array
    {
        return [
            'gem_registered' => 'boolean',
            'gem_authorized' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PartyGemDocument::class);
    }
}
