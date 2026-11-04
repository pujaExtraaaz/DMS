<?php

namespace App\Domains\Master\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use Illuminate\Validation\ValidationException;

class SalePricingService
{
    public function __construct(protected PriceMasterService $priceMasterService) {}

    /**
     * @param Customer $customer
     * @param array $items
     * @param float $discount
     * @param string $priceType 'exclusive' or 'inclusive'
     * @param bool $isIntraState
     * @return array{lines: array<int, array<string, mixed>>, subtotal: float, discount: float, tax: float, total: float, taxable_amount: float, cgst_amount: float, sgst_amount: float, igst_amount: float, round_off: float, grand_total: float}
     */
    public function price(
        Customer $customer,
        array $items,
        float $discount = 0,
        string $priceType = 'exclusive',
        bool $isIntraState = true
    ): array {
        $priceType = strtolower(trim($priceType)) === 'inclusive' ? 'inclusive' : 'exclusive';
        $lines = [];
        $grossSubtotal = 0.0;
        $taxableSubtotal = 0.0;
        $totalItemDiscount = 0.0;
        $totalTax = 0.0;
        $totalCgst = 0.0;
        $totalSgst = 0.0;
        $totalIgst = 0.0;

        foreach ($items as $index => $item) {
            $product = Product::findOrFail($item['product_id']);
            $uom = Uom::findOrFail($item['uom_id']);
            $quantity = (float) $item['quantity'];
            $hasManualPrice = isset($item['unit_price'])
                && $item['unit_price'] !== null
                && $item['unit_price'] !== ''
                && is_numeric($item['unit_price'])
                && (float) $item['unit_price'] >= 0;

            $unitPrice = $hasManualPrice
                ? (string) number_format((float) $item['unit_price'], 2, '.', '')
                : $this->priceMasterService->resolvePrice($customer, $product, $uom, $quantity);

            if ($unitPrice === null) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => "No active price is configured for {$product->name} and this customer's price category.",
                ]);
            }

            $enteredPrice = (float) $unitPrice;
            $taxRate = (float) ($product->tax_rate ?? 0);
            $discType = $item['discount_type'] ?? 'flat';
            $discVal = (float) ($item['discount_value'] ?? ($item['discount_amount'] ?? 0));

            $calc = GstTaxCalculationService::calculateLine(
                $quantity,
                $enteredPrice,
                $taxRate,
                $priceType,
                $discType,
                $discVal,
                $isIntraState
            );

            $grossSubtotal += $calc['gross_taxable'];
            $taxableSubtotal += $calc['taxable_amount'];
            $totalItemDiscount += $calc['discount_amount'];
            $totalTax += $calc['tax_amount'];
            $totalCgst += $calc['cgst_amount'];
            $totalSgst += $calc['sgst_amount'];
            $totalIgst += $calc['igst_amount'];

            $lines[] = array_merge($calc, [
                'product' => $product,
                'uom' => $uom,
                // Keep backward-compatible keys
                'unitPrice' => (string) number_format($calc['unit_price'], 2, '.', ''),
                'discount' => $calc['discount_amount'],
                'tax' => $calc['tax_amount'],
                'lineTotal' => $calc['line_total'],
            ]);
        }

        // Invoice-level additional discount (if passed as separate parameter, e.g. universal)
        $discount = min(max(0, $discount), $taxableSubtotal);
        if ($discount > 0 && $taxableSubtotal > 0) {
            $totalTax = 0.0;
            $totalCgst = 0.0;
            $totalSgst = 0.0;
            $totalIgst = 0.0;
            foreach ($lines as &$line) {
                $additionalDisc = round($discount * ($line['taxable_amount'] / $taxableSubtotal), 2);
                $netTaxable = max(0, $line['taxable_amount'] - $additionalDisc);
                $lineTax = round($netTaxable * ($line['tax_rate'] / 100), 2);
                $cgstAmt = $isIntraState ? round($lineTax / 2, 2) : 0.0;
                $sgstAmt = $isIntraState ? round($lineTax - $cgstAmt, 2) : 0.0;
                $igstAmt = $isIntraState ? 0.0 : $lineTax;

                $line['discount'] = round($line['discount'] + $additionalDisc, 2);
                $line['discount_amount'] = $line['discount'];
                $line['taxable_amount'] = $netTaxable;
                $line['tax'] = $lineTax;
                $line['tax_amount'] = $lineTax;
                $line['cgst_amount'] = $cgstAmt;
                $line['sgst_amount'] = $sgstAmt;
                $line['igst_amount'] = $igstAmt;
                $line['line_total'] = round($netTaxable + $lineTax, 2);
                $line['lineTotal'] = $line['line_total'];

                $totalTax += $lineTax;
                $totalCgst += $cgstAmt;
                $totalSgst += $sgstAmt;
                $totalIgst += $igstAmt;
            }
            unset($line);
            $totalItemDiscount = round($totalItemDiscount + $discount, 2);
            $taxableSubtotal = max(0, round($taxableSubtotal - $discount, 2));
        }

        $totalBeforeRound = round($taxableSubtotal + $totalTax, 2);
        $autoRound = GstTaxCalculationService::computeRoundOff($totalBeforeRound);
        $grandTotal = round($totalBeforeRound + $autoRound, 2);

        return [
            'lines' => $lines,
            'price_type' => $priceType,
            'is_intra_state' => $isIntraState,
            'subtotal' => round($taxableSubtotal, 2),
            'discount' => round($totalItemDiscount, 2),
            'tax' => round($totalTax, 2),
            'total' => round($totalBeforeRound, 2),
            'taxable_amount' => round($taxableSubtotal, 2),
            'cgst_amount' => round($totalCgst, 2),
            'sgst_amount' => round($totalSgst, 2),
            'igst_amount' => round($totalIgst, 2),
            'round_off' => $autoRound,
            'grand_total' => $grandTotal,
        ];
    }
}
