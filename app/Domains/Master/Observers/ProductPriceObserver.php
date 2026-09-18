<?php

namespace App\Domains\Master\Observers;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductPriceHistory;
use Carbon\CarbonImmutable;

/**
 * Records a snapshot of pricing whenever any of the master price fields
 * change on a Product. Fires on `saved` so both creation and update are covered.
 */
class ProductPriceObserver
{
    protected array $watchedColumns = [
        'selling_price',
        'trade_price',
        'purchase_price',
        'calculation_mrp',
    ];

    public function saved(Product $product): void
    {
        // Skip if nothing tracked changed AND a history row already exists — avoids duplicates on unrelated updates.
        if ($product->wasRecentlyCreated) {
            $this->write($product, 'create', 'created');
            return;
        }

        $dirty = array_intersect_key($product->getChanges(), array_flip($this->watchedColumns));
        if (empty($dirty)) {
            return;
        }

        // Close the previous open history row.
        ProductPriceHistory::query()
            ->where('product_id', $product->id)
            ->where('uom_id', $product->base_uom_id)
            ->whereNull('effective_to')
            ->update(['effective_to' => CarbonImmutable::yesterday()->toDateString()]);

        $this->write($product, 'update', 'auto-updated on save');
    }

    protected function write(Product $product, string $source, string $reason): void
    {
        ProductPriceHistory::create([
            'product_id' => $product->id,
            'uom_id' => $product->base_uom_id,
            'trade_price' => $product->trade_price,
            'selling_price' => $product->selling_price,
            'purchase_price' => $product->purchase_price,
            'mrp' => $product->calculation_mrp,
            'effective_from' => CarbonImmutable::now()->toDateString(),
            'effective_to' => null,
            'change_reason' => $reason,
            'source' => $source,
            'created_by' => auth()->id(),
        ]);
    }
}
