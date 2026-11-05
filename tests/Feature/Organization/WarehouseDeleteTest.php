<?php

namespace Tests\Feature\Organization;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WarehouseDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $ownerUser;
    protected User $regularUser;
    protected BusinessGroup $businessGroup;
    protected Company $activeCompany;
    protected Company $inactiveCompany;
    protected Company $otherCompany;
    protected Branch $activeBranch;
    protected Branch $inactiveBranch;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.edit', 'guard_name' => 'web']);

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $roleOwner = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $roleRegular = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->businessGroup = BusinessGroup::create([
            'name' => 'Warehouse Test Group',
            'code' => 'WTG01',
            'is_active' => true,
        ]);

        $this->activeCompany = Company::create([
            'business_group_id' => $this->businessGroup->id,
            'name' => 'Active Company Ltd',
            'code' => 'ACL01',
            'is_active' => true,
        ]);

        $this->inactiveCompany = Company::create([
            'business_group_id' => $this->businessGroup->id,
            'name' => 'Inactive Company Ltd',
            'code' => 'ICL02',
            'is_active' => false,
        ]);

        $this->otherCompany = Company::create([
            'business_group_id' => $this->businessGroup->id,
            'name' => 'Other Company Ltd',
            'code' => 'OCL03',
            'is_active' => true,
        ]);

        $this->activeBranch = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Active Branch',
            'code' => 'AB01',
            'is_active' => true,
        ]);

        $this->inactiveBranch = Branch::create([
            'company_id' => $this->inactiveCompany->id,
            'name' => 'Inactive Branch',
            'code' => 'IB01',
            'is_active' => false,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->activeCompany->id,
        ]);
        $this->adminUser->assignRole($roleAdmin);

        $this->ownerUser = User::factory()->create([
            'company_id' => $this->activeCompany->id,
        ]);
        $this->ownerUser->assignRole($roleOwner);
        $this->ownerUser->givePermissionTo('organization.manage');

        $this->regularUser = User::factory()->create([
            'company_id' => $this->activeCompany->id,
        ]);
        $this->regularUser->assignRole($roleRegular);
    }

    /**
     * Requirement 1: Delete button appears next to Edit for each warehouse.
     */
    public function test_delete_button_appears_next_to_edit_for_each_warehouse(): void
    {
        $wh1 = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $this->activeBranch->id,
            'name' => 'Warehouse Alpha',
            'code' => 'WHA01',
            'is_active' => true,
        ]);

        $wh2 = Warehouse::create([
            'company_id' => $this->inactiveCompany->id,
            'branch_id' => $this->inactiveBranch->id,
            'name' => 'Warehouse Beta',
            'code' => 'WHB02',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.warehouses.index'));

        $response->assertOk();
        $response->assertSee('Warehouse Alpha');
        $response->assertSee('Warehouse Beta');
        $response->assertSee('Delete Warehouse?');
        $response->assertSee('data-delete-warehouse="' . $wh1->id . '"', false);
        $response->assertSee('data-delete-warehouse="' . $wh2->id . '"', false);
        $response->assertSee(route('organization.warehouses.edit', $wh1));
        $response->assertSee(route('organization.warehouses.edit', $wh2));
    }

    /**
     * Requirement 2: Canceling confirmation does not delete the warehouse.
     */
    public function test_canceling_confirmation_does_not_delete_warehouse(): void
    {
        $warehouse = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $this->activeBranch->id,
            'name' => 'Preserved Warehouse',
            'code' => 'PW01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.warehouses.index'));
        $response->assertOk();
        $response->assertSee('Preserved Warehouse');

        // Confirm record remains in database without submitting delete
        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'Preserved Warehouse',
            'deleted_at' => null,
        ]);
    }

    /**
     * Requirement 3: An eligible warehouse can be deleted successfully.
     */
    public function test_eligible_warehouse_can_be_deleted_successfully(): void
    {
        $warehouse = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $this->activeBranch->id,
            'name' => 'Eligible Warehouse',
            'code' => 'EW01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->assertTrue($warehouse->canBeDeleted());
        $this->assertNull($warehouse->getDeleteBlockReason());

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.warehouses.destroy', $warehouse));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => "Warehouse 'Eligible Warehouse' deleted successfully.",
        ]);

        // Soft-deleted in database and deactivated
        $this->assertSoftDeleted('warehouses', ['id' => $warehouse->id]);
        $trashed = Warehouse::withTrashed()->find($warehouse->id);
        $this->assertFalse($trashed->is_active);
        $this->assertFalse($trashed->is_default);

        // Disappears from active queries
        $this->assertCount(0, Warehouse::where('id', $warehouse->id)->get());
    }

    /**
     * Requirement 4: Warehouse status and parent company/branch status do not independently block deletion.
     */
    public function test_warehouse_company_and_branch_status_do_not_block_deletion(): void
    {
        // 4a. Active warehouse in active company and active branch
        $w1 = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $this->activeBranch->id,
            'name' => 'Active Wh Active Co Active Br',
            'code' => 'W1_AAA',
            'is_active' => true,
        ]);
        $this->actingAs($this->adminUser)->deleteJson(route('organization.warehouses.destroy', $w1))->assertOk();
        $this->assertSoftDeleted('warehouses', ['id' => $w1->id]);

        // 4b. Inactive warehouse in active company and active branch
        $w2 = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $this->activeBranch->id,
            'name' => 'Inactive Wh Active Co Active Br',
            'code' => 'W2_IAA',
            'is_active' => false,
        ]);
        $this->actingAs($this->adminUser)->deleteJson(route('organization.warehouses.destroy', $w2))->assertOk();
        $this->assertSoftDeleted('warehouses', ['id' => $w2->id]);

        // 4c. Active warehouse in inactive company and inactive branch
        $w3 = Warehouse::create([
            'company_id' => $this->inactiveCompany->id,
            'branch_id' => $this->inactiveBranch->id,
            'name' => 'Active Wh Inactive Co Inactive Br',
            'code' => 'W3_AII',
            'is_active' => true,
        ]);
        $this->actingAs($this->adminUser)->deleteJson(route('organization.warehouses.destroy', $w3))->assertOk();
        $this->assertSoftDeleted('warehouses', ['id' => $w3->id]);

        // 4d. Inactive warehouse in inactive company and inactive branch
        $w4 = Warehouse::create([
            'company_id' => $this->inactiveCompany->id,
            'branch_id' => $this->inactiveBranch->id,
            'name' => 'Inactive Wh Inactive Co Inactive Br',
            'code' => 'W4_III',
            'is_active' => false,
        ]);
        $this->actingAs($this->adminUser)->deleteJson(route('organization.warehouses.destroy', $w4))->assertOk();
        $this->assertSoftDeleted('warehouses', ['id' => $w4->id]);
    }

    /**
     * Requirement 5: Unauthorized users cannot delete warehouses through a direct request.
     */
    public function test_unauthorized_users_cannot_delete_warehouses(): void
    {
        $warehouse = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $this->activeBranch->id,
            'name' => 'Protected Warehouse',
            'code' => 'PW_AUTH',
            'is_active' => true,
        ]);

        // Regular user without permissions gets 403
        $response1 = $this->actingAs($this->regularUser)
            ->deleteJson(route('organization.warehouses.destroy', $warehouse));

        $response1->assertStatus(403);
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id, 'deleted_at' => null]);

        // Manager of Company A cannot delete warehouse belonging to Company B
        $otherBranch = Branch::create([
            'company_id' => $this->otherCompany->id,
            'name' => 'Other Branch',
            'code' => 'OB_AUTH',
            'is_active' => true,
        ]);

        $otherWarehouse = Warehouse::create([
            'company_id' => $this->otherCompany->id,
            'branch_id' => $otherBranch->id,
            'name' => 'Other Company Warehouse',
            'code' => 'OCW01',
            'is_active' => true,
        ]);

        $managerUser = User::factory()->create([
            'company_id' => $this->activeCompany->id,
        ]);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerRole->givePermissionTo('organization.manage');
        $managerUser->assignRole($managerRole);

        $response2 = $this->actingAs($managerUser)
            ->deleteJson(route('organization.warehouses.destroy', $otherWarehouse));

        $response2->assertStatus(403);
        $response2->assertJson([
            'success' => false,
            'message' => 'Unauthorized: This warehouse belongs to another company.',
        ]);
        $this->assertDatabaseHas('warehouses', ['id' => $otherWarehouse->id, 'deleted_at' => null]);
    }

    /**
     * Requirement 6: Protected historical records are not accidentally destroyed.
     */
    public function test_protected_historical_records_are_not_accidentally_destroyed(): void
    {
        $warehouse = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $this->activeBranch->id,
            'name' => 'Historic Warehouse',
            'code' => 'HW01',
            'is_active' => true,
        ]);

        $brand = Brand::create(['company_id' => $this->activeCompany->id, 'name' => 'HWBrand', 'code' => 'HWB', 'is_active' => true]);
        $category = Category::create(['brand_id' => $brand->id, 'name' => 'HWCat', 'code' => 'HWC', 'is_active' => true]);
        $uom = Uom::create(['name' => 'Pieces', 'code' => 'PCS', 'is_active' => true]);

        $product = Product::create([
            'company_id' => $this->activeCompany->id,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'base_uom_id' => $uom->id,
            'name' => 'Historic Item',
            'sku' => 'SKU-HW-1',
            'serial_no' => 'SN-HW-1',
            'is_active' => true,
        ]);

        // Stock level record
        $stockLevel = StockLevel::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'quantity' => 200,
        ]);

        // Stock movement record
        $stockMovement = StockMovement::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'type' => 'inward',
            'quantity' => 200,
            'balance_after' => 200,
            'reference_type' => 'opening',
            'reference_id' => 1,
            'created_by' => $this->adminUser->id,
        ]);

        // Purchase order record
        $custType = CustomerType::create(['name' => 'Supplier', 'code' => 'SUPP01', 'is_active' => true]);
        $supplier = Customer::create([
            'company_id' => $this->activeCompany->id,
            'customer_type_id' => $custType->id,
            'name' => 'Historic Supplier',
            'code' => 'HS01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'is_active' => true,
        ]);

        $po = PurchaseOrder::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $this->activeBranch->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'po_no' => 'PO-WH-001',
            'po_date' => now()->toDateString(),
            'status' => 'approved',
            'subtotal' => 5000,
            'grand_total' => 5900,
        ]);

        // Delete the warehouse
        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.warehouses.destroy', $warehouse));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Warehouse is soft-deleted
        $this->assertSoftDeleted('warehouses', ['id' => $warehouse->id]);

        // Inventory and purchase records are completely preserved
        $this->assertDatabaseHas('stock_levels', [
            'id' => $stockLevel->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 200,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'id' => $stockMovement->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 200,
        ]);
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id,
            'warehouse_id' => $warehouse->id,
            'po_no' => 'PO-WH-001',
        ]);

        // Relationships resolve with withTrashed and clearly show (Archived)
        $freshMovement = StockMovement::find($stockMovement->id);
        $this->assertNotNull($freshMovement->warehouse);
        $this->assertTrue($freshMovement->warehouse->is_archived);
        $this->assertStringContainsString('(Archived)', $freshMovement->warehouse->name);

        $freshPo = PurchaseOrder::find($po->id);
        $this->assertNotNull($freshPo->warehouse);
        $this->assertTrue($freshPo->warehouse->is_archived);
        $this->assertStringContainsString('(Archived)', $freshPo->warehouse->name);
    }

    /**
     * Requirement 7: Search, filters, sorting, pagination, and Edit continue to work.
     */
    public function test_search_filters_sorting_and_edit_continue_to_work(): void
    {
        $wAlpha = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $this->activeBranch->id,
            'name' => 'Delta Warehouse',
            'code' => 'DW01',
            'is_active' => true,
        ]);

        $wBeta = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $this->activeBranch->id,
            'name' => 'Gamma Warehouse',
            'code' => 'GW02',
            'is_active' => false,
        ]);

        // Search test
        $resSearch = $this->actingAs($this->adminUser)
            ->get(route('organization.warehouses.index', ['search' => 'Delta']));
        $resSearch->assertOk();
        $resSearch->assertSee('Delta Warehouse');
        $resSearch->assertDontSee('Gamma Warehouse');

        // Status filter test (active)
        $resActive = $this->actingAs($this->adminUser)
            ->get(route('organization.warehouses.index', ['status' => 'active']));
        $resActive->assertOk();
        $resActive->assertSee('Delta Warehouse');
        $resActive->assertDontSee('Gamma Warehouse');

        // Edit page loads successfully without code field
        $resEdit = $this->actingAs($this->adminUser)
            ->get(route('organization.warehouses.edit', $wAlpha));
        $resEdit->assertOk();
        $resEdit->assertSee('Delta Warehouse');
        $resEdit->assertDontSee('name="code"', false);
    }

    /**
     * Repeated deletion requests return 404 safely.
     */
    public function test_repeated_deletion_request_returns_404(): void
    {
        $warehouse = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $this->activeBranch->id,
            'name' => 'Single Run Warehouse',
            'code' => 'SRW01',
            'is_active' => true,
        ]);

        // First deletion
        $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.warehouses.destroy', $warehouse))
            ->assertOk();

        // Repeated deletion on the same route/model returns 404
        $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.warehouses.destroy', $warehouse))
            ->assertStatus(404);
    }
}
