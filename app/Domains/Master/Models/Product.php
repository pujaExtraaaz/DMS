<?php

namespace App\Domains\Master\Models;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Organization\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'company_id',
        'brand_id',
        'category_id',
        'sub_category_id',
        'name',
        'sku',
        'serial_no',
        'hsn_code',
        'description',
        'specification',
        'base_uom_id',
        'tax_rate',
        'selling_price',
        'trade_price',
        'purchase_price',
        'calculation_mrp',
        'mrp_calculation_rules',
        'discount_percent',
        'discount_type',
        'discount_value',
        'selling_discount_type',
        'selling_discount_value',
        'apply_discount_on_payable',
        'warranty_months',
        'warranty_terms',
        'sender_warranty_months',
        'sender_warranty_terms',
        'customer_warranty_months',
        'customer_warranty_terms',
        'color_variant',
        'catalog_link',
        'image_path',
        'tracking_type',
        'min_stock',
        'reorder_level',
        'aging_threshold_days',
        'credit_period_days',
        'payment_period_days',
        'lifespan_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'trade_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'calculation_mrp' => 'decimal:2',
            'mrp_calculation_rules' => 'array',
            'discount_percent' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'selling_discount_value' => 'decimal:2',
            'apply_discount_on_payable' => 'boolean',
            'min_stock' => 'decimal:4',
            'reorder_level' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'base_uom_id');
    }

    public function productUoms(): HasMany
    {
        return $this->hasMany(ProductUom::class);
    }

    public function uoms(): BelongsToMany
    {
        return $this->belongsToMany(Uom::class, 'product_uoms')
            ->withPivot(['conversion_factor', 'is_base']);
    }

    public function priceMasters(): HasMany
    {
        return $this->hasMany(PriceMaster::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function isSerialTracked(): bool
    {
        return $this->tracking_type === 'serial';
    }

    public function isBatchTracked(): bool
    {
        return $this->tracking_type === 'batch';
    }
}
