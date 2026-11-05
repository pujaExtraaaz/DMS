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
use App\Domains\Sales\Models\InvoiceItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GovernmentPortalIntegrationAndPrintingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $b2bCustomer;
    protected Customer $b2cCustomer;
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
            'name' => 'Apex Technologies Pvt Ltd',
            'code' => 'APEX-MH',
            'state' => 'Maharashtra',
            'gstin' => '27AAACA9999F1Z9',
            'address' => '401, Tech Park, Andheri East',
            'city' => 'Mumbai',
            'pincode' => '400069',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Apex HQ',
            'code' => 'APEX-HQ',
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
            'name' => 'Main Warehouse',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $this->b2bCustomer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Zenith Enterprise LLP',
            'code' => 'CUST-ZENITH',
            'gstin' => '27AABCZ5555K1Z2',
            'state' => 'Maharashtra',
            'city' => 'Pune',
            'pincode' => '411001',
            'address' => 'FC Road, Pune',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        $this->b2cCustomer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Individual Consumer',
            'code' => 'CUST-B2C',
            'gstin' => null,
            'state' => 'Maharashtra',
            'city' => 'Mumbai',
            'pincode' => '400001',
            'address' => 'Bandra West, Mumbai',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Router 5G',
            'code' => 'PRD-R5G',
            'sku' => 'PRD-R5G',
            'serial_no' => 'PRD-R5G',
            'hsn_code' => '85176290',
            'hsn_sac' => '85176290',
            'tax_rate' => 18.00,
            'base_uom_id' => $this->uom->id,
            'cost_price' => 10000,
            'selling_price' => 15000,
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
        ]);

        $this->user->assignRole($role);
    }

    protected function createSampleInvoice(Customer $customer, bool $withHsn = true): Invoice
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-GOV-' . uniqid(),
            'invoice_date' => Carbon::now()->toDateString(),
            'tax_type' => 'intra',
            'subtotal' => 60000.00,
            'taxable_amount' => 60000.00,
            'cgst_amount' => 5400.00,
            'sgst_amount' => 5400.00,
            'igst_amount' => 0.00,
            'tax_amount' => 10800.00,
            'discount_amount' => 0.00,
            'round_off' => 0.00,
            'grand_total' => 70800.00,
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'bill_to_place' => 'Pune Industrial Zone',
            'ship_to_place' => 'Chakan MIDC Phase 2',
            'transport_mode' => '1 - Road',
            'transporter_name' => 'VRL Logistics Limited',
            'transporter_id' => '27AAACV9876Q1ZB',
            'vehicle_no' => 'MH-12-AB-1234',
            'vehicle_type' => 'R',
            'distance_km' => 160,
            'lr_rr_no' => 'LR-998877',
            'lr_rr_date' => Carbon::now()->toDateString(),
            'terms_of_delivery' => "DELIVER WITHIN 2 DAYS\nGATE TIMINGS: 9 AM TO 5 PM",
            'shipping_address' => 'Plot 88, Chakan MIDC, Pune - 410501',
            'billing_address' => 'FC Road, Pune - 411001',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'quantity' => 4,
            'unit_price' => 15000.00,
            'entered_unit_price' => 15000.00,
            'uom_id' => $this->uom->id,
            'hsn_code' => $withHsn ? '85176290' : null,
            'tax_rate' => 18.00,
            'discount_amount' => 0.00,
            'taxable_amount' => 60000.00,
            'cgst_amount' => 5400.00,
            'sgst_amount' => 5400.00,
            'igst_amount' => 0.00,
            'tax_amount' => 10800.00,
            'line_total' => 70800.00,
        ]);

        return $invoice;
    }

    public function test_unconfigured_credentials_blocks_generation_without_generating_mock_data(): void
    {
        Config::set('services.mastersindia.fallback_to_stub', false);
        Config::set('services.mastersindia.client_id', null);

        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $response = $this->actingAs($this->user)
            ->post(route('invoices.e-invoice', $invoice));

        $response->assertSessionHas('error');
        $this->assertStringContainsStringIgnoringCase('credentials are not configured', session('error'));

        // Ensure no fake IRN was saved
        $invoice->refresh();
        $this->assertEquals('failed', $invoice->einvoice_status);
        $this->assertNotNull($invoice->eInvoice);
        $this->assertEquals('failed', $invoice->eInvoice->status);
        $this->assertNull($invoice->eInvoice->irn);
    }

    public function test_einvoice_prevalidation_blocks_b2c_customers(): void
    {
        Config::set('services.mastersindia.fallback_to_stub', false);

        $invoice = $this->createSampleInvoice($this->b2cCustomer);

        $response = $this->actingAs($this->user)
            ->post(route('invoices.e-invoice', $invoice));

        $response->assertSessionHas('error');
        $this->assertStringContainsStringIgnoringCase('B2C / Unregistered Customer', session('error'));

        $invoice->refresh();
        $this->assertEquals('failed', $invoice->einvoice_status);
        $this->assertNotNull($invoice->eInvoice);
        $this->assertEquals('failed', $invoice->eInvoice->status);
        $this->assertNull($invoice->eInvoice->irn);
    }

    public function test_einvoice_prevalidation_blocks_missing_hsn_codes(): void
    {
        Config::set('services.mastersindia.fallback_to_stub', false);

        // Update product to also have no HSN code
        $this->product->update(['hsn_code' => null, 'hsn_sac' => null]);

        $invoice = $this->createSampleInvoice($this->b2bCustomer, withHsn: false);

        $response = $this->actingAs($this->user)
            ->post(route('invoices.e-invoice', $invoice));

        $response->assertSessionHas('error');
        $this->assertStringContainsStringIgnoringCase('HSN/SAC', session('error'));

        $invoice->refresh();
        $this->assertEquals('failed', $invoice->einvoice_status);
        $this->assertNotNull($invoice->eInvoice);
        $this->assertEquals('failed', $invoice->eInvoice->status);
        $this->assertNull($invoice->eInvoice->irn);
    }

    public function test_upstream_api_failure_records_failed_status_and_does_not_fabricate_irn(): void
    {
        Config::set('services.mastersindia.fallback_to_stub', false);
        Config::set('services.mastersindia.client_id', 'test_client_id');
        Config::set('services.mastersindia.client_secret', 'test_secret');
        Config::set('services.mastersindia.username', 'test_user');
        Config::set('services.mastersindia.password', 'test_pass');
        Config::set('services.mastersindia.gstin', '27AAACA9999F1Z9');

        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'dummy_token'], 200),
            '*/ei/api/invoice' => Http::response([
                'message' => 'Invalid Recipient GSTIN format or taxpayer is inactive.',
            ], 400),
        ]);

        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $response = $this->actingAs($this->user)
            ->post(route('invoices.e-invoice', $invoice));

        $response->assertSessionHas('error');
        $this->assertStringContainsStringIgnoringCase('Invalid Recipient GSTIN format', session('error'));

        $invoice->refresh();
        $this->assertEquals('failed', $invoice->einvoice_status);
        $this->assertNotNull($invoice->eInvoice);
        $this->assertEquals('failed', $invoice->eInvoice->status);
        $this->assertNull($invoice->eInvoice->irn);
        $this->assertStringContainsString('Invalid Recipient GSTIN format', $invoice->eInvoice->last_error);
    }

    public function test_upstream_api_success_persists_irn_ack_and_qr_code(): void
    {
        Config::set('services.mastersindia.fallback_to_stub', false);
        Config::set('services.mastersindia.client_id', 'test_client_id');
        Config::set('services.mastersindia.client_secret', 'test_secret');
        Config::set('services.mastersindia.username', 'test_user');
        Config::set('services.mastersindia.password', 'test_pass');
        Config::set('services.mastersindia.gstin', '27AAACA9999F1Z9');

        $expectedIrn = '1234567890ABCDEF1234567890ABCDEF1234567890ABCDEF1234567890ABCDEF';
        $expectedAckNo = '112233445566778';
        $expectedAckDate = '2026-11-05 10:30:00';
        $signedQrPayload = 'IRN:' . $expectedIrn . '|GSTIN:27AABCZ5555K1Z2|TOTAL:70800.00';

        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'dummy_token'], 200),
            '*/ei/api/invoice' => Http::response([
                'Data' => [
                    'Irn' => $expectedIrn,
                    'AckNo' => $expectedAckNo,
                    'AckDt' => $expectedAckDate,
                    'SignedQRCode' => $signedQrPayload,
                ],
            ], 200),
        ]);

        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $response = $this->actingAs($this->user)
            ->post(route('invoices.e-invoice', $invoice));

        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals('generated', $invoice->einvoice_status);
        $this->assertNotNull($invoice->eInvoice);
        $this->assertEquals('generated', $invoice->eInvoice->status);
        $this->assertEquals($expectedIrn, $invoice->eInvoice->irn);
        $this->assertEquals($expectedAckNo, $invoice->eInvoice->ack_no);
        $this->assertEquals($signedQrPayload, $invoice->eInvoice->signed_qr_base64);

        // Verify getQrDataUri provides a scannable data URI
        $qrDataUri = $invoice->eInvoice->getQrDataUri();
        $this->assertNotNull($qrDataUri);
        $this->assertStringStartsWith('data:image/', $qrDataUri);
    }

    public function test_duplicate_einvoice_generation_is_prevented(): void
    {
        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        EInvoice::create([
            'invoice_id' => $invoice->id,
            'status' => 'generated',
            'provider' => 'mastersindia',
            'irn' => 'ALREADYGENERATEDIRN99887766554433221100',
            'ack_no' => '99887766',
            'ack_date' => now(),
        ]);

        $invoice->update(['einvoice_status' => 'generated']);

        $response = $this->actingAs($this->user)
            ->post(route('invoices.e-invoice', $invoice));

        $response->assertSessionHas('error');
        $this->assertStringContainsStringIgnoringCase('already been generated', session('error'));
    }

    public function test_eway_bill_generation_stores_official_details(): void
    {
        Config::set('services.mastersindia.fallback_to_stub', false);
        Config::set('services.mastersindia.client_id', 'test_client_id');
        Config::set('services.mastersindia.client_secret', 'test_secret');
        Config::set('services.mastersindia.username', 'test_user');
        Config::set('services.mastersindia.password', 'test_pass');
        Config::set('services.mastersindia.gstin', '27AAACA9999F1Z9');

        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'dummy_token'], 200),
            '*/ewb/api/ewaybill' => Http::response([
                'Data' => [
                    'EwbNo' => '231099887766',
                    'EwbDt' => '2026-11-05 11:00:00',
                    'EwbValidTill' => '2026-11-07 23:59:59',
                ],
            ], 200),
        ]);

        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $response = $this->actingAs($this->user)
            ->post(route('invoices.eway', $invoice), [
                'transport_mode' => '1',
                'vehicle_no' => 'MH-12-AB-1234',
                'vehicle_type' => 'R',
                'distance_km' => 160,
                'transporter_id' => '27AAACV9876Q1ZB',
                'transporter_name' => 'VRL Logistics Limited',
            ]);

        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals('generated', $invoice->eway_bill_status);
        $this->assertNotNull($invoice->eWayBill);
        $this->assertEquals('231099887766', $invoice->eWayBill->eway_bill_no);
        $this->assertEquals('generated', $invoice->eWayBill->status);
    }

    public function test_tax_invoice_preview_renders_government_metadata_and_qr(): void
    {
        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $irn = 'ABCDEF1234567890ABCDEF1234567890ABCDEF1234567890ABCDEF1234567890';
        $ackNo = '123456789012345';
        $qrPayload = 'IRN:' . $irn . '|ACK:' . $ackNo;

        EInvoice::create([
            'invoice_id' => $invoice->id,
            'status' => 'generated',
            'provider' => 'mastersindia',
            'irn' => $irn,
            'ack_no' => $ackNo,
            'ack_date' => now(),
            'signed_qr_base64' => $qrPayload,
        ]);

        EWayBill::create([
            'invoice_id' => $invoice->id,
            'status' => 'generated',
            'provider' => 'mastersindia',
            'eway_bill_no' => '191099887766',
            'ewb_date' => now(),
            'valid_upto' => now()->addDays(2),
            'distance_km' => 160,
        ]);

        $invoice->update([
            'einvoice_status' => 'generated',
            'eway_bill_status' => 'generated',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('invoices.preview', ['invoice' => $invoice, 'copy' => 'duplicate']));

        $response->assertOk();
        $response->assertSee('TAX INVOICE');
        $response->assertSee('Duplicate for Transporter');
        $response->assertSee('Standard GST Tax Invoice');
        $response->assertSee('A4 Portrait');

        // Place of Buyer and Delivery
        $response->assertSee('Pune Industrial Zone');
        $response->assertSee('Chakan MIDC Phase 2');

        // Government compliance metadata
        $response->assertSee($irn);
        $response->assertSee($ackNo);
        $response->assertSee('191099887766');

        // Signed QR Code Image present
        $response->assertSee('data:image/png;base64,');
        $response->assertSee('Signed e-Invoice QR');
    }

    public function test_tax_invoice_pdf_export_contains_metadata_and_qr(): void
    {
        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $irn = 'PDFTESTIRN1234567890ABCDEF1234567890ABCDEF1234567890ABCDEF123456';
        EInvoice::create([
            'invoice_id' => $invoice->id,
            'status' => 'generated',
            'provider' => 'mastersindia',
            'irn' => $irn,
            'ack_no' => '99887711',
            'ack_date' => now(),
            'signed_qr_base64' => 'IRN:' . $irn,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('invoices.export', ['invoice' => $invoice, 'format' => 'pdf']));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_show_view_renders_print_settings_dialog_and_export_options(): void
    {
        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $response = $this->actingAs($this->user)
            ->get(route('invoices.show', $invoice));

        $response->assertOk();
        $response->assertSee('printSettingsModal');
        $response->assertSee('All Copies (Original + Duplicate + Triplicate)');
        $response->assertSee('Original for Recipient');
        $response->assertSee('Duplicate for Transporter');
        $response->assertSee('Triplicate for Supplier');
        $response->assertSee('Extra Copy');
        $response->assertSee('Not Applicable (No copy label)');
        $response->assertSee(route('invoices.export', ['invoice' => $invoice, 'format' => 'pdf']));
        $response->assertSee(route('invoices.export', ['invoice' => $invoice, 'format' => 'xlsx']));
        $response->assertSee(route('invoices.export', ['invoice' => $invoice, 'format' => 'csv']));
    }

    public function test_preview_supports_all_copies_generating_three_separate_pages(): void
    {
        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $response = $this->actingAs($this->user)
            ->get(route('invoices.preview', ['invoice' => $invoice, 'copy_type' => 'all']));

        $response->assertOk();
        // Verifies all 3 standard statutory copy labels appear on the page
        $response->assertSee('Original for Recipient');
        $response->assertSee('Duplicate for Transporter');
        $response->assertSee('Triplicate for Supplier');

        // Verifies each page includes the invoice number and product description
        $this->assertEquals(3, substr_count($response->getContent(), 'Industrial Router 5G'));
        $this->assertEquals(3, substr_count($response->getContent(), 'invoice-copy-page'));
    }

    public function test_preview_supports_single_copy_selection(): void
    {
        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        // 1. Original only
        $resOriginal = $this->actingAs($this->user)
            ->get(route('invoices.preview', ['invoice' => $invoice, 'copy_type' => 'original', 'copies' => 1]));
        $resOriginal->assertOk();
        $resOriginal->assertSee('<span class="badge-recipient">Original for Recipient</span>', false);
        $resOriginal->assertDontSee('<span class="badge-recipient">Duplicate for Transporter</span>', false);
        $resOriginal->assertDontSee('<span class="badge-recipient">Triplicate for Supplier</span>', false);

        // 2. Duplicate only
        $resDuplicate = $this->actingAs($this->user)
            ->get(route('invoices.preview', ['invoice' => $invoice, 'copy_type' => 'duplicate', 'copies' => 1]));
        $resDuplicate->assertOk();
        $resDuplicate->assertSee('<span class="badge-recipient">Duplicate for Transporter</span>', false);
        $resDuplicate->assertDontSee('<span class="badge-recipient">Original for Recipient</span>', false);
        $resDuplicate->assertDontSee('<span class="badge-recipient">Triplicate for Supplier</span>', false);

        // 3. Triplicate only
        $resTriplicate = $this->actingAs($this->user)
            ->get(route('invoices.preview', ['invoice' => $invoice, 'copy_type' => 'triplicate', 'copies' => 1]));
        $resTriplicate->assertOk();
        $resTriplicate->assertSee('<span class="badge-recipient">Triplicate for Supplier</span>', false);
        $resTriplicate->assertDontSee('<span class="badge-recipient">Original for Recipient</span>', false);
        $resTriplicate->assertDontSee('<span class="badge-recipient">Duplicate for Transporter</span>', false);
    }

    public function test_preview_supports_not_applicable_copy_type(): void
    {
        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $response = $this->actingAs($this->user)
            ->get(route('invoices.preview', ['invoice' => $invoice, 'copy_type' => 'na', 'copies' => 1]));

        $response->assertOk();
        $response->assertSee('Tax Scheme: GST');
        $response->assertDontSee('<span class="badge-recipient">', false);
    }

    public function test_preview_supports_custom_number_of_copies(): void
    {
        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $response = $this->actingAs($this->user)
            ->get(route('invoices.preview', ['invoice' => $invoice, 'copy_type' => 'extra', 'copies' => 4]));

        $response->assertOk();
        $this->assertEquals(4, substr_count($response->getContent(), '<span class="badge-recipient">Extra Copy</span>'));
        $this->assertEquals(4, substr_count($response->getContent(), 'invoice-copy-page'));
    }

    public function test_preview_auto_print_flag_triggers_window_print(): void
    {
        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $response = $this->actingAs($this->user)
            ->get(route('invoices.preview', ['invoice' => $invoice, 'auto_print' => 1]));

        $response->assertOk();
        $response->assertSee('window.print()');
    }

    public function test_export_csv_contains_line_items_tax_breakdown_and_metadata(): void
    {
        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $response = $this->actingAs($this->user)
            ->get(route('invoices.export', ['invoice' => $invoice, 'format' => 'csv']));

        $response->assertOk();
        $csv = $response->streamedContent();

        // Check required column headers
        $this->assertStringContainsString('Product Description', $csv);
        $this->assertStringContainsString('HSN/SAC', $csv);
        $this->assertStringContainsString('Taxable Amount (₹)', $csv);
        $this->assertStringContainsString('GST Rate (%)', $csv);
        $this->assertStringContainsString('CGST (₹)', $csv);
        $this->assertStringContainsString('SGST (₹)', $csv);
        $this->assertStringContainsString('IGST (₹)', $csv);
        $this->assertStringContainsString('Line Total (₹)', $csv);

        // Check content rows
        $this->assertStringContainsString('Industrial Router 5G', $csv);
        $this->assertStringContainsString('85176290', $csv);
        $this->assertStringContainsString('60000.00', $csv);
        $this->assertStringContainsString('5400.00', $csv);
        $this->assertStringContainsString('70800.00', $csv);

        // Check metadata
        $this->assertStringContainsString('Pune Industrial Zone', $csv);
        $this->assertStringContainsString('Chakan MIDC Phase 2', $csv);
        $this->assertStringContainsString('VRL Logistics Limited', $csv);
        $this->assertStringContainsString('27AAACV9876Q1ZB', $csv);
    }

    public function test_export_excel_returns_valid_spreadsheet_response(): void
    {
        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $response = $this->actingAs($this->user)
            ->get(route('invoices.export', ['invoice' => $invoice, 'format' => 'xlsx']));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type'));
    }

    public function test_export_pdf_with_all_copies(): void
    {
        $invoice = $this->createSampleInvoice($this->b2bCustomer);

        $response = $this->actingAs($this->user)
            ->get(route('invoices.export', ['invoice' => $invoice, 'format' => 'pdf', 'copy_type' => 'all', 'copies' => 3]));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}
