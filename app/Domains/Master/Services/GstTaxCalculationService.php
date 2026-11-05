<?php

namespace App\Domains\Master\Services;

class GstTaxCalculationService
{
    /**
     * Determine whether a transaction is intra-state (CGST + SGST) or inter-state (IGST).
     */
    public static function isIntraState(?string $sellerState, ?string $placeOfSupplyState): bool
    {
        $seller = self::normalizeState($sellerState);
        $pos = self::normalizeState($placeOfSupplyState);

        if ($seller === '' || $pos === '') {
            // Default to intra-state if unresolvable, but keep unconfirmed
            return true;
        }

        return $seller === $pos;
    }

    /**
     * Resolve the seller's state from Company / Branch.
     */
    public static function resolveSellerState(?object $company, ?object $branch = null): string
    {
        if ($branch && ! empty($branch->state)) {
            return trim((string) $branch->state);
        }

        if ($company && ! empty($company->state)) {
            return trim((string) $company->state);
        }

        return '';
    }

    /**
     * Calculate line tax and taxable value for given entered price, quantity, tax rate, and price type.
     *
     * @param float $quantity
     * @param float $enteredUnitPrice Unit price entered by user (can be inclusive or exclusive)
     * @param float $taxRate Total GST percentage (e.g. 18.0)
     * @param string $priceType 'exclusive' or 'inclusive'
     * @param string $discountType 'flat' or 'percent'
     * @param float $discountValue
     * @param bool $isIntraState
     * @return array
     */
    public static function calculateLine(
        float $quantity,
        float $enteredUnitPrice,
        float $taxRate,
        string $priceType = 'exclusive',
        string $discountType = 'flat',
        float $discountValue = 0,
        bool $isIntraState = true
    ): array {
        $quantity = max(0.0, $quantity);
        $enteredUnitPrice = max(0.0, $enteredUnitPrice);
        $taxRate = max(0.0, $taxRate);
        $priceType = strtolower(trim($priceType)) === 'inclusive' ? 'inclusive' : 'exclusive';

        if ($priceType === 'inclusive') {
            // Entered unit price includes GST.
            // When taxRate is 18%, taxable unit price = entered / (1 + 0.18)
            $taxableUnitPrice = $taxRate > 0
                ? round($enteredUnitPrice * 100 / (100 + $taxRate), 4)
                : $enteredUnitPrice;

            $grossTaxable = round($quantity * $taxableUnitPrice, 2);

            // Discount calculation on gross taxable
            $lineDiscount = 0.0;
            if ($discountValue > 0) {
                if ($discountType === 'percent') {
                    $lineDiscount = round($grossTaxable * ($discountValue / 100), 2);
                } else {
                    $lineDiscount = round(min($grossTaxable, $discountValue * max($quantity, 1.0)), 2);
                }
            }
            $lineTaxable = max(0.0, round($grossTaxable - $lineDiscount, 2));

            // Tax calculation
            $lineTax = round($lineTaxable * ($taxRate / 100), 2);
            $lineTotal = round($lineTaxable + $lineTax, 2);

            // Rate breakdowns
            $cgstRate = $isIntraState ? round($taxRate / 2, 2) : 0.0;
            $sgstRate = $isIntraState ? round($taxRate / 2, 2) : 0.0;
            $igstRate = $isIntraState ? 0.0 : $taxRate;

            $cgstAmount = $isIntraState ? round($lineTax / 2, 2) : 0.0;
            $sgstAmount = $isIntraState ? round($lineTax - $cgstAmount, 2) : 0.0;
            $igstAmount = $isIntraState ? 0.0 : $lineTax;

            return [
                'price_type' => 'inclusive',
                'entered_unit_price' => $enteredUnitPrice,
                'unit_price' => $taxableUnitPrice,
                'quantity' => $quantity,
                'tax_rate' => $taxRate,
                'gross_taxable' => $grossTaxable,
                'discount_amount' => $lineDiscount,
                'taxable_amount' => $lineTaxable,
                'cgst_rate' => $cgstRate,
                'sgst_rate' => $sgstRate,
                'igst_rate' => $igstRate,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'igst_amount' => $igstAmount,
                'tax_amount' => $lineTax,
                'line_total' => $lineTotal,
            ];
        }

        // Tax Exclusive: entered unit price is base taxable price
        $taxableUnitPrice = $enteredUnitPrice;
        $grossTaxable = round($quantity * $taxableUnitPrice, 2);

        $lineDiscount = 0.0;
        if ($discountValue > 0) {
            if ($discountType === 'percent') {
                $lineDiscount = round($grossTaxable * ($discountValue / 100), 2);
            } else {
                $lineDiscount = round(min($grossTaxable, $discountValue * max($quantity, 1.0)), 2);
            }
        }
        $lineTaxable = max(0.0, round($grossTaxable - $lineDiscount, 2));

        $lineTax = round($lineTaxable * ($taxRate / 100), 2);
        $lineTotal = round($lineTaxable + $lineTax, 2);

        $cgstRate = $isIntraState ? round($taxRate / 2, 2) : 0.0;
        $sgstRate = $isIntraState ? round($taxRate / 2, 2) : 0.0;
        $igstRate = $isIntraState ? 0.0 : $taxRate;

        $cgstAmount = $isIntraState ? round($lineTax / 2, 2) : 0.0;
        $sgstAmount = $isIntraState ? round($lineTax - $cgstAmount, 2) : 0.0;
        $igstAmount = $isIntraState ? 0.0 : $lineTax;

        return [
            'price_type' => 'exclusive',
            'entered_unit_price' => $enteredUnitPrice,
            'unit_price' => $taxableUnitPrice,
            'quantity' => $quantity,
            'tax_rate' => $taxRate,
            'gross_taxable' => $grossTaxable,
            'discount_amount' => $lineDiscount,
            'taxable_amount' => $lineTaxable,
            'cgst_rate' => $cgstRate,
            'sgst_rate' => $sgstRate,
            'igst_rate' => $igstRate,
            'cgst_amount' => $cgstAmount,
            'sgst_amount' => $sgstAmount,
            'igst_amount' => $igstAmount,
            'tax_amount' => $lineTax,
            'line_total' => $lineTotal,
        ];
    }

    /**
     * Compute automatic round off amount for a given total before round off.
     */
    public static function computeRoundOff(float $totalBeforeRoundOff): float
    {
        $rounded = round($totalBeforeRoundOff);
        return round($rounded - $totalBeforeRoundOff, 2);
    }

    /**
     * Normalize state strings for reliable comparison.
     */
    public static function normalizeState(?string $state): string
    {
        if ($state === null) {
            return '';
        }

        $s = strtolower(trim($state));
        // Remove common punctuation or prefixes if any
        $s = preg_replace('/[^a-z0-9]/', '', $s);

        return $s;
    }
}

