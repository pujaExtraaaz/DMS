<?php

namespace Tally\Inventory;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherStatus;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Invoice;
use Tally\Models\Product;
use Tally\Models\StockMovement;
use Tally\Models\StockTransaction;
use Tally\Support\Queries\DateRange;
use Illuminate\Support\Collection;

/**
 * Stock reports from posted movements. Opening quantity stays on the product
 * and is not placed in a godown. Cancelled documents and their reversals are excluded.
 */
class InventoryReports
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Company $company, FinancialYear $year, ?int $branchId, string $from, string $to, ?int $productId, ?int $godownId, string $search = ''): array
    {
        $products = $this->products($company, $productId, $search);
        $movements = $this->posted($company, $year, $branchId, $productId, $godownId, null, $to);
        $rows = [];
        $totals = $this->blankTotals();

        foreach ($products as $product) {
            $mine = $movements->where('product_id', $product->id);
            $row = $this->quantities($product, $mine, $from, $godownId === null, $godownId !== null);

            if ($productId === null && $search === '' && $this->isZero($row)) {
                continue;
            }

            $rows[] = ['product' => $product->name, 'code' => $product->code, 'product_id' => $product->id] + $row;
            $this->addTotals($totals, $row);
        }

        return ['rows' => $rows] + $this->formatTotals($totals);
    }

    /**
     * @return array<string, mixed>
     */
    public function ledger(Company $company, FinancialYear $year, ?int $branchId, ?int $productId, ?int $godownId, string $from, string $to): array
    {
        $product = $productId ? $company->products()->whereKey($productId)->first() : null;
        $empty = [
            'product' => $product,
            'opening_quantity' => '0.0000',
            'closing_quantity' => '0.0000',
            'rows' => [],
        ];

        if (! $product) {
            return $empty;
        }

        $movements = $this->posted($company, $year, $branchId, $product->id, $godownId, null, $to);
        $opening = $godownId === null ? Quantity::scale((string) $product->opening_quantity, 4) : 0;

        foreach ($movements as $movement) {
            if ($movement->movement_date->toDateString() < $from) {
                $opening += Quantity::signedScale((string) $movement->quantity, 4);
            }
        }

        $running = $opening;
        $rows = [];

        foreach ($movements as $movement) {
            $date = $movement->movement_date->toDateString();

            if ($date < $from || $date > $to) {
                continue;
            }

            $signed = Quantity::signedScale((string) $movement->quantity, 4);
            $running += $signed;
            $rows[] = [
                'date' => $movement->movement_date->format('d M Y'),
                'number' => $movement->referenceLabel(),
                'type' => $movement->movement_type->label(),
                'godown' => $movement->godown->name,
                'in_quantity' => $signed > 0 ? Quantity::format($signed) : '',
                'out_quantity' => $signed < 0 ? Quantity::format(abs($signed)) : '',
                'balance' => Quantity::format($running),
                'rate' => $movement->rate,
                'value' => $movement->value,
            ];
        }

        return [
            'product' => $product,
            'opening_quantity' => Quantity::format($opening),
            'closing_quantity' => Quantity::format($running),
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function godowns(Company $company, FinancialYear $year, ?int $branchId, string $from, string $to, ?int $productId, ?int $godownId, string $search = ''): array
    {
        $products = $this->products($company, $productId, $search);
        $movements = $this->posted($company, $year, $branchId, $productId, $godownId, null, $to);
        $rows = [];

        foreach ($products as $product) {
            $mine = $movements->where('product_id', $product->id);
            $godownIds = $mine->pluck('godown_id')->unique()->filter();

            foreach ($godownIds as $id) {
                $godownMovements = $mine->where('godown_id', $id)->values();
                $row = $this->quantities($product, $godownMovements, $from, false, true);
                $godown = $godownMovements->first()?->godown;

                if ($this->isZero($row)) {
                    continue;
                }

                $rows[] = [
                    'product' => $product->name,
                    'godown' => $godown?->name ?? '—',
                ] + $row;
            }

            if ($godownId === null) {
                $opening = Quantity::scale((string) $product->opening_quantity, 4);

                if ($opening > 0) {
                    $rows[] = [
                        'product' => $product->name,
                        'godown' => 'Not in a godown',
                        'opening_quantity' => Quantity::format($opening),
                        'in_quantity' => '0.0000',
                        'out_quantity' => '0.0000',
                        'closing_quantity' => Quantity::format($opening),
                        'rate' => (string) $product->opening_rate,
                        'opening_value' => (string) $product->opening_value,
                        'in_value' => '0.00',
                        'out_value' => '0.00',
                        'closing_value' => (string) $product->opening_value,
                    ];
                }
            }
        }

        return ['rows' => $rows];
    }

    /**
     * @return array{rows: list<array<string, mixed>>}
     */
    public function movements(Company $company, FinancialYear $year, ?int $branchId, string $from, string $to, ?int $productId, ?int $godownId, string $search = ''): array
    {
        $productIds = $search === '' ? null : $this->products($company, $productId, $search)->pluck('id')->all();
        $rows = [];

        foreach ($this->posted($company, $year, $branchId, $productId, $godownId, $from, $to) as $movement) {
            if ($productIds !== null && ! in_array($movement->product_id, $productIds, true)) {
                continue;
            }

            $signed = Quantity::signedScale((string) $movement->quantity, 4);
            $rows[] = [
                'date' => $movement->movement_date->format('d M Y'),
                'number' => $movement->referenceLabel(),
                'product' => $movement->product->name,
                'godown' => $movement->godown->name,
                'type' => $movement->movement_type->label(),
                'in_quantity' => $signed > 0 ? Quantity::format($signed) : '',
                'out_quantity' => $signed < 0 ? Quantity::format(abs($signed)) : '',
                'rate' => $movement->rate,
                'value' => $movement->value,
            ];
        }

        return ['rows' => $rows];
    }

    /**
     * @return array{rows: list<array<string, string>>}
     */
    public function lowStock(Company $company, FinancialYear $year, ?int $branchId, string $to, ?int $productId, string $search = ''): array
    {
        $products = $this->products($company, $productId, $search);
        $movements = $this->posted($company, $year, $branchId, $productId, null, null, $to);
        $rows = [];

        foreach ($products as $product) {
            $threshold = $this->threshold($product);

            if ($threshold <= 0) {
                continue;
            }

            $closing = Quantity::scale((string) $product->opening_quantity, 4);

            foreach ($movements->where('product_id', $product->id) as $movement) {
                $closing += Quantity::signedScale((string) $movement->quantity, 4);
            }

            if ($closing > $threshold) {
                continue;
            }

            $rows[] = [
                'product' => $product->name,
                'product_id' => $product->id,
                'code' => (string) $product->code,
                'closing_quantity' => Quantity::format($closing),
                'reorder_level' => Quantity::format(Quantity::scale((string) $product->reorder_level, 4)),
                'minimum_stock' => Quantity::format(Quantity::scale((string) $product->minimum_stock, 4)),
                'shortfall' => Quantity::format($threshold - $closing),
            ];
        }

        return ['rows' => $rows];
    }

    /**
     * @param  Collection<int, StockMovement>  $movements
     * @return array<string, string>
     */
    private function quantities(Product $product, Collection $movements, string $from, bool $includeOpening, bool $includeTransfers = true): array
    {
        $openingQty = $includeOpening ? Quantity::scale((string) $product->opening_quantity, 4) : 0;
        $openingValue = $includeOpening ? $this->cents($product->opening_value) : 0;
        $inQty = 0;
        $outQty = 0;
        $inValue = 0;
        $outValue = 0;

        foreach ($movements as $movement) {
            $transfer = in_array($movement->movement_type, [StockMovementType::TransferIn, StockMovementType::TransferOut], true);

            if ($transfer && ! $includeTransfers) {
                continue;
            }

            $signed = Quantity::signedScale((string) $movement->quantity, 4);
            $value = $this->cents($movement->value);
            $date = $movement->movement_date->toDateString();

            if ($date < $from) {
                $openingQty += $signed;
                $openingValue += $value;

                continue;
            }

            if ($signed > 0) {
                $inQty += $signed;
                $inValue += $value;
            } elseif ($signed < 0) {
                $outQty += abs($signed);
                $outValue += abs($value);
            }
        }

        $closingQty = $openingQty + $inQty - $outQty;
        $closingValue = $openingValue + $inValue - $outValue;

        return [
            'opening_quantity' => Quantity::format($openingQty),
            'in_quantity' => Quantity::format($inQty),
            'out_quantity' => Quantity::format($outQty),
            'closing_quantity' => Quantity::format($closingQty),
            'rate' => $this->rate($closingValue, $closingQty),
            'opening_value' => Money::format($openingValue),
            'in_value' => Money::format($inValue),
            'out_value' => Money::format($outValue),
            'closing_value' => Money::format($closingValue),
        ];
    }

    /**
     * @return Collection<int, Product>
     */
    private function products(Company $company, ?int $productId, string $search): Collection
    {
        $like = '%'.addcslashes($search, '%_\\').'%';

        return $company->products()
            ->when($productId, fn ($query) => $query->whereKey($productId))
            ->when($search !== '', function ($query) use ($like) {
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
                });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, StockMovement>
     */
    private function posted(Company $company, FinancialYear $year, ?int $branchId, ?int $productId, ?int $godownId, ?string $from, ?string $to): Collection
    {
        return StockMovement::query()
            ->with(['product', 'godown', 'reference'])
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->where('is_reversal', false)
            ->whereHasMorph('reference', [StockTransaction::class, Invoice::class, \Tally\Models\ManufacturingOrder::class], function ($query) {
                $query->where('status', VoucherStatus::Posted->value);
            })
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->when($godownId, fn ($query) => $query->where('godown_id', $godownId))
            ->tap(fn ($query) => DateRange::apply($query, 'movement_date', $from, $to))
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();
    }

    private function threshold(Product $product): int
    {
        $reorder = Quantity::scale((string) $product->reorder_level, 4);

        if ($reorder > 0) {
            return $reorder;
        }

        return Quantity::scale((string) $product->minimum_stock, 4);
    }

    /**
     * @param  array<string, string>  $row
     */
    private function isZero(array $row): bool
    {
        foreach (['opening_quantity', 'in_quantity', 'out_quantity', 'closing_quantity'] as $key) {
            if (Quantity::signedScale($row[$key], 4) !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, int>
     */
    private function blankTotals(): array
    {
        return [
            'opening_quantity' => 0,
            'in_quantity' => 0,
            'out_quantity' => 0,
            'closing_quantity' => 0,
            'opening_value' => 0,
            'in_value' => 0,
            'out_value' => 0,
            'closing_value' => 0,
        ];
    }

    /**
     * @param  array<string, int>  $totals
     * @param  array<string, string>  $row
     */
    private function addTotals(array &$totals, array $row): void
    {
        foreach (['opening_quantity', 'in_quantity', 'out_quantity', 'closing_quantity'] as $key) {
            $totals[$key] += Quantity::signedScale($row[$key], 4);
        }

        foreach (['opening_value', 'in_value', 'out_value', 'closing_value'] as $key) {
            $totals[$key] += $this->cents($row[$key]);
        }
    }

    /**
     * @param  array<string, int>  $totals
     * @return array<string, string>
     */
    private function formatTotals(array $totals): array
    {
        $formatted = [];

        foreach (['opening_quantity', 'in_quantity', 'out_quantity', 'closing_quantity'] as $key) {
            $formatted[$key] = Quantity::format($totals[$key]);
        }

        foreach (['opening_value', 'in_value', 'out_value', 'closing_value'] as $key) {
            $formatted[$key] = Money::format($totals[$key]);
        }

        return $formatted;
    }

    private function rate(int $valueCents, int $scaledQty): string
    {
        if ($scaledQty === 0 || $valueCents === 0) {
            return '0.00';
        }

        $negative = ($valueCents < 0) !== ($scaledQty < 0);
        $cents = intdiv((abs($valueCents) * 10000) + intdiv(abs($scaledQty), 2), abs($scaledQty));

        return ($negative ? '-' : '').Money::format($cents);
    }

    private function cents(mixed $value): int
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return 0;
        }

        if (preg_match('/^-?\d+\.\d{3,}$/', $raw)) {
            $raw = number_format((float) $raw, 2, '.', '');
        }

        return Money::cents($raw);
    }
}
