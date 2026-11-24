<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Sales\Models\QuotationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuotationDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'quotations.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'quotations.delete', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'quotations.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'sales.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'sales.delete', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'sales.manage', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Apex Solutions',
            'code' => 'APX',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'BR-01',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin Manager',
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole('super-admin');

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Star Industries',
            'code' => 'CUST-STAR',
            'party_type' => 'sundry_debtors',
            'is_active' => true,
        ]);

        $this->uom = Uom::firstOrCreate(
            ['name' => 'NOS'],
            ['code' => 'NOS', 'is_active' => true]
        );

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Enterprise Switch 24-Port',
            'sku' => 'SW-24P',
            'serial_no' => 'SN-SW-24P',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 15000.00,
            'is_active' => true,
        ]);
    }

    public function test_can_delete_eligible_draft_quotation_via_json(): void
    {
        $quotation = $this->createQuotation('QTN-DEL-001', 'draft');

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('quotations.destroy', $quotation));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Quotation deleted successfully.',
            ]);

        $this->assertDatabaseMissing('quotations', ['id' => $quotation->id]);
        $this->assertDatabaseMissing('quotation_items', ['quotation_id' => $quotation->id]);

        // Unrelated records remain untouched
        $this->assertDatabaseHas('customers', ['id' => $this->customer->id]);
        $this->assertDatabaseHas('products', ['id' => $this->product->id]);
        $this->assertDatabaseHas('companies', ['id' => $this->company->id]);
    }

    public function test_can_delete_eligible_draft_quotation_via_standard_request(): void
    {
        $quotation = $this->createQuotation('QTN-DEL-002', 'draft');

        $response = $this->actingAs($this->adminUser)
            ->delete(route('quotations.destroy', $quotation));

        $response->assertRedirect(route('quotations.index'))
            ->assertSessionHas('success', 'Quotation deleted successfully.');

        $this->assertDatabaseMissing('quotations', ['id' => $quotation->id]);
        $this->assertDatabaseMissing('quotation_items', ['quotation_id' => $quotation->id]);
    }

    public function test_cancel_deletion_leaves_quotation_intact(): void
    {
        $quotation = $this->createQuotation('QTN-KEEP-003', 'draft');

        // Visiting index and not confirming deletion leaves data intact
        $response = $this->actingAs($this->adminUser)->get(route('quotations.index'));
        $response->assertOk();
        $response->assertSee('QTN-KEEP-003');

        $this->assertDatabaseHas('quotations', ['id' => $quotation->id]);
        $this->assertDatabaseHas('quotation_items', ['quotation_id' => $quotation->id]);
    }

    public function test_cannot_delete_quotation_linked_to_sales_order(): void
    {
        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'is_active' => true,
        ]);

        $quotation = $this->createQuotation('QTN-CONV-004', 'converted');

        $order = Order::create([
            'order_no' => 'SO-2026-004',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $warehouse->id,
            'quotation_id' => $quotation->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'subtotal' => 15000.00,
            'tax_amount' => 2700.00,
            'grand_total' => 17700.00,
        ]);

        $quotation->update(['converted_order_id' => $order->id]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('quotations.destroy', $quotation));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString('converted to a Sales Order', $response->json('message'));
        $this->assertDatabaseHas('quotations', ['id' => $quotation->id]);
        $this->assertDatabaseHas('quotation_items', ['quotation_id' => $quotation->id]);
    }

    public function test_cannot_delete_quotation_linked_to_tax_invoice(): void
    {
        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Secondary Warehouse',
            'code' => 'WH-SEC',
            'is_active' => true,
        ]);

        $quotation = $this->createQuotation('QTN-INV-005', 'converted');

        $order = Order::create([
            'order_no' => 'SO-2026-005',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $warehouse->id,
            'quotation_id' => $quotation->id,
            'order_date' => now()->toDateString(),
            'status' => 'converted',
            'subtotal' => 15000.00,
            'tax_amount' => 2700.00,
            'grand_total' => 17700.00,
        ]);

        $quotation->update(['converted_order_id' => $order->id]);

        $invoice = Invoice::create([
            'invoice_no' => 'INV-2026-005',
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'status' => 'issued',
            'subtotal' => 15000.00,
            'tax_amount' => 2700.00,
            'grand_total' => 17700.00,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('quotations.destroy', $quotation));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString('Tax Invoice', $response->json('message'));
        $this->assertDatabaseHas('quotations', ['id' => $quotation->id]);
    }

    public function test_unauthorized_users_cannot_delete_quotations(): void
    {
        $quotation = $this->createQuotation('QTN-UNAUTH-006', 'draft');

        // User with only view permission
        $viewUser = User::factory()->create([
            'name' => 'Viewer User',
            'company_id' => $this->company->id,
        ]);
        $viewUser->givePermissionTo('quotations.view');

        $response = $this->actingAs($viewUser)
            ->deleteJson(route('quotations.destroy', $quotation));

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to delete quotations.',
            ]);

        $this->assertDatabaseHas('quotations', ['id' => $quotation->id]);

        // User with quotations.delete can delete
        $deleteUser = User::factory()->create([
            'name' => 'Delete Staff',
            'company_id' => $this->company->id,
        ]);
        $deleteUser->givePermissionTo('quotations.delete');

        $deleteResponse = $this->actingAs($deleteUser)
            ->deleteJson(route('quotations.destroy', $quotation));

        $deleteResponse->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('quotations', ['id' => $quotation->id]);
    }

    public function test_deleted_quotation_no_longer_appears_in_listing(): void
    {
        $quotation = $this->createQuotation('QTN-LIST-007', 'draft');

        $indexBefore = $this->actingAs($this->adminUser)->get(route('quotations.index'));
        $indexBefore->assertOk();
        $indexBefore->assertSee('QTN-LIST-007');

        $this->actingAs($this->adminUser)
            ->deleteJson(route('quotations.destroy', $quotation))
            ->assertOk();

        $indexAfter = $this->actingAs($this->adminUser)->get(route('quotations.index'));
        $indexAfter->assertOk();
        $indexAfter->assertDontSee('QTN-LIST-007');
    }

    public function test_listing_view_renders_delete_action_and_confirmation_modal(): void
    {
        $quotation = $this->createQuotation('QTN-MODAL-008', 'draft');

        $response = $this->actingAs($this->adminUser)->get(route('quotations.index'));

        $response->assertOk();
        $response->assertSee('View');
        $response->assertSee('Preview');
        $response->assertSee('Delete');
        $response->assertSee('data-delete-quotation="'.$quotation->id.'"', false);
        $response->assertSee('Delete Quotation');
        $response->assertSee('Are you sure you want to delete this quotation? This action cannot be undone.');
        $response->assertSee('Cancel');
        $response->assertSee('executeDelete()', false);
    }

    protected function createQuotation(string $quotationNo, string $status = 'draft'): Quotation
    {
        $quotation = Quotation::create([
            'company_id' => $this->company->id,
            'quotation_no' => $quotationNo,
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->adminUser->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'status' => $status,
            'subtotal' => 15000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 2700.00,
            'grand_total' => 17700.00,
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 1,
            'unit_price' => 15000.00,
            'discount_amount' => 0.00,
            'line_total' => 15000.00,
        ]);

        return $quotation;
    }
}
