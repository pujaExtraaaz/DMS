<?php

namespace Tests\Feature\Master;

use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockCostConsumption;
use App\Domains\Inventory\Models\StockCostLayer;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Inventory\Services\StockValuationService;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductSupplyCostingAndBehaviourTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Uom $baseUom;
    protected Uom $altUom;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Supply Test Co',
            'code' => 'STC-01',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->assignRole($role);

        $this->baseUom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);
        $this->altUom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Box',
            'code' => 'BOX',
            'is_active' => true,
        ]);
    }

    public function test_product_form_saves_supply_type_tax_basis_costing_and_behaviour(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Capital Drill',
            'sku' => 'CAP-DRILL-01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'dimensions_not_applicable' => 1,
            'has_dimensions' => 1,
            'tax_rate' => 18,
            'purchase_price' => 118,
            'purchase_price_tax_basis' => 'inclusive',
            'supply_type' => 'capital_goods',
            'costing_method' => 'std_cost',
            'market_valuation_method' => 'last_sale_price',
            'provide_behaviour' => 1,
            'ignore_physical_count_diff' => 0,
            'ignore_negative_balances' => 1,
            'treat_sales_as_new_manufacture' => 0,
            'treat_purchases_as_consumed' => 1,
            'treat_rejections_inward_as_scrap' => 0,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'CAP-DRILL-01')->first();
        $this->assertNotNull($product);
        $this->assertSame('capital_goods', $product->supply_type);
        $this->assertSame('inclusive', $product->purchase_price_tax_basis);
        $this->assertSame('std_cost', $product->costing_method);
        $this->assertSame('last_sale_price', $product->market_valuation_method);
        $this->assertTrue($product->provide_behaviour);
        $this->assertTrue($product->treat_purchases_as_consumed);
        $this->assertTrue($product->ignore_negative_balances);
        $this->assertFalse($product->treat_sales_as_new_manufacture);
        $this->assertEquals(100.0, $product->exclusivePurchasePrice());
    }

    public function test_purchase_is_consumed_immediately_when_behaviour_is_yes(): void
    {
        $consumed = $this->makeProduct('Consumed Item', 'CON-01', [
            'provide_behaviour' => true,
            'treat_purchases_as_consumed' => true,
        ]);
        $stocked = $this->makeProduct('Stocked Item', 'STK-01');

        $this->actingAs($this->user)->post(route('inventory.purchases.store'), [
            'purchase_date' => now()->toDateString(),
            'supplier_name' => 'Acme Supplies',
            'items' => [
                [
                    'product_id' => $consumed->id,
                    'uom_id' => $this->baseUom->id,
                    'quantity' => 6,
                    'unit_cost' => 40,
                ],
                [
                    'product_id' => $stocked->id,
                    'uom_id' => $this->baseUom->id,
                    'quantity' => 6,
                    'unit_cost' => 40,
                ],
            ],
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $this->assertEquals(0.0, $this->onHand($consumed));
        $this->assertEquals(6.0, $this->onHand($stocked));
        $this->assertTrue(
            StockMovement::query()->where('product_id', $consumed->id)->where('type', 'consumption')->exists()
        );
        $this->assertFalse(
            StockMovement::query()->where('product_id', $stocked->id)->where('type', 'consumption')->exists()
        );
    }

    public function test_existing_stock_is_kept_when_a_later_purchase_is_consumed(): void
    {
        $product = $this->makeProduct('Mixed Item', 'MIX-01');
        $service = app(StockMovementService::class);

        $this->actingAs($this->user);
        $service->recordIn($product, $this->baseUom, 5, 'purchase', null, 'Opening buy', $this->user, null, 10);

        $product->update([
            'provide_behaviour' => true,
            'treat_purchases_as_consumed' => true,
        ]);

        $service->recordIn($product->fresh(), $this->baseUom, 4, 'purchase', null, 'Later buy', $this->user, null, 12);

        $this->assertEquals(5.0, $this->onHand($product));
    }

    public function test_sale_as_new_manufacture_does_not_reduce_existing_stock(): void
    {
        $product = $this->makeProduct('Made On Sale', 'MFG-01', [
            'provide_behaviour' => true,
            'treat_sales_as_new_manufacture' => true,
            'purchase_price' => 25,
        ]);
        $service = app(StockMovementService::class);
        $this->actingAs($this->user);
        $service->recordIn($product, $this->baseUom, 8, 'opening', null, null, $this->user, null, 25);

        $service->recordOut($product->fresh(), $this->baseUom, 3, 'sale', null, 'Sale', $this->user);

        $this->assertEquals(8.0, $this->onHand($product));
        $this->assertTrue(StockMovement::query()->where('product_id', $product->id)->where('type', 'manufacture')->exists());
        $this->assertTrue(StockMovement::query()->where('product_id', $product->id)->where('type', 'sale')->exists());
    }

    public function test_rejection_inward_is_scrapped_instead_of_added_to_stock(): void
    {
        $product = $this->makeProduct('Scrap Return', 'SCR-01', [
            'provide_behaviour' => true,
            'treat_rejections_inward_as_scrap' => true,
        ]);
        $service = app(StockMovementService::class);
        $this->actingAs($this->user);
        $service->recordIn($product, $this->baseUom, 8, 'opening', null, null, $this->user, null, 15);
        $service->recordIn($product->fresh(), $this->baseUom, 4, 'return', null, 'Rejection inward', $this->user, null, 15);

        $this->assertEquals(8.0, $this->onHand($product));
        $this->assertTrue(StockMovement::query()->where('product_id', $product->id)->where('type', 'scrap')->exists());
    }

    public function test_physical_recount_difference_is_ignored(): void
    {
        $product = $this->makeProduct('Counted Item', 'CNT-01', [
            'provide_behaviour' => true,
            'ignore_physical_count_diff' => true,
        ]);
        $service = app(StockMovementService::class);
        $this->actingAs($this->user);
        $service->recordIn($product, $this->baseUom, 10, 'opening', null, null, $this->user, null, 9);

        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-RECOUNT-1',
            'adjustment_date' => now()->toDateString(),
            'reason' => 'recount',
            'status' => 'posted',
            'created_by' => $this->user->id,
        ]);

        $service->recordIn($product->fresh(), $this->baseUom, 5, 'adjustment', $adjustment, 'Recount', $this->user);

        $this->assertEquals(10.0, $this->onHand($product));
        $movement = StockMovement::query()->where('product_id', $product->id)->where('type', 'adjustment')->first();
        $this->assertNotNull($movement);
        $this->assertEquals(0.0, (float) $movement->quantity);
    }

    public function test_average_cost_issues_at_the_weighted_average(): void
    {
        $product = $this->makeProduct('Average Item', 'AVG-01', [
            'costing_method' => 'avg_cost',
            'purchase_price' => 1,
        ]);
        $service = app(StockMovementService::class);
        $this->actingAs($this->user);
        $service->recordIn($product, $this->baseUom, 10, 'purchase', null, null, $this->user, null, 10);
        $service->recordIn($product->fresh(), $this->baseUom, 10, 'purchase', null, null, $this->user, null, 30);
        $service->recordOut($product->fresh(), $this->baseUom, 10, 'sale', null, null, $this->user);

        $issued = StockCostConsumption::query()->where('product_id', $product->id)->get();
        $this->assertNotEmpty($issued);
        $this->assertEquals(20.0, round((float) $issued->avg('unit_cost'), 2));
    }

    public function test_at_zero_cost_stores_layers_at_zero(): void
    {
        $product = $this->makeProduct('Zero Cost Item', 'ZERO-01', [
            'costing_method' => 'at_zero_cost',
            'purchase_price' => 80,
        ]);
        $this->actingAs($this->user);
        app(StockMovementService::class)->recordIn($product, $this->baseUom, 5, 'purchase', null, null, $this->user, null, 80);

        $layer = StockCostLayer::query()->where('product_id', $product->id)->first();
        $this->assertNotNull($layer);
        $this->assertEquals(0.0, (float) $layer->unit_cost);
        $this->assertEquals(0.0, app(StockValuationService::class)->inventoryValue(null, $this->company->id)['at_cost']);
    }

    public function test_negative_balances_are_left_out_of_market_value(): void
    {
        Config::set('dms.allow_negative_stock', false);

        $ignored = $this->makeProduct('Negative Ignored', 'NEG-01', [
            'provide_behaviour' => true,
            'ignore_negative_balances' => true,
            'market_valuation_method' => 'std_price',
            'selling_price' => 80,
            'purchase_price' => 50,
        ]);
        $valued = $this->makeProduct('Negative Valued', 'NEG-02', [
            'market_valuation_method' => 'std_price',
            'selling_price' => 80,
            'purchase_price' => 50,
        ]);

        $service = app(StockMovementService::class);
        $this->actingAs($this->user);
        $service->recordOut($ignored, $this->baseUom, 4, 'sale', null, null, $this->user);
        Config::set('dms.allow_negative_stock', true);
        $service->recordOut($valued, $this->baseUom, 4, 'sale', null, null, $this->user);

        $this->assertEquals(-4.0, $this->onHand($ignored));
        $value = app(StockValuationService::class)->inventoryValue();
        $this->assertEquals(-640.0, $value['at_selling']);
        $this->assertEquals(-320.0, $value['at_market']);
    }

    private function makeProduct(string $name, string $sku, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'company_id' => $this->company->id,
            'name' => $name,
            'sku' => $sku,
            'serial_no' => 'SN-'.$sku,
            'base_uom_id' => $this->baseUom->id,
            'purchase_price' => 10,
            'selling_price' => 20,
            'tax_rate' => 18,
            'is_active' => true,
        ], $overrides));
    }

    private function onHand(Product $product): float
    {
        return (float) StockLevel::query()->where('product_id', $product->id)->sum('quantity');
    }
}
