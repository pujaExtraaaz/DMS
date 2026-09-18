<?php

namespace App\Domains\Master\Services;

use App\Domains\Master\Models\Product;

/**
 * Resolves discount amounts for product line items.
 *
 * The DMS supports two discount planes on a product:
 *   - `discount_type` / `discount_value`          → the product-master discount ("earned" discount).
 *   - `selling_discount_type` / `selling_discount_value` → the default offered at billing time.
 *
 * A universal (header-level) discount can be added on top by the invoice/order flow.
 * `apply_discount_on_payable` = true means the product discount should be subtracted
 * from the net payable automatically without the operator touching the line.
 */
class ProductDiscountService
{
    /**
     * Compute the *line-level* discount amount for a product.
     *
     * @param  float|null  $overrideValue  Manual override (in same currency); if provided, wins.
     * @param  string|null $overrideType   'percent' | 'flat'; only honoured with $overrideValue.
     */
    public function lineDiscount(
        Product $product,
        float $quantity,
        float $unitPrice,
        ?float $overrideValue = null,
        ?string $overrideType = null,
        bool $preferSellingTime = true,
    ): float {
        $gross = round($quantity * $unitPrice, 2);

        [$type, $value] = $this->resolvePreferred($product, $overrideValue, $overrideType, $preferSellingTime);

        if ($value <= 0) {
            return 0.0;
        }

        return $type === 'flat'
            ? min($gross, round($value * max($quantity, 1), 2))
            : round($gross * ($value / 100), 2);
    }

    /**
     * Break down a line into gross, discount and net for previews.
     *
     * @return array{gross: float, discount: float, net: float, type: string, value: float}
     */
    public function breakdown(
        Product $product,
        float $quantity,
        float $unitPrice,
        ?float $overrideValue = null,
        ?string $overrideType = null,
        bool $preferSellingTime = true,
    ): array {
        $gross = round($quantity * $unitPrice, 2);
        [$type, $value] = $this->resolvePreferred($product, $overrideValue, $overrideType, $preferSellingTime);
        $discount = $this->lineDiscount($product, $quantity, $unitPrice, $overrideValue, $overrideType, $preferSellingTime);

        return [
            'gross' => $gross,
            'discount' => $discount,
            'net' => round($gross - $discount, 2),
            'type' => $type,
            'value' => $value,
        ];
    }

    /**
     * Header-level universal discount applied on the invoice/order subtotal.
     */
    public function universalDiscount(float $subTotal, string $type, float $value): float
    {
        if ($value <= 0 || $subTotal <= 0) {
            return 0.0;
        }

        return $type === 'flat'
            ? min($subTotal, round($value, 2))
            : round($subTotal * ($value / 100), 2);
    }

    /**
     * @return array{0: string, 1: float}
     */
    protected function resolvePreferred(
        Product $product,
        ?float $overrideValue,
        ?string $overrideType,
        bool $preferSellingTime,
    ): array {
        if ($overrideValue !== null) {
            return [$overrideType ?: 'percent', (float) $overrideValue];
        }

        if ($preferSellingTime && (float) ($product->selling_discount_value ?? 0) > 0) {
            return [$product->selling_discount_type ?? 'percent', (float) $product->selling_discount_value];
        }

        if ((float) ($product->discount_value ?? 0) > 0) {
            return [$product->discount_type ?? 'percent', (float) $product->discount_value];
        }

        // Legacy fallback: discount_percent is still populated on older records.
        if ((float) ($product->discount_percent ?? 0) > 0) {
            return ['percent', (float) $product->discount_percent];
        }

        return ['percent', 0.0];
    }
}
