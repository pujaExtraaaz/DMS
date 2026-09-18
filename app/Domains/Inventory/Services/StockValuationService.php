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
    ): array {
        $qtyNeeded = abs($quantity);
        if ($qtyNeeded <= 0) {
            return ['unit_cost' => 0.0, 'consumptions' => []];
        }

        return DB::transaction(function () use ($product, $uom, $qtyNeeded, $warehouseId, $movement, $reference, $companyId) {
            $method = $this->methodFor($companyId ?? auth()->user()?->company_id ?? $product->company_id);

            $query = StockCostLayer::query()
                ->where('product_id', $product->id)
                ->where('uom_id', $uom->id)
                ->where('quantity_remaining', '>', 0)
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId), fn ($q) => $q->whereNull('warehouse_id'))
                ->lockForUpdate();

            $layers = $method === 'lifo'
                ? $query->orderByDesc('received_on')->orderByDesc('id')->get()
                : $query->orderBy('received_on')->orderBy('id')->get();

            $remaining = $qtyNeeded;
            $totalCost = 0.0;
            $consumptions = [];

            foreach ($layers as $layer) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min((float) $layer->quantity_remaining, $remaining);
                $unitCost = $layer->effectiveUnitCost();

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

            if ($remaining > 0.0001) {
                // Fall back to product purchase price for uncovered qty so posting is not blocked.
                $fallback = (float) ($product->purchase_price ?? 0);
                $totalCost += $remaining * $fallback;
                $remaining = 0;
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

        $atCost = 0.0;
        $atLanded = 0.0;
        foreach ($layers as $layer) {
            $qty = (float) $layer->quantity_remaining;
            $atCost += $qty * (float) $layer->unit_cost;
            $atLanded += $qty * $layer->effectiveUnitCost();
        }

        $selling = Product::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->sum(function (Product $product) use ($warehouseId) {
                $qty = (float) \App\Domains\Inventory\Models\StockLevel::query()
                    ->where('product_id', $product->id)
                    ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                    ->sum('quantity');

                return $qty * (float) $product->selling_price;
            });

        return [
            'method' => $this->methodFor($companyId),
            'at_cost' => round($atCost, 2),
            'at_landed' => round($atLanded, 2),
            'at_selling' => round((float) $selling, 2),
        ];
    }
}
