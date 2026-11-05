<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductUomConversionAndPricingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Uom $mtrUom;
    protected Uom $rollUom;
    protected Brand $brand;
    protected Category $category;
    protected SubCategory $subCategory;
    protected ProductType $productType;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Wire & Cable Industries',
            'code' => 'WCI-01',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $this->mtrUom = Uom::firstOrCreate(['code' => 'MTR'], [
            'company_id' => $this->company->id,
            'name' => 'Meter',
            'is_active' => true,
        ]);

        $this->rollUom = Uom::firstOrCreate(['code' => 'ROLL'], [
            'company_id' => $this->company->id,
            'name' => 'Roll',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Polycab',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Cables',
            'code' => 'CAT-CAB',
            'is_active' => true,
        ]);

        $this->subCategory = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Power Cables',
            'code' => 'SUB-PWR',
            'is_active' => true,
        ]);

        $this->productType = ProductType::firstOrCreate(['name' => 'RAW MATERIAL'], [
            'company_id' => $this->company->id,
            'code' => 'RM',
            'is_active' => true,
        ]);
    }

    public function test_base_uom_is_mandatory(): void
    {
        $payload = [
            'name' => 'Copper Flex Wire 2.5mm',
            'sku' => 'CFW-2.5',
            'alternate_uom_id' => $this->rollUom->id,
            'conversion_factor' => 100,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('masters.products.store'), $payload);

        $response->assertSessionHasErrors('base_uom_id');
    }

    public function test_alternative_uom_is_mandatory(): void
    {
        $payload = [
            'name' => 'Copper Flex Wire 2.5mm',
            'sku' => 'CFW-2.5',
            'base_uom_id' => $this->mtrUom->id,
            'conversion_factor' => 100,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('masters.products.store'), $payload);

        $response->assertSessionHasErrors('alternate_uom_id');
    }

    public function test_alternative_uom_can_be_marked_not_applicable(): void
    {
        $payload = [
            'name' => 'Single Unit Cable',
            'sku' => 'SU-CABLE-1',
            'base_uom_id' => $this->mtrUom->id,
            'alternate_uom_not_applicable' => 1,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertRedirect(route('masters.products.index'));

        $product = \App\Domains\Master\Models\Product::where('sku', 'SU-CABLE-1')->first();
        $this->assertNotNull($product);
        $this->assertNull($product->alternate_uom_id);
    }

    public function test_alternative_uom_cannot_be_same_as_base_uom(): void
    {
        $payload = [
            'name' => 'Copper Flex Wire 2.5mm',
            'sku' => 'CFW-2.5',
            'base_uom_id' => $this->mtrUom->id,
            'alternate_uom_id' => $this->mtrUom->id, // Same unit!
            'conversion_factor' => 1,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('masters.products.store'), $payload);

        $response->assertSessionHasErrors('alternate_uom_id');
    }

    public function test_conversion_factor_is_mandatory_and_must_be_greater_than_zero(): void
    {
        $payloadZero = [
            'name' => 'Copper Flex Wire 2.5mm',
            'sku' => 'CFW-2.5',
            'base_uom_id' => $this->mtrUom->id,
            'alternate_uom_id' => $this->rollUom->id,
            'conversion_factor' => 0,
        ];

        $responseZero = $this->actingAs($this->user)
            ->post(route('masters.products.store'), $payloadZero);

        $responseZero->assertSessionHasErrors('conversion_factor');

        $payloadNegative = [
            'name' => 'Copper Flex Wire 2.5mm',
            'sku' => 'CFW-2.5',
            'base_uom_id' => $this->mtrUom->id,
            'alternate_uom_id' => $this->rollUom->id,
            'conversion_factor' => -5,
        ];

        $responseNegative = $this->actingAs($this->user)
            ->post(route('masters.products.store'), $payloadNegative);

        $responseNegative->assertSessionHasErrors('conversion_factor');
    }

    public function test_can_create_product_with_base_and_alternative_uom_and_derived_pricing(): void
    {
        $payload = [
            'name' => 'Copper Flex Wire 2.5mm',
            'sku' => 'CFW-2.5',
            'model' => 'CFW-2026',
            'product_type_id' => $this->productType->id,
            'product_type' => $this->productType->name,
            'base_uom_id' => $this->mtrUom->id,
            'alternate_uom_id' => $this->rollUom->id,
            'conversion_factor' => 100, // 1 ROLL = 100 MTR
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'tax_rate' => 18,
            'purchase_price' => 15,
            'trade_price' => 18,
            'selling_price' => 20,
            'calculation_mrp' => 25,
            // alt prices intentionally left blank to test server-side auto-calculation: Base * 100
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('masters.products.store'), $payload);

        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'CFW-2.5')->first();
        $this->assertNotNull($product);
        $this->assertSame($this->mtrUom->id, (int) $product->base_uom_id);
        $this->assertSame($this->rollUom->id, (int) $product->alternate_uom_id);
        $this->assertEquals(100, (float) $product->conversion_factor);

        // Check Base Prices
        $this->assertEquals(15, (float) $product->purchase_price);
        $this->assertEquals(18, (float) $product->trade_price);
        $this->assertEquals(20, (float) $product->selling_price);
        $this->assertEquals(25, (float) $product->calculation_mrp);

        // Check Auto-Calculated Alternative Prices (Base * 100)
        $this->assertEquals(1500, (float) $product->alt_purchase_price);
        $this->assertEquals(1800, (float) $product->alt_trade_price);
        $this->assertEquals(2000, (float) $product->alt_selling_price);
        $this->assertEquals(2500, (float) $product->alt_calculation_mrp);

        // Check synchronized product_uoms entries
        $baseUomRow = ProductUom::where('product_id', $product->id)->where('uom_id', $this->mtrUom->id)->first();
        $this->assertNotNull($baseUomRow);
        $this->assertTrue((bool) $baseUomRow->is_base);
        $this->assertEquals(1, (float) $baseUomRow->conversion_factor);

        $altUomRow = ProductUom::where('product_id', $product->id)->where('uom_id', $this->rollUom->id)->first();
        $this->assertNotNull($altUomRow);
        $this->assertFalse((bool) $altUomRow->is_base);
        $this->assertEquals(100, (float) $altUomRow->conversion_factor);
        $this->assertEquals(2000, (float) $altUomRow->selling_price);
    }

    public function test_can_manually_override_alternative_pricing(): void
    {
        $payload = [
            'name' => 'Industrial Cat6 Cable',
            'sku' => 'CAT6-IND-305',
            'base_uom_id' => $this->mtrUom->id,
            'alternate_uom_id' => $this->rollUom->id,
            'conversion_factor' => 305, // 1 ROLL = 305 MTR
            'purchase_price' => 10,
            'trade_price' => 12,
            'selling_price' => 15,
            'calculation_mrp' => 20,
            // Manual overrides instead of strict Base * 305
            'alt_purchase_price' => 2900,
            'alt_trade_price' => 3500,
            'alt_selling_price' => 4300,
            'alt_calculation_mrp' => 5800,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('masters.products.store'), $payload);

        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'CAT6-IND-305')->first();
        $this->assertNotNull($product);

        $this->assertEquals(2900, (float) $product->alt_purchase_price);
        $this->assertEquals(3500, (float) $product->alt_trade_price);
        $this->assertEquals(4300, (float) $product->alt_selling_price);
        $this->assertEquals(5800, (float) $product->alt_calculation_mrp);

        $altUomRow = ProductUom::where('product_id', $product->id)->where('uom_id', $this->rollUom->id)->first();
        $this->assertEquals(4300, (float) $altUomRow->selling_price);
        $this->assertEquals(2900, (float) $altUomRow->purchase_price);
    }

    public function test_stock_movement_deducts_and_adds_stock_in_base_uom_without_duplication(): void
    {
        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Headquarter Branch',
            'code' => 'BR-HQ',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Main Central Warehouse',
            'code' => 'WH-MAIN',
            'is_active' => true,
        ]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Armoured Power Cable 4C 16sqmm',
            'sku' => 'APC-4C-16',
            'serial_no' => 'PRD-SN-APC16',
            'base_uom_id' => $this->mtrUom->id,
            'alternate_uom_id' => $this->rollUom->id,
            'conversion_factor' => 100, // 1 ROLL = 100 MTR
            'purchase_price' => 200,
            'selling_price' => 250,
            'alt_purchase_price' => 20000,
            'alt_selling_price' => 25000,
            'is_active' => true,
        ]);

        ProductUom::create([
            'product_id' => $product->id,
            'uom_id' => $this->mtrUom->id,
            'conversion_factor' => 1,
            'is_base' => true,
        ]);

        ProductUom::create([
            'product_id' => $product->id,
            'uom_id' => $this->rollUom->id,
            'conversion_factor' => 100,
            'is_base' => false,
            'selling_price' => 25000,
            'purchase_price' => 20000,
        ]);

        $service = app(StockMovementService::class);

        // Receive 5 ROLL (which is 5 * 100 = 500 MTR)
        $inMovement = $service->recordIn(
            product: $product,
            uom: $this->rollUom,
            quantity: 5,
            type: 'receipt',
            notes: 'Purchase receipt in ROLL',
            warehouseId: $warehouse->id,
            unitCost: 20000,
        );

        $this->assertNotNull($inMovement);
        // Verify movement converted quantity to Base UOM
        $this->assertEquals(500, (float) $inMovement->quantity);
        $this->assertSame($this->mtrUom->id, (int) $inMovement->uom_id);

        // Verify StockLevel in warehouse is in Base UOM
        $stockLevel = StockLevel::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($stockLevel);
        $this->assertSame($this->mtrUom->id, (int) $stockLevel->uom_id);
        $this->assertEquals(500, (float) $stockLevel->quantity);

        // Dispatch 2 ROLL (which is 2 * 100 = 200 MTR)
        $outMovement = $service->recordOut(
            product: $product,
            uom: $this->rollUom,
            quantity: 2,
            type: 'delivery',
            notes: 'Sales invoice dispatch in ROLL',
            warehouseId: $warehouse->id,
        );

        $this->assertNotNull($outMovement);
        $this->assertEquals(-200, (float) $outMovement->quantity);
        $this->assertSame($this->mtrUom->id, (int) $outMovement->uom_id);

        // StockLevel balance should now be 500 - 200 = 300 MTR
        $stockLevel->refresh();
        $this->assertEquals(300, (float) $stockLevel->quantity);

        // Ensure no duplicate stock levels were created under the ROLL UOM
        $altStockCount = StockLevel::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->where('uom_id', $this->rollUom->id)
            ->count();

        $this->assertSame(0, $altStockCount);
    }
}
