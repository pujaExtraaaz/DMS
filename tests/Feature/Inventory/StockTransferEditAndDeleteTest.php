<?php

namespace Tests\Feature\Inventory;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Models\StockReclassification;
use App\Domains\Inventory\Models\StockTransfer;
use App\Domains\Inventory\Models\StockTransferItem;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockTransferEditAndDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected Warehouse $warehouseSource;
    protected Warehouse $warehouseDest;
    protected Uom $boxUom;
    protected Brand $brand;
    protected Category $category;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::first() ?? User::factory()->create();
        if (! $this->user->hasRole('super-admin')) {
            $this->user->assignRole($role);
        }

        $this->company = Company::create([
            'name' => 'Test Corp',
            'code' => 'TC-01',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
        ]);

        $this->warehouseSource = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Source Warehouse',
            'code' => 'WH-SRC',
            'is_active' => true,
        ]);

        $this->warehouseDest = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Destination Warehouse',
            'code' => 'WH-DST',
            'is_active' => true,
        ]);

        $this->boxUom = Uom::create([
            'name' => 'Box',
            'code' => 'BOX',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'name' => 'Global Brand',
            'code' => 'GB-01',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Hardware',
            'code' => 'HW-01',
            'is_active' => true,
        ]);

        $this->productA = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Product Alpha',
            'sku' => 'SKU-ALPHA',
            'serial_no' => 'SN-ALPHA-01',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->boxUom->id,
            'purchase_price' => 100.00,
            'selling_price' => 150.00,
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Product Beta',
            'sku' => 'SKU-BETA',
            'serial_no' => 'SN-BETA-01',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->boxUom->id,
            'purchase_price' => 80.00,
            'selling_price' => 120.00,
            'is_active' => true,
        ]);
    }

    public function test_transfers_index_displays_view_edit_and_delete_buttons(): void
    {
        $transfer = StockTransfer::create([
            'transfer_no' => 'ST-2026-0001',
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $this->warehouseSource->id,
            'to_warehouse_id' => $this->warehouseDest->id,
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('inventory.transfers.index', ['tab' => 'location']));
        $response->assertStatus(200);
        $response->assertSee('View');
        $response->assertSee('Edit');
        $response->assertSee('Delete');
        $response->assertSee('ST-2026-0001');
    }

    public function test_user_can_view_edit_page_for_draft_transfer(): void
    {
        $transfer = StockTransfer::create([
            'transfer_no' => 'ST-2026-0002',
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $this->warehouseSource->id,
            'to_warehouse_id' => $this->warehouseDest->id,
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->productA->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($this->user)->get(route('inventory.transfers.edit', $transfer));
        $response->assertStatus(200);
        $response->assertSee('ST-2026-0002');
        $response->assertSee('Save Changes');
    }

    public function test_user_cannot_edit_received_transfer(): void
    {
        $transfer = StockTransfer::create([
            'transfer_no' => 'ST-2026-0003',
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $this->warehouseSource->id,
            'to_warehouse_id' => $this->warehouseDest->id,
            'status' => 'received',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('inventory.transfers.edit', $transfer));
        $response->assertRedirect(route('inventory.transfers.show', $transfer));
        $response->assertSessionHas('error');
    }

    public function test_user_can_update_draft_location_transfer(): void
    {
        $transfer = StockTransfer::create([
            'transfer_no' => 'ST-2026-0004',
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $this->warehouseSource->id,
            'to_warehouse_id' => $this->warehouseDest->id,
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->productA->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 5,
        ]);

        $updateData = [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $this->warehouseSource->id,
            'to_warehouse_id' => $this->warehouseDest->id,
            'notes' => 'Updated draft transfer notes',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'uom_id' => $this->boxUom->id,
                    'quantity' => 15,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->put(route('inventory.transfers.update', $transfer), $updateData);
        $response->assertRedirect(route('inventory.transfers.show', $transfer));

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'notes' => 'Updated draft transfer notes',
        ]);

        $this->assertDatabaseHas('stock_transfer_items', [
            'stock_transfer_id' => $transfer->id,
            'quantity' => 15,
        ]);
    }

    public function test_user_can_delete_draft_location_transfer(): void
    {
        $transfer = StockTransfer::create([
            'transfer_no' => 'ST-2026-0005',
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $this->warehouseSource->id,
            'to_warehouse_id' => $this->warehouseDest->id,
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->productA->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 5,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(route('inventory.transfers.destroy', $transfer));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('stock_transfers', ['id' => $transfer->id]);
        $this->assertDatabaseMissing('stock_transfer_items', ['stock_transfer_id' => $transfer->id]);
    }

    public function test_user_can_delete_in_transit_transfer_safely_restoring_source_stock(): void
    {
        // 1. Give source warehouse 20 units
        StockLevel::create([
            'warehouse_id' => $this->warehouseSource->id,
            'product_id' => $this->productA->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 20,
            'reserved_quantity' => 0,
        ]);

        $transfer = StockTransfer::create([
            'transfer_no' => 'ST-2026-0006',
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $this->warehouseSource->id,
            'to_warehouse_id' => $this->warehouseDest->id,
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->productA->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 8,
        ]);

        // 2. Dispatch transfer -> Deducts 8 from source (20 - 8 = 12 remaining)
        $movementService = app(StockMovementService::class);
        $movementService->recordOut(
            product: $this->productA,
            uom: $this->boxUom,
            quantity: 8,
            type: 'transfer_out',
            reference: $transfer,
            notes: "Transfer {$transfer->transfer_no} out",
            user: $this->user,
            warehouseId: $this->warehouseSource->id,
        );
        $transfer->update(['status' => 'in_transit']);

        $sourceLevel = StockLevel::where('warehouse_id', $this->warehouseSource->id)
            ->where('product_id', $this->productA->id)
            ->first();
        $this->assertEquals(12, $sourceLevel->quantity);

        // 3. Delete in_transit transfer via API -> Should safely restore 8 to source warehouse (12 + 8 = 20)
        $response = $this->actingAs($this->user)->deleteJson(route('inventory.transfers.destroy', $transfer));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $sourceLevel->refresh();
        $this->assertEquals(20, $sourceLevel->quantity);

        $this->assertDatabaseMissing('stock_transfers', ['id' => $transfer->id]);
    }

    public function test_user_cannot_delete_received_transfer(): void
    {
        $transfer = StockTransfer::create([
            'transfer_no' => 'ST-2026-0007',
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $this->warehouseSource->id,
            'to_warehouse_id' => $this->warehouseDest->id,
            'status' => 'received',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(route('inventory.transfers.destroy', $transfer));
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);

        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id]);
    }

    public function test_user_can_edit_and_update_reclassification(): void
    {
        // 1. Setup initial stock and reclassify
        StockLevel::create([
            'warehouse_id' => $this->warehouseSource->id,
            'product_id' => $this->productA->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $movementService = app(StockMovementService::class);
        $reclassification = StockReclassification::create([
            'reclassification_no' => 'REC-2026-0001',
            'reclassification_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouseSource->id,
            'from_product_id' => $this->productA->id,
            'to_product_id' => $this->productB->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 10,
            'notes' => 'Initial reclassification',
            'created_by' => $this->user->id,
        ]);

        $movementService->recordOut(
            product: $this->productA,
            uom: $this->boxUom,
            quantity: 10,
            type: 'reclassification_out',
            reference: $reclassification,
            notes: "Reclassified out to {$this->productB->name}",
            user: $this->user,
            warehouseId: $this->warehouseSource->id,
        );

        $movementService->recordIn(
            product: $this->productB,
            uom: $this->boxUom,
            quantity: 10,
            type: 'reclassification_in',
            reference: $reclassification,
            notes: "Reclassified in from {$this->productA->name}",
            user: $this->user,
            warehouseId: $this->warehouseSource->id,
        );

        // 2. View edit page
        $response = $this->actingAs($this->user)->get(route('inventory.transfers.reclassifications.edit', $reclassification));
        $response->assertStatus(200);
        $response->assertSee('REC-2026-0001');
        $response->assertSee('Update Name Transfer');

        // 3. Update quantity from 10 to 15
        $updateData = [
            'transfer_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouseSource->id,
            'from_product_id' => $this->productA->id,
            'to_product_id' => $this->productB->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 15,
            'notes' => 'Updated reclassification quantity',
        ];

        $response = $this->actingAs($this->user)->put(route('inventory.transfers.reclassifications.update', $reclassification), $updateData);
        $response->assertRedirect(route('inventory.transfers.reclassifications.show', $reclassification));

        // Source product A: 50 - 15 = 35
        $levelA = StockLevel::where('warehouse_id', $this->warehouseSource->id)->where('product_id', $this->productA->id)->first();
        $this->assertEquals(35, $levelA->quantity);

        // Destination product B: 15
        $levelB = StockLevel::where('warehouse_id', $this->warehouseSource->id)->where('product_id', $this->productB->id)->first();
        $this->assertEquals(15, $levelB->quantity);
    }

    public function test_user_can_delete_reclassification_safely_restoring_source_stock(): void
    {
        // 1. Setup initial stock
        StockLevel::create([
            'warehouse_id' => $this->warehouseSource->id,
            'product_id' => $this->productA->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $movementService = app(StockMovementService::class);
        $reclassification = StockReclassification::create([
            'reclassification_no' => 'REC-2026-0002',
            'reclassification_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouseSource->id,
            'from_product_id' => $this->productA->id,
            'to_product_id' => $this->productB->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 10,
            'notes' => 'To delete',
            'created_by' => $this->user->id,
        ]);

        $movementService->recordOut(
            product: $this->productA,
            uom: $this->boxUom,
            quantity: 10,
            type: 'reclassification_out',
            reference: $reclassification,
            notes: 'Test',
            user: $this->user,
            warehouseId: $this->warehouseSource->id,
        );

        $movementService->recordIn(
            product: $this->productB,
            uom: $this->boxUom,
            quantity: 10,
            type: 'reclassification_in',
            reference: $reclassification,
            notes: 'Test',
            user: $this->user,
            warehouseId: $this->warehouseSource->id,
        );

        // Before delete: A has 40, B has 10
        $levelA = StockLevel::where('warehouse_id', $this->warehouseSource->id)->where('product_id', $this->productA->id)->first();
        $levelB = StockLevel::where('warehouse_id', $this->warehouseSource->id)->where('product_id', $this->productB->id)->first();
        $this->assertEquals(40, $levelA->quantity);
        $this->assertEquals(10, $levelB->quantity);

        // Delete reclassification
        $response = $this->actingAs($this->user)->deleteJson(route('inventory.transfers.reclassifications.destroy', $reclassification));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // After delete: A is restored to 50, B is reduced back to 0
        $levelA->refresh();
        $levelB->refresh();
        $this->assertEquals(50, $levelA->quantity);
        $this->assertEquals(0, $levelB->quantity);

        $this->assertDatabaseMissing('stock_reclassifications', ['id' => $reclassification->id]);
    }

    public function test_reclassification_deletion_blocked_if_destination_stock_was_consumed(): void
    {
        // 1. Setup initial stock
        StockLevel::create([
            'warehouse_id' => $this->warehouseSource->id,
            'product_id' => $this->productA->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $movementService = app(StockMovementService::class);
        $reclassification = StockReclassification::create([
            'reclassification_no' => 'REC-2026-0003',
            'reclassification_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouseSource->id,
            'from_product_id' => $this->productA->id,
            'to_product_id' => $this->productB->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 10,
            'notes' => 'Consumed test',
            'created_by' => $this->user->id,
        ]);

        $movementService->recordOut(
            product: $this->productA,
            uom: $this->boxUom,
            quantity: 10,
            type: 'reclassification_out',
            reference: $reclassification,
            notes: 'Test',
            user: $this->user,
            warehouseId: $this->warehouseSource->id,
        );

        $movementService->recordIn(
            product: $this->productB,
            uom: $this->boxUom,
            quantity: 10,
            type: 'reclassification_in',
            reference: $reclassification,
            notes: 'Test',
            user: $this->user,
            warehouseId: $this->warehouseSource->id,
        );

        // 2. Consume 5 units of product B (so only 5 remain, less than the 10 needed to reverse)
        $levelB = StockLevel::where('warehouse_id', $this->warehouseSource->id)->where('product_id', $this->productB->id)->first();
        $levelB->update(['quantity' => 5]);

        // 3. Attempt to delete -> Should be blocked to prevent negative stock!
        $response = $this->actingAs($this->user)->deleteJson(route('inventory.transfers.reclassifications.destroy', $reclassification));
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);

        $this->assertDatabaseHas('stock_reclassifications', ['id' => $reclassification->id]);
    }
}
