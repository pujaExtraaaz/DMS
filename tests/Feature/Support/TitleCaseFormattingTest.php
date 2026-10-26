<?php

namespace Tests\Feature\Support;

use App\Http\Middleware\UppercaseNameInputs;
use App\Support\NameCase;
use App\Support\TitleCase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TitleCaseFormattingTest extends TestCase
{
    /**
     * Test exact examples specified in requirements.
     */
    public function test_user_specified_title_case_examples(): void
    {
        $this->assertSame('Sakshi Chavan', TitleCase::format('sakshi chavan'));
        $this->assertSame('Punjab National Bank', TitleCase::format('punjab national bank'));
        $this->assertSame('Private Limited Company', TitleCase::format('private limited company'));
        $this->assertSame('Trojan Distributors', TitleCase::format('trojan distributors'));
        $this->assertSame('Head Office / Billing', TitleCase::format('head office / billing'));

        // Additional casing tests
        $this->assertSame('Sony Professional Display', TitleCase::format('sony professional display'));
        $this->assertSame('Sony Professional Display', TitleCase::format('SONY PROFESSIONAL DISPLAY'));
        $this->assertSame('Apple Iphone 18 Pro', TitleCase::format('apple iphone 18 pro'));
        $this->assertSame('Bulk Foods Depot', TitleCase::format('bulk foods depot'));
        $this->assertSame('Finished Goods', TitleCase::format('finished goods'));
        $this->assertSame('North Zone', TitleCase::format('north zone'));
    }

    /**
     * Test common brand names, hardware terms, and acronym whitelist.
     */
    public function test_acronym_and_technical_casing_preservation(): void
    {
        // Technology acronyms
        $this->assertSame('4K OLED TV With HDMI And USB Ports', TitleCase::format('4k oled tv with hdmi and usb ports'));
        $this->assertSame('Industrial PLC And VFD Panel', TitleCase::format('industrial plc and vfd panel'));
        $this->assertSame('Intel CPU With 16GB DDR5 RAM And 1TB NVMe SSD', TitleCase::format('intel cpu with 16gb ddr5 ram and 1tb nvme ssd'));
        $this->assertSame('Wi-Fi 6 Router', TitleCase::format('wi-fi 6 router'));
        $this->assertSame('PVC Conduit Pipe', TitleCase::format('pvc conduit pipe'));
        $this->assertSame('Double Pole MCB Switch', TitleCase::format('double pole mcb switch'));
    }

    /**
     * Test preservation of alphanumeric part codes and model numbers.
     */
    public function test_alphanumeric_code_preservation(): void
    {
        // Alphanumeric models like FW-43EZ20L, INV-001
        $this->assertSame('Sony FW-43EZ20L Monitor', TitleCase::format('sony FW-43EZ20L monitor'));
        $this->assertSame('Invoice INV-001 Series', TitleCase::format('invoice INV-001 series'));
    }

    /**
     * Test apostrophe handling.
     */
    public function test_smart_apostrophe_formatting(): void
    {
        $this->assertSame("Supplier's Catalog", TitleCase::format("supplier's catalog"));
        $this->assertSame("Buyer's Order", TitleCase::format("buyer's order"));
        $this->assertSame("O'Connor & Sons", TitleCase::format("o'connor & sons"));
    }

    /**
     * Test raw URLs and email addresses are untouched.
     */
    public function test_urls_and_emails_are_not_modified(): void
    {
        $this->assertSame('https://example.com/api/v1/resource', TitleCase::format('https://example.com/api/v1/resource'));
        $this->assertSame('contact@mycompany.org', TitleCase::format('contact@mycompany.org'));
    }

    /**
     * Test NameCase field identification and exclusions.
     */
    public function test_name_case_field_filtering(): void
    {
        // Human-readable text and name fields
        $this->assertTrue(NameCase::isApplicableField('name'));
        $this->assertTrue(NameCase::isApplicableField('legal_name'));
        $this->assertTrue(NameCase::isApplicableField('customer_name'));
        $this->assertTrue(NameCase::isApplicableField('supplier_name'));
        $this->assertTrue(NameCase::isApplicableField('party_name'));
        $this->assertTrue(NameCase::isApplicableField('bank_name'));
        $this->assertTrue(NameCase::isApplicableField('branch_name'));
        $this->assertTrue(NameCase::isApplicableField('account_holder_name'));
        $this->assertTrue(NameCase::isApplicableField('company_type'));
        $this->assertTrue(NameCase::isApplicableField('product_type'));
        $this->assertTrue(NameCase::isApplicableField('address_line_1'));
        $this->assertTrue(NameCase::isApplicableField('address_line_2'));
        $this->assertTrue(NameCase::isApplicableField('city'));
        $this->assertTrue(NameCase::isApplicableField('state'));
        $this->assertTrue(NameCase::isApplicableField('country'));
        $this->assertTrue(NameCase::isApplicableField('headquarters'));
        $this->assertTrue(NameCase::isApplicableField('industry'));
        $this->assertTrue(NameCase::isApplicableField('address_label'));
        $this->assertTrue(NameCase::isApplicableField('title'));
        $this->assertTrue(NameCase::isApplicableField('items[0][name]'));
        $this->assertTrue(NameCase::isApplicableField('addresses[0][address_line_1]'));
        $this->assertTrue(NameCase::isApplicableField('bank_accounts[0][bank_name]'));

        // Sentences and paragraphs exclusions
        $this->assertFalse(NameCase::isApplicableField('description'));
        $this->assertFalse(NameCase::isApplicableField('notes'));
        $this->assertFalse(NameCase::isApplicableField('remarks'));
        $this->assertFalse(NameCase::isApplicableField('narration'));
        $this->assertFalse(NameCase::isApplicableField('terms_and_conditions'));

        // Backward compatibility
        $this->assertTrue(NameCase::isNameField('name'));
        $this->assertTrue(NameCase::isNameField('bank_name'));
        $this->assertTrue(NameCase::isNameField('address_line_1'));

        // Statutory & technical non-name exclusions
        $this->assertFalse(NameCase::isApplicableField('email'));
        $this->assertFalse(NameCase::isApplicableField('secondary_email'));
        $this->assertFalse(NameCase::isApplicableField('contact_email'));
        $this->assertFalse(NameCase::isApplicableField('password'));
        $this->assertFalse(NameCase::isApplicableField('password_confirmation'));
        $this->assertFalse(NameCase::isApplicableField('gstin'));
        $this->assertFalse(NameCase::isApplicableField('supplier_gstin'));
        $this->assertFalse(NameCase::isApplicableField('pan'));
        $this->assertFalse(NameCase::isApplicableField('cin'));
        $this->assertFalse(NameCase::isApplicableField('tan'));
        $this->assertFalse(NameCase::isApplicableField('ifsc'));
        $this->assertFalse(NameCase::isApplicableField('bank_ifsc'));
        $this->assertFalse(NameCase::isApplicableField('ifsc_code'));
        $this->assertFalse(NameCase::isApplicableField('sku'));
        $this->assertFalse(NameCase::isApplicableField('part_number'));
        $this->assertFalse(NameCase::isApplicableField('hsn_code'));
        $this->assertFalse(NameCase::isApplicableField('code'));
        $this->assertFalse(NameCase::isApplicableField('asset_code'));
        $this->assertFalse(NameCase::isApplicableField('account_number'));
        $this->assertFalse(NameCase::isApplicableField('account_no'));
        $this->assertFalse(NameCase::isApplicableField('bank_account_no'));
        $this->assertFalse(NameCase::isApplicableField('invoice_no'));
        $this->assertFalse(NameCase::isApplicableField('order_no'));
        $this->assertFalse(NameCase::isApplicableField('serial_no'));
        $this->assertFalse(NameCase::isApplicableField('serial_number'));
        $this->assertFalse(NameCase::isApplicableField('batch_no'));
        $this->assertFalse(NameCase::isApplicableField('pincode'));
        $this->assertFalse(NameCase::isApplicableField('pin_code'));
        $this->assertFalse(NameCase::isApplicableField('phone'));
        $this->assertFalse(NameCase::isApplicableField('mobile'));
        $this->assertFalse(NameCase::isApplicableField('url'));
        $this->assertFalse(NameCase::isApplicableField('website'));
        $this->assertFalse(NameCase::isApplicableField('api_key'));
        $this->assertFalse(NameCase::isApplicableField('token'));
        $this->assertFalse(NameCase::isApplicableField('_token'));
        $this->assertFalse(NameCase::isApplicableField('slug'));
        $this->assertFalse(NameCase::isApplicableField('search'));
        $this->assertFalse(NameCase::isApplicableField('q'));
        $this->assertFalse(NameCase::isApplicableField('customer_id'));
        $this->assertFalse(NameCase::isApplicableField('amount'));
        $this->assertFalse(NameCase::isApplicableField('total_amount'));
        $this->assertFalse(NameCase::isApplicableField('unit_price'));
        $this->assertFalse(NameCase::isApplicableField('rate'));
        $this->assertFalse(NameCase::isApplicableField('qty'));
        $this->assertFalse(NameCase::isApplicableField('order_date'));
        $this->assertFalse(NameCase::isApplicableField('created_at'));
        $this->assertFalse(NameCase::isApplicableField('credit_status'));
        $this->assertFalse(NameCase::isApplicableField('credit_period_basis'));
        $this->assertFalse(NameCase::isApplicableField('dimensions_not_applicable'));
        $this->assertTrue(NameCase::isApplicableField('compliance_status'));
    }

    /**
     * Test HTTP Middleware normalizes all user examples and preserves exclusions.
     */
    public function test_middleware_normalizes_request_inputs_with_user_examples(): void
    {
        $middleware = new UppercaseNameInputs();

        $request = Request::create('/test', 'POST', [
            'name' => 'sakshi chavan',
            'bank_name' => 'punjab national bank',
            'company_type' => 'private limited company',
            'party_name' => 'trojan distributors',
            'label' => 'head office / billing',
            'email' => 'sakshi@example.com',
            'password' => 'secret123',
            'gstin' => '27aapfu0939f1zv',
            'pan' => 'aapfu0939f',
            'ifsc_code' => 'punb0012300',
            'account_number' => '987654321012',
            'pincode' => '400001',
            'addresses' => [
                [
                    'address_line_1' => '2nd floor, mg road',
                    'city' => 'mumbai',
                    'state' => 'maharashtra',
                    'pincode' => '400001',
                ]
            ],
            'bank_accounts' => [
                [
                    'bank_name' => 'state bank of india',
                    'branch_name' => 'nariman point',
                    'account_holder_name' => 'sakshi chavan',
                    'ifsc_code' => 'sbin0000123',
                ]
            ],
        ]);

        $middleware->handle($request, function ($req) {
            // User examples formatted to Title Case
            $this->assertSame('Sakshi Chavan', $req->input('name'));
            $this->assertSame('Punjab National Bank', $req->input('bank_name'));
            $this->assertSame('Private Limited Company', $req->input('company_type'));
            $this->assertSame('Trojan Distributors', $req->input('party_name'));
            $this->assertSame('Head Office / Billing', $req->input('label'));

            // Nested repeater fields formatted
            $this->assertSame('2nd Floor, Mg Road', $req->input('addresses.0.address_line_1'));
            $this->assertSame('Mumbai', $req->input('addresses.0.city'));
            $this->assertSame('Maharashtra', $req->input('addresses.0.state'));
            $this->assertSame('State Bank Of India', $req->input('bank_accounts.0.bank_name'));
            $this->assertSame('Nariman Point', $req->input('bank_accounts.0.branch_name'));
            $this->assertSame('Sakshi Chavan', $req->input('bank_accounts.0.account_holder_name'));

            // Strictly excluded fields untouched
            $this->assertSame('sakshi@example.com', $req->input('email'));
            $this->assertSame('secret123', $req->input('password'));
            $this->assertSame('27aapfu0939f1zv', $req->input('gstin'));
            $this->assertSame('aapfu0939f', $req->input('pan'));
            $this->assertSame('punb0012300', $req->input('ifsc_code'));
            $this->assertSame('987654321012', $req->input('account_number'));
            $this->assertSame('400001', $req->input('pincode'));
            $this->assertSame('400001', $req->input('addresses.0.pincode'));
            $this->assertSame('sbin0000123', $req->input('bank_accounts.0.ifsc_code'));

            return response('OK');
        });
    }

    /**
     * Test TitleCaseNamesCommand audit run.
     */
    public function test_title_case_audit_command_runs_successfully(): void
    {
        $this->artisan('dms:title-case-names', ['--audit' => true])
            ->assertExitCode(0);
    }
}
