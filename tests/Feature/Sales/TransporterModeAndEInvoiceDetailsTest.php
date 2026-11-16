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
use App\Domains\Sales\Models\EInvoice;
use App\Domains\Sales\Models\EWayBill;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Services\GstComplianceService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransporterModeAndEInvoiceDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customer;
    protected Customer $transporterParty;
    protected PartyAddress $billingAddress;
    protected PartyAddress $deliveryAddress;
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

        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Mumbai Head Office',
            'code' => 'HO-MUM',
            'is_active' => true,
        ]);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-2027',
            'starts_on' => Carbon::now()->subMonths(2)->startOfMonth()->toDateString(),
            'ends_on' => Carbon::now()->addMonths(10)->endOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Warehouse',
            'code' => 'WH-MUM-01',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Reliance Retail Hub',
            'code' => 'CUST-RRH',
            'gstin' => '27AAACR1234F1Z1',
            'state' => 'Maharashtra',
            'city' => 'Mumbai',
            'pincode' => '400001',
            'address' => 'Nariman Point, Mumbai',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        // Transporter party record in master
        $this->transporterParty = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'VRL Logistics Limited',
            'code' => 'VRL-TRANS-01',
            'gstin' => '29AAACV5678F1Z9',
            'state' => 'Karnataka',
            'city' => 'Hubballi',
            'party_type' => 'transporter',
            'is_active' => true,
        ]);

        $this->billingAddress = PartyAddress::create([
            'customer_id' => $this->customer->id,
            'label' => 'Registered Office',
            'type' => 'billing',
            'address_line_1' => 'Tower A, Floor 12, Express Towers',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400021',
            'gstin' => '27AAACR1234F1Z1',
            'is_default_billing' => true,
        ]);

        $this->deliveryAddress = PartyAddress::create([
            'customer_id' => $this->customer->id,
            'label' => 'Bhiwandi Fulfilment Centre',
            'type' => 'delivery',
            'address_line_1' => 'Gala 5-8, Logistics Park, Mankoli',
            'city' => 'Bhiwandi',
            'state' => 'Maharashtra',
            'pincode' => '421302',
            'gstin' => '27AAACR1234F1Z1',
            'is_default_delivery' => true,
        ]);

        $this->uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Enterprise Switch 24-Port',
            'code' => 'PRD-SW-24',
            'sku' => 'SW-24-PORT',
            'serial_no' => 'PRD-SW-24',
            'hsn_code' => '85176290',
            'hsn_sac' => '85176290',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 8000,
            'cost_price' => 5000,
            'tax_rate' => 18,
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);
    }

    public function test_transporter_list_is_accessible_in_create_view(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));

        $response->assertStatus(200);
        $response->assertSee($this->transporterParty->name);
        $response->assertSee('29AAACV5678F1Z9');
        $response->assertSee('Place of Party');
        $response->assertSee('Bill to Place');
        $response->assertSee('Ship to Place');
        $response->assertSee('Mode of Transport');
    }

    public function test_invoice_creation_saves_transporter_details_and_part_b_road_mode(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'billing_address_id' => $this->billingAddress->id,
            'shipping_address_id' => $this->deliveryAddress->id,
            'billing_address' => 'Tower A, Mumbai',
            'shipping_address' => 'Gala 5-8, Bhiwandi',
            'bill_to_place' => 'Mumbai',
            'ship_to_place' => 'Bhiwandi',
            'invoice_date' => Carbon::now()->format('Y-m-d'),
            'delivery_state' => 'Maharashtra',
            'price_type' => 'exclusive',
            'eway_bill_no' => '241098765432',
            'eway_bill_date' => Carbon::now()->format('Y-m-d'),
            'transport_mode' => '1 - Road',
            'transporter_name' => $this->transporterParty->name,
            'transporter_id' => '29AAACV5678F1Z9',
            'motor_vehicle_no' => 'MH04AB1234',
            'vehicle_type' => 'R',
            'distance_km' => 45,
            'lr_rr_no' => 'VRL-LR-8991',
            'lr_rr_date' => Carbon::now()->format('Y-m-d'),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 10,
                    'unit_price' => 8000,
                    'tax_rate' => 18,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);

        $this->assertEquals('Mumbai', $invoice->bill_to_place);
        $this->assertEquals('Bhiwandi', $invoice->ship_to_place);
        $this->assertEquals('1 - Road', $invoice->transport_mode);
        $this->assertEquals($this->transporterParty->name, $invoice->transporter_name);
        $this->assertEquals('29AAACV5678F1Z9', $invoice->transporter_id);
        $this->assertEquals('MH04AB1234', $invoice->vehicle_no);
        $this->assertEquals('VRL-LR-8991', $invoice->lr_rr_no);

        // Check linked E-Way Bill Part B sync
        $eway = EWayBill::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($eway);
        $this->assertEquals('MH04AB1234', $eway->vehicle_no);
        $this->assertEquals('VRL-LR-8991', $eway->doc_no);
        $this->assertEquals('1', $eway->transport_mode);
        $this->assertEquals('29AAACV5678F1Z9', $eway->transporter_id);
    }

    public function test_invoice_creation_supports_rail_air_ship_without_forcing_vehicle_no(): void
    {
        foreach (['2 - Rail', '3 - Air', '4 - Ship'] as $mode) {
            $payload = [
                'customer_id' => $this->customer->id,
                'billing_address_id' => $this->billingAddress->id,
                'shipping_address_id' => $this->deliveryAddress->id,
                'bill_to_place' => 'Mumbai',
                'ship_to_place' => 'Bhiwandi',
                'invoice_date' => Carbon::now()->format('Y-m-d'),
                'delivery_state' => 'Maharashtra',
                'price_type' => 'exclusive',
                'transport_mode' => $mode,
                'transporter_name' => 'Container Corp Rail Logistics',
                'transporter_id' => '27AAACC4321F1Z2',
                'lr_rr_no' => 'RR-998822',
                'lr_rr_date' => Carbon::now()->format('Y-m-d'),
                'motor_vehicle_no' => '', // Empty vehicle number should NOT fail for Rail/Air/Ship
                'distance_km' => 200,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'uom_id' => $this->uom->id,
                        'quantity' => 2,
                        'unit_price' => 8000,
                        'tax_rate' => 18,
                    ],
                ],
            ];

            $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
            $response->assertSessionHasNoErrors();

            $invoice = Invoice::latest('id')->first();
            $this->assertEquals($mode, $invoice->transport_mode);
            $this->assertNull($invoice->vehicle_no);
            $this->assertEquals('RR-998822', $invoice->lr_rr_no);
        }
    }

    public function test_selecting_none_transporter_clears_transporter_data(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'bill_to_place' => 'Mumbai',
            'ship_to_place' => 'Mumbai',
            'invoice_date' => Carbon::now()->format('Y-m-d'),
            'delivery_state' => 'Maharashtra',
            'price_type' => 'exclusive',
            'transport_mode' => 'Not Applicable',
            'transporter_name' => '',
            'transporter_id' => '',
            'motor_vehicle_no' => '',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 8000,
                    'tax_rate' => 18,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();
        $this->assertEquals('Not Applicable', $invoice->transport_mode);
        $this->assertNull($invoice->transporter_name);
        $this->assertNull($invoice->transporter_id);
    }

    public function test_place_of_party_defaults_to_city_when_omitted(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'billing_address_id' => $this->billingAddress->id,
            'shipping_address_id' => $this->deliveryAddress->id,
            'invoice_date' => Carbon::now()->format('Y-m-d'),
            'delivery_state' => 'Maharashtra',
            'price_type' => 'exclusive',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 8000,
                    'tax_rate' => 18,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();
        $this->assertEquals('Mumbai', $invoice->bill_to_place);
        $this->assertEquals('Bhiwandi', $invoice->ship_to_place);
    }

    public function test_invoice_edit_and_update_preserves_place_of_party_and_transporter(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-TEST-001',
            'invoice_date' => Carbon::now(),
            'bill_to_place' => 'Pune',
            'ship_to_place' => 'Nagpur',
            'transport_mode' => '1 - Road',
            'transporter_name' => 'VRL Logistics Limited',
            'transporter_id' => '29AAACV5678F1Z9',
            'vehicle_no' => 'MH12CD5678',
            'grand_total' => 9440,
            'delivery_state' => 'Maharashtra',
            'status' => 'issued',
        ]);

        $editRes = $this->actingAs($this->user)->get(route('invoices.edit', $invoice));
        $editRes->assertStatus(200);
        $editRes->assertSee('Pune');
        $editRes->assertSee('Nagpur');
        $editRes->assertSee('VRL Logistics Limited');

        $updatePayload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => Carbon::now()->format('Y-m-d'),
            'bill_to_place' => 'Pune City',
            'ship_to_place' => 'Nagpur MIDC',
            'transport_mode' => '2 - Rail',
            'transporter_name' => 'VRL Logistics Limited',
            'transporter_id' => '29AAACV5678F1Z9',
            'lr_rr_no' => 'RR-776655',
            'lr_rr_date' => Carbon::now()->format('Y-m-d'),
            'delivery_state' => 'Maharashtra',
            'price_type' => 'exclusive',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 8000,
                    'tax_rate' => 18,
                ],
            ],
        ];

        $updateRes = $this->actingAs($this->user)->put(route('invoices.update', $invoice), $updatePayload);
        $updateRes->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertEquals('Pune City', $invoice->bill_to_place);
        $this->assertEquals('Nagpur MIDC', $invoice->ship_to_place);
        $this->assertEquals('2 - Rail', $invoice->transport_mode);
        $this->assertEquals('RR-776655', $invoice->lr_rr_no);
    }

    public function test_e_invoice_details_display_only_when_backed_by_actual_record(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-EINV-001',
            'invoice_date' => Carbon::now(),
            'bill_to_place' => 'Mumbai',
            'ship_to_place' => 'Mumbai',
            'grand_total' => 118000,
            'delivery_state' => 'Maharashtra',
            'status' => 'issued',
        ]);

        // When no e-Invoice exists yet, document route redirects back to show view
        $unauthDocRes = $this->actingAs($this->user)->get(route('invoices.e-invoice.document', $invoice));
        $unauthDocRes->assertRedirect(route('invoices.show', $invoice));

        // And the invoice show page displays pending e-invoice status
        $showRes = $this->actingAs($this->user)->get(route('invoices.show', $invoice));
        $showRes->assertStatus(200);
        $showRes->assertSee('GST E-Invoice (IRN)');
        $showRes->assertDontSee('987654321012'); // No fake Ack No

        // Now attach an actual verified EInvoice record
        $eInvoice = EInvoice::create([
            'invoice_id' => $invoice->id,
            'irn' => 'e987f6543210abcdef0123456789abcdef0123456789abcdef0123456789abcd',
            'ack_no' => '122610998877665',
            'ack_date' => Carbon::now(),
            'status' => 'generated',
            'gsp_provider' => 'masters_india',
        ]);

        $pageResWithRecord = $this->actingAs($this->user)->get(route('invoices.e-invoice.document', $invoice));
        $pageResWithRecord->assertStatus(200);
        $pageResWithRecord->assertSee('122610998877665');
        $pageResWithRecord->assertSee('e987f6543210abcdef0123456789abcdef0123456789abcdef0123456789abcd');
        $pageResWithRecord->assertSee('Generated / Active');

        // Check edit view banner as well
        $editRes = $this->actingAs($this->user)->get(route('invoices.edit', $invoice));
        $editRes->assertStatus(200);
        $editRes->assertSee('122610998877665');
        $editRes->assertSee('e-Invoice Details (Government IRP)');
    }

    public function test_gst_compliance_service_evaluates_eligibility_by_mode(): void
    {
        $service = app(GstComplianceService::class);

        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-EWB-002',
            'invoice_date' => Carbon::now(),
            'grand_total' => 75000,
            'delivery_state' => 'Maharashtra',
            'transport_mode' => '2 - Rail',
            'lr_rr_no' => 'RR-12345',
            'distance_km' => 150,
            'status' => 'issued',
        ]);

        $eligibility = $service->evaluateEWayBillEligibility($invoice);
        $this->assertTrue($eligibility['eligible']);
        $this->assertEmpty($eligibility['missing_fields']);

        // Road mode requires vehicle number or transporter ID
        $invoice->transport_mode = '1 - Road';
        $invoice->vehicle_no = null;
        $invoice->transporter_id = null;
        $invoice->save();

        $roadEligibility = $service->evaluateEWayBillEligibility($invoice);
        $this->assertTrue($roadEligibility['eligible']);
        $this->assertContains('Vehicle Number or Transporter ID (GSTIN)', $roadEligibility['missing_fields']);
    }
}
