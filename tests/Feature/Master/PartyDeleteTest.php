<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\PartyContact;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;
    protected Warehouse $warehouse;
    protected CustomerType $customerType;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);

        $this->company = Company::create([
            'name' => 'Apex Enterprises',
            'code' => 'APEX-01',
            'is_active' => true,
        ]);

        $this->adminUser->update(['company_id' => $this->company->id]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Central Warehouse',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $this->customerType = CustomerType::create([
            'name' => 'Distributor',
            'code' => 'DIST-01',
            'is_active' => true,
        ]);

        $this->area = Area::create([
            'name' => 'South Zone',
            'code' => 'SZ-01',
            'is_active' => true,
        ]);
    }

    public function test_parties_listing_renders_delete_button_and_confirmation_modal(): void
    {
        $party = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Acme Trading Co',
            'code' => 'PRT-ACME-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'phone' => '9876543210',
            'gstin' => '27ABCDE1234F1Z5',
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('masters.customers.index'));

        $response->assertStatus(200);
        $response->assertSee('Acme Trading Co');
        $response->assertSee('data-delete-party="' . $party->id . '"', false);
        $response->assertSee('data-delete-customer="' . $party->id . '"', false);
        $response->assertSee('Delete Party');
        $response->assertSee('Confirm Delete');
        $response->assertSee('Cancel');
    }

    public function test_can_delete_party_without_dependencies_permanently(): void
    {
        $party = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Standalone Party',
            'code' => 'PRT-STAND-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        PartyAddress::create([
            'customer_id' => $party->id,
            'label' => 'Billing Address',
            'address_line_1' => '123 Market Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'type' => 'billing',
            'is_default_billing' => true,
            'is_active' => true,
        ]);

        PartyContact::create([
            'customer_id' => $party->id,
            'name' => 'John Doe',
            'mobile' => '9876543210',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.customers.destroy', $party));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => "Party 'Standalone Party' (PRT-STAND-01) deleted successfully.",
        ]);

        // Permanently deleted because no historical transactions exist
        $this->assertDatabaseMissing('customers', [
            'id' => $party->id,
        ]);
        $this->assertDatabaseMissing('party_addresses', [
            'customer_id' => $party->id,
        ]);
        $this->assertDatabaseMissing('party_contacts', [
            'customer_id' => $party->id,
        ]);
    }

    public function test_can_delete_party_without_dependencies_via_form_redirect(): void
    {
        $party = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Form Delete Party',
            'code' => 'PRT-FORM-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('masters.customers.destroy', $party));

        $response->assertRedirect(route('masters.customers.index'));
        $this->assertDatabaseMissing('customers', [
            'id' => $party->id,
        ]);
    }

    public function test_party_linked_to_sales_invoices_is_safely_archived_and_transaction_remains_intact(): void
    {
        $party = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Key Account Customer',
            'code' => 'PRT-KEY-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $uom = Uom::create(['name' => 'Box', 'code' => 'BOX', 'is_active' => true]);
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Laser Printer',
            'sku' => 'PRD-LP-01',
            'serial_no' => 'SN-LP-01',
            'base_uom_id' => $uom->id,
            'purchase_price' => 5000,
            'selling_price' => 7500,
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $party->id,
            'invoice_no' => 'INV-2026-0001',
            'invoice_date' => now()->toDateString(),
            'subtotal' => 7500,
            'tax_amount' => 1350,
            'grand_total' => 8850,
            'status' => 'issued',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.customers.destroy', $party));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'archived' => true,
        ]);

        // Party is soft-deleted, not destroyed
        $this->assertSoftDeleted('customers', [
            'id' => $party->id,
            'is_active' => false,
        ]);

        // Historical invoice remains completely intact and still has customer relation
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'customer_id' => $party->id,
        ]);

        $freshInvoice = Invoice::find($invoice->id);
        $this->assertNotNull($freshInvoice->customer);
        $this->assertEquals('Key Account Customer', $freshInvoice->customer->name);

        // Soft-deleted customer should not appear in default listing
        $indexResponse = $this->actingAs($this->adminUser)->get(route('masters.customers.index'));
        $indexResponse->assertDontSee('PRT-KEY-01');
    }

    public function test_party_linked_to_purchase_orders_is_safely_archived_and_po_remains_intact(): void
    {
        $supplier = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Major Component Supplier',
            'code' => 'SUP-MAJ-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'is_active' => true,
        ]);

        $po = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $supplier->id,
            'po_no' => 'PO-2026-001',
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 10000,
            'grand_total' => 11800,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.customers.destroy', $supplier));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'archived' => true,
        ]);

        // Supplier is soft-deleted
        $this->assertSoftDeleted('customers', [
            'id' => $supplier->id,
            'is_active' => false,
        ]);

        // Historical PO remains intact
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id,
            'supplier_id' => $supplier->id,
        ]);

        $freshPo = PurchaseOrder::find($po->id);
        $this->assertNotNull($freshPo->supplier);
        $this->assertEquals('Major Component Supplier', $freshPo->supplier->name);
    }

    public function test_unauthorized_user_cannot_delete_party(): void
    {
        $viewer = User::factory()->create(['company_id' => $this->company->id]);
        $viewerRole = Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
        $viewer->assignRole($viewerRole);

        $party = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Protected Party',
            'code' => 'PRT-PROT-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $response = $this->actingAs($viewer)
            ->deleteJson(route('masters.customers.destroy', $party));

        $response->assertStatus(403);
        $this->assertDatabaseHas('customers', [
            'id' => $party->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_cannot_delete_party_belonging_to_another_company(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Enterprise',
            'code' => 'OTH-01',
            'is_active' => true,
        ]);

        $managerRole = Role::firstOrCreate(['name' => 'sales-manager', 'guard_name' => 'web']);
        $manager = User::factory()->create(['company_id' => $otherCompany->id]);
        $manager->assignRole($managerRole);

        $partyInFirstCompany = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Company A Party',
            'code' => 'PRT-COMA-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)
            ->deleteJson(route('masters.customers.destroy', $partyInFirstCompany));

        $response->assertStatus(403);
        $this->assertDatabaseHas('customers', [
            'id' => $partyInFirstCompany->id,
            'deleted_at' => null,
        ]);
    }

    public function test_search_and_filters_work_on_parties_listing(): void
    {
        Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Delta Distribution',
            'code' => 'CUST-DLT-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'phone' => '9988776655',
            'gstin' => '27DELTA1234F1Z0',
            'is_active' => true,
        ]);

        Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Beta Logistics',
            'code' => 'SUPP-BTA-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'phone' => '9123456789',
            'gstin' => '27BETAA1234F1Z1',
            'is_active' => true,
        ]);

        // Search by name
        $response = $this->actingAs($this->adminUser)->get(route('masters.customers.index', ['search' => 'Delta']));
        $response->assertStatus(200);
        $response->assertSee('Delta Distribution');
        $response->assertDontSee('Beta Logistics');

        // Filter by party type
        $filterResponse = $this->actingAs($this->adminUser)->get(route('masters.customers.index', ['party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS]));
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('Beta Logistics');
        $filterResponse->assertDontSee('Delta Distribution');
    }
}
