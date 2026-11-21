<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxInvoiceSinglePreviewRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customerA;
    protected Customer $customerB;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
            'masters.view', 'masters.create', 'masters.edit', 'customers.view',
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
            'upi_id' => 'apex@icici',
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

        $this->customerA = Customer::create([
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

        $this->customerB = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'BlueStar Technologies',
            'code' => 'CUST-BLUESTAR',
            'gstin' => '27AABCB1234J1Z8',
            'state' => 'Maharashtra',
            'city' => 'Nagpur',
            'pincode' => '440001',
            'address' => 'Civil Lines, Nagpur',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'code' => 'PCS',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Enterprise Switch 24-Port',
            'code' => 'SW-24P-ENT',
            'sku' => 'SW-24P-ENT',
            'serial_no' => 'SW-24P-ENT',
            'type' => 'goods',
            'base_uom_id' => $this->uom->id,
            'uom_id' => $this->uom->id,
            'hsn_sac' => '85176290',
            'hsn_code' => '85176290',
            'tax_rate' => 18.00,
            'purchase_price' => 12000.00,
            'selling_price' => 18000.00,
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'preview-tester@apex.com',
        ]);
        $this->user->assignRole('super-admin');
    }

    protected function createInvoice(Customer $customer, string $invNo, float $qty = 2): Invoice
    {
        $unitPrice = 18000.00;
        $taxable = $qty * $unitPrice;
        $cgst = round($taxable * 0.09, 2);
        $sgst = round($taxable * 0.09, 2);
        $grandTotal = $taxable + $cgst + $sgst;

        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_no' => $invNo,
            'invoice_date' => Carbon::now()->toDateString(),
            'tax_type' => 'intra',
            'subtotal' => $taxable,
            'taxable_amount' => $taxable,
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'igst_amount' => 0.00,
            'tax_amount' => $cgst + $sgst,
            'discount_amount' => 0.00,
            'round_off' => 0.00,
            'grand_total' => $grandTotal,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'shipping_address' => $customer->address,
            'billing_address' => $customer->address,
            'qr_token' => bin2hex(random_bytes(16)),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'entered_unit_price' => $unitPrice,
            'selling_price' => $unitPrice,
            'taxable_amount' => $taxable,
            'hsn_code' => '85176290',
            'tax_rate' => 18.00,
            'gst_rate' => 18.00,
            'cgst_rate' => 9.00,
            'cgst_amount' => $cgst,
            'sgst_rate' => 9.00,
            'sgst_amount' => $sgst,
            'igst_rate' => 0.00,
            'igst_amount' => 0.00,
            'discount_amount' => 0.00,
            'tax_amount' => $cgst + $sgst,
            'line_total' => $grandTotal,
            'total_amount' => $grandTotal,
        ]);

        return $invoice;
    }

    public function test_default_preview_renders_exactly_one_invoice_copy_and_not_three(): void
    {
        $invoice = $this->createInvoice($this->customerA, 'INV-2026-0001');

        $response = $this->actingAs($this->user)
            ->get(route('invoices.preview', $invoice));

        $response->assertOk();
        $content = $response->getContent();

        // 1. Verify exactly one invoice sheet is rendered on the preview screen
        $this->assertEquals(1, substr_count($content, 'invoice-copy-page'), 'Preview screen should render exactly one invoice sheet by default.');
        $this->assertEquals(1, substr_count($content, '<span class="badge-recipient">'), 'Preview screen should show only one copy badge.');

        // 2. Default copy is "Original for Recipient"
        $this->assertStringContainsString('<span class="badge-recipient">Original for Recipient</span>', $content);
        $this->assertStringNotContainsString('<span class="badge-recipient">Duplicate for Transporter</span>', $content);
        $this->assertStringNotContainsString('<span class="badge-recipient">Triplicate for Supplier</span>', $content);

        // 3. Invoice details are rendered once inside invoice body
        $this->assertEquals(1, substr_count($content, '<td class="text-right font-mono" style="width: 50%; font-weight: 700;">INV-2026-0001</td>'));
        $this->assertStringContainsStringIgnoringCase('Zenith Enterprise', $content);
        $this->assertStringContainsString('Enterprise Switch 24-Port', $content);

        // 4. Selector has "Original for Recipient" selected and copies input is 1
        $this->assertStringContainsString('<option value="original" selected', $content);
        $this->assertStringContainsString('value="1"', $content);
    }

    public function test_preview_another_invoice_renders_only_that_selected_invoice_once(): void
    {
        $invoiceA = $this->createInvoice($this->customerA, 'INV-2026-AAAA');
        $invoiceB = $this->createInvoice($this->customerB, 'INV-2026-BBBB');

        // Preview Invoice B
        $response = $this->actingAs($this->user)
            ->get(route('invoices.preview', $invoiceB));

        $response->assertOk();
        $content = $response->getContent();

        // Exactly one copy
        $this->assertEquals(1, substr_count($content, 'invoice-copy-page'));

        // Shows Invoice B and Customer B
        $this->assertStringContainsString('INV-2026-BBBB', $content);
        $this->assertStringContainsString('BlueStar Technologies', $content);

        // Does NOT show Invoice A or Customer A
        $this->assertStringNotContainsString('INV-2026-AAAA', $content);
        $this->assertStringNotContainsString('Zenith Enterprise LLP', $content);
    }

    public function test_preview_preserves_invoice_totals_tax_breakdown_and_qr_code(): void
    {
        $invoice = $this->createInvoice($this->customerA, 'INV-2026-CALC', 2);

        $response = $this->actingAs($this->user)
            ->get(route('invoices.preview', $invoice));

        $response->assertOk();
        $content = $response->getContent();

        // 2 * 18000 = 36000 taxable, CGST 3240, SGST 3240, Grand Total 42480
        $this->assertStringContainsString('36,000.00', $content);
        $this->assertStringContainsString('3,240.00', $content);
        $this->assertStringContainsString('42,480.00', $content);

        // Scannable invoice QR is rendered
        $this->assertStringContainsString('data:image/png;base64', $content);
    }

    public function test_preview_still_supports_all_copies_when_explicitly_requested(): void
    {
        $invoice = $this->createInvoice($this->customerA, 'INV-2026-ALL');

        $response = $this->actingAs($this->user)
            ->get(route('invoices.preview', ['invoice' => $invoice, 'copy_type' => 'all', 'copies' => 3]));

        $response->assertOk();
        $content = $response->getContent();

        // Explicit all copies request still renders 3 pages
        $this->assertEquals(3, substr_count($content, 'invoice-copy-page'));
        $this->assertStringContainsString('Original for Recipient', $content);
        $this->assertStringContainsString('Duplicate for Transporter', $content);
        $this->assertStringContainsString('Triplicate for Supplier', $content);
    }

    public function test_preview_supports_specific_statutory_copies(): void
    {
        $invoice = $this->createInvoice($this->customerA, 'INV-2026-SPEC');

        // 1. Duplicate
        $resDuplicate = $this->actingAs($this->user)
            ->get(route('invoices.preview', ['invoice' => $invoice, 'copy_type' => 'duplicate', 'copies' => 1]));
        $resDuplicate->assertOk();
        $this->assertEquals(1, substr_count($resDuplicate->getContent(), 'invoice-copy-page'));
        $this->assertStringContainsString('<span class="badge-recipient">Duplicate for Transporter</span>', $resDuplicate->getContent());
        $this->assertStringNotContainsString('<span class="badge-recipient">Triplicate for Supplier</span>', $resDuplicate->getContent());
        $this->assertStringNotContainsString('<span class="badge-recipient">Original for Recipient</span>', $resDuplicate->getContent());

        // 2. Triplicate
        $resTriplicate = $this->actingAs($this->user)
            ->get(route('invoices.preview', ['invoice' => $invoice, 'copy_type' => 'triplicate', 'copies' => 1]));
        $resTriplicate->assertOk();
        $this->assertEquals(1, substr_count($resTriplicate->getContent(), 'invoice-copy-page'));
        $this->assertStringContainsString('<span class="badge-recipient">Triplicate for Supplier</span>', $resTriplicate->getContent());
        $this->assertStringNotContainsString('<span class="badge-recipient">Original for Recipient</span>', $resTriplicate->getContent());
        $this->assertStringNotContainsString('<span class="badge-recipient">Duplicate for Transporter</span>', $resTriplicate->getContent());
    }

    public function test_default_pdf_export_downloads_single_copy(): void
    {
        $invoice = $this->createInvoice($this->customerA, 'INV-2026-PDF');

        $response = $this->actingAs($this->user)
            ->get(route('invoices.export', ['invoice' => $invoice, 'format' => 'pdf']));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_invoice_show_page_renders_cleanly_without_undefined_variable_errors(): void
    {
        $invoice = $this->createInvoice($this->customerA, 'INV-2026-SHOW');

        $response = $this->actingAs($this->user)
            ->get(route('invoices.show', $invoice));

        $response->assertOk();
        $response->assertSee($invoice->invoice_no);
    }
}
