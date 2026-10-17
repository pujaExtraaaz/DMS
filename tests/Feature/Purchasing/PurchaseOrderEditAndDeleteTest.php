<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderEditAndDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $purchaserUser;
    protected User $unauthorizedUser;
    protected Company $company;
    protected Customer $supplier;
    protected Warehouse $warehouse;
    protected Brand $brand;
    protected Product $product;
    protected Product $product2;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $purchaserRole = Role::firstOrCreate(['name' => 'purchaser', 'guard_name' => 'web']);
        $guestRole = Role::firstOrCreate(['name' => 'guest', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'purchases.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.manage', 'guard_name' => 'web']);

        $purchaserRole->syncPermissions([
            'purchases.view',
            'purchases.create',
            'purchases.edit',
            'purchases.manage',
            'purchase-orders.view',
            'purchase-orders.create',
            'purchase-orders.edit',
            'purchase-orders.manage',
        ]);

        $this->company = Company::create([
            'name' => 'Acme Trading Co',
            'code' => 'ATC-01',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($superAdminRole);

        $this->purchaserUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->purchaserUser->assignRole($purchaserRole);

        $this->unauthorizedUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->unauthorizedUser->assignRole($guestRole);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $branch = Branch::create([
            'name' => 'Main Branch',
            'code' => 'MB01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Warehouse',
            'code' => 'WH-MAIN',
            'is_active' => true,
        ]);

        $customerType = CustomerType::create([
            'name' => 'Vendor',
            'code' => 'VND',
            'is_active' => true,
        ]);

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'name' => 'Apex Components Pvt Ltd',
            'code' => 'SUP-APEX',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'name' => 'OmniCore',
            'code' => 'OMNI',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Hydraulic Valve',
            'sku' => 'HV-100',
            'serial_no' => 'SN-HV-100',
            'brand_id' => $this->brand->id,
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 2000.00,
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Control Sensor',
            'sku' => 'CS-200',
            'serial_no' => 'SN-CS-200',
            'brand_id' => $this->brand->id,
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 1200.00,
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);
    }

    protected function createDraftPurchaseOrder(array $attributes = []): PurchaseOrder
    {
        $order = PurchaseOrder::create(array_merge([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-TEST-' . uniqid(),
            'po_date' => now()->toDateString(),
            'expected_date' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'subtotal' => 10000.00,
            'tax_amount' => 1800.00,
            'grand_total' => 11800.00,
            'notes' => 'Test notes for draft order',
            'created_by' => $this->purchaserUser->id,
        ], $attributes));

        PurchaseOrderItem::create([
            'purchase_order_id' => $order->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 5,
            'received_qty' => 0,
            'unit_cost' => 2000.00,
            'tax_percent' => 18.00,
            'cgst_percent' => 9.00,
            'sgst_percent' => 9.00,
            'cgst_amount' => 900.00,
            'sgst_amount' => 900.00,
            'line_total' => 11800.00,
            'batch_no' => 'BATCH-001',
        ]);

        return $order;
    }

    public function test_actions_column_displays_preview_view_edit_and_delete_buttons(): void
    {
        $order = $this->createDraftPurchaseOrder();

        $response = $this->actingAs($this->purchaserUser)->get(route('purchasing.orders.index'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Existing Preview and View buttons preserved
        $this->assertStringContainsString('Preview', $content);
        $this->assertStringContainsString(route('purchasing.orders.preview', $order), $content);
        $this->assertStringContainsString('View', $content);
        $this->assertStringContainsString(route('purchasing.orders.show', $order), $content);

        // 2. New Edit button with pencil icon
        $this->assertStringContainsString('Edit', $content);
        $this->assertStringContainsString(route('purchasing.orders.edit', $order), $content);

        // 3. New Delete button with trash icon & confirmation trigger
        $this->assertStringContainsString('Delete', $content);
        $this->assertStringContainsString('promptDelete(', $content);
        $this->assertStringContainsString('Delete Purchase Order', $content);
    }

    public function test_edit_page_loads_correct_purchase_order_data(): void
    {
        $order = $this->createDraftPurchaseOrder();

        $response = $this->actingAs($this->purchaserUser)->get(route('purchasing.orders.edit', $order));
        $response->assertStatus(200);

        $response->assertSee($order->po_no);
        $response->assertSee($this->supplier->name);
        $response->assertSee($this->product->name);
        $response->assertSee('BATCH-001');
        $response->assertSee('Test notes for draft order');
    }

    public function test_saving_edits_updates_existing_record_without_duplicating_it(): void
    {
        $order = $this->createDraftPurchaseOrder();
        $originalPoNo = $order->po_no;
        $originalId = $order->id;
        $initialCount = PurchaseOrder::count();

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'expected_date' => now()->addDays(14)->toDateString(),
            'notes' => 'Updated instructions for delivery',
            'submit_for_approval' => 0,
            'items' => [
                [
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->product2->id,
                    'batch_no' => 'BATCH-NEW-02',
                    'uom_id' => $this->uom->id,
                    'quantity' => 10,
                    'unit_cost' => 1200.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->purchaserUser)->put(route('purchasing.orders.update', $order), $payload);
        $response->assertStatus(302);
        $response->assertRedirect(route('purchasing.orders.show', $order));

        // Ensure record count is identical (no duplication)
        $this->assertSame($initialCount, PurchaseOrder::count());

        $refreshed = $order->fresh(['items']);
        $this->assertSame($originalId, $refreshed->id);
        $this->assertSame($originalPoNo, $refreshed->po_no);
        $this->assertSame('Updated instructions for delivery', $refreshed->notes);

        // Subtotal = 10 * 1200 = 12000, Tax = 12000 * 0.18 = 2160, Grand Total = 14160
        $this->assertEquals(12000.00, (float) $refreshed->subtotal);
        $this->assertEquals(2160.00, (float) $refreshed->tax_amount);
        $this->assertEquals(14160.00, (float) $refreshed->grand_total);

        // Check updated item
        $this->assertCount(1, $refreshed->items);
        $item = $refreshed->items->first();
        $this->assertSame($this->product2->id, $item->product_id);
        $this->assertEquals(10.0, (float) $item->quantity);
        $this->assertSame('BATCH-NEW-02', $item->batch_no);
    }

    public function test_finalized_po_cannot_be_edited(): void
    {
        $order = $this->createDraftPurchaseOrder([
            'status' => 'closed',
        ]);

        $response = $this->actingAs($this->purchaserUser)->get(route('purchasing.orders.edit', $order));
        $response->assertStatus(302);
        $response->assertSessionHas('error');

        $updateResponse = $this->actingAs($this->purchaserUser)->put(route('purchasing.orders.update', $order), [
            'supplier_id' => $this->supplier->id,
            'po_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_cost' => 2000.00,
                    'tax_percent' => 18.00,
                ],
            ],
        ]);
        $updateResponse->assertStatus(302);
        $updateResponse->assertSessionHas('error');
    }

    public function test_eligible_draft_po_can_be_deleted(): void
    {
        $order = $this->createDraftPurchaseOrder();
        $orderId = $order->id;

        $response = $this->actingAs($this->purchaserUser)->delete(route('purchasing.orders.destroy', $order));
        $response->assertStatus(302);
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('purchase_orders', ['id' => $orderId]);
        $this->assertDatabaseMissing('purchase_order_items', ['purchase_order_id' => $orderId]);
    }

    public function test_eligible_draft_po_can_be_deleted_via_ajax(): void
    {
        $order = $this->createDraftPurchaseOrder();
        $orderId = $order->id;

        $response = $this->actingAs($this->purchaserUser)->deleteJson(route('purchasing.orders.destroy', $order));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('purchase_orders', ['id' => $orderId]);
    }

    public function test_po_with_dependent_inwards_cannot_be_deleted(): void
    {
        $order = $this->createDraftPurchaseOrder([
            'status' => 'approved',
        ]);

        PurchaseInward::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'purchase_order_id' => $order->id,
            'inward_no' => 'GRN-TEST-001',
            'inward_date' => now()->toDateString(),
            'status' => 'posted',
            'created_by' => $this->purchaserUser->id,
        ]);

        $response = $this->actingAs($this->purchaserUser)->delete(route('purchasing.orders.destroy', $order));
        $response->assertStatus(302);
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('purchase_orders', ['id' => $order->id]);

        $jsonResponse = $this->actingAs($this->purchaserUser)->deleteJson(route('purchasing.orders.destroy', $order));
        $jsonResponse->assertStatus(422);
        $jsonResponse->assertJson([
            'success' => false,
        ]);
    }

    public function test_unauthorized_user_cannot_edit_or_delete_purchase_order(): void
    {
        $order = $this->createDraftPurchaseOrder();

        $editResponse = $this->actingAs($this->unauthorizedUser)->get(route('purchasing.orders.edit', $order));
        $editResponse->assertStatus(403);

        $updateResponse = $this->actingAs($this->unauthorizedUser)->put(route('purchasing.orders.update', $order), [
            'supplier_id' => $this->supplier->id,
            'po_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_cost' => 2000.00,
                ],
            ],
        ]);
        $updateResponse->assertStatus(403);

        $deleteResponse = $this->actingAs($this->unauthorizedUser)->delete(route('purchasing.orders.destroy', $order));
        $deleteResponse->assertStatus(403);
    }
}
