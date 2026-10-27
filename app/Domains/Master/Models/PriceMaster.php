<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceMaster extends Model
{
    protected $fillable = [
        'customer_type_id',
        'product_id',
        'uom_id',
        'rate',
        'entered_rate',
        'rate_includes_gst',
        'min_qty',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'entered_rate' => 'decimal:2',
            'rate_includes_gst' => 'boolean',
            'min_qty' => 'decimal:2',
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
        $entered = round($entered, 2);
        $taxPercent = max(0, $taxPercent);

        if ($includesGst && $taxPercent > 0) {
            $exclusive = round($entered / (1 + ($taxPercent / 100)), 2);
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
        ];
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
}
