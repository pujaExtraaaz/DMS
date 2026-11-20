<?php

namespace Tests\Feature\Sales;

use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Sales\Models\EWayBill;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DiagnoseAndFixEWayBillGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Customer $customer;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super-admin');

        $this->company = Company::create([
            'name' => 'Apex Industrial Corp',
            'legal_name' => 'Apex Industrial Corporation Ltd',
            'gstin' => '27AAACA1234A1Z5',
            'address' => 'Plot 42, MIDC Industrial Area, Andheri East',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400093',
            'country' => 'India',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->customer = Customer::create([
            'name' => 'Western Logistics Hub',
            'code' => 'CUST-0099',
            'party_type' => 'sundry_debtors',
            'gstin' => '27AABCU9603R1ZM',
            'address' => 'Unit 10, Logistics Park, Vashi',
            'city' => 'Navi Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400703',
            'phone' => '9820012345',
            'is_active' => true,
        ]);

        $this->uom = Uom::firstOrCreate(['code' => 'NOS'], ['name' => 'Numbers']);

        $this->product = Product::create([
            'name' => 'Hydraulic Valve System',
            'sku' => 'HVS-900',
            'serial_no' => 'SN-900',
            'hsn_code' => '84818090',
            'selling_price' => 28000.00,
            'tax_rate' => 18.00,
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
            'tracking_type' => 'none',
        ]);
    }

    protected function createValidInvoice(float $unitPrice = 30000.00, int $qty = 2, ?string $hsnCode = '84818090'): Invoice
    {
        if ($hsnCode !== '84818090') {
            $this->product->update(['hsn_code' => $hsnCode]);
        }

        $taxable = $unitPrice * $qty;
        $cgst = round(($taxable * 0.09), 2);
        $sgst = round(($taxable * 0.09), 2);
        $grandTotal = $taxable + $cgst + $sgst;

        $invoice = Invoice::create([
            'invoice_no' => 'INV-' . date('Ymd') . '-' . rand(1000, 9999),
            'invoice_date' => Carbon::now()->toDateString(),
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->adminUser->id,
            'subtotal' => $taxable,
            'taxable_amount' => $taxable,
            'tax_amount' => $cgst + $sgst,
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'igst_amount' => 0.00,
            'grand_total' => $grandTotal,
            'delivery_state' => 'Maharashtra',
            'price_type' => 'exclusive',
            'tax_type' => 'intra',
            'status' => 'issued',
            'eway_bill_status' => 'pending',
            'consignor_pincode' => '400093',
            'consignor_city' => 'Mumbai',
            'consignee_pincode' => '400703',
            'consignee_city' => 'Navi Mumbai',
        ]);

        $invoice->items()->create([
            'product_id' => $this->product->id,
            'item_name' => $this->product->name,
            'hsn_code' => $hsnCode,
            'quantity' => $qty,
            'uom_id' => $this->uom->id,
            'unit_price' => $unitPrice,
            'tax_rate' => 18.00,
            'taxable_amount' => $taxable,
            'tax_amount' => $cgst + $sgst,
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'line_total' => $grandTotal,
        ]);

        return $invoice;
    }

    public function test_missing_transport_details_shows_field_specific_validation_messages(): void
    {
        $invoice = $this->createValidInvoice();

        // Submit generation with missing vehicle number and transporter ID for Road mode
        $response = $this->actingAs($this->adminUser)->post(route('invoices.eway.generate', $invoice), [
            'transport_mode' => '1',
            'distance_km' => 60,
            'vehicle_no' => '',
            'transporter_id' => '',
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('error');
        $errorMessage = session('error');
        $this->assertStringContainsString('Vehicle Number', $errorMessage);
        $this->assertStringContainsString('Transporter ID', $errorMessage);
    }

    public function test_invalid_distance_km_shows_validation_error(): void
    {
        $invoice = $this->createValidInvoice();

        $response = $this->actingAs($this->adminUser)->post(route('invoices.eway.generate', $invoice), [
            'transport_mode' => '1',
            'vehicle_no' => 'MH12AB1234',
            'distance_km' => 0,
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('error');
        $this->assertMatchesRegularExpression('/Transport Distance \(in KM\) is required/i', session('error'));
    }

    public function test_missing_product_hsn_code_shows_specific_error(): void
    {
        $invoice = $this->createValidInvoice(30000.00, 2, null);

        // Product row has no HSN code
        $response = $this->actingAs($this->adminUser)->post(route('invoices.eway.generate', $invoice), [
            'transport_mode' => '1',
            'vehicle_no' => 'MH12AB1234',
            'distance_km' => 75,
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('error');
        $this->assertMatchesRegularExpression('/missing an HSN code/i', session('error'));
    }

    public function test_unconfigured_api_credentials_displays_clear_configuration_error(): void
    {
        Config::set('services.mastersindia.fallback_to_stub', false);
        Config::set('services.mastersindia.client_id', '');
        Config::set('services.mastersindia.client_secret', '');

        $invoice = $this->createValidInvoice();

        $response = $this->actingAs($this->adminUser)->post(route('invoices.eway.generate', $invoice), [
            'transport_mode' => '1',
            'vehicle_no' => 'MH12AB1234',
            'distance_km' => 75,
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('error');
        $this->assertMatchesRegularExpression('/Configuration Error/i', session('error'));
        $this->assertMatchesRegularExpression('/MASTERSINDIA_CLIENT_ID/i', session('error'));

        $invoice->refresh();
        $this->assertEquals('failed', $invoice->eway_bill_status);
        $this->assertNotNull($invoice->eWayBill);
        $this->assertEquals('failed', $invoice->eWayBill->status);
        $this->assertStringContainsString('Configuration Error', $invoice->eWayBill->last_error);
    }

    public function test_api_authentication_failure_displays_clear_authentication_error(): void
    {
        Config::set('services.mastersindia.fallback_to_stub', false);
        Config::set('services.mastersindia.client_id', 'test_client_id');
        Config::set('services.mastersindia.client_secret', 'test_secret');
        Config::set('services.mastersindia.username', 'test_user');
        Config::set('services.mastersindia.password', 'test_pass');
        Config::set('services.mastersindia.gstin', '27AAACA1234A1Z5');

        Http::fake([
            '*/oauth/token' => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed due to invalid credentials.',
            ], 401),
        ]);

        $invoice = $this->createValidInvoice();

        $response = $this->actingAs($this->adminUser)->post(route('invoices.eway.generate', $invoice), [
            'transport_mode' => '1',
            'vehicle_no' => 'MH12AB1234',
            'distance_km' => 75,
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('error');
        $this->assertMatchesRegularExpression('/Authentication Error/i', session('error'));
        $this->assertMatchesRegularExpression('/Client authentication failed/i', session('error'));

        $invoice->refresh();
        $this->assertEquals('failed', $invoice->eway_bill_status);
        $this->assertMatchesRegularExpression('/Authentication Error/i', $invoice->eWayBill->last_error);
    }

    public function test_government_portal_rejection_displays_exact_rejection_reason(): void
    {
        Config::set('services.mastersindia.fallback_to_stub', false);
        Config::set('services.mastersindia.client_id', 'test_client_id');
        Config::set('services.mastersindia.client_secret', 'test_secret');
        Config::set('services.mastersindia.username', 'test_user');
        Config::set('services.mastersindia.password', 'test_pass');
        Config::set('services.mastersindia.gstin', '27AAACA1234A1Z5');

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_token_abc_123',
                'token_type' => 'Bearer',
            ], 200),
            '*/ewb/api/ewaybill' => Http::response([
                'status_cd' => '0',
                'status_desc' => 'Vehicle Number format is invalid according to VAHAN database registration.',
                'error' => [
                    'message' => 'Vehicle Number format is invalid according to VAHAN database registration.',
                    'error_cd' => '402',
                ],
            ], 200),
        ]);

        $invoice = $this->createValidInvoice();

        $response = $this->actingAs($this->adminUser)->post(route('invoices.eway.generate', $invoice), [
            'transport_mode' => '1',
            'vehicle_no' => 'MH12AB1234',
            'distance_km' => 75,
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('error');
        $this->assertMatchesRegularExpression('/Government Portal Rejection/i', session('error'));
        $this->assertMatchesRegularExpression('/VAHAN database registration/i', session('error'));

        $invoice->refresh();
        $this->assertEquals('failed', $invoice->eway_bill_status);
        $this->assertMatchesRegularExpression('/VAHAN database registration/i', $invoice->eWayBill->last_error);

        // Verify the error is shown on the invoice details page
        $showRes = $this->actingAs($this->adminUser)->get(route('invoices.show', $invoice));
        $showRes->assertOk();
        $showRes->assertSee('E-Way Bill Generation Failed');
        $this->assertMatchesRegularExpression('/VAHAN database registration/i', $showRes->getContent());
    }

    public function test_successful_eway_bill_generation_saves_details_and_renders_document(): void
    {
        Config::set('services.mastersindia.fallback_to_stub', false);
        Config::set('services.mastersindia.client_id', 'test_client_id');
        Config::set('services.mastersindia.client_secret', 'test_secret');
        Config::set('services.mastersindia.username', 'test_user');
        Config::set('services.mastersindia.password', 'test_pass');
        Config::set('services.mastersindia.gstin', '27AAACA1234A1Z5');

        $generatedEwbNo = '271098765432';
        $validUntil = Carbon::now()->addDays(2)->format('d/m/Y 23:59:59');

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_token_abc_123',
                'token_type' => 'Bearer',
            ], 200),
            '*/ewb/api/ewaybill' => Http::response([
                'status_cd' => '1',
                'data' => [
                    'EwbNo' => $generatedEwbNo,
                    'EwbDt' => Carbon::now()->format('d/m/Y H:i:s'),
                    'EwbValidTill' => $validUntil,
                ],
            ], 200),
        ]);

        $invoice = $this->createValidInvoice();

        $response = $this->actingAs($this->adminUser)->post(route('invoices.eway.generate', $invoice), [
            'transport_mode' => '1',
            'vehicle_no' => 'MH12AB1234',
            'vehicle_type' => 'R',
            'distance_km' => 120,
            'transporter_name' => 'Blue Dart Express',
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('success');
        $this->assertStringContainsString($generatedEwbNo, session('success'));

        $invoice->refresh();
        $this->assertEquals('generated', $invoice->eway_bill_status);
        $this->assertEquals($generatedEwbNo, $invoice->eway_bill_no);

        $ewayBill = EWayBill::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertEquals('generated', $ewayBill->status);
        $this->assertEquals($generatedEwbNo, $ewayBill->eway_bill_no);
        $this->assertEquals('MH12AB1234', $ewayBill->vehicle_no);
        $this->assertEquals(120, $ewayBill->distance_km);
        $this->assertNull($ewayBill->last_error);

        // Verify details are rendered on Invoice View
        $showRes = $this->actingAs($this->adminUser)->get(route('invoices.show', $invoice));
        $showRes->assertOk();
        $showRes->assertSee($generatedEwbNo);
        $showRes->assertSee('View / Print E-Way Bill');
        $showRes->assertSee('E-Way Bill Doc');

        // Verify details are rendered on Preview
        $previewRes = $this->actingAs($this->adminUser)->get(route('invoices.preview', $invoice));
        $previewRes->assertOk();
        $previewRes->assertSee($generatedEwbNo);

        // Verify official E-Way Bill document loads successfully
        $docRes = $this->actingAs($this->adminUser)->get(route('invoices.eway.document', $invoice));
        $docRes->assertOk();
        $docRes->assertSee('E-WAY BILL');
        $docRes->assertSee($generatedEwbNo);
        $docRes->assertSee('PART - A');
        $docRes->assertSee('PART - B');
    }

    public function test_prevent_duplicate_generation_when_valid_eway_bill_already_exists(): void
    {
        $invoice = $this->createValidInvoice();
        $invoice->update([
            'eway_bill_no' => '271098765432',
            'eway_bill_status' => 'generated',
        ]);
        EWayBill::create([
            'invoice_id' => $invoice->id,
            'eway_bill_no' => '271098765432',
            'status' => 'generated',
            'provider' => 'mastersindia',
            'ewb_date' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('invoices.eway.generate', $invoice), [
            'transport_mode' => '1',
            'vehicle_no' => 'MH12AB1234',
            'distance_km' => 80,
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('error');
        $this->assertMatchesRegularExpression('/Duplicate generation is not allowed/i', session('error'));
        $this->assertStringContainsString('271098765432', session('error'));
    }

    public function test_invoice_details_page_shows_generate_button_when_not_yet_generated(): void
    {
        $invoice = $this->createValidInvoice();

        $showRes = $this->actingAs($this->adminUser)->get(route('invoices.show', $invoice));
        $showRes->assertOk();
        $showRes->assertSee('Generate E-Way Bill');
        // Does not show "E-Way Bill Has Not Been Generated For This Invoice."
        $showRes->assertDontSee('E-Way Bill Has Not Been Generated For This Invoice.');

        // Attempting to visit eway document route when not generated redirects with helpful guidance
        $docRes = $this->actingAs($this->adminUser)->get(route('invoices.eway.document', $invoice));
        $docRes->assertRedirect(route('invoices.show', $invoice));
        $docRes->assertSessionHas('error');
        $this->assertStringContainsString('Please generate it using the "Generate E-Way Bill" button', session('error'));
    }
}
