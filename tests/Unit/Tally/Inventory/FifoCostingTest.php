<?php

namespace Tests\Unit\Tally\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Inventory\Quantity;
use Tally\Inventory\StockMovementService;
use Tally\Inventory\StockMovementType;
use Tally\Models\Godown;
use Tally\Models\Product;
use Tally\Models\ProductGroup;
use Tally\Models\Unit;

class FifoCostingTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_fifo_issue_consumes_oldest_layers_first(): void
    {
        $company = $this->makeCompany([
            'inventory_valuation_method' => 'fifo',
        ]);
        $year = $company->financialYears()->first();
        $branch = $company->branches()->first();

        $godown = Godown::query()->create([
            'company_id' => $company->id,
            'name' => 'Main Store',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $group = ProductGroup::query()->create([
            'company_id' => $company->id,
            'name' => 'Goods',
            'is_active' => true,
        ]);
        $unit = Unit::query()->create([
            'company_id' => $company->id,
            'name' => 'Nos',
            'symbol' => 'Nos',
            'decimal_places' => 0,
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'company_id' => $company->id,
            'product_group_id' => $group->id,
            'primary_unit_id' => $unit->id,
            'name' => 'FIFO Widget',
            'code' => 'W-FIFO',
            'purchase_rate' => '10.00',
            'sales_rate' => '30.00',
            'opening_quantity' => '0',
            'opening_rate' => '0',
            'opening_value' => '0',
            'is_active' => true,
        ]);

        $stock = app(StockMovementService::class);
        $date = $year->start_date->toDateString();

        DB::transaction(function () use ($stock, $company, $branch, $year, $product, $godown, $date) {
            $stock->move(
                $company,
                $branch,
                $year,
                $product,
                $godown,
                $date,
                Quantity::scale('10', 4),
                '10.00',
                StockMovementType::In,
                $product,
            );

            $stock->move(
                $company,
                $branch,
                $year,
                $product,
                $godown,
                $date,
                Quantity::scale('5', 4),
                '20.00',
                StockMovementType::In,
                $product,
            );

            $out = $stock->move(
                $company,
                $branch,
                $year,
                $product,
                $godown,
                $date,
                -Quantity::scale('12', 4),
                '0.00',
                StockMovementType::Out,
                $product,
            );

            // 10@10 + 2@20 = 140
            $this->assertSame('140.00', ltrim((string) $out->value, '-'));
            $this->assertEqualsWithDelta(140.0, abs((float) $out->value), 0.001);

            $remaining = app(\Tally\Inventory\FifoCostService::class)
                ->inventoryValue($company->fresh(), $product->fresh(), $godown);

            // 3@20 = 60
            $this->assertSame('60.00', $remaining);
        });
    }
}
