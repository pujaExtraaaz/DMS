<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Company;
use App\Support\CodeGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'detail',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Brand $brand) {
            if (blank($brand->code)) {
                $brand->code = CodeGenerator::forBrand($brand->company_id);
            }
        });

        static::updating(function (Brand $brand) {
            // Preserve existing codes on update
            if ($brand->isDirty('code') && !blank($brand->getOriginal('code'))) {
                $brand->code = $brand->getOriginal('code');
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
