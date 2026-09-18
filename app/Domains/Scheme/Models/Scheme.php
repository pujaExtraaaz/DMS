<?php

namespace App\Domains\Scheme\Models;

use App\Domains\Catalog\Models\Brand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scheme extends Model
{
    protected $fillable = [
        'code',
        'name',
        'brand_id',
        'starts_on',
        'ends_on',
        'status',
        'basis',
        'net_credit_notes',
        'notes',
        'created_by_name',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'net_credit_notes' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function slabs(): HasMany
    {
        return $this->hasMany(SchemeSlab::class)->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(SchemeProduct::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(SchemeAchievement::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(SchemeSettlement::class);
    }
}
