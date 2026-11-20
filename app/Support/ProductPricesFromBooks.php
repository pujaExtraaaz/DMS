<?php

namespace App\Support;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Sync\Support\SyncGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copy Books rates onto DMS products (and base product_uoms) when DMS prices are empty.
 * Prefer purchase_rate / sales_rate; fall back to opening_rate (common for Tally exports
 * that omit STANDARDCOST / STANDARDPRICE).
 */
final class ProductPricesFromBooks
{
    /**
     * @return array{updated:int,uoms:int,skipped:int}
     */
    public static function sync(?int $companyId = null): array
    {
        return SyncGuard::run(function () use ($companyId) {
            $stats = ['updated' => 0, 'uoms' => 0, 'skipped' => 0];

            if (! Schema::hasTable('acct_products') || ! Schema::hasTable('sync_entity_links')) {
                return $stats;
            }

            $links = DB::table('sync_entity_links')
                ->where('entity_key', 'product')
                ->when($companyId, function ($query) use ($companyId) {
                    $query->whereIn('acct_id', function ($sub) use ($companyId) {
                        $sub->select('id')->from('acct_products')->where('company_id', $companyId);
                    });
                })
                ->get(['dms_id', 'acct_id']);

            if ($links->isEmpty()) {
                return $stats;
            }

            $books = DB::table('acct_products')
                ->whereIn('id', $links->pluck('acct_id'))
                ->get(['id', 'purchase_rate', 'sales_rate', 'opening_rate'])
                ->keyBy('id');

            $godownRates = [];
            if (Schema::hasTable('acct_stock_godown_openings')) {
                $godownRates = DB::table('acct_stock_godown_openings')
                    ->whereIn('product_id', $links->pluck('acct_id'))
                    ->where('rate', '>', 0)
                    ->select('product_id', DB::raw('AVG(rate) as avg_rate'))
                    ->groupBy('product_id')
                    ->pluck('avg_rate', 'product_id')
                    ->all();
            }

            foreach ($links as $link) {
                $product = Product::query()->find($link->dms_id);
                $acct = $books->get($link->acct_id);
                if (! $product || ! $acct) {
                    $stats['skipped']++;

                    continue;
                }

                $opening = (float) ($acct->opening_rate ?? 0);
                if ($opening <= 0) {
                    $opening = (float) ($godownRates[$link->acct_id] ?? 0);
                }

                $purchase = self::prefer((float) ($acct->purchase_rate ?? 0), $opening);
                $selling = self::prefer((float) ($acct->sales_rate ?? 0), $opening);

                if ($purchase <= 0 && $selling <= 0) {
                    if ($product->base_uom_id) {
                        self::ensureBaseUom($product, 0, 0, 0);
                        $stats['uoms']++;
                    }
                    $stats['skipped']++;

                    continue;
                }

                if ($purchase <= 0) {
                    $purchase = $selling;
                }
                if ($selling <= 0) {
                    $selling = $purchase;
                }

                $needsPrice = (float) $product->purchase_price <= 0
                    || (float) $product->selling_price <= 0
                    || (float) $product->trade_price <= 0
                    || (float) ($product->calculation_mrp ?? 0) <= 0;

                if ($needsPrice) {
                    $product->forceFill([
                        'purchase_price' => (float) $product->purchase_price > 0 ? $product->purchase_price : $purchase,
                        'selling_price' => (float) $product->selling_price > 0 ? $product->selling_price : $selling,
                        'trade_price' => (float) $product->trade_price > 0 ? $product->trade_price : $selling,
                        'calculation_mrp' => (float) ($product->calculation_mrp ?? 0) > 0 ? $product->calculation_mrp : $selling,
                    ])->save();
                    $stats['updated']++;
                }

                if ($product->base_uom_id) {
                    self::ensureBaseUom(
                        $product,
                        (float) $product->selling_price,
                        (float) $product->trade_price,
                        (float) $product->purchase_price,
                        (float) ($product->calculation_mrp ?? 0),
                    );
                    $stats['uoms']++;
                }
            }

            // Keep Books list rates filled for future DMS pulls.
            DB::table('acct_products')
                ->where('opening_rate', '>', 0)
                ->where('purchase_rate', '<=', 0)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->update(['purchase_rate' => DB::raw('opening_rate'), 'updated_at' => now()]);

            DB::table('acct_products')
                ->where('opening_rate', '>', 0)
                ->where('sales_rate', '<=', 0)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->update(['sales_rate' => DB::raw('opening_rate'), 'updated_at' => now()]);

            return $stats;
        });
    }

    private static function prefer(float $rate, float $fallback): float
    {
        return $rate > 0 ? round($rate, 2) : ($fallback > 0 ? round($fallback, 2) : 0.0);
    }

    private static function ensureBaseUom(Product $product, float $selling, float $trade, float $purchase, float $mrp = 0): void
    {
        ProductUom::query()->updateOrCreate(
            ['product_id' => $product->id, 'uom_id' => $product->base_uom_id],
            [
                'conversion_factor' => 1,
                'is_base' => true,
                'label' => 'Base',
                'selling_price' => $selling,
                'trade_price' => $trade > 0 ? $trade : $selling,
                'purchase_price' => $purchase,
                'mrp' => $mrp > 0 ? $mrp : $selling,
                'is_default_sales' => true,
                'is_active' => true,
            ],
        );
    }
}
