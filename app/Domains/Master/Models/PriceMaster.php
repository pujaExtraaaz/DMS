<?php

namespace App\Domains\Master\Models;

use App\Domains\Catalog\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceMaster extends Model
{
    public const TYPE_CUSTOMER_TYPE = 'customer_type';
    public const TYPE_BRAND = 'brand';

    protected $fillable = [
        'type',
        'name',
        'customer_type_id',
        'brand_id',
        'product_id',
        'uom_id',
        'rate',
        'entered_rate',
        'rate_includes_gst',
        'dealer_price',
        'dealer_price_entered',
        'dealer_price_gst_mode',
        'retail_price',
        'retail_price_entered',
        'retail_price_gst_mode',
        'min_qty',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'entered_rate' => 'decimal:2',
            'rate_includes_gst' => 'boolean',
            'dealer_price' => 'decimal:2',
            'dealer_price_entered' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'retail_price_entered' => 'decimal:2',
            'min_qty' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->rate === null) {
                if ($model->dealer_price !== null) {
                    $model->rate = $model->dealer_price;
                } elseif ($model->retail_price !== null) {
                    $model->rate = $model->retail_price;
                } else {
                    $model->rate = 0.0;
                }
            }
        });
    }

    /**
     * Calculate tax breakdown for a price based on GST mode and tax rate.
     *
     * @return array{entered: ?float, exclusive: ?float, gst: ?float, final: ?float, tax_percent: float, gst_mode: string}
     */
    public static function calculateTaxBreakdown(?float $entered, string $gstMode, float $taxPercent): array
    {
        if ($entered === null) {
            return [
                'entered' => null,
                'exclusive' => null,
                'gst' => null,
                'final' => null,
                'tax_percent' => max(0, $taxPercent),
                'gst_mode' => $gstMode,
            ];
        }

        $entered = round($entered, 2);
        $taxPercent = max(0, $taxPercent);
        $isInclusive = strtolower($gstMode) === 'inclusive';

        if ($isInclusive && $taxPercent > 0) {
            $exclusive = round($entered * 100 / (100 + $taxPercent), 2);
            $gst = round($entered - $exclusive, 2);
            $final = $entered;
        } else {
            $exclusive = $entered;
            $gst = round($exclusive * $taxPercent / 100, 2);
            $final = round($exclusive + $gst, 2);
        }

        return [
            'entered' => $entered,
            'exclusive' => $exclusive,
            'gst' => $gst,
            'final' => $final,
            'tax_percent' => $taxPercent,
            'gst_mode' => $isInclusive ? 'inclusive' : 'exclusive',
        ];
    }

    /**
     * Split an entered price using the product GST percent.
     * `rate` stored for billing stays exclusive of GST.
     *
     * @return array{entered: float, exclusive: float, gst: float, final: float, tax_percent: float}
     */
    public static function splitRate(float $entered, bool $includesGst, float $taxPercent): array
    {
        $breakdown = self::calculateTaxBreakdown($entered, $includesGst ? 'inclusive' : 'exclusive', $taxPercent);

        return [
            'entered' => (float) $breakdown['entered'],
            'exclusive' => (float) $breakdown['exclusive'],
            'gst' => (float) $breakdown['gst'],
            'final' => (float) $breakdown['final'],
            'tax_percent' => $breakdown['tax_percent'],
        ];
    }

    /**
     * @return array{entered: ?float, exclusive: ?float, gst: ?float, final: ?float, tax_percent: float, gst_mode: string}
     */
    public function dealerPricingSummary(): array
    {
        $tax = (float) ($this->product?->tax_rate ?? 0);
        $entered = $this->dealer_price_entered !== null
            ? (float) $this->dealer_price_entered
            : ($this->dealer_price !== null ? (float) $this->dealer_price : ($this->entered_rate !== null ? (float) $this->entered_rate : ($this->rate !== null ? (float) $this->rate : null)));
        $mode = $this->dealer_price_gst_mode ?: ($this->rate_includes_gst ? 'inclusive' : 'exclusive');

        return self::calculateTaxBreakdown($entered, $mode, $tax);
    }

    /**
     * @return array{entered: ?float, exclusive: ?float, gst: ?float, final: ?float, tax_percent: float, gst_mode: string}
     */
    public function retailPricingSummary(): array
    {
        $tax = (float) ($this->product?->tax_rate ?? 0);
        $entered = $this->retail_price_entered !== null
            ? (float) $this->retail_price_entered
            : ($this->retail_price !== null ? (float) $this->retail_price : null);
        $mode = $this->retail_price_gst_mode ?: 'exclusive';

        return self::calculateTaxBreakdown($entered, $mode, $tax);
    }

    /**
     * @return array{entered: float, exclusive: float, gst: float, final: float, tax_percent: float, includes_gst: bool}
     */
    public function pricingSummary(): array
    {
        $tax = (float) ($this->product?->tax_rate ?? 0);
        $exclusive = round((float) $this->rate, 2);
        $entered = round((float) ($this->entered_rate ?? $this->rate), 2);
        $includes = (bool) $this->rate_includes_gst;

        if ($includes) {
            $gst = round($entered - $exclusive, 2);
            $final = $entered;
        } else {
            $gst = round($exclusive * max(0, $tax) / 100, 2);
            $final = round($exclusive + $gst, 2);
            $entered = $exclusive;
        }

        return [
            'entered' => $entered,
            'exclusive' => $exclusive,
            'gst' => $gst,
            'final' => $final,
            'tax_percent' => $tax,
            'includes_gst' => $includes,
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function isBrandWise(): bool
    {
        return $this->type === self::TYPE_BRAND;
    }

    public function isCustomerTypeWise(): bool
    {
        return $this->type === self::TYPE_CUSTOMER_TYPE || empty($this->type);
    }

    public function scopeCustomerTypeWise(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('price_masters.type', self::TYPE_CUSTOMER_TYPE)
                ->orWhereNull('price_masters.type');
        });
    }

    public function scopeBrandWise(Builder $query): Builder
    {
        return $query->where('price_masters.type', self::TYPE_BRAND);
    }
}
