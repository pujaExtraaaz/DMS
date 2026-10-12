<?php

namespace Tally\Selling;

use Tally\Models\Party;
use Tally\Models\PriceListLine;
use Tally\Models\Product;

class PartyPricing
{
    public function rateFor(Party $party, Product $product, ?string $asOn = null): ?string
    {
        $asOn ??= now()->toDateString();

        $assignment = $party->priceLists()
            ->where('is_active', true)
            ->where(function ($q) use ($asOn) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $asOn);
            })
            ->where(function ($q) use ($asOn) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $asOn);
            })
            ->with('priceList')
            ->latest('id')
            ->first();

        if ($assignment) {
            $line = PriceListLine::query()
                ->where('price_list_id', $assignment->price_list_id)
                ->where('product_id', $product->id)
                ->first();

            if ($line) {
                return (string) $line->rate;
            }
        }

        return $product->sales_rate !== null ? (string) $product->sales_rate : null;
    }
}
