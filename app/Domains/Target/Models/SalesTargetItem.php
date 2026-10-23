<?php

namespace App\Domains\Target\Models;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTargetItem extends Model
{
    protected $table = 'sales_target_items';

    protected $fillable = [
        'sales_target_id',
        'target_type',
        'brand_id',
        'product_id',
        'sku',
        'category_id',
        'sub_category_id',
        'target_amount',
        'target_quantity',
        'achieved_amount',
        'achieved_quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'target_quantity' => 'decimal:4',
            'achieved_amount' => 'decimal:2',
            'achieved_quantity' => 'decimal:4',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(SalesTarget::class, 'sales_target_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }
}

