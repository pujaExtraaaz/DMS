<?php

namespace App\Domains\Master\Services;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use InvalidArgumentException;

class UomConversionService
{
    public function toBaseQuantity(Product $product, float $quantity, Uom $fromUom): float
    {
        $factor = $this->getConversionFactor($product, $fromUom);

        return $quantity * $factor;
    }

    public function fromBaseQuantity(Product $product, float $baseQuantity, Uom $toUom): float
    {
        $factor = $this->getConversionFactor($product, $toUom);

        if ($factor == 0) {
            throw new InvalidArgumentException('Conversion factor cannot be zero.');
        }

        return $baseQuantity / $factor;
    }

    public function convert(Product $product, float $quantity, Uom $fromUom, Uom $toUom): float
    {
        if ($fromUom->id === $toUom->id) {
            return $quantity;
        }

        $baseQuantity = $this->toBaseQuantity($product, $quantity, $fromUom);

        return $this->fromBaseQuantity($product, $baseQuantity, $toUom);
    }

    protected function getConversionFactor(Product $product, Uom $uom): float
    {
        if ($product->base_uom_id === $uom->id) {
            return 1.0;
        }

        $productUom = ProductUom::query()
            ->where('product_id', $product->id)
            ->where('uom_id', $uom->id)
            ->first();

        if ($productUom) {
            return (float) $productUom->conversion_factor;
        }

        // Standard metric fallback conversions
        $baseUomCode = strtoupper($product->baseUom?->code ?? '');
        $targetUomCode = strtoupper($uom->code);

        if ($baseUomCode === 'KG' && $targetUomCode === 'QTL') {
            return 100.0;
        }
        if ($baseUomCode === 'KG' && $targetUomCode === 'GM') {
            return 0.001;
        }
        if ($baseUomCode === 'PCS' && $targetUomCode === 'BOX') {
            return 10.0;
        }
        if ($baseUomCode === 'PCS' && $targetUomCode === 'CASE') {
            return 100.0;
        }

        throw new InvalidArgumentException(
            "No conversion factor defined for product [{$product->id}] and UOM [{$uom->id}]."
        );
    }
}
