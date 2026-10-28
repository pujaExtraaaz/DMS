<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\StockCostConsumption;
use App\Domains\Inventory\Models\StockCostLayer;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Models\StockValuationSetting;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\FinancialYear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockValuationService
{
    public function methodFor(?int $companyId = null, ?Carbon $date = null): string
    {
        $companyId ??= auth()->user()?->company_id;
        $date ??= now();

        $fyId = FinancialYear::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->value('id');

        $setting = StockValuationSetting::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($fyId, fn ($q) => $q->where('financial_year_id', $fyId))
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        $method = strtolower((string) ($setting?->method ?? 'fifo'));

        return in_array($method, ['fifo', 'lifo'], true) ? $method : 'fifo';
    }

    /**
     * Flat issue rate for product costing methods that do not follow layer order.
     * Null means the caller should use each layer's own cost (FIFO / LIFO).
     */
    public function issueRate(Product $product, $layers = null): ?float
    {
        $method = $product->costingMethod();
        if ($method === null || in_array($method, ['fifo', 'fifo_perpetual', 'lifo_annual', 'lifo_perpetual'], true)) {
            return null;
        }

        if ($method === 'at_zero_cost') {
            return 0.0;
        }

        if ($method === 'std_cost') {
            return $product->exclusivePurchasePrice();
        }

        $layers ??= StockCostLayer::query()
            ->where('product_id', $product->id)
            ->where('quantity_remaining', '>', 0)
            ->get();

        if ($method === 'last_purchase_cost') {
            $latest = StockCostLayer::query()
                ->where('product_id', $product->id)
                ->orderByDesc('received_on')
                ->orderByDesc('id')
                ->first();

            return $latest ? (float) $latest->unit_cost : $product->exclusivePurchasePrice();
        }

        if ($method === 'monthly_avg_cost') {
            $monthLayers = $layers->filter(function (StockCostLayer $layer) {
                return $layer->received_on && $layer->received_on->isSameMonth(now());
            });

            return $this->weightedAverage($monthLayers->isNotEmpty() ? $monthLayers : $layers)
                ?? $product->exclusivePurchasePrice();
        }

        return $this->weightedAverage($layers) ?? $product->exclusivePurchasePrice();
    }

    public function addLayer(
        Product $product,
        Uom $uom,
        float $quantity,
        float $unitCost,
        ?int $warehouseId = null,
        ?Model $source = null,
        ?float $landedUnitCost = null,
        ?string $batchNo = null,
        ?Carbon $receivedOn = null,
        ?int $companyId = null,
    ): StockCostLayer {
        return StockCostLayer::create([
            'company_id' => $companyId ?? auth()->user()?->company_id ?? $product->company_id,
            'warehouse_id' => $warehouseId,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'quantity_remaining' => abs($quantity),
            'unit_cost' => $unitCost,
            'landed_unit_cost' => $landedUnitCost,
            'received_on' => ($receivedOn ?? now())->toDateString(),
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'batch_no' => $batchNo,
        ]);
    }

    /**
     * Consume layers using FIFO or LIFO and return weighted average unit cost consumed.
     *
     * @return array{unit_cost: float, consumptions: array<int, StockCostConsumption>}
     */
    public function consume(
        Product $product,
        Uom $uom,
        float $quantity,
        ?int $warehouseId = null,
        ?StockMovement $movement = null,
        ?Model $reference = null,
        ?int $companyId = null,
        ?int $onlyLayerId = null,
    ): array {
        $qtyNeeded = abs($quantity);
        if ($qtyNeeded <= 0) {
            return ['unit_cost' => 0.0, 'consumptions' => []];
        }

        return DB::transaction(function () use ($product, $uom, $qtyNeeded, $warehouseId, $movement, $reference, $companyId, $onlyLayerId) {
            $layers = $this->layersForConsumption($product, $uom, $warehouseId, $companyId, $onlyLayerId);
            $flatRate = $onlyLayerId ? null : $this->issueRate($product, $layers);

            $remaining = $qtyNeeded;
            $totalCost = 0.0;
            $consumptions = [];

            foreach ($layers as $layer) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min((float) $layer->quantity_remaining, $remaining);
                $unitCost = $flatRate ?? $layer->effectiveUnitCost();

                $layer->update([
                    'quantity_remaining' => (float) $layer->quantity_remaining - $take,
                ]);

                $consumptions[] = StockCostConsumption::create([
                    'stock_cost_layer_id' => $layer->id,
                    'stock_movement_id' => $movement?->id,
                    'product_id' => $product->id,
                    'quantity' => $take,
                    'unit_cost' => $unitCost,
                    'reference_type' => $reference?->getMorphClass(),
                    'reference_id' => $reference?->getKey(),
                ]);

                $totalCost += $take * $unitCost;
                $remaining -= $take;
            }

            if ($remaining > 0.0001 && ! $product->usesBehaviour('ignore_negative_balances')) {
                $fallback = $flatRate ?? $product->exclusivePurchasePrice();
                $totalCost += $remaining * $fallback;
            }

            $avg = $qtyNeeded > 0 ? round($totalCost / $qtyNeeded, 4) : 0.0;

            return ['unit_cost' => $avg, 'consumptions' => $consumptions];
        });
    }

    public function inventoryValue(?int $warehouseId = null, ?int $companyId = null): array
    {
        $layers = StockCostLayer::query()
            ->where('quantity_remaining', '>', 0)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->get();

        $products = Product::query()
            ->whereIn('id', $layers->pluck('product_id')->unique()->filter())
            ->get()
            ->keyBy('id');

        $atCost = 0.0;
        $atLanded = 0.0;
        foreach ($layers->groupBy('product_id') as $productId => $productLayers) {
            $product = $products->get($productId);
            $method = $product?->costingMethod();
            $rateBased = $product && in_array($method, ['at_zero_cost', 'avg_cost', 'last_purchase_cost', 'monthly_avg_cost', 'std_cost'], true);

            if ($rateBased) {
                $qty = (float) $productLayers->sum(fn (StockCostLayer $layer) => (float) $layer->quantity_remaining);
                $rate = (float) $this->issueRate($product, $productLayers);
                $atCost += $qty * $rate;
                $atLanded += $qty * $rate;
                continue;
            }

            foreach ($productLayers as $layer) {
                $qty = (float) $layer->quantity_remaining;
                $atCost += $qty * (float) $layer->unit_cost;
                $atLanded += $qty * $layer->effectiveUnitCost();
            }
        }

        $stockQty = \App\Domains\Inventory\Models\StockLevel::query()
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($companyId, function ($q) use ($companyId) {
                $q->whereIn('product_id', Product::query()->where('company_id', $companyId)->select('id'));
            })
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $marketProducts = Product::query()->whereIn('id', $stockQty->keys())->get();
        $saleAverages = \App\Domains\Sales\Models\InvoiceItem::query()
            ->whereIn('product_id', $stockQty->keys())
            ->selectRaw('product_id, AVG(unit_price) as avg_price')
            ->groupBy('product_id')
            ->pluck('avg_price', 'product_id');

        $selling = 0.0;
        $atMarket = 0.0;
        foreach ($marketProducts as $product) {
            $qty = (float) ($stockQty[$product->id] ?? 0);
            $selling += $qty * (float) $product->selling_price;
            if ($qty < 0 && $product->usesBehaviour('ignore_negative_balances')) {
                $qty = 0;
            }
            $average = $saleAverages->has($product->id) ? (float) $saleAverages[$product->id] : null;
            $atMarket += $qty * $product->marketUnitPrice($average);
        }

        return [
            'method' => $this->methodFor($companyId),
            'at_cost' => round($atCost, 2),
            'at_landed' => round($atLanded, 2),
            'at_selling' => round($selling, 2),
            'at_market' => round($atMarket, 2),
        ];
    }

    private function layersForConsumption(Product $product, Uom $uom, ?int $warehouseId, ?int $companyId, ?int $onlyLayerId)
    {
        $query = StockCostLayer::query()
            ->where('product_id', $product->id)
            ->where('uom_id', $uom->id)
            ->where('quantity_remaining', '>', 0)
            ->when($onlyLayerId, fn ($q) => $q->whereKey($onlyLayerId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId), fn ($q) => $q->whereNull('warehouse_id'))
            ->lockForUpdate();

        if ($onlyLayerId) {
            return $query->get();
        }

        $method = $product->costingMethod();
        $companyMethod = $this->methodFor($companyId ?? auth()->user()?->company_id ?? $product->company_id);
        $order = match ($method) {
            'lifo_perpetual' => 'lifo',
            'lifo_annual' => 'lifo_annual',
            'fifo', 'fifo_perpetual' => 'fifo',
            default => $method === null && $companyMethod === 'lifo' ? 'lifo' : 'fifo',
        };

        if ($order === 'lifo_annual') {
            $year = FinancialYear::query()
                ->when($product->company_id, fn ($q) => $q->where('company_id', $product->company_id))
                ->where('is_current', true)
                ->orderByDesc('id')
                ->first();

            if ($year) {
                return $query
                    ->orderByRaw('CASE WHEN received_on BETWEEN ? AND ? THEN 0 ELSE 1 END', [
                        $year->starts_on->toDateString(),
                        $year->ends_on->toDateString(),
                    ])
                    ->orderByDesc('received_on')
                    ->orderByDesc('id')
                    ->get();
            }
        }

        if ($order === 'lifo' || $order === 'lifo_annual') {
            return $query->orderByDesc('received_on')->orderByDesc('id')->get();
        }

        return $query->orderBy('received_on')->orderBy('id')->get();
    }

    private function weightedAverage($layers): ?float
    {
        $qty = 0.0;
        $cost = 0.0;
        foreach ($layers as $layer) {
            $lineQty = (float) $layer->quantity_remaining;
            $qty += $lineQty;
            $cost += $lineQty * (float) $layer->unit_cost;
        }

        return $qty > 0 ? round($cost / $qty, 4) : null;
    }
}
