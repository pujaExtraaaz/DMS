<?php

namespace Tally\Inventory;

use Tally\Accounting\Money;
use Tally\Models\Product;
use Tally\Models\StockMovement;

/**
 * Average cost from opening value plus movement value, over on-hand quantity.
 * This is the valuation already used by the stock valuation report.
 */
class InventoryValuation
{
    /**
     * @return array{0: int, 1: int} scaled quantity, value in cents
     */
    public function position(Product $product): array
    {
        $quantity = Quantity::scale((string) $product->opening_quantity, 4);
        $value = Money::cents((string) $product->opening_value);

        StockMovement::query()
            ->where('product_id', $product->id)
            ->select(['id', 'quantity', 'value'])
            ->chunkById(500, function ($rows) use (&$quantity, &$value): void {
                foreach ($rows as $row) {
                    $quantity += Quantity::signedScale((string) $row->quantity, 4);
                    $value += Money::cents((string) $row->value);
                }
            });

        return [$quantity, $value];
    }

    public function averageRate(Product $product): string
    {
        [$quantity, $value] = $this->position($product);

        if ($quantity > 0 && $value > 0) {
            return Money::format(intdiv($value * 10000, $quantity));
        }

        return Money::format(max(0, Money::cents((string) $product->purchase_rate)));
    }
}
