<?php

namespace Tests\Feature\Support;

use App\Support\NameCase;
use App\Support\TitleCase;
use Tests\TestCase;

class TitleCaseFormattingTest extends TestCase
{
    /**
     * Test exact examples specified in requirements.
     */
    public function test_user_specified_title_case_examples(): void
    {
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
     * Test NameCase field identification.
     */
    public function test_name_case_field_filtering(): void
    {
        // Name fields
        $this->assertTrue(NameCase::isNameField('name'));
        $this->assertTrue(NameCase::isNameField('customer_name'));
        $this->assertTrue(NameCase::isNameField('supplier_name'));
        $this->assertTrue(NameCase::isNameField('product_type'));
        $this->assertTrue(NameCase::isNameField('items[0][name]'));

        // Technical non-name exclusions
        $this->assertFalse(NameCase::isNameField('sku'));
        $this->assertFalse(NameCase::isNameField('part_number'));
        $this->assertFalse(NameCase::isNameField('hsn_code'));
        $this->assertFalse(NameCase::isNameField('gstin'));
        $this->assertFalse(NameCase::isNameField('pan'));
        $this->assertFalse(NameCase::isNameField('email'));
        $this->assertFalse(NameCase::isNameField('password'));
        $this->assertFalse(NameCase::isNameField('serial_no'));
        $this->assertFalse(NameCase::isNameField('batch_no'));
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

