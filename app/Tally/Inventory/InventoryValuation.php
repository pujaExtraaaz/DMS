<?php

namespace Tally\Inventory;

use Tally\Accounting\Money;
use Tally\Models\Company;
use Tally\Models\Product;
use Tally\Models\StockMovement;

/**
 * Average cost from opening value plus movement value, over on-hand quantity.
 * When the company uses FIFO, on-hand value comes from remaining cost layers.
 *
 * Company.inventory_valuation_method may be weighted_average (default) or fifo.
 */
class InventoryValuation
{
    public const METHOD_WEIGHTED_AVERAGE = 'weighted_average';

    public const METHOD_FIFO = 'fifo';

    public function __construct(
        private readonly FifoCostService $fifo,
    ) {}

    /**
     * Configured valuation method for the company (defaults to weighted_average).
     */
    public function methodFor(Company $company): string
    {
        $method = (string) ($company->inventory_valuation_method ?? self::METHOD_WEIGHTED_AVERAGE);

        return in_array($method, [self::METHOD_WEIGHTED_AVERAGE, self::METHOD_FIFO], true)
            ? $method
            : self::METHOD_WEIGHTED_AVERAGE;
    }

    /**
     * Human-readable note about how rates are derived for this company.
     */
    public function notesFor(Company $company): string
    {
        return match ($this->methodFor($company)) {
            self::METHOD_FIFO => 'FIFO: inventory value from remaining cost layers (oldest issued first).',
            default => 'Weighted average: opening value plus movement value ÷ on-hand quantity.',
        };
    }

    /**
     * @return array{0: int, 1: int} scaled quantity, value in cents
     */
    public function position(Product $product): array
    {
        $product->loadMissing('company');
        $company = $product->company;

        if ($company && $this->methodFor($company) === self::METHOD_FIFO) {
            $quantity = $this->fifo->remainingScaled($company, $product, null);
            $value = Money::cents($this->fifo->inventoryValue($company, $product, null));

            return [$quantity, $value];
        }

        $quantity = Quantity::scale((string) $product->opening_quantity, 4);
        $value = Money::cents((string) $product->opening_value);

        StockMovement::query()
            ->where('product_id', $product->id)
            ->withoutPlacedOpening()
            ->select(['id', 'quantity', 'value'])
            ->chunkById(500, function ($rows) use (&$quantity, &$value): void {
                foreach ($rows as $row) {
                    $quantity += Quantity::signedScale((string) $row->quantity, 4);
                    $value += Money::cents((string) $row->value);
                }
            });

        return [$quantity, $value];
    }

    /**
     * Stock valuation snapshot for the product under the company's method.
     *
     * @return array{quantity: string, value: string, rate: string, method: string}
     */
    public function valuation(Product $product): array
    {
        $product->loadMissing('company');
        $method = $product->company
            ? $this->methodFor($product->company)
            : self::METHOD_WEIGHTED_AVERAGE;

        [$quantity, $value] = $this->position($product);

        return [
            'quantity' => Quantity::format($quantity),
            'value' => Money::format($value),
            'rate' => $this->averageRate($product),
            'method' => $method,
        ];
    }

    /**
     * Unit rate from position (weighted average or FIFO remaining layers).
     */
    public function averageRate(Product $product): string
    {
        [$quantity, $value] = $this->position($product);

        if ($quantity > 0 && $value > 0) {
            return Money::format(intdiv($value * 10000, $quantity));
        }

        return Money::format(max(0, Money::cents((string) $product->purchase_rate)));
    }
}
