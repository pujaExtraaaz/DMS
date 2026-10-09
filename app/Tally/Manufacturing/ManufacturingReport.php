<?php

namespace Tally\Manufacturing;

use Tally\Accounting\VoucherStatus;
use Tally\Inventory\Quantity;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\ManufacturingOrder;
use Tally\Models\StockMovement;

class ManufacturingReport
{
    /**
     * @return array{rows: list<array<string, string>>}
     */
    public function production(Company $company, FinancialYear $year, ?int $branchId): array
    {
        $orders = ManufacturingOrder::query()
            ->with('bill.finishedProduct')
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->where('status', VoucherStatus::Posted)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('manufactured_on')
            ->get();

        $rows = [];

        foreach ($orders->groupBy(fn (ManufacturingOrder $order) => $order->bill->finished_product_id) as $group) {
            $quantity = 0;
            $cost = 0;

            foreach ($group as $order) {
                $quantity += Quantity::scale((string) $order->quantity, 4);
                $cost += (int) round(((float) $order->material_cost) * 100);
            }

            $rows[] = [
                'product' => $group->first()->bill->finishedProduct->name,
                'quantity' => Quantity::format($quantity),
                'material_cost' => number_format($cost / 100, 2, '.', ''),
                'orders' => (string) $group->count(),
            ];
        }

        return ['rows' => $rows];
    }

    /**
     * @return array{rows: list<array<string, string>>}
     */
    public function consumption(Company $company, FinancialYear $year, ?int $branchId): array
    {
        $movements = StockMovement::query()
            ->with('product')
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->where('reference_type', (new ManufacturingOrder)->getMorphClass())
            ->where('quantity', '<', 0)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->get();

        $rows = [];

        foreach ($movements->groupBy('product_id') as $group) {
            $quantity = 0;
            $value = 0;

            foreach ($group as $movement) {
                $quantity += abs(Quantity::signedScale((string) $movement->quantity, 4));
                $value += abs((int) round(((float) $movement->value) * 100));
            }

            $rows[] = [
                'product' => $group->first()->product->name,
                'quantity' => Quantity::format($quantity),
                'value' => number_format($value / 100, 2, '.', ''),
            ];
        }

        return ['rows' => $rows];
    }
}
