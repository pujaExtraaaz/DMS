<?php

namespace Tally\Inventory;

use Tally\Accounting\Money;
use Tally\Models\Company;
use Tally\Models\Godown;
use Tally\Models\Product;
use Tally\Models\StockCostConsumption;
use Tally\Models\StockCostLayer;
use Tally\Models\StockMovement;
use Illuminate\Validation\ValidationException;

/**
 * FIFO cost layers: inbound receipts create layers; outbound issues consume
 * oldest remaining quantity first.
 */
class FifoCostService
{
    public function addLayer(
        Company $company,
        Product $product,
        ?Godown $godown,
        StockMovement $movement,
        string $date,
        string $qty,
        string $unitCost,
    ): StockCostLayer {
        $scaled = Quantity::scale(ltrim($qty, '+'), 4);

        if ($scaled <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'FIFO layer quantity must be greater than zero.',
            ]);
        }

        $cost = $this->formatUnitCost($unitCost);

        return StockCostLayer::query()->create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'godown_id' => $godown?->id,
            'stock_movement_id' => $movement->id,
            'received_on' => $date,
            'quantity_in' => Quantity::format($scaled),
            'quantity_remaining' => Quantity::format($scaled),
            'unit_cost' => $cost,
        ]);
    }

    /**
     * Consume oldest layers first.
     *
     * @return array{quantity: string, value: string, unit_cost: string}
     */
    public function consume(
        Company $company,
        Product $product,
        ?Godown $godown,
        StockMovement $movement,
        string $date,
        string $qty,
    ): array {
        $need = Quantity::scale(ltrim($qty, '+-'), 4);

        if ($need <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'FIFO consume quantity must be greater than zero.',
            ]);
        }

        $query = StockCostLayer::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('received_on')
            ->orderBy('id');

        if ($godown !== null) {
            $query->where('godown_id', $godown->id);
        }

        $layers = $query->lockForUpdate()->get();
        $remaining = $need;
        $valueCents = 0;

        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }

            $available = Quantity::scale((string) $layer->quantity_remaining, 4);
            $take = min($available, $remaining);
            $lineCents = $this->valueCents($take, (string) $layer->unit_cost);

            StockCostConsumption::query()->create([
                'company_id' => $company->id,
                'stock_cost_layer_id' => $layer->id,
                'stock_movement_id' => $movement->id,
                'quantity' => Quantity::format($take),
                'unit_cost' => $this->formatUnitCost((string) $layer->unit_cost),
                'value' => Money::format($lineCents),
                'consumed_on' => $date,
            ]);

            $layer->update([
                'quantity_remaining' => Quantity::format($available - $take),
            ]);

            $valueCents += $lineCents;
            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Not enough FIFO cost layers for '.$product->name.'.',
            ]);
        }

        $unitCost = $need > 0
            ? $this->formatUnitCostFromCents($valueCents, $need)
            : '0.0000';

        return [
            'quantity' => Quantity::format($need),
            'value' => Money::format($valueCents),
            'unit_cost' => $unitCost,
        ];
    }

    /**
     * Sum of remaining quantity × unit cost across layers (money, 2 dp).
     */
    public function inventoryValue(Company $company, Product $product, ?Godown $godown = null): string
    {
        $query = StockCostLayer::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->where('quantity_remaining', '>', 0);

        if ($godown !== null) {
            $query->where('godown_id', $godown->id);
        }

        $cents = 0;

        foreach ($query->get(['quantity_remaining', 'unit_cost']) as $layer) {
            $cents += $this->valueCents(
                Quantity::scale((string) $layer->quantity_remaining, 4),
                (string) $layer->unit_cost,
            );
        }

        return Money::format($cents);
    }

    /**
     * Remaining on-hand quantity in layers (scaled ×10000).
     */
    public function remainingScaled(Company $company, Product $product, ?Godown $godown = null): int
    {
        $query = StockCostLayer::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->where('quantity_remaining', '>', 0);

        if ($godown !== null) {
            $query->where('godown_id', $godown->id);
        }

        $total = 0;

        foreach ($query->pluck('quantity_remaining') as $qty) {
            $total += Quantity::scale((string) $qty, 4);
        }

        return $total;
    }

    private function formatUnitCost(string $unitCost): string
    {
        $raw = trim($unitCost);

        if ($raw === '' || ! preg_match('/^-?\d+(\.\d{1,4})?$/', $raw)) {
            throw ValidationException::withMessages([
                'rate' => 'Enter a unit cost with up to 4 decimal places.',
            ]);
        }

        $negative = str_starts_with($raw, '-');
        $raw = ltrim($raw, '-');
        [$whole, $fraction] = array_pad(explode('.', $raw, 2), 2, '0');
        $fraction = str_pad(substr($fraction, 0, 4), 4, '0');

        return ($negative ? '-' : '').$whole.'.'.$fraction;
    }

    private function costScale(string $unitCost): int
    {
        $formatted = $this->formatUnitCost($unitCost);
        $negative = str_starts_with($formatted, '-');
        $raw = ltrim($formatted, '-');
        [$whole, $fraction] = array_pad(explode('.', $raw, 2), 2, '0');
        $scaled = ((int) $whole * 10000) + (int) str_pad(substr($fraction, 0, 4), 4, '0');

        return $negative ? -$scaled : $scaled;
    }

    private function valueCents(int $qtyScaled, string $unitCost): int
    {
        if ($qtyScaled === 0) {
            return 0;
        }

        $costScaled = $this->costScale($unitCost);

        // (qty/10000) * (cost/10000) * 100 = qty * cost / 1_000_000
        return intdiv(($qtyScaled * $costScaled) + 500000, 1000000);
    }

    private function formatUnitCostFromCents(int $valueCents, int $qtyScaled): string
    {
        if ($qtyScaled <= 0) {
            return '0.0000';
        }

        // unit_cost_4dp = value_cents * 10000 / qty_scaled  (since value = qty * cost / 100 in money terms with 2dp)
        // value_cents = qty * cost * 100 / 10000 = qty_scaled * cost_4dp / 1_000_000
        // cost_4dp = value_cents * 1_000_000 / qty_scaled
        $scaled = intdiv(($valueCents * 1000000) + intdiv($qtyScaled, 2), $qtyScaled);
        $sign = $scaled < 0 ? '-' : '';
        $scaled = abs($scaled);

        return $sign.intdiv($scaled, 10000).'.'.str_pad((string) ($scaled % 10000), 4, '0', STR_PAD_LEFT);
    }
}
