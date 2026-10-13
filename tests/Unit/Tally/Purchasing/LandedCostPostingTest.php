<?php

namespace Tests\Unit\Tally\Purchasing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Models\Godown;
use Tally\Models\Product;
use Tally\Models\ProductGroup;
use Tally\Models\Unit;
use Tally\Models\Voucher;
use Tally\Parties\PartyService;
use Tally\Purchasing\GoodsReceiptService;
use Tally\Purchasing\LandedCostService;

class LandedCostPostingTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_posting_landed_cost_allocates_and_posts_journal(): void
    {
        $company = $this->makeCompany();
        $year = $company->financialYears()->first();
        $branch = $company->branches()->first();
        $user = User::factory()->create();

        $supplier = app(PartyService::class)->save($company, [
            'type' => 'supplier',
            'name' => 'LC Vendor',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

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

        $productA = Product::query()->create([
            'company_id' => $company->id,
            'product_group_id' => $group->id,
            'primary_unit_id' => $unit->id,
            'name' => 'Widget A',
            'code' => 'WA-LC',
            'purchase_rate' => '100.00',
            'sales_rate' => '150.00',
            'opening_quantity' => '0',
            'opening_rate' => '0',
            'opening_value' => '0',
            'is_active' => true,
        ]);
        $productB = Product::query()->create([
            'company_id' => $company->id,
            'product_group_id' => $group->id,
            'primary_unit_id' => $unit->id,
            'name' => 'Widget B',
            'code' => 'WB-LC',
            'purchase_rate' => '50.00',
            'sales_rate' => '80.00',
            'opening_quantity' => '0',
            'opening_rate' => '0',
            'opening_value' => '0',
            'is_active' => true,
        ]);

        $receipt = app(GoodsReceiptService::class)->save($company, $branch, $year, $user, [
            'receipt_date' => $year->start_date->toDateString(),
            'supplier_ledger_id' => $supplier->ledger_id,
            'godown_id' => $godown->id,
            'receipt_kind' => 'goods',
            'status' => 'draft',
            'lines' => [
                [
                    'item_name' => 'Widget A',
                    'product_id' => $productA->id,
                    'ordered_qty' => '10',
                    'received_qty' => '10',
                    'rate' => '100.00',
                ],
                [
                    'item_name' => 'Widget B',
                    'product_id' => $productB->id,
                    'ordered_qty' => '10',
                    'received_qty' => '10',
                    'rate' => '50.00',
                ],
            ],
        ]);

        $service = app(LandedCostService::class);
        $lc = $service->save($company, $branch, $year, [
            'voucher_date' => $year->start_date->toDateString(),
            'goods_receipt_id' => $receipt->id,
            'freight' => '90.00',
            'duty' => '60.00',
            'other_charges' => '0.00',
            'allocation_basis' => 'value',
            'status' => 'draft',
        ]);

        $this->assertSame('draft', $lc->status);

        $posted = $service->post($lc, $user);

        $this->assertSame('posted', $posted->status);
        $this->assertNotNull($posted->voucher_id);
        $this->assertNotNull($posted->posted_at);
        $this->assertCount(2, $posted->allocations);

        $allocA = $posted->allocations->firstWhere('product_id', $productA->id);
        $allocB = $posted->allocations->firstWhere('product_id', $productB->id);
        $this->assertNotNull($allocA);
        $this->assertNotNull($allocB);
        // Value basis: A=1000, B=500 → 2:1 of 150 → 100 / 50
        $this->assertSame('100.00', (string) $allocA->allocated_charges);
        $this->assertSame('50.00', (string) $allocB->allocated_charges);

        $voucher = Voucher::query()->findOrFail($posted->voucher_id);
        $this->assertTrue($voucher->isPosted());
        $this->assertSame('150.00', (string) $voucher->total_debit);
        $this->assertSame('150.00', (string) $voucher->total_credit);
    }

    public function test_qty_basis_allocates_equally_for_equal_quantities(): void
    {
        $company = $this->makeCompany();
        $service = app(LandedCostService::class);

        $rows = $service->computeAllocations([
            ['product_id' => 1, 'item_name' => 'A', 'quantity' => 5.0, 'base_value' => 900.0, 'unit_cost' => 180.0],
            ['product_id' => 2, 'item_name' => 'B', 'quantity' => 5.0, 'base_value' => 100.0, 'unit_cost' => 20.0],
        ], 'qty', 40.0);

        $this->assertSame(20.0, $rows[0]['allocated_charges']);
        $this->assertSame(20.0, $rows[1]['allocated_charges']);
    }
}
