<?php

namespace Tests\Feature\Inventory;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Brand $brand;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::first() ?? User::factory()->create();
        if (! $this->user->hasRole('super-admin')) {
            $this->user->assignRole($role);
        }

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TC-01',
        ]);
        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
        ]);
        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Warehouse',
            'code' => 'MW-01',
            'is_active' => true,
        ]);
        $this->uom = Uom::create([
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);
        $this->brand = Brand::create([
            'name' => 'Apex Brand',
            'code' => 'BR-APX',
            'is_active' => true,
        ]);
        $this->category = Category::create([
            'name' => 'Electronics',
            'code' => 'CAT-ELEC',
            'is_active' => true,
        ]);
    }

    public function test_stock_page_loads_and_displays_all_required_columns(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Solar Inverter 5kVA',
            'sku' => 'INV-5000',
            'serial_no' => 'SN-INV-99',
            'color_variant' => 'Midnight Blue',
            'base_uom_id' => $this->uom->id,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        StockLevel::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 25,
        ]);

        $response = $this->actingAs($this->user)->get(route('inventory.stock.index'));

        $response->assertStatus(200);
        $response->assertSee('Solar Inverter 5kVA');
        $response->assertSee('INV-5000');
        $response->assertSee('SN: SN-INV-99');
        $response->assertSee('Apex Brand');
        $response->assertSee('Electronics');
        $response->assertSee('Midnight Blue');
        $response->assertSee('Main Warehouse');
        $response->assertSee('PCS');
        $response->assertSee('25.00');
        $response->assertSee('In Stock');
    }

    public function test_stock_page_filters_by_low_stock(): void
    {
        $productNormal = Product::create([
            'name' => 'Normal Stock Product',
            'sku' => 'NORM-01',
            'serial_no' => 'SN-NORM-01',
            'base_uom_id' => $this->uom->id,
            'reorder_level' => 5,
            'is_active' => true,
        ]);
        StockLevel::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $productNormal->id,
            'uom_id' => $this->uom->id,
            'quantity' => 50,
        ]);

        $productLow = Product::create([
            'name' => 'Low Stock Product',
            'sku' => 'LOW-01',
            'serial_no' => 'SN-LOW-01',
            'base_uom_id' => $this->uom->id,
            'reorder_level' => 10,
            'is_active' => true,
        ]);
        StockLevel::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $productLow->id,
            'uom_id' => $this->uom->id,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($this->user)->get(route('inventory.stock.index', ['low_stock' => '1']));

        $response->assertStatus(200);
        $stockLevels = $response->viewData('stockLevels');
        $this->assertCount(1, $stockLevels);
        $this->assertEquals('Low Stock Product', $stockLevels->first()->product->name);
        $response->assertSee('Low Stock');
    }

    public function test_stock_page_filters_by_brand_and_search(): void
    {
        $otherBrand = Brand::create(['name' => 'Zeta Tech', 'code' => 'BR-ZET', 'is_active' => true]);

        $prod1 = Product::create([
            'brand_id' => $this->brand->id,
            'name' => 'Alpha Device',
            'sku' => 'ALPHA-1',
            'serial_no' => 'SN-ALPHA-1',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);
        StockLevel::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $prod1->id,
            'uom_id' => $this->uom->id,
            'quantity' => 10,
        ]);

        $prod2 = Product::create([
            'brand_id' => $otherBrand->id,
            'name' => 'Beta Sensor',
            'sku' => 'BETA-1',
            'serial_no' => 'SN-BETA-1',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);
        StockLevel::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $prod2->id,
            'uom_id' => $this->uom->id,
            'quantity' => 20,
        ]);

        // Filter by brand
        $response = $this->actingAs($this->user)->get(route('inventory.stock.index', ['brand_id' => $this->brand->id]));
        $response->assertStatus(200);
        $brandLevels = $response->viewData('stockLevels');
        $this->assertCount(1, $brandLevels);
        $this->assertEquals('Alpha Device', $brandLevels->first()->product->name);

        // Filter by search
        $searchResponse = $this->actingAs($this->user)->get(route('inventory.stock.index', ['search' => 'Sensor']));
        $searchResponse->assertStatus(200);
        $searchLevels = $searchResponse->viewData('stockLevels');
        $this->assertCount(1, $searchLevels);
        $this->assertEquals('Beta Sensor', $searchLevels->first()->product->name);
    }

    public function test_stock_page_displays_recent_movements(): void
    {
        $product = Product::create([
            'name' => 'Movement Test Product',
            'sku' => 'MOV-01',
            'serial_no' => 'SN-MOV-01',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        StockMovement::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $product->id,
            'uom_id' => $this->uom->id,
            'type' => 'purchase',
            'quantity' => 100,
            'balance_after' => 100,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('inventory.stock.index'));

        $response->assertStatus(200);
        $response->assertSee('Recent Stock Movements');
        $response->assertSee('Movement Test Product');
        $response->assertSee('purchase');
        $response->assertSee('100.00');
    }

    public function test_stock_page_sorting_works(): void
    {
        $prodA = Product::create([
            'name' => 'Apple Cable',
            'sku' => 'APL-01',
            'serial_no' => 'SN-APL-01',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);
        StockLevel::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $prodA->id,
            'uom_id' => $this->uom->id,
            'quantity' => 10,
        ]);

        $prodZ = Product::create([
            'name' => 'Zebra Scanner',
            'sku' => 'ZEB-01',
            'serial_no' => 'SN-ZEB-01',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);
        StockLevel::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $prodZ->id,
            'uom_id' => $this->uom->id,
            'quantity' => 50,
        ]);

        // Ascending by product name
        $ascResponse = $this->actingAs($this->user)->get(route('inventory.stock.index', ['sort' => 'product', 'direction' => 'asc']));
        $ascResponse->assertStatus(200);
        $ascLevels = $ascResponse->viewData('stockLevels');
        $this->assertEquals('Apple Cable', $ascLevels->first()->product->name);

        // Descending by product name
        $descResponse = $this->actingAs($this->user)->get(route('inventory.stock.index', ['sort' => 'product', 'direction' => 'desc']));
        $descResponse->assertStatus(200);
        $descLevels = $descResponse->viewData('stockLevels');
        $this->assertEquals('Zebra Scanner', $descLevels->first()->product->name);

        // Sorting by quantity
        $qtyResponse = $this->actingAs($this->user)->get(route('inventory.stock.index', ['sort' => 'quantity', 'direction' => 'desc']));
        $qtyResponse->assertStatus(200);
        $qtyLevels = $qtyResponse->viewData('stockLevels');
        $this->assertEquals('Zebra Scanner', $qtyLevels->first()->product->name);
    }
}
