<?php

namespace App\Domains\Master\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;

class PriceMasterService
{
    public function __construct(
        protected UomConversionService $uomConversionService = new UomConversionService()
    ) {}

    public function resolvePrice(Customer $customer, Product $product, Uom $uom, float $quantity): ?string
    {
        // 1. Direct match on customer type, product, uom, and quantity tier
        $price = PriceMaster::query()
            ->where('customer_type_id', $customer->customer_type_id)
            ->where('product_id', $product->id)
            ->where('uom_id', $uom->id)
            ->where(function ($query) use ($quantity) {
                $query->whereNull('min_qty')
                    ->orWhere('min_qty', '<=', $quantity);
            })
            ->orderByDesc('min_qty')
            ->first();

        if ($price && $price->rate > 0) {
            return (string) number_format((float) $price->rate, 2, '.', '');
        }

        // 2. Base UOM PriceMaster match converted to target UOM
        if ($product->base_uom_id && $product->base_uom_id !== $uom->id) {
            $basePrice = PriceMaster::query()
                ->where('customer_type_id', $customer->customer_type_id)
                ->where('product_id', $product->id)
                ->where('uom_id', $product->base_uom_id)
                ->where(function ($query) use ($quantity) {
                    $query->whereNull('min_qty')
                        ->orWhere('min_qty', '<=', $quantity);
                })
                ->orderByDesc('min_qty')
                ->first();

            if ($basePrice && $basePrice->rate > 0) {
                try {
                    $baseUom = Uom::find($product->base_uom_id);
                    if ($baseUom) {
                        $factor = $this->uomConversionService->toBaseQuantity($product, 1.0, $uom);
                        $convertedRate = (float) $basePrice->rate * $factor;
                        return (string) number_format($convertedRate, 2, '.', '');
                    }
                } catch (\Exception $e) {
                    // Fallthrough to product selling price
                }
            }
        }

        // 3. Fallback to product default selling price with UOM conversion
        $sellingPrice = (float) ($product->selling_price ?? 0);
        if ($sellingPrice > 0) {
            try {
                if ($product->base_uom_id && $product->base_uom_id !== $uom->id) {
                    $factor = $this->uomConversionService->toBaseQuantity($product, 1.0, $uom);
                    $sellingPrice *= $factor;
                }
                return (string) number_format($sellingPrice, 2, '.', '');
            } catch (\Exception $e) {
                return (string) number_format((float) $product->selling_price, 2, '.', '');
            }
        }

        return null;
    }
}
