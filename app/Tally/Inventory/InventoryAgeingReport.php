<?php

namespace Tally\Inventory;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Tally\Models\Company;
use Tally\Models\Product;
use Tally\Models\StockMovement;

class InventoryAgeingReport
{
    /**
     * Age on-hand stock by last movement date when StockMovement exists;
     * otherwise fall back to product.updated_at.
     *
     * Buckets: 0–30, 31–60, 61–90, 90+.
     *
     * @return array{as_on: string, rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function run(Company $company, ?string $asOn = null): array
    {
        $asOnDate = Carbon::parse($asOn ?: now()->toDateString())->startOfDay();
        $hasMovements = Schema::hasTable('acct_stock_movements');

        $rows = [];
        $totals = [
            'b0_30' => 0.0,
            'b31_60' => 0.0,
            'b61_90' => 0.0,
            'b90_plus' => 0.0,
            'total' => 0.0,
        ];

        $products = Product::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $lastMovementDates = [];
        if ($hasMovements && $products->isNotEmpty()) {
            $lastMovementDates = StockMovement::query()
                ->where('company_id', $company->id)
                ->whereIn('product_id', $products->modelKeys())
                ->whereDate('movement_date', '<=', $asOnDate->toDateString())
                ->selectRaw('product_id, MAX(movement_date) as last_date')
                ->groupBy('product_id')
                ->pluck('last_date', 'product_id')
                ->all();
        }

        $movements = app(StockMovementService::class);

        foreach ($products as $product) {
            $qty = (float) $movements->quantity($product);
            if ($qty <= 0) {
                continue;
            }

            $lastDate = isset($lastMovementDates[$product->id])
                ? Carbon::parse($lastMovementDates[$product->id])->startOfDay()
                : Carbon::parse($product->updated_at ?? $product->created_at ?? $asOnDate)->startOfDay();

            $days = max(0, $lastDate->diffInDays($asOnDate));
            $bucket = match (true) {
                $days <= 30 => 'b0_30',
                $days <= 60 => 'b31_60',
                $days <= 90 => 'b61_90',
                default => 'b90_plus',
            };

            $rows[] = [
                'product_id' => $product->id,
                'product' => $product->name,
                'code' => $product->code,
                'quantity' => number_format($qty, 4, '.', ''),
                'last_movement' => $lastDate->toDateString(),
                'days' => $days,
                'bucket' => $bucket,
            ];

            $totals[$bucket] += $qty;
            $totals['total'] += $qty;
        }

        return [
            'as_on' => $asOnDate->toDateString(),
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}
