<?php

namespace App\Domains\Master\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;

class PriceMasterService
{
    public function __construct(
        protected UomConversionService $uomConversionService = new UomConversionService()
    ) {}

    public function resolvePrice(Customer $customer, Product $product, Uom $uom, float $quantity): ?string
    {
        // 1. Brand Wise pricing priority (if product belongs to a brand)
        if ($product->brand_id) {
            $brandPrice = PriceMaster::query()
                ->where('type', PriceMaster::TYPE_BRAND)
                ->where('brand_id', $product->brand_id)
                ->where('product_id', $product->id)
                ->where('uom_id', $uom->id)
                ->where(function ($query) use ($quantity) {
                    $query->whereNull('min_qty')
                        ->orWhere('min_qty', '<=', $quantity);
                })
                ->orderByDesc('min_qty')
                ->first();

            if ($brandPrice) {
                $rate = $this->extractPrice($brandPrice, $customer);
                if ($rate !== null && $rate > 0) {
                    return (string) number_format($rate, 2, '.', '');
                }
            }

            // Brand Wise match on Base UOM converted to target UOM
            if ($product->base_uom_id && $product->base_uom_id !== $uom->id) {
                $baseBrandPrice = PriceMaster::query()
                    ->where('type', PriceMaster::TYPE_BRAND)
                    ->where('brand_id', $product->brand_id)
                    ->where('product_id', $product->id)
                    ->where('uom_id', $product->base_uom_id)
                    ->where(function ($query) use ($quantity) {
                        $query->whereNull('min_qty')
                            ->orWhere('min_qty', '<=', $quantity);
                    })
                    ->orderByDesc('min_qty')
                    ->first();

                if ($baseBrandPrice) {
                    $baseRate = $this->extractPrice($baseBrandPrice, $customer);
                    if ($baseRate !== null && $baseRate > 0) {
                        try {
                            $factor = $this->uomConversionService->toBaseQuantity($product, 1.0, $uom);
                            $convertedRate = $baseRate * $factor;
                            return (string) number_format($convertedRate, 2, '.', '');
                        } catch (\Exception $e) {
                            // Continue to next priority
                        }
                    }
                }
            }
        }

        // 2. Direct match on customer type, product, uom, and quantity tier
        if ($customer->customer_type_id) {
            $price = PriceMaster::query()
                ->customerTypeWise()
                ->where('customer_type_id', $customer->customer_type_id)
                ->where('product_id', $product->id)
                ->where('uom_id', $uom->id)
                ->where(function ($query) use ($quantity) {
                    $query->whereNull('min_qty')
                        ->orWhere('min_qty', '<=', $quantity);
                })
                ->orderByDesc('min_qty')
                ->first();

            if ($price) {
                $rate = $this->extractPrice($price, $customer);
                if ($rate !== null && $rate > 0) {
                    return (string) number_format($rate, 2, '.', '');
                }
            }
        }

        // 3. Direct match on ProductUom or Product alternate selling price
        $productUom = ProductUom::query()
            ->where('product_id', $product->id)
            ->where('uom_id', $uom->id)
            ->first();

        if ($productUom && (float) $productUom->selling_price > 0) {
            return (string) number_format((float) $productUom->selling_price, 2, '.', '');
        }

        if ((int) ($product->alternate_uom_id ?? 0) === (int) $uom->id && (float) ($product->alt_selling_price ?? 0) > 0) {
            return (string) number_format((float) $product->alt_selling_price, 2, '.', '');
        }

        // 4. Base UOM PriceMaster match converted to target UOM
        if ($customer->customer_type_id && $product->base_uom_id && $product->base_uom_id !== $uom->id) {
            $basePrice = PriceMaster::query()
                ->customerTypeWise()
                ->where('customer_type_id', $customer->customer_type_id)
                ->where('product_id', $product->id)
                ->where('uom_id', $product->base_uom_id)
                ->where(function ($query) use ($quantity) {
                    $query->whereNull('min_qty')
                        ->orWhere('min_qty', '<=', $quantity);
                })
                ->orderByDesc('min_qty')
                ->first();

            if ($basePrice) {
                $baseRate = $this->extractPrice($basePrice, $customer);
                if ($baseRate !== null && $baseRate > 0) {
                    try {
                        $baseUom = Uom::find($product->base_uom_id);
                        if ($baseUom) {
                            $factor = $this->uomConversionService->toBaseQuantity($product, 1.0, $uom);
                            $convertedRate = $baseRate * $factor;
                            return (string) number_format($convertedRate, 2, '.', '');
                        }
                    } catch (\Exception $e) {
                        // Fallthrough to product selling price
                    }
                }
            }
        }

        // 5. Fallback to product default selling price with UOM conversion
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

    protected function extractPrice(PriceMaster $master, Customer $customer): ?float
    {
        $typeName = '';
        if ($customer->relationLoaded('customerType') && $customer->customerType) {
            $typeName = strtolower($customer->customerType->name);
        } elseif ($customer->customer_type_id) {
            $ct = CustomerType::find($customer->customer_type_id);
            $typeName = $ct ? strtolower($ct->name) : '';
        }

        $isRetail = str_contains($typeName, 'retail') || str_contains($typeName, 'consumer') || str_contains($typeName, 'b2c');

        if ($isRetail && $master->retail_price !== null && (float) $master->retail_price > 0) {
            return (float) $master->retail_price;
        }

        if ($master->dealer_price !== null && (float) $master->dealer_price > 0) {
            return (float) $master->dealer_price;
        }

        if ($master->rate !== null && (float) $master->rate > 0) {
            return (float) $master->rate;
        }

        if ($master->retail_price !== null && (float) $master->retail_price > 0) {
            return (float) $master->retail_price;
        }

        return null;
    }
}
