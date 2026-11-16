<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use App\Support\IndianStates;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxInvoiceDeliveryStateDropdownTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customerMh;
    protected Customer $customerGj;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
            'masters.view', 'masters.create', 'masters.edit',
            'customers.view', 'customers.create', 'customers.edit',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Seller company registered in Maharashtra (State code 27)
        $this->company = Company::create([
            'name' => 'DMS Maharashtra Corp',
            'code' => 'DMS-MH',
            'state' => 'Maharashtra',
            'gstin' => '27AAACD1234F1Z5',
            'address' => 'Plot 10, MIDC Andheri East',
            'city' => 'Mumbai',
            'pincode' => '400069',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Mumbai Main Branch',
            'code' => 'B-MUM',
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Warehouse',
            'code' => 'WH-MUM',
            'is_active' => true,
        ]);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => Carbon::now()->startOfYear()->toDateString(),
            'ends_on' => Carbon::now()->endOfYear()->toDateString(),
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
        ]);
        $this->user->assignRole($role);

        // Customer A: Registered in Maharashtra (Intra-state)
        $this->customerMh = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Apex Retailers Maharashtra',
            'code' => 'CUST-MH',
            'phone' => '9820011111',
            'address' => 'Shop 5, Dadar Commercial Complex',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400014',
            'gstin' => '27ABCDE1234F1Z5',
            'is_active' => true,
        ]);

        // Customer B: Registered in Gujarat (Inter-state)
        $this->customerGj = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Gujarat Traders Surat',
            'code' => 'CUST-GJ',
            'phone' => '9820022222',
            'address' => 'Ring Road Textile Market',
            'city' => 'Surat',
            'state' => 'Gujarat',
            'pincode' => '395002',
            'gstin' => '24ABCDE1234F1Z5',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Valve 50mm',
            'sku' => 'VALVE-50',
            'serial_no' => 'PRD-VALVE-50',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 1000.00,
            'tax_rate' => 18.0,
            'is_active' => true,
        ]);
    }

    /**
     * Scenario 1: Tax Invoice create form renders the state dropdown component
     * and preselects Maharashtra when customer is registered in Maharashtra.
     */
    public function test_create_form_renders_searchable_state_dropdown_with_all_states(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('invoices.create', ['customer_id' => $this->customerMh->id]));

        $response->assertOk();
        // Check that state dropdown is present with name="delivery_state"
        $response->assertSee('name="delivery_state"', false);
        $response->assertSee('Delivery State');
        // Check that states list includes Gujarat (24) and Maharashtra (27)
        $response->assertSee('Maharashtra');
        $response->assertSee('Gujarat');
        $response->assertSee('Search or select delivery state...');
    }

    /**
     * Scenario 2: When Delivery State is Gujarat (Inter-state),
     * server applies IGST (18%) and sets cgst/sgst to 0.
     */
    public function test_delivery_state_gujarat_applies_igst(): void
    {
        $payload = [
            'customer_id' => $this->customerMh->id,
            'invoice_date' => now()->toDateString(),
            'delivery_state' => 'Gujarat',
            'bill_to_place' => 'Mumbai',
            'ship_to_place' => 'Surat',
            'price_type' => 'exclusive',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 1000.00,
                    'tax_rate' => 18.0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertRedirect();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('Gujarat', $invoice->delivery_state);
        $this->assertEquals('inter', $invoice->tax_type);
        $this->assertEquals(0, (float) $invoice->cgst_amount);
        $this->assertEquals(0, (float) $invoice->sgst_amount);
        $this->assertEquals(360.00, (float) $invoice->items->first()->igst_amount);
        $this->assertEquals(0, (float) $invoice->items->first()->cgst_amount);
        $this->assertEquals(0, (float) $invoice->items->first()->sgst_amount);
    }

    /**
     * Scenario 3: When Delivery State is Maharashtra (Intra-state),
     * server applies CGST (9%) + SGST (9%) and sets igst to 0.
     */
    public function test_delivery_state_maharashtra_applies_cgst_and_sgst(): void
    {
        $payload = [
            'customer_id' => $this->customerMh->id,
            'invoice_date' => now()->toDateString(),
            'delivery_state' => 'Maharashtra',
            'bill_to_place' => 'Mumbai',
            'ship_to_place' => 'Pune',
            'price_type' => 'exclusive',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 1000.00,
                    'tax_rate' => 18.0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertRedirect();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('Maharashtra', $invoice->delivery_state);
        $this->assertEquals('intra', $invoice->tax_type);
        $this->assertEquals(180.00, (float) $invoice->cgst_amount);
        $this->assertEquals(180.00, (float) $invoice->sgst_amount);
        $this->assertEquals(180.00, (float) $invoice->items->first()->cgst_amount);
        $this->assertEquals(180.00, (float) $invoice->items->first()->sgst_amount);
        $this->assertEquals(0, (float) $invoice->items->first()->igst_amount);
    }

    /**
     * Scenario 4: Edit existing invoice preserves and displays previously selected delivery state.
     * Updating delivery state to an inter-state location updates tax_type and persists state.
     */
    public function test_edit_existing_invoice_loads_saved_state_and_updates_correctly(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-TEST-001',
            'customer_id' => $this->customerMh->id,
            'invoice_date' => now()->toDateString(),
            'delivery_state' => 'Maharashtra',
            'place_of_supply_state' => 'Maharashtra',
            'tax_type' => 'intra',
            'status' => 'issued',
            'subtotal' => 2000.00,
            'taxable_amount' => 2000.00,
            'tax_amount' => 360.00,
            'cgst_amount' => 180.00,
            'sgst_amount' => 180.00,
            'igst_amount' => 0.00,
            'total' => 2360.00,
        ]);

        $editResponse = $this->actingAs($this->user)->get(route('invoices.edit', $invoice));
        $editResponse->assertOk();
        $editResponse->assertSee('value="Maharashtra"', false);
        $editResponse->assertSee('name="delivery_state"', false);

        // Update delivery state to Rajasthan (Inter-state)
        $updateResponse = $this->actingAs($this->user)->put(route('invoices.update', $invoice), [
            'delivery_state' => 'Rajasthan',
            'ship_to_place' => 'Jaipur',
            'destination' => 'Jaipur Depot',
        ]);

        $updateResponse->assertRedirect();
        $invoice->refresh();
        $this->assertEquals('Rajasthan', $invoice->delivery_state);
        $this->assertEquals('Rajasthan', $invoice->place_of_supply_state);
        $this->assertEquals('inter', $invoice->tax_type);
    }

    /**
     * Scenario 5: Customer addresses API returns registered states
     * allowing automatic state synchronization on customer selection.
     */
    public function test_customer_addresses_endpoint_returns_registered_state(): void
    {
        // Add specific delivery branch in Gujarat for Customer B
        PartyAddress::create([
            'customer_id' => $this->customerGj->id,
            'type' => 'delivery',
            'label' => 'Ahmedabad Hub',
            'address_line_1' => 'Plot 55, GIDC Vatva',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'pincode' => '382445',
            'is_default_delivery' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(url("/masters/customers/{$this->customerGj->id}/addresses"));

        $response->assertOk();
        $json = $response->json();
        $this->assertTrue($json['success']);
        $this->assertNotEmpty($json['addresses']);
        $this->assertEquals('Gujarat', $json['addresses'][0]['state']);
    }

    /**
     * Scenario 6: Invoice show view displays delivery state in Delivery Address card.
     */
    public function test_show_view_displays_delivery_state(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-TEST-002',
            'customer_id' => $this->customerMh->id,
            'invoice_date' => now()->toDateString(),
            'delivery_state' => 'Gujarat',
            'ship_to_place' => 'Surat',
            'tax_type' => 'inter',
            'status' => 'issued',
            'subtotal' => 1000.00,
            'taxable_amount' => 1000.00,
            'tax_amount' => 180.00,
            'igst_amount' => 180.00,
            'total' => 1180.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('invoices.show', $invoice));
        $response->assertOk();
        $response->assertSee('State: Gujarat');
    }
}

