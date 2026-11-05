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
use App\Domains\Sales\Models\EWayBill;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyDeliveryAddressAndEwayDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customer;
    protected PartyAddress $billingAddress;
    protected PartyAddress $deliveryAddress1;
    protected PartyAddress $deliveryAddress2;
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
            'masters.view', 'masters.create', 'masters.edit', 'customers.view', 'customers.create', 'customers.edit',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

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

        $this->user = User::factory()->create([
            'name' => 'Billing Manager',
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => Carbon::now()->subMonths(2)->startOfMonth()->toDateString(),
            'ends_on' => Carbon::now()->addMonths(10)->endOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name' => 'Mumbai Depot',
            'code' => 'DEP-MUM',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Hub Mumbai',
            'code' => 'WH-MUM',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Units',
            'code' => 'UNT',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Router 5G',
            'sku' => 'RTR-5G-01',
            'serial_no' => 'PRD-RTR-5G',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 10000,
            'cost_price' => 7000,
            'tax_rate' => 18,
            'hsn_code' => '851762',
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Zenith Tech Systems Ltd',
            'code' => 'CUST-ZENITH',
            'state' => 'Maharashtra',
            'shipping_state' => 'Maharashtra',
            'gstin' => '27AABCA9999Z1Z8',
            'address' => 'Head Office, Nariman Point',
            'city' => 'Mumbai',
            'pincode' => '400021',
            'credit_days' => 30,
            'is_active' => true,
        ]);

        // Main Billing Address
        $this->billingAddress = PartyAddress::create([
            'customer_id' => $this->customer->id,
            'type' => 'billing',
            'label' => 'Headquarters Billing',
            'name' => 'Zenith Accounts Dept',
            'contact_person' => 'Rahul Sharma',
            'contact_phone' => '9820098200',
            'address_line_1' => 'Suite 501, Maker Chambers V',
            'address_line_2' => 'Nariman Point',
            'address' => 'Suite 501, Maker Chambers V, Nariman Point',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400021',
            'gstin' => '27AABCA9999Z1Z8',
            'is_default_billing' => true,
            'is_default' => true,
            'is_active' => true,
        ]);

        // Intra-state Delivery Address (Maharashtra)
        $this->deliveryAddress1 = PartyAddress::create([
            'customer_id' => $this->customer->id,
            'type' => 'delivery',
            'label' => 'Pune Warehouse',
            'name' => 'Zenith Tech Pune Depot',
            'contact_person' => 'Suresh Patil',
            'contact_phone' => '9820011223',
            'address_line_1' => 'Gat No 234, Chakan Industrial Area',
            'address_line_2' => 'Phase II',
            'address' => 'Gat No 234, Chakan Industrial Area, Phase II',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '410501',
            'gstin' => '27AABCA9999Z1Z8',
            'is_default_delivery' => true,
            'is_active' => true,
        ]);

        // Inter-state Delivery Address (Gujarat)
        $this->deliveryAddress2 = PartyAddress::create([
            'customer_id' => $this->customer->id,
            'type' => 'delivery',
            'label' => 'Ahmedabad Plant',
            'name' => 'Zenith Manufacturing Gujarat',
            'contact_person' => 'Jignesh Patel',
            'contact_phone' => '9898098980',
            'address_line_1' => 'Plot 88, GIDC Sanand',
            'address_line_2' => 'Near Tata Motors',
            'address' => 'Plot 88, GIDC Sanand',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'pincode' => '382170',
            'gstin' => '24AABCA9999Z1Z2',
            'is_default_delivery' => false,
            'is_active' => true,
        ]);
    }

    public function test_customer_addresses_endpoint_returns_all_party_addresses(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.addresses', $this->customer));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'default_billing_id' => $this->billingAddress->id,
                'default_delivery_id' => $this->deliveryAddress1->id,
            ]);

        $addresses = $response->json('addresses');
        $this->assertCount(3, $addresses);

        $puneAddr = collect($addresses)->firstWhere('id', $this->deliveryAddress1->id);
        $this->assertNotNull($puneAddr);
        $this->assertEquals('Pune Warehouse', $puneAddr['label']);
        $this->assertEquals('Pune', $puneAddr['city']);
        $this->assertEquals('Maharashtra', $puneAddr['state']);
        $this->assertEquals('410501', $puneAddr['pincode']);
        $this->assertTrue($puneAddr['is_default_delivery']);
    }

    public function test_quick_store_delivery_address_for_party(): void
    {
        $payload = [
            'label' => 'Nagpur Depot',
            'type' => 'delivery',
            'contact_person' => 'Amit Deshmukh',
            'contact_phone' => '9822233445',
            'address_line_1' => 'Plot 12, Butibori Industrial Zone',
            'address_line_2' => 'MIDC Area',
            'city' => 'Nagpur',
            'state' => 'Maharashtra',
            'pincode' => '441108',
            'gstin' => '27AABCA9999Z1Z8',
            'is_default_delivery' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('masters.customers.addresses.store', $this->customer), $payload);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Delivery address added successfully.',
            ]);

        $this->assertDatabaseHas('party_addresses', [
            'customer_id' => $this->customer->id,
            'label' => 'Nagpur Depot',
            'city' => 'Nagpur',
            'pincode' => '441108',
            'is_default_delivery' => true,
        ]);

        // Previous default delivery address should be unflagged
        $this->assertFalse($this->deliveryAddress1->fresh()->is_default_delivery);
    }

    public function test_store_invoice_with_party_delivery_address_intra_state_cgst_sgst(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'billing_address_id' => $this->billingAddress->id,
            'shipping_address_id' => $this->deliveryAddress1->id,
            'billing_address' => $this->billingAddress->formatSnapshot(),
            'shipping_address' => $this->deliveryAddress1->formatSnapshot(),
            'delivery_state' => 'Maharashtra',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 10000,
                    'discount_value' => 0,
                ],
            ],
            // e-Way Bill & Transport details
            'eway_bill_required' => 'no',
            'transport_mode' => '1',
            'distance_km' => 140,
            'transporter_id' => '27ABCDE1234F1Z1',
            'transporter_name' => 'Western Logistics Express',
            'motor_vehicle_no' => 'MH-14-GH-9988',
            'vehicle_type' => 'R',
            'lr_rr_no' => 'LR-MUM-PUN-001',
            'lr_rr_date' => Carbon::now()->toDateString(),
            // Consignor & Consignee details
            'consignor_name' => 'DMS Maharashtra Corp',
            'consignor_gstin' => '27AAACD1234F1Z5',
            'consignor_state' => 'Maharashtra',
            'consignee_name' => 'Zenith Tech Pune Depot',
            'consignee_gstin' => '27AABCA9999Z1Z8',
            'consignee_state' => 'Maharashtra',
            'consignee_city' => 'Pune',
            'consignee_pincode' => '410501',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('invoices.store'), $payload);

        $response->assertRedirect();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($this->billingAddress->id, $invoice->billing_address_id);
        $this->assertEquals($this->deliveryAddress1->id, $invoice->shipping_address_id);
        $this->assertEquals('Maharashtra', $invoice->delivery_state);
        $this->assertEquals('intra', $invoice->tax_type);
        $this->assertGreaterThan(0, (float) $invoice->cgst_amount);
        $this->assertGreaterThan(0, (float) $invoice->sgst_amount);
        $this->assertEquals(0, (float) $invoice->igst_amount);

        // Verify Consignor & Consignee fields saved
        $this->assertEquals('DMS Maharashtra Corp', $invoice->consignor_name);
        $this->assertEquals('Zenith Tech Pune Depot', $invoice->consignee_name);
        $this->assertEquals('Pune', $invoice->consignee_city);
        $this->assertEquals('410501', $invoice->consignee_pincode);

        // Verify Transport fields saved
        $this->assertEquals('Western Logistics Express', $invoice->transporter_name);
        $this->assertEquals('27ABCDE1234F1Z1', $invoice->transporter_id);
        $this->assertEquals(140, $invoice->distance_km);
        $this->assertEquals('MH-14-GH-9988', $invoice->vehicle_no);
        $this->assertEquals('LR-MUM-PUN-001', $invoice->lr_rr_no);
    }

    public function test_store_invoice_with_interstate_delivery_address_triggers_igst(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'billing_address_id' => $this->billingAddress->id,
            'shipping_address_id' => $this->deliveryAddress2->id, // Gujarat
            'billing_address' => $this->billingAddress->formatSnapshot(),
            'shipping_address' => $this->deliveryAddress2->formatSnapshot(),
            'delivery_state' => 'Gujarat',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'discount_value' => 0,
                ],
            ],
            'eway_bill_required' => 'no',
            'consignee_name' => 'Zenith Manufacturing Gujarat',
            'consignee_state' => 'Gujarat',
            'consignee_city' => 'Ahmedabad',
            'consignee_pincode' => '382170',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('invoices.store'), $payload);

        $response->assertRedirect();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('Gujarat', $invoice->delivery_state);
        $this->assertEquals('inter', $invoice->tax_type);
        $this->assertEquals(0, (float) $invoice->cgst_amount);
        $this->assertEquals(0, (float) $invoice->sgst_amount);
        $this->assertGreaterThan(0, (float) $invoice->igst_amount);
        $this->assertEquals('Zenith Manufacturing Gujarat', $invoice->consignee_name);
    }

    public function test_manual_eway_bill_entry_creates_and_links_eway_bill_record(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'billing_address_id' => $this->billingAddress->id,
            'shipping_address_id' => $this->deliveryAddress1->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 10,
                    'unit_price' => 10000,
                    'discount_value' => 0,
                ],
            ],
            // Manual e-Way Bill
            'eway_bill_required' => 'yes',
            'eway_bill_no' => '241098765432',
            'eway_bill_date' => Carbon::now()->toDateString(),
            'eway_sub_type' => 'Supply',
            'eway_doc_type' => 'Tax Invoice',
            'eway_supply_type' => 'Outward',
            'distance_km' => 150,
            'transporter_id' => '27ABCDE1234F1Z1',
            'transporter_name' => 'VRL Logistics',
            'motor_vehicle_no' => 'MH-12-XX-1122',
            'vehicle_type' => 'R',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('invoices.store'), $payload);

        $response->assertRedirect();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('241098765432', $invoice->eway_bill_no);
        $this->assertEquals('generated', $invoice->eway_bill_status);

        // Verify linked EWayBill record created
        $ewayBill = EWayBill::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($ewayBill);
        $this->assertEquals('241098765432', $ewayBill->eway_bill_no);
        $this->assertEquals('manual', $ewayBill->provider);
        $this->assertEquals('generated', $ewayBill->status);
        $this->assertEquals(150, $ewayBill->distance_km);
        $this->assertEquals('MH-12-XX-1122', $ewayBill->vehicle_no);
    }

    public function test_same_as_billing_address_auto_syncs_delivery_details(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'billing_address_id' => $this->billingAddress->id,
            'shipping_address_id' => $this->billingAddress->id, // Same as billing
            'billing_address' => $this->billingAddress->formatSnapshot(),
            'shipping_address' => $this->billingAddress->formatSnapshot(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 5000,
                ],
            ],
            'eway_bill_required' => 'no',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('invoices.store'), $payload);

        $response->assertRedirect();

        $invoice = Invoice::latest('id')->first();
        $this->assertEquals($invoice->billing_address_id, $invoice->shipping_address_id);
        $this->assertEquals($invoice->billing_address, $invoice->shipping_address);
    }

    public function test_updating_invoice_persists_new_delivery_and_eway_details(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-TEST-001',
            'customer_id' => $this->customer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'due_date' => Carbon::now()->addDays(30)->toDateString(),
            'status' => 'issued',
            'billing_address_id' => $this->billingAddress->id,
            'shipping_address_id' => $this->deliveryAddress1->id,
            'delivery_state' => 'Maharashtra',
            'tax_type' => 'intra',
            'subtotal' => 10000,
            'taxable_amount' => 10000,
            'tax_amount' => 1800,
            'cgst_amount' => 900,
            'sgst_amount' => 900,
            'igst_amount' => 0,
            'round_off' => 0,
            'grand_total' => 11800,
        ]);

        $updatePayload = [
            'shipping_address_id' => $this->deliveryAddress2->id, // Change to Gujarat
            'eway_bill_no' => '241199887766',
            'eway_bill_date' => Carbon::now()->toDateString(),
            'eway_sub_type' => 'Supply',
            'eway_doc_type' => 'Tax Invoice',
            'transporter_name' => 'Blue Dart Aviation',
            'distance_km' => 550,
            'motor_vehicle_no' => 'GJ-01-AA-9999',
            'consignee_name' => 'Zenith Gujarat Depot',
            'consignee_state' => 'Gujarat',
            'consignee_city' => 'Ahmedabad',
            'consignee_pincode' => '382170',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('invoices.update', $invoice), $updatePayload);

        $response->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals($this->deliveryAddress2->id, $invoice->shipping_address_id);
        $this->assertEquals('Gujarat', $invoice->delivery_state);
        $this->assertEquals('241199887766', $invoice->eway_bill_no);
        $this->assertEquals('Blue Dart Aviation', $invoice->transporter_name);
        $this->assertEquals(550, $invoice->distance_km);
        $this->assertEquals('GJ-01-AA-9999', $invoice->vehicle_no);
        $this->assertEquals('Zenith Gujarat Depot', $invoice->consignee_name);
    }

    public function test_invoice_show_and_pdf_render_consignor_consignee_and_eway_details(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-TEST-002',
            'customer_id' => $this->customer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'due_date' => Carbon::now()->addDays(30)->toDateString(),
            'status' => 'issued',
            'billing_address_id' => $this->billingAddress->id,
            'shipping_address_id' => $this->deliveryAddress1->id,
            'billing_address' => $this->billingAddress->formatSnapshot(),
            'shipping_address' => $this->deliveryAddress1->formatSnapshot(),
            'delivery_state' => 'Maharashtra',
            'tax_type' => 'intra',
            'subtotal' => 10000,
            'taxable_amount' => 10000,
            'tax_amount' => 1800,
            'cgst_amount' => 900,
            'sgst_amount' => 900,
            'igst_amount' => 0,
            'round_off' => 0,
            'grand_total' => 11800,
            'consignor_name' => 'DMS Maharashtra Corp',
            'consignor_gstin' => '27AAACD1234F1Z5',
            'consignee_name' => 'Zenith Tech Pune Depot',
            'consignee_gstin' => '27AABCA9999Z1Z8',
            'consignee_city' => 'Pune',
            'consignee_state' => 'Maharashtra',
            'eway_bill_no' => '271234567890',
            'transporter_name' => 'SafeX Express Cargo',
        ]);

        // Test Show Page
        $showResponse = $this->actingAs($this->user)
            ->get(route('invoices.show', $invoice));
        $showResponse->assertOk()
            ->assertSee('DMS Maharashtra Corp')
            ->assertSee('Zenith Tech Pune Depot')
            ->assertSee('271234567890')
            ->assertSee('SafeX Express Cargo');

        // Test Preview Page
        $previewResponse = $this->actingAs($this->user)
            ->get(route('invoices.preview', $invoice));
        $previewResponse->assertOk()
            ->assertSee('Zenith Tech Pune Depot')
            ->assertSee('271234567890');

        // Test PDF Output
        $pdfResponse = $this->actingAs($this->user)
            ->get(route('invoices.pdf', $invoice));
        $pdfResponse->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}

