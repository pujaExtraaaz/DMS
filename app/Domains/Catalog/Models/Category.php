<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Master\Models\Product;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasUppercaseNames;

    protected $fillable = [
        'brand_id',
        'name',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (blank($category->code)) {
                $companyId = $category->brand_id
                    ? Brand::query()->whereKey($category->brand_id)->value('company_id')
                    : null;
                $category->code = CodeGenerator::forCategory($companyId ? (int) $companyId : null);
            }
        });

        static::updating(function (Category $category) {
            if ($category->isDirty('code') && ! blank($category->getOriginal('code'))) {
                $category->code = $category->getOriginal('code');
            }
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
