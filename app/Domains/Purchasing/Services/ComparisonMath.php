<?php

namespace App\Domains\Purchasing\Services;

/**
 * Effective landed cost for one vendor quote.
 *
 * Supports exclusive/inclusive GST, % or amount discount, optional cash discount,
 * free/flat/% freight, packing/insurance/other charges, and financing cost.
 */
class ComparisonMath
{
    /**
     * @param  array<string, mixed>  $quote
     * @param  array<string, mixed>  $vendorTerms  Optional vendor-level commercial defaults
     * @return array<string, float|bool|string>|null
     */
    public function measure(array $quote, array $vendorTerms = []): ?array
    {
        if (empty($quote['is_quoted'])) {
            return null;
        }

        $quantity = (float) ($quote['quoted_quantity'] ?? 0);
        $basic = (float) ($quote['basic_rate'] ?? 0);

        if ($quantity <= 0) {
            return null;
        }

        $gstRate = (float) ($quote['gst_rate'] ?? 0);
        $taxBasis = ($quote['tax_basis'] ?? 'exclusive') === 'inclusive' ? 'inclusive' : 'exclusive';

        // Normalize basic rate to tax-exclusive unit price for comparable landed cost.
        $exclusiveBasic = $taxBasis === 'inclusive' && $gstRate > 0
            ? round($basic / (1 + ($gstRate / 100)), 4)
            : round($basic, 4);

        $discountValue = (float) ($quote['discount_value'] ?? 0);
        $discountPerUnit = ($quote['discount_type'] ?? 'percent') === 'amount'
            ? $discountValue
            : $exclusiveBasic * ($discountValue / 100);
        $afterTradeDiscount = max(0, round($exclusiveBasic - $discountPerUnit, 4));

        $cashDiscountPercent = (float) ($quote['cash_discount_percent']
            ?? $vendorTerms['cash_discount_percent']
            ?? 0);
        $cashDiscountPerUnit = round($afterTradeDiscount * ($cashDiscountPercent / 100), 4);
        $netRate = max(0, round($afterTradeDiscount - $cashDiscountPerUnit, 4));
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

        $financing = (float) ($quote['financing_cost'] ?? 0);
        if ($financing <= 0) {
            $interestPercent = (float) ($vendorTerms['bank_interest_percent'] ?? 0);
            $interestDays = (int) ($vendorTerms['bank_interest_days'] ?? 0);
            if ($interestPercent > 0 && $interestDays > 0) {
                // Simple interest on goods value for the financing period.
                $financing = round($goods * ($interestPercent / 100) * ($interestDays / 365), 2);
            }
        } else {
            $financing = round($financing, 2);
        }

        $preGst = round($goods + $freight + $packing + $insurance + $other + $financing, 2);
        $gstAmount = round($preGst * ($gstRate / 100), 2);
        $landed = round($preGst + $gstAmount, 2);
        $invoiceValue = $landed;

        return [
            'tax_basis' => $taxBasis,
            'basic_rate' => round($basic, 4),
            'exclusive_basic' => $exclusiveBasic,
            'discount_per_unit' => round($discountPerUnit, 4),
            'cash_discount_per_unit' => $cashDiscountPerUnit,
            'net_rate' => $netRate,
            'goods' => $goods,
            'freight' => $freight,
            'packing' => $packing,
            'insurance' => $insurance,
            'other' => $other,
            'financing_cost' => $financing,
            'charges' => round($packing + $insurance + $other + $financing, 2),
            'taxable_value' => $preGst,
            'pre_gst' => $preGst,
            'gst_rate' => $gstRate,
            'gst_amount' => $gstAmount,
            'invoice_value' => $invoiceValue,
            'landed' => $landed,
            'per_unit' => round($landed / $quantity, 4),
        ];
    }
}
