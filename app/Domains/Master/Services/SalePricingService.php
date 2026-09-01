<?php

namespace App\Domains\Master\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use Illuminate\Validation\ValidationException;

class SalePricingService
{
    public function __construct(protected PriceMasterService $priceMasterService) {}

    /** @return array{lines: array<int, array<string, mixed>>, subtotal: float, discount: float, tax: float, total: float} */
    public function price(Customer $customer, array $items, float $discount = 0): array
    {
        $lines = [];
        $subtotal = 0.0;

        foreach ($items as $index => $item) {
            $product = Product::findOrFail($item['product_id']);
            $uom = Uom::findOrFail($item['uom_id']);
            $quantity = (float) $item['quantity'];
            $unitPrice = $this->priceMasterService->resolvePrice($customer, $product, $uom, $quantity);

            if ($unitPrice === null) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => "No active price is configured for {$product->name} and this customer's price category.",
                ]);
            }

            $lineTotal = round($quantity * (float) $unitPrice, 2);
            $subtotal += $lineTotal;
            $lines[] = compact('product', 'uom', 'quantity', 'unitPrice', 'lineTotal');
        }

        $discount = min(max(0, $discount), $subtotal);
        $tax = 0.0;
        foreach ($lines as &$line) {
            $lineDiscount = $subtotal > 0 ? round($discount * ($line['lineTotal'] / $subtotal), 2) : 0.0;
            $taxable = max(0, $line['lineTotal'] - $lineDiscount);
            $lineTax = round($taxable * ((float) $line['product']->tax_rate / 100), 2);
            $line['discount'] = $lineDiscount;
            $line['tax'] = $lineTax;
            $tax += $lineTax;
        }
        unset($line);

        return [
            'lines' => $lines,
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'total' => round($subtotal - $discount + $tax, 2),
        ];
    }
}
