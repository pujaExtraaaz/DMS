<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Services\GstComplianceService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EInvoiceAndEWayBillDirectBillingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $b2bCustomer;
    protected Customer $b2cCustomer;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->company = Company::create([
            'name' => 'Apex Technologies Pvt Ltd',
            'code' => 'APEX-01',
            'state' => 'Maharashtra',
            'gstin' => '27AAACA1234F1Z8',
            'address' => 'Plot 10, Andheri East',
            'city' => 'Mumbai',
            'pincode' => '400069',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'name' => 'Billing Officer',
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

        $this->uom = Uom::create([
            'code' => 'PCS',
            'name' => 'Pieces',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Controller X1',
            'code' => 'PRD-IC-X1',
            'sku' => 'IC-X1-001',
            'serial_no' => 'PRD-IC-X1',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 25000.00,
            'tax_rate' => 18.00,
            'hsn_code' => '85371000',
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $this->b2bCustomer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Precision Automation Ltd',
            'code' => 'CUST-B2B-01',
            'state' => 'Maharashtra',
            'shipping_state' => 'Maharashtra',
            'gstin' => '27AABCP1234M1ZR',
            'address' => 'Plot 40, MIDC Bhosari',
            'city' => 'Pune',
            'pincode' => '411026',
            'credit_days' => 30,
            'is_active' => true,
        ]);

        $this->b2cCustomer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Ramesh Walk-in Buyer',
            'code' => 'CUST-B2C-01',
            'state' => 'Maharashtra',
            'shipping_state' => 'Maharashtra',
            'gstin' => null,
            'address' => 'Shop 5, FC Road',
            'city' => 'Pune',
            'pincode' => '411005',
            'credit_days' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Verify the section is positioned after Line Items and Commercial Schemes, before footer actions.
     */
    public function test_compliance_section_is_positioned_below_line_items_and_schemes(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));
        $response->assertStatus(200);

        $html = $response->getContent();

        $lineItemsPos = strpos($html, 'Line Items');
        $grandTotalPos = strpos($html, 'Grand Total:');
        $schemesPos = strpos($html, 'Commercial Schemes');
        $compliancePos = strpos($html, 'id="compliance-and-logistics-section"');
        $createInvoiceBtnPos = strpos($html, 'Create Invoice');

        $this->assertNotFalse($lineItemsPos, 'Line Items section should be present');
        $this->assertNotFalse($grandTotalPos, 'Grand Total should be present');
        $this->assertNotFalse($schemesPos, 'Commercial Schemes section should be present');
        $this->assertNotFalse($compliancePos, 'Compliance & Logistics section should be present');
        $this->assertNotFalse($createInvoiceBtnPos, 'Create Invoice button should be present');

        // Statutory section must be AFTER Line Items, Totals, and Schemes, and BEFORE Create Invoice button
        $this->assertGreaterThan($lineItemsPos, $compliancePos, 'Compliance section must be below Line Items');
        $this->assertGreaterThan($grandTotalPos, $compliancePos, 'Compliance section must be below Grand Total');
        $this->assertGreaterThan($schemesPos, $compliancePos, 'Compliance section must be below Commercial Schemes');
        $this->assertLessThan($createInvoiceBtnPos, $compliancePos, 'Compliance section must be before Create Invoice button');
    }

    /**
     * Test 2: Dynamic variables and customer GSTIN data are passed to the view and Alpine config.
     */
    public function test_compliance_metadata_and_customer_gstins_are_available_in_view(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));
        $response->assertStatus(200);

        // Verify Company GSTIN is present
        $response->assertSee('27AAACA1234F1Z8');

        // Verify threshold is the statutory constant 50000
        $this->assertEquals(50000.0, GstComplianceService::EWAY_BILL_THRESHOLD);
        $response->assertSee('ewayThreshold: 50000', false);

        // Verify customer option includes data-gstin
        $response->assertSee('data-gstin="27AABCP1234M1ZR"', false);
        $response->assertSee('data-gstin=""', false);

        // Verify reactive computed properties exist in script
        $response->assertSee('isEInvoiceApplicable', false);
        $response->assertSee('isEWayBillMandatory', false);
        $response->assertSee('isEWayBillApplicable', false);
        $response->assertSee('buyerGstin', false);
        $response->assertSee('isB2B', false);
    }

    /**
     * Test 3: Renders statutory rule references (Rule 48(4) for E-Invoice and Rule 138 for E-Way Bill).
     */
    public function test_view_renders_statutory_gst_rule_badges_and_explanations(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));
        $response->assertStatus(200);

        $response->assertSee('Rule 48(4)');
        $response->assertSee('Rule 138');
        $response->assertSee('E-Invoice &amp; E-Way Bill Not Required for this Invoice', false);
        $response->assertSee('+ Enable e-Way Bill / Transport Details', false);
    }

    /**
     * Test 4: B2B invoice with Grand Total >= ₹50,000 saves both E-Invoice (pending) and E-Way Bill (pending).
     */
    public function test_saving_qualifying_b2b_invoice_persists_pending_status_for_both_compliances(): void
    {
        // 2 units @ 25,000 + 18% GST = 50,000 + 9,000 = ₹59,000 (exceeds ₹50,000 threshold and is B2B)
        $payload = [
            'customer_id' => $this->b2bCustomer->id,
            'invoice_date' => Carbon::now()->format('Y-m-d'),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'eway_bill_required' => 'auto',
            'transport_mode' => '1 - Road',
            'motor_vehicle_no' => 'MH-12-AB-9988',
            'distance_km' => 45,
            'consignor_name' => $this->company->name,
            'consignor_gstin' => $this->company->gstin,
            'consignor_state' => $this->company->state,
            'consignee_name' => $this->b2bCustomer->name,
            'consignee_gstin' => $this->b2bCustomer->gstin,
            'consignee_state' => $this->b2bCustomer->state,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 25000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(59000.00, (float) $invoice->grand_total);

        // B2B with registered buyer -> einvoice_status is pending
        $this->assertEquals('pending', $invoice->einvoice_status);

        // Exceeds 50,000 threshold -> eway_bill_status is pending
        $this->assertEquals('pending', $invoice->eway_bill_status);

        // Consignor, Consignee, Vehicle and Distance details are stored
        $this->assertEquals('MH-12-AB-9988', $invoice->vehicle_no);
        $this->assertEquals(45, $invoice->distance_km);
        $this->assertEquals($this->company->gstin, $invoice->consignor_gstin);
        $this->assertEquals($this->b2bCustomer->gstin, $invoice->consignee_gstin);
    }

    /**
     * Test 5: B2C invoice below ₹50,000 saves both compliances as not_applicable.
     */
    public function test_saving_non_qualifying_b2c_invoice_persists_not_applicable_for_both_compliances(): void
    {
        // 1 unit @ 25,000 + 18% GST = 29,500 (< ₹50,000 and customer is B2C unregistered)
        $payload = [
            'customer_id' => $this->b2cCustomer->id,
            'invoice_date' => Carbon::now()->format('Y-m-d'),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'eway_bill_required' => 'auto',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 25000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(29500.00, (float) $invoice->grand_total);

        // B2C unregistered customer -> E-Invoice is not applicable under GST Rule 48(4)
        $this->assertEquals('not_applicable', $invoice->einvoice_status);

        // Value < 50,000 and auto -> E-Way Bill is not applicable
        $this->assertEquals('not_applicable', $invoice->eway_bill_status);
    }

    /**
     * Test 6: Below ₹50,000 with explicit opt-in ('yes') activates E-Way Bill but leaves B2C E-Invoice as not applicable.
     */
    public function test_user_can_opt_in_for_eway_bill_below_threshold(): void
    {
        $payload = [
            'customer_id' => $this->b2cCustomer->id,
            'invoice_date' => Carbon::now()->format('Y-m-d'),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'eway_bill_required' => 'yes',
            'transport_mode' => '1 - Road',
            'motor_vehicle_no' => 'MH-14-CC-1122',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 10000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);

        $this->assertEquals('not_applicable', $invoice->einvoice_status);
        $this->assertEquals('pending', $invoice->eway_bill_status);
        $this->assertEquals('yes', $invoice->eway_bill_required);
    }

    /**
     * Test 7: Manual entry of E-Way Bill number saves record and marks status as generated.
     */
    public function test_manual_eway_bill_entry_marks_status_as_generated(): void
    {
        $payload = [
            'customer_id' => $this->b2bCustomer->id,
            'invoice_date' => Carbon::now()->format('Y-m-d'),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'eway_bill_required' => 'auto',
            'eway_bill_no' => '987654321098',
            'eway_bill_date' => Carbon::now()->format('Y-m-d'),
            'transport_mode' => '1 - Road',
            'motor_vehicle_no' => 'MH-12-XY-9999',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 3,
                    'unit_price' => 25000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('generated', $invoice->eway_bill_status);
        $this->assertEquals('987654321098', $invoice->eway_bill_no);
        $this->assertNotNull($invoice->eWayBill);
        $this->assertEquals('generated', $invoice->eWayBill->status);
    }
}
