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
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $ownerUser;
    protected User $regularUser;
    protected BusinessGroup $businessGroup;
    protected Company $activeCompany;
    protected Company $inactiveCompany;
    protected Company $otherCompany;

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
            'name' => 'Test Group',
            'code' => 'TG01',
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
     * Scenario 1: Delete a branch with no linked records.
     */
    public function test_delete_branch_with_no_linked_records(): void
    {
        $branch = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Standalone Branch',
            'code' => 'SB01',
            'is_active' => true,
        ]);

        $this->assertTrue($branch->canBeDeleted());
        $this->assertNull($branch->getDeleteBlockReason());

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.branches.destroy', $branch));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => "Branch 'Standalone Branch' deleted successfully.",
        ]);

        // Branch is soft-deleted in database
        $this->assertSoftDeleted('branches', ['id' => $branch->id]);

        // Branch disappears from normal active listing query
        $this->assertCount(0, Branch::where('id', $branch->id)->get());
    }

    /**
     * Scenario 2: Delete a branch with linked warehouses.
     * Warehouses must be handled automatically (soft-deleted/archived) without blocking branch deletion.
     */
    public function test_delete_branch_with_linked_warehouses(): void
    {
        $branch = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Branch With Warehouses',
            'code' => 'BWW01',
            'is_active' => true,
        ]);

        $warehouse1 = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $branch->id,
            'name' => 'Main Warehouse 1',
            'code' => 'MW01',
            'is_active' => true,
        ]);

        $warehouse2 = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $branch->id,
            'name' => 'Overflow Warehouse 2',
            'code' => 'OW02',
            'is_active' => true,
        ]);

        $this->assertTrue($branch->canBeDeleted());

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.branches.destroy', $branch));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => "Branch 'Branch With Warehouses' deleted successfully.",
        ]);

        // Branch and warehouses are soft-deleted in database
        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
        $this->assertSoftDeleted('warehouses', ['id' => $warehouse1->id]);
        $this->assertSoftDeleted('warehouses', ['id' => $warehouse2->id]);

        // Both disappear from active listings
        $this->assertCount(0, Branch::where('id', $branch->id)->get());
        $this->assertCount(0, Warehouse::where('id', $warehouse1->id)->get());
        $this->assertCount(0, Warehouse::where('id', $warehouse2->id)->get());

        // Warehouses are deactivated and clearly marked as archived
        $trashedWh1 = Warehouse::withTrashed()->find($warehouse1->id);
        $this->assertFalse($trashedWh1->is_active);
        $this->assertTrue($trashedWh1->is_archived);
        $this->assertStringContainsString('(Archived)', $trashedWh1->name);
    }

    /**
     * Scenario 3: Delete a branch with related inventory records.
     * Deletion succeeds and inventory records are preserved.
     */
    public function test_delete_branch_with_related_inventory_records(): void
    {
        $branch = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Branch With Inventory',
            'code' => 'BWI01',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $branch->id,
            'name' => 'Inventory Warehouse',
            'code' => 'IW01',
            'is_active' => true,
        ]);

        $brand = Brand::create(['company_id' => $this->activeCompany->id, 'name' => 'InvBrand', 'code' => 'IB01', 'is_active' => true]);
        $category = Category::create(['brand_id' => $brand->id, 'name' => 'InvCat', 'code' => 'IC01', 'is_active' => true]);
        $uom = Uom::create(['name' => 'Units', 'code' => 'UNT', 'is_active' => true]);

        $product = Product::create([
            'company_id' => $this->activeCompany->id,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'base_uom_id' => $uom->id,
            'name' => 'Stocked Product X',
            'sku' => 'SKU-SPX01',
            'serial_no' => 'SN-SPX-01',
            'is_active' => true,
        ]);

        $stockLevel = StockLevel::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'quantity' => 150,
        ]);

        $stockMovement = StockMovement::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'type' => 'inward',
            'quantity' => 150,
            'balance_after' => 150,
            'reference_type' => 'opening',
            'reference_id' => 1,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.branches.destroy', $branch));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Branch and warehouse soft deleted
        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
        $this->assertSoftDeleted('warehouses', ['id' => $warehouse->id]);

        // Inventory records remain intact in database
        $this->assertDatabaseHas('stock_levels', [
            'id' => $stockLevel->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 150,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'id' => $stockMovement->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 150,
        ]);

        // Stock movement can still load the warehouse and it indicates archive status
        $freshMovement = StockMovement::find($stockMovement->id);
        $this->assertNotNull($freshMovement->warehouse);
        $this->assertStringContainsString('(Archived)', $freshMovement->warehouse->name);
    }

    /**
     * Scenario 4: Delete a branch with historical purchase or sales transactions.
     * Deletion succeeds and transactions remain intact, referencing the archived branch.
     */
    public function test_delete_branch_with_historical_purchase_transactions(): void
    {
        $branch = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Branch With POs',
            'code' => 'BWPO01',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $branch->id,
            'name' => 'PO Warehouse',
            'code' => 'POW01',
            'is_active' => true,
        ]);

        $custType = CustomerType::create([
            'name' => 'Vendor Type',
            'code' => 'VT01',
            'is_active' => true,
        ]);

        $supplier = Customer::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $branch->id,
            'customer_type_id' => $custType->id,
            'name' => 'Acme Vendor',
            'code' => 'AV01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'is_active' => true,
        ]);

        $po = PurchaseOrder::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'po_no' => 'PO-HIST-1001',
            'po_date' => now()->toDateString(),
            'status' => 'approved',
            'subtotal' => 2500,
            'grand_total' => 2950,
        ]);

        $pi = PurchaseInvoice::create([
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $po->id,
            'invoice_no' => 'PINV-HIST-2001',
            'invoice_date' => now()->toDateString(),
            'status' => 'posted',
            'subtotal' => 2500,
            'grand_total' => 2950,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.branches.destroy', $branch));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Branch is soft-deleted
        $this->assertSoftDeleted('branches', ['id' => $branch->id]);

        // Purchase transactions are completely preserved
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id,
            'branch_id' => $branch->id,
            'po_no' => 'PO-HIST-1001',
        ]);
        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $pi->id,
            'warehouse_id' => $warehouse->id,
            'invoice_no' => 'PINV-HIST-2001',
        ]);

        // Relation resolves with withTrashed and clearly marks branch and warehouse as archived
        $freshPo = PurchaseOrder::find($po->id);
        $this->assertNotNull($freshPo->branch);
        $this->assertTrue($freshPo->branch->is_archived);
        $this->assertStringContainsString('(Archived)', $freshPo->branch->name);
        $this->assertStringContainsString('(Archived)', $freshPo->warehouse->name);

        $freshPi = PurchaseInvoice::find($pi->id);
        $this->assertNotNull($freshPi->warehouse);
        $this->assertStringContainsString('(Archived)', $freshPi->warehouse->name);
    }

    /**
     * Scenario 5: Delete branches under both Active and Inactive companies and branch statuses.
     */
    public function test_delete_branches_under_both_active_and_inactive_companies(): void
    {
        // 5a. Active branch under Active company
        $b1 = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Active Branch Active Co',
            'code' => 'ABAC99',
            'is_active' => true,
        ]);
        $r1 = $this->actingAs($this->adminUser)->deleteJson(route('organization.branches.destroy', $b1));
        $r1->assertOk();
        $this->assertSoftDeleted('branches', ['id' => $b1->id]);

        // 5b. Inactive branch under Active company
        $b2 = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Inactive Branch Active Co',
            'code' => 'IBAC99',
            'is_active' => false,
        ]);
        $r2 = $this->actingAs($this->adminUser)->deleteJson(route('organization.branches.destroy', $b2));
        $r2->assertOk();
        $this->assertSoftDeleted('branches', ['id' => $b2->id]);

        // 5c. Active branch under Inactive company
        $b3 = Branch::create([
            'company_id' => $this->inactiveCompany->id,
            'name' => 'Active Branch Inactive Co',
            'code' => 'ABIC99',
            'is_active' => true,
        ]);
        $r3 = $this->actingAs($this->adminUser)->deleteJson(route('organization.branches.destroy', $b3));
        $r3->assertOk();
        $this->assertSoftDeleted('branches', ['id' => $b3->id]);

        // 5d. Inactive branch under Inactive company
        $b4 = Branch::create([
            'company_id' => $this->inactiveCompany->id,
            'name' => 'Inactive Branch Inactive Co',
            'code' => 'IBIC99',
            'is_active' => false,
        ]);
        $r4 = $this->actingAs($this->adminUser)->deleteJson(route('organization.branches.destroy', $b4));
        $r4->assertOk();
        $this->assertSoftDeleted('branches', ['id' => $b4->id]);
    }

    /**
     * Scenario 6: Confirm that unrelated branches, companies, and business records remain intact.
     */
    public function test_unrelated_branches_companies_and_business_records_remain_intact(): void
    {
        $targetBranch = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Target Branch To Delete',
            'code' => 'TBD01',
            'is_active' => true,
        ]);

        $unrelatedBranch = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Unrelated Branch Survives',
            'code' => 'UBS01',
            'is_active' => true,
        ]);

        $unrelatedWarehouse = Warehouse::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $unrelatedBranch->id,
            'name' => 'Unrelated Warehouse Survives',
            'code' => 'UWS01',
            'is_active' => true,
        ]);

        $otherCompanyBranch = Branch::create([
            'company_id' => $this->otherCompany->id,
            'name' => 'Other Company Branch Survives',
            'code' => 'OCBS01',
            'is_active' => true,
        ]);

        // Execute delete on target branch
        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.branches.destroy', $targetBranch));
        $response->assertOk();

        // Target branch is deleted
        $this->assertSoftDeleted('branches', ['id' => $targetBranch->id]);

        // Unrelated branch, warehouse, and other company branch are completely unaffected
        $this->assertDatabaseHas('branches', [
            'id' => $unrelatedBranch->id,
            'name' => 'Unrelated Branch Survives',
            'deleted_at' => null,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('warehouses', [
            'id' => $unrelatedWarehouse->id,
            'name' => 'Unrelated Warehouse Survives',
            'deleted_at' => null,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('branches', [
            'id' => $otherCompanyBranch->id,
            'name' => 'Other Company Branch Survives',
            'deleted_at' => null,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('companies', ['id' => $this->activeCompany->id]);
        $this->assertDatabaseHas('companies', ['id' => $this->otherCompany->id]);
    }

    /**
     * Scenario 7: Confirm that historical reports remain usable after deletion or archival.
     */
    public function test_historical_reports_remain_usable_after_deletion(): void
    {
        $branch = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Reporting Branch',
            'code' => 'RB01',
            'is_active' => true,
        ]);

        $custType = CustomerType::create(['name' => 'Client', 'code' => 'CLI01', 'is_active' => true]);
        $customer = Customer::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $branch->id,
            'customer_type_id' => $custType->id,
            'name' => 'Reporting Client',
            'code' => 'RC01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $po = PurchaseOrder::create([
            'company_id' => $this->activeCompany->id,
            'branch_id' => $branch->id,
            'supplier_id' => $customer->id,
            'po_no' => 'PO-REP-9001',
            'po_date' => now()->toDateString(),
            'status' => 'approved',
            'subtotal' => 1000,
            'grand_total' => 1180,
        ]);

        // Delete the branch
        $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.branches.destroy', $branch))
            ->assertOk();

        // Customer and purchase order can still load branch details and display status
        $freshCustomer = Customer::find($customer->id);
        $this->assertNotNull($freshCustomer->branch);
        $this->assertEquals('Reporting Branch (Archived)', $freshCustomer->branch->name);
        $this->assertEquals('Reporting Branch', $freshCustomer->branch->getRawOriginal('name'));
        $this->assertTrue($freshCustomer->branch->is_archived);

        $freshPo = PurchaseOrder::find($po->id);
        $this->assertNotNull($freshPo->branch);
        $this->assertEquals('Reporting Branch (Archived)', $freshPo->branch->name);

        // Historical query joining branches table directly remains fully functional
        $joinedData = \Illuminate\Support\Facades\DB::table('purchase_orders')
            ->join('branches', 'branches.id', '=', 'purchase_orders.branch_id')
            ->where('purchase_orders.id', $po->id)
            ->select('purchase_orders.po_no', 'branches.name as branch_name', 'branches.code as branch_code')
            ->first();

        $this->assertNotNull($joinedData);
        $this->assertEquals('PO-REP-9001', $joinedData->po_no);
        $this->assertEquals('Reporting Branch', $joinedData->branch_name);
        $this->assertEquals('RB01', $joinedData->branch_code);
    }

    /**
     * Scenario 8: Unauthorized users cannot delete branches.
     */
    public function test_unauthorized_users_cannot_delete_branches(): void
    {
        $branch = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Protected Branch',
            'code' => 'PB01',
            'is_active' => true,
        ]);

        // 1. Regular user without permissions gets 403
        $response1 = $this->actingAs($this->regularUser)
            ->deleteJson(route('organization.branches.destroy', $branch));

        $response1->assertStatus(403);
        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'deleted_at' => null]);

        // 2. Manager of Company A cannot delete branch of Company B
        $managerUser = User::factory()->create([
            'company_id' => $this->activeCompany->id,
        ]);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerRole->givePermissionTo('organization.manage');
        $managerUser->assignRole($managerRole);

        $otherBranch = Branch::create([
            'company_id' => $this->otherCompany->id,
            'name' => 'Other Company Branch',
            'code' => 'OCB01',
            'is_active' => true,
        ]);

        $responseCross = $this->actingAs($managerUser)
            ->deleteJson(route('organization.branches.destroy', $otherBranch));

        $responseCross->assertStatus(403);
        $responseCross->assertJson([
            'success' => false,
            'message' => 'Unauthorized: This branch belongs to another company.',
        ]);
        $this->assertDatabaseHas('branches', ['id' => $otherBranch->id, 'deleted_at' => null]);
    }

    /**
     * Scenario 9: Cancelling confirmation leaves branch unchanged.
     */
    public function test_cancelling_confirmation_leaves_branch_unchanged(): void
    {
        $branch = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Preserved Branch',
            'code' => 'PB99',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.branches.index'));
        $response->assertOk();
        $response->assertSee('Preserved Branch');

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Preserved Branch',
            'deleted_at' => null,
        ]);
    }

    /**
     * Scenario 10: Repeated deletion request returns 404 safely.
     */
    public function test_repeated_deletion_request_returns_404(): void
    {
        $branch = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'One Time Branch',
            'code' => 'OTB01',
            'is_active' => true,
        ]);

        // First deletion succeeds
        $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.branches.destroy', $branch))
            ->assertOk();

        // Second deletion on the same ID returns 404
        $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.branches.destroy', $branch))
            ->assertStatus(404);
    }

    /**
     * Scenario 11: Branches index table renders active delete buttons for all branches.
     */
    public function test_branches_index_renders_active_delete_buttons_for_all_branches(): void
    {
        $branchActiveCo = Branch::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Active Company Branch',
            'code' => 'ACB01',
            'is_active' => true,
        ]);

        $branchInactiveCo = Branch::create([
            'company_id' => $this->inactiveCompany->id,
            'name' => 'Inactive Company Branch',
            'code' => 'ICB01',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.branches.index'));

        $response->assertOk();
        $response->assertSee('Active Company Branch');
        $response->assertSee('Inactive Company Branch');
        $response->assertSee('Delete Branch?');
        $response->assertSee('data-delete-branch="' . $branchActiveCo->id . '"', false);
        $response->assertSee('data-delete-branch="' . $branchInactiveCo->id . '"', false);
    }
}
