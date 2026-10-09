<?php

namespace Tally\Inventory;

use Tally\Models\Product;
use InvalidArgumentException;

/**
 * Converts an alternate-unit quantity into the product's primary unit.
 * One alternate unit equals conversion_factor primary units.
 */
class UnitConversion
{
    public function toPrimaryScaled(Product $product, string $quantity, ?int $unitId): int
    {
        $scaled = Quantity::scale($quantity, 4);

        if (! $unitId || (int) $unitId === (int) $product->primary_unit_id) {
            return $scaled;
        }

        if ((int) $unitId !== (int) $product->alternate_unit_id || $product->conversion_factor === null) {
            throw new InvalidArgumentException('The component unit is not the primary or alternate unit of '.$product->name.'.');
        }

        $micros = (int) bcmul((string) $product->conversion_factor, '1000000', 0);

        return intdiv($scaled * $micros + 500000, 1000000);
    }

    public function withWastage(int $scaled, string $percent): int
    {
        $wastage = Quantity::scale($percent === '' ? '0' : $percent, 4);

        return intdiv($scaled * (1000000 + $wastage) + 500000, 1000000);
    }

    public function times(int $perUnit, int $production): int
    {
        return intdiv($perUnit * $production + 5000, 10000);
    }
}
