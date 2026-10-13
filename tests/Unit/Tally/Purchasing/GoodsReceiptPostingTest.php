<?php

namespace Tests\Unit\Tally\Purchasing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Models\Godown;
use Tally\Models\Product;
use Tally\Models\ProductGroup;
use Tally\Models\StockMovement;
use Tally\Models\Unit;
use Tally\Parties\PartyService;
use Tally\Purchasing\GoodsReceiptService;

class GoodsReceiptPostingTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_posting_goods_receipt_increases_stock_and_sets_posted(): void
    {
        $company = $this->makeCompany();
        $year = $company->financialYears()->first();
        $branch = $company->branches()->first();
        $user = User::factory()->create();

        $supplier = app(PartyService::class)->save($company, [
            'type' => 'supplier',
            'name' => 'GRN Vendor',
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
        $product = Product::query()->create([
            'company_id' => $company->id,
            'product_group_id' => $group->id,
            'primary_unit_id' => $unit->id,
            'name' => 'Widget',
            'code' => 'W-GRN',
            'purchase_rate' => '25.00',
            'sales_rate' => '40.00',
            'opening_quantity' => '0',
            'opening_rate' => '0',
            'opening_value' => '0',
            'is_active' => true,
        ]);

        $service = app(GoodsReceiptService::class);

        $receipt = $service->save($company, $branch, $year, $user, [
            'receipt_date' => $year->start_date->toDateString(),
            'supplier_ledger_id' => $supplier->ledger_id,
            'godown_id' => $godown->id,
            'receipt_kind' => 'goods',
            'status' => 'draft',
            'lines' => [
                [
                    'item_name' => 'Widget',
                    'product_id' => $product->id,
                    'ordered_qty' => '10',
                    'received_qty' => '10',
                    'rate' => '25.00',
                ],
            ],
        ]);

        $this->assertSame('draft', $receipt->status);

        $posted = $service->post($receipt, $user);

        $this->assertSame('posted', $posted->status);
        $this->assertFalse((bool) $posted->is_invoiced);
        $this->assertNotNull($posted->stock_transaction_id);

        $qty = (float) StockMovement::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->where('godown_id', $godown->id)
            ->where('is_reversal', false)
            ->sum('quantity');

        $this->assertEqualsWithDelta(10.0, $qty, 0.0001);
    }
}
