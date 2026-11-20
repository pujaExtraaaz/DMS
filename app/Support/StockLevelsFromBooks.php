<?php

namespace App\Support;

use App\Domains\Master\Models\Product;
use App\Domains\Sync\Support\SyncGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Populate DMS stock_levels from Books acct_stock_godown_openings via sync_entity_links.
 * Idempotent upsert by (warehouse_id, product_id). Runs under SyncGuard so Books observers
 * do not push openings back into Books while we are reading from Books.
 */
final class StockLevelsFromBooks
{
    /**
     * @return array{created:int,updated:int,skipped:int}
     */
    public static function sync(?int $companyId = null): array
    {
        return SyncGuard::run(function () use ($companyId) {
            $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

            if (! Schema::hasTable('acct_stock_godown_openings') || ! Schema::hasTable('sync_entity_links')) {
                return $stats;
            }

            $productMap = DB::table('sync_entity_links')
                ->where('entity_key', 'product')
                ->pluck('dms_id', 'acct_id');
            $warehouseMap = DB::table('sync_entity_links')
                ->where('entity_key', 'warehouse')
                ->pluck('dms_id', 'acct_id');

            $openings = DB::table('acct_stock_godown_openings')
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->select(['product_id', 'godown_id', 'quantity'])
                ->get();

            /** @var array<string, float> $totals key = "{warehouse}|{product}" */
            $totals = [];
            foreach ($openings as $row) {
                $productId = (int) ($productMap[$row->product_id] ?? 0);
                $warehouseId = (int) ($warehouseMap[$row->godown_id] ?? 0);
                if (! $productId || ! $warehouseId) {
                    $stats['skipped']++;

                    continue;
                }
                $key = $warehouseId.'|'.$productId;
                $totals[$key] = ($totals[$key] ?? 0) + (float) $row->quantity;
            }

            if ($totals === []) {
                return $stats;
            }

            $productIds = collect(array_keys($totals))
                ->map(fn ($key) => (int) explode('|', $key)[1])
                ->unique()
                ->values()
                ->all();

            $uomByProduct = Product::query()
                ->whereIn('id', $productIds)
                ->pluck('base_uom_id', 'id');

            $now = now();

            foreach ($totals as $key => $qty) {
                [$warehouseId, $productId] = array_map('intval', explode('|', $key));
                $payload = [
                    'uom_id' => $uomByProduct[$productId] ?? null,
                    'quantity' => $qty,
                    'updated_at' => $now,
                ];

                $existingId = DB::table('stock_levels')
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->value('id');

                if ($existingId) {
                    DB::table('stock_levels')->where('id', $existingId)->update($payload);
                    $stats['updated']++;
                } else {
                    DB::table('stock_levels')->insert($payload + [
                        'warehouse_id' => $warehouseId,
                        'product_id' => $productId,
                        'created_at' => $now,
                    ]);
                    $stats['created']++;
                }
            }

            return $stats;
        });
    }
}
