<?php

namespace App\Domains\Master\Models;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Organization\Models\Company;
use App\Domains\Sales\Models\InvoiceItem;
use App\Support\TitleCase;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasUppercaseNames, SoftDeletes;

    public const SUPPLY_TYPES = [
        'capital_goods' => 'Capital Goods',
        'goods' => 'Goods',
        'services' => 'Services',
    ];

    public const PURCHASE_TAX_BASES = [
        'exclusive' => 'Exclusive Tax',
        'inclusive' => 'Inclusive Tax',
    ];

    public const COSTING_METHODS = [
        'at_zero_cost' => 'At Zero Cost',
        'avg_cost' => 'Avg. Cost',
        'fifo' => 'FIFO',
        'fifo_perpetual' => 'FIFO Perpetual',
        'last_purchase_cost' => 'Last Purchase Cost',
        'lifo_annual' => 'LIFO Annual',
        'lifo_perpetual' => 'LIFO Perpetual',
        'monthly_avg_cost' => 'Monthly Avg. Cost',
        'std_cost' => 'Std. Cost',
    ];

    public const MARKET_VALUATION_METHODS = [
        'at_zero_price' => 'At Zero Price',
        'avg_price' => 'Avg. Price',
        'last_sale_price' => 'Last Sale Price',
        'std_price' => 'Std. Price',
    ];

    public const BEHAVIOUR_FLAGS = [
        'ignore_physical_count_diff' => 'Ignore difference due to physical counting',
        'ignore_negative_balances' => 'Ignore negative balances',
        'treat_sales_as_new_manufacture' => 'Treat all sales as new manufacture',
        'treat_purchases_as_consumed' => 'Treat all purchases as consumed',
        'treat_rejections_inward_as_scrap' => 'Treat all rejections inward as scrap',
    ];

    protected $fillable = [
        'company_id',
        'brand_id',
        'category_id',
        'sub_category_id',
        'name',
        'sku',
        'series',
        'model',
        'models',
        'product_type',
        'product_type_id',
        'serial_no',
        'hsn_code',
        'description',
        'specification',
        'warranty',
        'height_cm',
        'length_cm',
        'width_cm',
        'height_in',
        'length_in',
        'width_in',
        'net_weight',
        'weight_unit',
        'base_uom_id',
        'alternate_uom_id',
        'conversion_factor',
        'tax_rate',
        'selling_price',
        'trade_price',
        'purchase_price',
        'purchase_price_tax_basis',
        'supply_type',
        'costing_method',
        'market_valuation_method',
        'provide_behaviour',
        'ignore_physical_count_diff',
        'ignore_negative_balances',
        'treat_sales_as_new_manufacture',
        'treat_purchases_as_consumed',
        'treat_rejections_inward_as_scrap',
        'last_sale_price',
        'calculation_mrp',
        'alt_purchase_price',
        'alt_trade_price',
        'alt_selling_price',
        'alt_calculation_mrp',
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
            'height_cm' => 'decimal:2',
            'length_cm' => 'decimal:2',
            'width_cm' => 'decimal:2',
            'height_in' => 'decimal:2',
            'length_in' => 'decimal:2',
            'width_in' => 'decimal:2',
            'net_weight' => 'decimal:3',
            'conversion_factor' => 'decimal:4',
            'tax_rate' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'trade_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'last_sale_price' => 'decimal:2',
            'calculation_mrp' => 'decimal:2',
            'provide_behaviour' => 'boolean',
            'ignore_physical_count_diff' => 'boolean',
            'ignore_negative_balances' => 'boolean',
            'treat_sales_as_new_manufacture' => 'boolean',
            'treat_purchases_as_consumed' => 'boolean',
            'treat_rejections_inward_as_scrap' => 'boolean',
            'alt_purchase_price' => 'decimal:2',
            'alt_trade_price' => 'decimal:2',
            'alt_selling_price' => 'decimal:2',
            'alt_calculation_mrp' => 'decimal:2',
            'mrp_calculation_rules' => 'array',
            'discount_percent' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'selling_discount_value' => 'decimal:2',
            'apply_discount_on_payable' => 'boolean',
            'min_stock' => 'decimal:4',
            'reorder_level' => 'decimal:4',
            'is_active' => 'boolean',
            'warranty_months' => 'integer',
        ];
    }

    /**
     * Standardize product name to Title Case formatting.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value !== null ? TitleCase::format(trim($value)) : null,
            set: fn (?string $value) => $value !== null ? TitleCase::format(trim($value)) : null,
        );
    }

    /**
     * Provide seamless compatibility between 'model' and 'models' access.
     */
    public function getModelsAttribute(): ?string
    {
        return $this->attributes['model'] ?? null;
    }

    public function setModelsAttribute(?string $value): void
    {
        $this->attributes['model'] = $value;
    }

    public function getHeightInAttribute($value): ?float
    {
        if ($value !== null && $value !== '') {
            return (float) $value;
        }
        return $this->height_cm ? round((float) $this->height_cm / 2.54, 2) : null;
    }

    public function getLengthInAttribute($value): ?float
    {
        if ($value !== null && $value !== '') {
            return (float) $value;
        }
        return $this->length_cm ? round((float) $this->length_cm / 2.54, 2) : null;
    }

    public function getWidthInAttribute($value): ?float
    {
        if ($value !== null && $value !== '') {
            return (float) $value;
        }
        return $this->width_cm ? round((float) $this->width_cm / 2.54, 2) : null;
    }

    public function getFormattedDimensionsCmAttribute(): string
    {
        if ($this->length_cm && $this->width_cm && $this->height_cm) {
            return "{$this->length_cm} × {$this->width_cm} × {$this->height_cm} CM";
        }
        return '—';
    }

    public function getFormattedDimensionsInAttribute(): string
    {
        if ($this->length_in && $this->width_in && $this->height_in) {
            return "{$this->length_in} × {$this->width_in} × {$this->height_in} in";
        }
        return '—';
    }

    public function usesBehaviour(string $flag): bool
    {
        if (! $this->provide_behaviour || ! array_key_exists($flag, self::BEHAVIOUR_FLAGS)) {
            return false;
        }

        return (bool) $this->{$flag};
    }

    public function costingMethod(): ?string
    {
        $method = $this->costing_method;

        return is_string($method) && array_key_exists($method, self::COSTING_METHODS) ? $method : null;
    }

    /**
     * Purchase price with tax removed when the stored price is tax-inclusive.
     */
    public function exclusivePurchasePrice(): float
    {
        $price = (float) ($this->purchase_price ?? 0);
        if ($this->purchase_price_tax_basis !== 'inclusive') {
            return $price;
        }

        $rate = (float) ($this->tax_rate ?? 0);
        if ($rate <= 0) {
            return $price;
        }

        return round($price / (1 + ($rate / 100)), 4);
    }

    public function marketUnitPrice(?float $averageSalePrice = null): float
    {
        return match ($this->market_valuation_method) {
            'at_zero_price' => 0.0,
            'last_sale_price' => (float) ($this->last_sale_price ?? $this->selling_price ?? 0),
            'std_price' => (float) ($this->selling_price ?? 0),
            default => $averageSalePrice ?? $this->averageSalePrice(),
        };
    }

    public function averageSalePrice(): float
    {
        $avg = InvoiceItem::query()->where('product_id', $this->id)->avg('unit_price');

        return $avg !== null ? round((float) $avg, 4) : (float) ($this->selling_price ?? 0);
    }

    /**
     * Determine if this product has historical transactions or inventory records.
     */
    public function hasHistoricalRecords(): bool
    {
        return \App\Domains\Order\Models\OrderItem::where('product_id', $this->id)->exists()
            || \App\Domains\Purchasing\Models\PurchaseOrderItem::where('product_id', $this->id)->exists()
            || \App\Domains\Purchasing\Models\PurchaseInwardItem::where('product_id', $this->id)->exists()
            || \App\Domains\Purchasing\Models\PurchaseInvoiceItem::where('product_id', $this->id)->exists()
            || \App\Domains\Sales\Models\InvoiceItem::where('product_id', $this->id)->exists()
            || \App\Domains\Inventory\Models\StockMovement::where('product_id', $this->id)->exists()
            || \App\Domains\Inventory\Models\ProductSerial::where('product_id', $this->id)->exists()
            || \App\Domains\Inventory\Models\ProductBatch::where('product_id', $this->id)->exists()
            || \App\Domains\Inventory\Models\StockLevel::where('product_id', $this->id)->where('quantity', '!=', 0)->exists();
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

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'base_uom_id');
    }

    public function alternateUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'alternate_uom_id');
    }

    public function productUoms(): HasMany
    {
        return $this->hasMany(ProductUom::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(\App\Domains\Inventory\Models\ProductSerial::class);
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
        if (is_array($this->tracking_type)) {
            return in_array('serial', $this->tracking_type, true);
        }

        return in_array('serial', explode(',', (string) $this->tracking_type), true)
            || $this->tracking_type === 'serial';
    }

    public function isBatchTracked(): bool
    {
        if (is_array($this->tracking_type)) {
            return in_array('batch', $this->tracking_type, true);
        }

        return in_array('batch', explode(',', (string) $this->tracking_type), true)
            || $this->tracking_type === 'batch';
    }

    public function getTrackingTypesAttribute(): array
    {
        $val = $this->tracking_type;
        if (is_array($val)) {
            return array_values(array_filter($val));
        }
        if (empty($val) || $val === 'none') {
            return [];
        }
        if (str_starts_with((string) $val, '[') && str_ends_with((string) $val, ']')) {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded));
            }
        }
        return array_values(array_filter(array_map('trim', explode(',', (string) $val))));
    }
}