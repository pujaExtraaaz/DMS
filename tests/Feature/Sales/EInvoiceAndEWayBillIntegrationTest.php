<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Sales\Models\EInvoice;
use App\Domains\Sales\Models\EWayBill;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Sales\Services\GstComplianceService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EInvoiceAndEWayBillIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Customer $b2bCustomer;
    protected Customer $b2cCustomer;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product;
    protected GstComplianceService $complianceService;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->company = Company::create([
            'name' => 'DMS Enterprise Ltd',
            'code' => 'DMS-ENT',
            'state' => 'Maharashtra',
            'gstin' => '27AAACD1234F1Z5',
            'address' => 'Andheri East, Mumbai',
            'city' => 'Mumbai',
            'pincode' => '400069',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Tax Officer',
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($role);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => Carbon::now()->subMonths(2)->startOfMonth()->toDateString(),
            'ends_on' => Carbon::now()->addMonths(10)->endOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        $this->b2bCustomer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Apex Retailers Pvt Ltd',
            'code' => 'CUST-B2B',
            'state' => 'Maharashtra',
            'shipping_state' => 'Maharashtra',
            'gstin' => '27AABCA5678B1Z2',
            'address' => 'Plot 42, MIDC Hinjewadi',
            'pincode' => '411057',
            'shipping_address' => 'Plot 42, MIDC Hinjewadi',
            'shipping_pincode' => '411057',
            'credit_days' => 30,
            'is_active' => true,
        ]);

        $this->b2cCustomer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'John Doe Consumer',
            'code' => 'CUST-B2C',
            'state' => 'Maharashtra',
            'shipping_state' => 'Maharashtra',
            'gstin' => null,
            'address' => 'Bandra West, Mumbai',
            'pincode' => '400050',
            'credit_days' => 0,
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name' => 'Mumbai Depot',
            'code' => 'DEP-01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Hub',
            'code' => 'HUB-MUM',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Units',
            'code' => 'NOS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Gateway Router',
            'sku' => 'IND-ROUTER',
            'serial_no' => 'PRD-IND-ROUTER',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 25000.00,
            'tax_rate' => 18.00,
            'hsn_code' => '851762',
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $this->complianceService = app(GstComplianceService::class);
    }

    public function test_b2b_invoice_defaults_to_einvoice_pending_and_evaluates_eligible(): void
    {
        $payload = [
            'customer_id' => $this->b2bCustomer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'credit_days' => 15,
            'eway_bill_required' => 'auto',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 25000.00,
                    'discount_percent' => 0,
                ],
            ],
        ];

        $res = $this->actingAs($this->adminUser)->post(route('invoices.store'), $payload);
        $res->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->firstOrFail();

        // Under Rule 48(4), B2B supply is eligible for E-Invoice
        $this->assertEquals('pending', $invoice->einvoice_status);

        $eval = $this->complianceService->evaluateEInvoiceEligibility($invoice);
        $this->assertTrue($eval['eligible']);
    }

    public function test_b2c_invoice_defaults_to_einvoice_not_applicable(): void
    {
        $payload = [
            'customer_id' => $this->b2cCustomer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'credit_days' => 0,
            'eway_bill_required' => 'auto',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 25000.00,
                    'discount_percent' => 0,
                ],
            ],
        ];

        $res = $this->actingAs($this->adminUser)->post(route('invoices.store'), $payload);
        $res->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->firstOrFail();

        // B2C is strictly exempt from IRP E-Invoicing
        $this->assertEquals('not_applicable', $invoice->einvoice_status);

        $eval = $this->complianceService->evaluateEInvoiceEligibility($invoice);
        $this->assertFalse($eval['eligible']);
        $this->assertStringContainsString('B2C', $eval['reason']);
    }

    public function test_consignment_threshold_evaluates_eway_bill_eligibility_correctly(): void
    {
        // 1. High-value invoice (> ₹50,000 threshold): 3 x 25,000 = 75,000 + 18% tax = 88,500
        $highValuePayload = [
            'customer_id' => $this->b2bCustomer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'eway_bill_required' => 'auto',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 3,
                    'unit_price' => 25000.00,
                    'discount_percent' => 0,
                ],
            ],
        ];

        $this->actingAs($this->adminUser)->post(route('invoices.store'), $highValuePayload);
        $highInv = Invoice::latest('id')->firstOrFail();
        $this->assertEquals('pending', $highInv->eway_bill_status);

        $evalHigh = $this->complianceService->evaluateEWayBillEligibility($highInv);
        $this->assertTrue($evalHigh['eligible']);

        // 2. Low-value invoice (<= ₹50,000) with auto: 1 x 10,000 = 10,000 + 18% = 11,800
        $lowValuePayload = [
            'customer_id' => $this->b2bCustomer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'eway_bill_required' => 'auto',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 10000.00,
                    'discount_percent' => 0,
                ],
            ],
        ];

        $this->actingAs($this->adminUser)->post(route('invoices.store'), $lowValuePayload);
        $lowInv = Invoice::latest('id')->firstOrFail();
        $this->assertEquals('not_applicable', $lowInv->eway_bill_status);

        // 3. Low-value invoice with explicit voluntary 'yes'
        $lowValuePayload['eway_bill_required'] = 'yes';
        $this->actingAs($this->adminUser)->post(route('invoices.store'), $lowValuePayload);
        $voluntaryInv = Invoice::latest('id')->firstOrFail();
        $this->assertEquals('pending', $voluntaryInv->eway_bill_status);
    }

    public function test_e_invoice_generation_and_cancellation_lifecycle(): void
    {
        $payload = [
            'customer_id' => $this->b2bCustomer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'eway_bill_required' => 'auto',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 25000.00,
                    'discount_percent' => 0,
                ],
            ],
        ];

        $this->actingAs($this->adminUser)->post(route('invoices.store'), $payload);
        $invoice = Invoice::latest('id')->firstOrFail();

        // 1. Generate IRN
        $genRes = $this->actingAs($this->adminUser)->post(route('invoices.e-invoice.generate', $invoice));
        $genRes->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals('generated', $invoice->einvoice_status);

        $eInvoice = EInvoice::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertEquals('generated', $eInvoice->status);
        $this->assertEquals(64, strlen($eInvoice->irn));
        $this->assertNotEmpty($eInvoice->ack_no);
        $this->assertNotEmpty($eInvoice->signed_qr_code);

        // 2. Prevent duplicate generation
        $dupRes = $this->actingAs($this->adminUser)->post(route('invoices.e-invoice.generate', $invoice));
        $dupRes->assertSessionHas('error');

        // 3. Cancel IRN with reason code
        $cancelRes = $this->actingAs($this->adminUser)->post(route('invoices.e-invoice.cancel', $invoice), [
            'cancel_reason' => '2',
            'cancel_remarks' => 'Wrong HSN entered by operator',
        ]);
        $cancelRes->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $eInvoice->refresh();

        $this->assertEquals('cancelled', $invoice->einvoice_status);
        $this->assertEquals('cancelled', $eInvoice->status);
        $this->assertStringContainsStringIgnoringCase('Data Entry Mistake', $eInvoice->cancel_reason);
        $this->assertEquals('Wrong HSN Entered By Operator', $eInvoice->cancel_remarks);
        $this->assertNotNull($eInvoice->cancelled_at);
        $this->assertEquals($this->adminUser->id, $eInvoice->cancelled_by);
    }

    public function test_eway_bill_generation_update_vehicle_and_cancellation_lifecycle(): void
    {
        $payload = [
            'customer_id' => $this->b2bCustomer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'transport_mode' => 'road',
            'vehicle_no' => 'MH12AB1234',
            'vehicle_type' => 'R',
            'distance_km' => 85,
            'eway_bill_required' => 'auto',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 3,
                    'unit_price' => 25000.00,
                    'discount_percent' => 0,
                ],
            ],
        ];

        $this->actingAs($this->adminUser)->post(route('invoices.store'), $payload);
        $invoice = Invoice::latest('id')->firstOrFail();

        // 1. Generate E-Way Bill
        $ewbGen = $this->actingAs($this->adminUser)->post(route('invoices.eway.generate', $invoice), [
            'transporter_id' => '27TRANS1234F1Z9',
            'transporter_name' => 'Swift Logistics Ltd',
            'vehicle_no' => 'MH12AB1234',
            'distance_km' => 90,
            'vehicle_type' => 'R',
        ]);
        $ewbGen->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals('generated', $invoice->eway_bill_status);
        $this->assertEquals('Swift Logistics Ltd', $invoice->transporter_name);

        $eWayBill = EWayBill::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertEquals('generated', $eWayBill->status);
        $this->assertEquals(12, strlen($eWayBill->eway_bill_no));
        $this->assertNotNull($eWayBill->valid_until);

        // 2. Update Vehicle in Transit
        $vehRes = $this->actingAs($this->adminUser)->post(route('invoices.eway.update-vehicle', $invoice), [
            'vehicle_no' => 'MH14XY9876',
            'from_place' => 'Pune Transshipment Hub',
            'from_state' => 'Maharashtra',
            'reason_code' => '1',
            'remarks' => 'Vehicle breakdown near Lonavala',
        ]);
        $vehRes->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals('MH14XY9876', $invoice->vehicle_no);

        // 3. Cancel E-Way Bill
        $cancelRes = $this->actingAs($this->adminUser)->post(route('invoices.eway.cancel', $invoice), [
            'cancel_reason' => '1',
            'cancel_remarks' => 'Customer order cancelled before dispatch',
        ]);
        $cancelRes->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $eWayBill->refresh();

        $this->assertEquals('cancelled', $invoice->eway_bill_status);
        $this->assertEquals('cancelled', $eWayBill->status);
        $this->assertStringContainsStringIgnoringCase('Duplicate', $eWayBill->cancel_reason);
        $this->assertEquals('Customer Order Cancelled Before Dispatch', $eWayBill->cancel_remarks);
        $this->assertNotNull($eWayBill->cancelled_at);
    }

    public function test_invoice_index_filters_by_compliance_status(): void
    {
        // Invoice 1: Generated E-Invoice
        $inv1 = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->b2bCustomer->id,
            'invoice_no' => 'INV-TEST-EINV',
            'invoice_date' => Carbon::now(),
            'status' => 'pending',
            'subtotal' => 20000,
            'tax_amount' => 3600,
            'grand_total' => 23600,
            'paid_amount' => 0,
            'einvoice_status' => 'generated',
            'eway_bill_status' => 'not_applicable',
        ]);
        EInvoice::create([
            'invoice_id' => $inv1->id,
            'status' => 'generated',
            'irn' => str_repeat('a', 64),
        ]);

        // Invoice 2: Generated E-Way Bill
        $inv2 = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->b2bCustomer->id,
            'invoice_no' => 'INV-TEST-EWB',
            'invoice_date' => Carbon::now(),
            'status' => 'pending',
            'subtotal' => 75000,
            'tax_amount' => 13500,
            'grand_total' => 88500,
            'paid_amount' => 0,
            'einvoice_status' => 'not_applicable',
            'eway_bill_status' => 'generated',
        ]);
        EWayBill::create([
            'invoice_id' => $inv2->id,
            'status' => 'generated',
            'eway_bill_no' => '123456789012',
        ]);

        // Filter by einvoice_status=generated
        $resEinv = $this->actingAs($this->adminUser)->get(route('invoices.index', ['einvoice_status' => 'generated']));
        $resEinv->assertOk();
        $resEinv->assertSee('INV-TEST-EINV');
        $resEinv->assertDontSee('INV-TEST-EWB');

        // Filter by eway_bill_status=generated
        $resEwb = $this->actingAs($this->adminUser)->get(route('invoices.index', ['eway_bill_status' => 'generated']));
        $resEwb->assertOk();
        $resEwb->assertSee('INV-TEST-EWB');
        $resEwb->assertDontSee('INV-TEST-EINV');
    }

    public function test_eway_bill_view_renders_cleanly_for_active_and_cancelled(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->b2bCustomer->id,
            'invoice_no' => 'INV-TEST-VIEW',
            'invoice_date' => Carbon::now(),
            'status' => 'pending',
            'subtotal' => 60000,
            'tax_amount' => 10800,
            'grand_total' => 70800,
            'paid_amount' => 0,
            'vehicle_no' => 'MH12CD5678',
            'distance_km' => 120,
            'transport_mode' => 'road',
            'einvoice_status' => 'pending',
            'eway_bill_status' => 'generated',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_price' => 30000,
            'tax_rate' => 18,
            'tax_amount' => 10800,
            'line_total' => 60000,
            'total_amount' => 70800,
        ]);

        $ewb = EWayBill::create([
            'invoice_id' => $invoice->id,
            'status' => 'generated',
            'eway_bill_no' => '321098765432',
            'valid_until' => Carbon::now()->addDays(2),
        ]);

        // Active document view
        $resActive = $this->actingAs($this->adminUser)->get(route('invoices.eway.document', $invoice));
        $resActive->assertOk();
        $resActive->assertSee('321098765432');
        $resActive->assertSee('ACTIVE');
        $resActive->assertSee('MH12CD5678');
        $resActive->assertSee('120 KM');

        // Cancelled document view
        $ewb->update([
            'status' => 'cancelled',
            'cancel_reason' => 'Order Cancelled',
            'cancel_remarks' => 'Partially damaged in warehouse',
            'cancelled_at' => Carbon::now(),
        ]);
        $invoice->update(['eway_bill_status' => 'cancelled']);

        $resCancelled = $this->actingAs($this->adminUser)->get(route('invoices.eway.document', $invoice));
        $resCancelled->assertOk();
        $resCancelled->assertSee('This E-Way Bill was CANCELLED');
        $resCancelled->assertSee('Partially Damaged In Warehouse');
    }
}
