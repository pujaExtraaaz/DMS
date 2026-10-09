<?php

namespace Tally\Inventory;

use Tally\Accounting\Money;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\StockBatch;
use Tally\Models\StockMovement;
use Tally\Models\StockSerial;

/**
 * Reads the same stock movements. It does not calculate quantity a second way.
 */
class AdvancedInventoryReport
{
    public function __construct(private readonly StockMovementService $stock) {}

    /**
     * @return array{rows: list<array<string, string>>}
     */
    public function valuation(Company $company, FinancialYear $year): array
    {
        $rows = [];
        $totals = $this->stock->totalsForCompany($company->id, $year->id);

        foreach ($company->products()->orderBy('name')->get() as $product) {
            $scaled = ($totals['quantity'][$product->id] ?? 0) + Quantity::scale((string) $product->opening_quantity, 4);
            $quantity = Quantity::format($scaled);
            $value = Money::cents((string) $product->opening_value) + ($totals['value'][$product->id] ?? 0);

            if ($scaled === 0 && $value === 0) {
                continue;
            }

            $rows[] = [
                'product' => $product->name,
                'product_id' => (string) $product->id,
                'quantity' => $quantity,
                'value' => Money::format($value),
                'rate' => $scaled === 0 ? '0.00' : Money::format(intdiv($value * 10000, abs($scaled))),
            ];
        }

        return ['rows' => $rows];
    }

    /**
     * @return array{rows: list<array<string, string>>}
     */
    public function expiry(Company $company, string $asOf, int $withinDays): array
    {
        $until = date('Y-m-d', strtotime($asOf.' +'.$withinDays.' days'));
        $rows = [];
        $onHand = $this->stock->batchTotals($company->id);

        $batches = StockBatch::query()
            ->with('product')
            ->where('company_id', $company->id)
            ->whereNotNull('expires_on')
            ->where('expires_on', '<=', $until)
            ->orderBy('expires_on')
            ->get();

        foreach ($batches as $batch) {
            $quantity = Quantity::format($onHand[$batch->id] ?? 0);

            if (Quantity::signedScale($quantity, 4) <= 0) {
                continue;
            }

            $rows[] = [
                'product' => $batch->product->name,
                'batch' => $batch->batch_number,
                'manufactured_on' => $batch->manufactured_on?->format('d M Y') ?? '—',
                'expires_on' => $batch->expires_on->format('d M Y'),
                'quantity' => $quantity,
                'state' => $batch->expires_on->toDateString() < $asOf ? 'Expired' : 'Near expiry',
            ];
        }

        return ['rows' => $rows];
    }

    /**
     * @return array{rows: list<array<string, string>>}
     */
    public function limits(Company $company): array
    {
        $rows = [];
        $totals = $this->stock->totalsForCompany($company->id);

        foreach ($company->products()->orderBy('name')->get() as $product) {
            $scaled = ($totals['quantity'][$product->id] ?? 0) + Quantity::scale((string) $product->opening_quantity, 4);
            $quantity = Quantity::format($scaled);
            $reorder = Quantity::scale((string) $product->reorder_level, 4);
            $minimum = Quantity::scale((string) $product->minimum_stock, 4);
            $maximum = $product->maximum_stock === null ? null : Quantity::scale((string) $product->maximum_stock, 4);
            $flags = [];

            if (($reorder > 0 && $scaled <= $reorder) || ($minimum > 0 && $scaled <= $minimum)) {
                $flags[] = 'Reorder';
            }

            if ($maximum !== null && $maximum > 0 && $scaled > $maximum) {
                $flags[] = 'Above maximum';
            }

            if ($flags === []) {
                continue;
            }

            $rows[] = [
                'product' => $product->name,
                'product_id' => (string) $product->id,
                'quantity' => $quantity,
                'reorder_level' => Quantity::format($reorder),
                'minimum_stock' => Quantity::format($minimum),
                'maximum_stock' => $maximum === null ? '—' : Quantity::format($maximum),
                'flag' => implode(', ', $flags),
            ];
        }

        return ['rows' => $rows];
    }

    /**
     * @return array{rows: list<array<string, mixed>>}
     */
    public function history(Company $company, ?int $productId, ?int $batchId, ?int $serialId): array
    {
        $movements = StockMovement::query()
            ->with(['product', 'godown', 'batch', 'serial'])
            ->where('company_id', $company->id)
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->when($batchId, fn ($query) => $query->where('batch_id', $batchId))
            ->when($serialId, fn ($query) => $query->where('serial_id', $serialId))
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return [
            'rows' => $movements->map(fn (StockMovement $movement) => [
                'date' => $movement->movement_date->format('d M Y'),
                'product' => $movement->product->name,
                'godown' => $movement->godown->name,
                'batch' => $movement->batch?->batch_number ?? '—',
                'serial' => $movement->serial?->serial_number ?? '—',
                'quantity' => $movement->quantity,
                'value' => $movement->value,
                'reference' => $movement->referenceLabel(),
            ])->all(),
        ];
    }

    /**
     * @return list<StockBatch>
     */
    public function batches(Company $company): array
    {
        return StockBatch::query()->with('product')->where('company_id', $company->id)->orderBy('batch_number')->get()->all();
    }

    /**
     * @return list<StockSerial>
     */
    public function serials(Company $company): array
    {
        return StockSerial::query()->with('product')->where('company_id', $company->id)->orderBy('serial_number')->get()->all();
    }
}
