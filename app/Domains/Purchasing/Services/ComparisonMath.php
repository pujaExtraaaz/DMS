<?php

namespace App\Domains\Purchasing\Services;

/**
 * Effective landed cost for one vendor quote.
 * Discount amount is per unit. Freight, packing, insurance, and other charges
 * are added before GST. Free freight adds nothing.
 */
class ComparisonMath
{
    /**
     * @param  array<string, mixed>  $quote
     * @return array<string, float|bool>|null
     */
    public function measure(array $quote): ?array
    {
        if (empty($quote['is_quoted'])) {
            return null;
        }

        $quantity = (float) ($quote['quoted_quantity'] ?? 0);
        $basic = (float) ($quote['basic_rate'] ?? 0);

        if ($quantity <= 0) {
            return null;
        }

        $discountValue = (float) ($quote['discount_value'] ?? 0);
        $discountPerUnit = ($quote['discount_type'] ?? 'percent') === 'amount'
            ? $discountValue
            : $basic * ($discountValue / 100);
        $netRate = max(0, round($basic - $discountPerUnit, 4));
        $goods = round($netRate * $quantity, 2);

        $freight = 0.0;
        if (($quote['freight_type'] ?? 'free') === 'extra') {
            $freightValue = (float) ($quote['freight_value'] ?? 0);
            $freight = ($quote['freight_mode'] ?? 'amount') === 'percent'
                ? round($goods * ($freightValue / 100), 2)
                : round($freightValue, 2);
        }

        $packing = round((float) ($quote['packing_charges'] ?? 0), 2);
        $insurance = round((float) ($quote['insurance_charges'] ?? 0), 2);
        $other = round((float) ($quote['other_charges'] ?? 0), 2);
        $preGst = round($goods + $freight + $packing + $insurance + $other, 2);
        $gstRate = (float) ($quote['gst_rate'] ?? 0);
        $gstAmount = round($preGst * ($gstRate / 100), 2);
        $landed = round($preGst + $gstAmount, 2);

        return [
            'basic_rate' => round($basic, 4),
            'discount_per_unit' => round($discountPerUnit, 4),
            'net_rate' => $netRate,
            'goods' => $goods,
            'freight' => $freight,
            'charges' => round($packing + $insurance + $other, 2),
            'pre_gst' => $preGst,
            'gst_rate' => $gstRate,
            'gst_amount' => $gstAmount,
            'landed' => $landed,
            'per_unit' => round($landed / $quantity, 4),
        ];
    }
}
