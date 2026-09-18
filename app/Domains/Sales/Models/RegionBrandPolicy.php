<?php

namespace App\Domains\Sales\Models;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Area;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionBrandPolicy extends Model
{
    protected $fillable = [
        'area_id',
        'brand_id',
        'is_allowed',
        'max_discount_percent',
        'requires_approval',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_allowed' => 'boolean',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'max_discount_percent' => 'decimal:2',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
