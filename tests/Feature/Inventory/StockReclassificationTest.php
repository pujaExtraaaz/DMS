<?php

namespace Tests\Feature\Inventory;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Models\StockReclassification;
use App\Domains\Inventory\Models\StockTransfer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockReclassificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected Warehouse $warehouseMain;
    protected Warehouse $warehouseSecondary;
    protected Uom $boxUom;
    protected Brand $brand;
    protected Category $category;
    protected Product $productApple;
    protected Product $productOrange;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::first() ?? User::factory()->create();
        if (! $this->user->hasRole('super-admin')) {
            $this->user->assignRole($role);
        }

        $this->company = Company::create([
            'name' => 'Test Agri Corp',
            'code' => 'TAC-01',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
        ]);

        $this->warehouseMain = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Location',
            'code' => 'MAIN-LOC',
            'is_active' => true,
        ]);

        $this->warehouseSecondary = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Other Location',
            'code' => 'OTHER-LOC',
            'is_active' => true,
        ]);

        $this->boxUom = Uom::create([
            'name' => 'Box',
            'code' => 'BOX',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'name' => 'Fresh Orchard',
            'code' => 'BR-FO',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Fruits',
            'code' => 'CAT-FRT',
            'is_active' => true,
        ]);

        $this->productApple = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Apple',
            'sku' => 'APL-001',
            'serial_no' => 'SN-APL-001',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->boxUom->id,
            'purchase_price' => 50.00,
            'selling_price' => 75.00,
            'is_active' => true,
        ]);

        $this->productOrange = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Oranges',
            'sku' => 'ORG-001',
            'serial_no' => 'SN-ORG-001',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->boxUom->id,
            'purchase_price' => 45.00,
            'selling_price' => 70.00,
            'is_active' => true,
        ]);
    }

    public function test_transfers_index_page_loads_with_location_and_name_tabs(): void
    {
        $responseLocation = $this->actingAs($this->user)->get(route('inventory.transfers.index', ['tab' => 'location']));
        $responseLocation->assertStatus(200);
        $responseLocation->assertSee('Stock Transfers');
        $responseLocation->assertSee('Location Transfers');
        $responseLocation->assertSee('Item / Stock Name Transfers');

        $responseName = $this->actingAs($this->user)->get(route('inventory.transfers.index', ['tab' => 'name']));
        $responseName->assertStatus(200);
        $responseName->assertSee('Item / Stock Name Transfers');
        $responseName->assertSee('Reference No');
    }

    public function test_transfers_create_page_loads_with_both_transfer_types(): void
    {
        $response = $this->actingAs($this->user)->get(route('inventory.transfers.create'));
        $response->assertStatus(200);
        $response->assertSee('New Stock Transfer');
        $response->assertSee('Location Transfer');
        $response->assertSee('Item / Stock Name Transfer');
    }

    public function test_stock_availability_endpoint_returns_accurate_quantity(): void
    {
        StockLevel::create([
            'warehouse_id' => $this->warehouseMain->id,
            'product_id' => $this->productApple->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('inventory.transfers.stock-availability', [
            'product_id' => $this->productApple->id,
            'warehouse_id' => $this->warehouseMain->id,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'product_id' => $this->productApple->id,
            'product_name' => 'Apple',
            'available_quantity' => 100,
            'uom_code' => 'BOX',
        ]);
    }

    public function test_existing_location_transfer_remains_fully_functional(): void
    {
        $payload = [
            'transfer_date' => now()->format('Y-m-d'),
            'from_warehouse_id' => $this->warehouseMain->id,
            'to_warehouse_id' => $this->warehouseSecondary->id,
            'notes' => 'Standard warehouse relocation',
            'items' => [
                [
                    'product_id' => $this->productApple->id,
                    'uom_id' => $this->boxUom->id,
                    'quantity' => 5,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('inventory.transfers.store'), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('stock_transfers', [
            'from_warehouse_id' => $this->warehouseMain->id,
            'to_warehouse_id' => $this->warehouseSecondary->id,
            'status' => 'draft',
        ]);
    }

    public function test_stock_name_transfer_reclassifies_identity_without_changing_warehouse(): void
    {
        // Initial state: Apple has 100 BOX at Main Location
        StockLevel::create([
            'warehouse_id' => $this->warehouseMain->id,
            'product_id' => $this->productApple->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $payload = [
            'transfer_date' => now()->format('Y-m-d'),
            'warehouse_id' => $this->warehouseMain->id,
            'from_product_id' => $this->productApple->id,
            'to_product_id' => $this->productOrange->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 10,
            'notes' => 'Change identity of 10 boxes from Apple to Oranges',
        ];

        $response = $this->actingAs($this->user)->post(route('inventory.transfers.name-transfer'), $payload);
        $response->assertSessionHasNoErrors();

        // 1. Check StockReclassification record created
        $reclassification = StockReclassification::first();
        $this->assertNotNull($reclassification);
        $this->assertEquals($this->warehouseMain->id, $reclassification->warehouse_id);
        $this->assertEquals($this->productApple->id, $reclassification->from_product_id);
        $this->assertEquals($this->productOrange->id, $reclassification->to_product_id);
        $this->assertEquals(10, (float) $reclassification->quantity);
        $this->assertStringStartsWith('SNT-', $reclassification->reclassification_no);

        // 2. Check stock levels:
        // Apple at Main Location reduced from 100 to 90
        $appleLevel = StockLevel::where('warehouse_id', $this->warehouseMain->id)
            ->where('product_id', $this->productApple->id)
            ->first();
        $this->assertNotNull($appleLevel);
        $this->assertEquals(90.0, (float) $appleLevel->quantity);

        // Orange at Main Location increased from 0 to 10
        $orangeLevel = StockLevel::where('warehouse_id', $this->warehouseMain->id)
            ->where('product_id', $this->productOrange->id)
            ->first();
        $this->assertNotNull($orangeLevel);
        $this->assertEquals(10.0, (float) $orangeLevel->quantity);

        // Neither product exists at Secondary Warehouse
        $secondaryStock = StockLevel::where('warehouse_id', $this->warehouseSecondary->id)->count();
        $this->assertEquals(0, $secondaryStock);

        // 3. Check stock movement audit log
        $movements = StockMovement::where('type', 'reclassification')
            ->where('reference_type', StockReclassification::class)
            ->where('reference_id', $reclassification->id)
            ->get();

        $this->assertCount(2, $movements);
        $outMovement = $movements->firstWhere('product_id', $this->productApple->id);
        $inMovement = $movements->firstWhere('product_id', $this->productOrange->id);

        $this->assertNotNull($outMovement);
        $this->assertEquals(-10, (float) $outMovement->quantity);
        $this->assertEquals($this->warehouseMain->id, $outMovement->warehouse_id);

        $this->assertNotNull($inMovement);
        $this->assertEquals(10, (float) $inMovement->quantity);
        $this->assertEquals($this->warehouseMain->id, $inMovement->warehouse_id);

        // 4. Verify Product Master catalog records were NOT renamed or mutated
        $this->productApple->refresh();
        $this->productOrange->refresh();
        $this->assertEquals('Apple', $this->productApple->name);
        $this->assertEquals('APL-001', $this->productApple->sku);
        $this->assertEquals('Oranges', $this->productOrange->name);
        $this->assertEquals('ORG-001', $this->productOrange->sku);

        // 5. Check show page renders properly
        $showResponse = $this->actingAs($this->user)->get(route('inventory.transfers.reclassifications.show', $reclassification));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($reclassification->reclassification_no);
        $showResponse->assertSee('Apple');
        $showResponse->assertSee('Oranges');
        $showResponse->assertSee('Main Location');
    }

    public function test_reclassification_validation_fails_when_same_from_and_to_product(): void
    {
        StockLevel::create([
            'warehouse_id' => $this->warehouseMain->id,
            'product_id' => $this->productApple->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 100,
        ]);

        $payload = [
            'transfer_date' => now()->format('Y-m-d'),
            'warehouse_id' => $this->warehouseMain->id,
            'from_product_id' => $this->productApple->id,
            'to_product_id' => $this->productApple->id, // Same product!
            'uom_id' => $this->boxUom->id,
            'quantity' => 10,
        ];

        $response = $this->actingAs($this->user)->post(route('inventory.transfers.name-transfer'), $payload);
        $response->assertSessionHasErrors(['from_product_id']);
    }

    public function test_reclassification_validation_fails_when_insufficient_stock(): void
    {
        StockLevel::create([
            'warehouse_id' => $this->warehouseMain->id,
            'product_id' => $this->productApple->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 5, // Only 5 available
        ]);

        $payload = [
            'transfer_date' => now()->format('Y-m-d'),
            'warehouse_id' => $this->warehouseMain->id,
            'from_product_id' => $this->productApple->id,
            'to_product_id' => $this->productOrange->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 20, // Demands 20
        ];

        $response = $this->actingAs($this->user)->post(route('inventory.transfers.name-transfer'), $payload);
        $response->assertSessionHasErrors(['quantity']);

        // Stock remains unchanged
        $this->assertEquals(5, (float) StockLevel::where('product_id', $this->productApple->id)->value('quantity'));
        $this->assertEquals(0, StockReclassification::count());
    }

    public function test_reclassification_validation_fails_when_quantity_is_zero_or_negative(): void
    {
        $payload = [
            'transfer_date' => now()->format('Y-m-d'),
            'warehouse_id' => $this->warehouseMain->id,
            'from_product_id' => $this->productApple->id,
            'to_product_id' => $this->productOrange->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => -2,
        ];

        $response = $this->actingAs($this->user)->post(route('inventory.transfers.name-transfer'), $payload);
        $response->assertSessionHasErrors(['quantity']);
    }
}
