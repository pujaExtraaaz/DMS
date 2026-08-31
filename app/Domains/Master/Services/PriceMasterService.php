<?php

namespace App\Domains\Master\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;

class PriceMasterService
{
    public function resolvePrice(Customer $customer, Product $product, Uom $uom, float $quantity): ?string
    {
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

        return $price?->rate;
    }
}
