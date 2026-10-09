<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Integration\Concerns\HasExternalReference;
use Tally\Inventory\Quantity;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends AccountingModel
{
    /** @use HasFactory<ProductFactory> */
    use HasExternalReference, HasFactory;

    /**
     * Opening quantity, rate, and value are master data.
     * Stock movements and valuation are not posted from here.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'product_group_id',
        'primary_unit_id',
        'alternate_unit_id',
        'name',
        'code',
        'barcode',
        'hsn_sac_id',
        'tax_rate_id',
        'conversion_factor',
        'purchase_rate',
        'sales_rate',
        'opening_quantity',
        'opening_rate',
        'opening_value',
        'minimum_stock',
        'reorder_level',
        'maximum_stock',
        'track_batch',
        'track_serial',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:6',
            'purchase_rate' => 'decimal:2',
            'sales_rate' => 'decimal:2',
            'opening_quantity' => 'decimal:4',
            'opening_rate' => 'decimal:2',
            'opening_value' => 'decimal:2',
            'minimum_stock' => 'decimal:4',
            'reorder_level' => 'decimal:4',
            'maximum_stock' => 'decimal:4',
            'track_batch' => 'boolean',
            'track_serial' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    public function productGroup(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class);
    }

    public function primaryUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'primary_unit_id');
    }

    public function alternateUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'alternate_unit_id');
    }

    public function hsnSac(): BelongsTo
    {
        return $this->belongsTo(HsnSac::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function movements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function canBeDeleted(): bool
    {
        return ! $this->movements()->exists()
            && ! $this->hasMany(StockTransactionLine::class)->exists()
            && ! $this->hasMany(InvoiceLine::class)->exists()
            && ! $this->hasMany(BomLine::class)->exists()
            && ! $this->hasMany(BomByproduct::class)->exists()
            && ! $this->hasMany(BillOfMaterial::class, 'finished_product_id')->exists()
            && ! $this->hasMany(StockBatch::class)->exists()
            && ! $this->hasMany(StockSerial::class)->exists();
    }

    public static function openingValue(string $quantity, string $rate): string
    {
        return Quantity::openingValue($quantity, $rate);
    }

    public function trimmedQuantity(string $attribute): string
    {
        $value = (string) $this->getAttribute($attribute);

        if (! str_contains($value, '.')) {
            return $value;
        }

        $trimmed = rtrim(rtrim($value, '0'), '.');

        return $trimmed === '' ? '0' : $trimmed;
    }
}
