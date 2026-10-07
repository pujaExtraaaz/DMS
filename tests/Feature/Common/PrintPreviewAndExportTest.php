<?php

namespace Tests\Feature\Common;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Models\User;
use App\Support\DocumentExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrintPreviewAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $supplier;
    protected Customer $customer;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Apex Global Solutions Ltd',
            'legal_name' => 'Apex Global Solutions Private Limited',
            'code' => 'AGS-01',
            'gstin' => '27AABCA1234F1Z8',
            'pan' => 'AABCA1234F',
            'cin' => 'U12345MH2020PTC123456',
            'address' => 'Plot 42, Tech Park, Andheri East',
            'state' => 'Maharashtra',
            'pincode' => '400069',
            'phone' => '+91 9876543210',
            'email' => 'finance@apexsolutions.test',
            'website' => 'https://apexsolutions.test',
            'bank_name' => 'HDFC Bank',
            'bank_account_no' => '50200012345678',
            'bank_ifsc' => 'HDFC0000123',
            'upi_id' => 'apex@okhdfcbank',
            'purchase_terms_and_conditions' => 'Standard PO payment within 30 days.',
            'selling_terms_and_conditions' => 'Standard sales invoice terms. Goods once sold will not be taken back.',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Admin Manager',
            'email' => 'admin@apexsolutions.test',
        ]);
        $this->user->assignRole($role);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(3)->toDateString(),
            'ends_on' => now()->addMonths(9)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $custType = CustomerType::create([
            'name' => 'Dealer',
            'code' => 'DLR',
            'is_active' => true,
        ]);

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $custType->id,
            'name' => 'Bharat Steel & Supply Co',
            'code' => 'SUP-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'gstin' => '27XYZPA9876Q1Z5',
            'pan' => 'XYZPA9876Q',
            'phone' => '9820011223',
            'email' => 'sales@bharatsteel.test',
            'address' => '101 Industrial Estate, Pune, Maharashtra 411018',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $custType->id,
            'name' => 'Om Retail Enterprises',
            'code' => 'CUST-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'gstin' => '27ABCDE5678F1Z2',
            'pan' => 'ABCDE5678F',
            'phone' => '9870099887',
            'email' => 'omretail@test.com',
            'address' => '204 Commercial Hub, Dadar, Mumbai, Maharashtra 400014',
            'is_active' => true,
        ]);

        $branch = \App\Domains\Organization\Models\Branch::create([
            'name' => 'Main Branch',
            'code' => 'MB01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Main Bhiwandi Warehouse',
            'code' => 'WH-BHW',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Heavy Duty Industrial Bolt M12',
            'sku' => 'SKU-M12',
            'serial_no' => 'SN-M12-001',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 150.00,
            'selling_price' => 200.00,
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);
    }

    public function test_number_to_indian_words_converter(): void
    {
        $this->assertEquals('Rupees Zero Only', DocumentExporter::numberToIndianWords(0));
        $this->assertEquals('Rupees Forty Five and Fifty Paise Only', DocumentExporter::numberToIndianWords(45.50));
        $this->assertEquals(
            'Rupees One Lakh Twenty Three Thousand Four Hundred Fifty Six and Seventy Five Paise Only',
            DocumentExporter::numberToIndianWords(123456.75)
        );
        $this->assertEquals(
            'Rupees One Crore Two Lakh Three Thousand Four Hundred Five Only',
            DocumentExporter::numberToIndianWords(10203405.00)
        );
    }

    public function test_purchase_order_print_preview_screen(): void
    {
        $order = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-2026-001',
            'po_date' => now()->toDateString(),
            'expected_date' => now()->addDays(10)->toDateString(),
            'status' => 'approved',
            'subtotal' => 15000.00,
            'tax_amount' => 2700.00,
            'grand_total' => 17700.00,
            'notes' => 'Urgent site delivery required.',
            'created_by' => $this->user->id,
            'billing_address' => 'Billing Office, Pune',
            'shipping_address' => 'Delivery Warehouse, Bhiwandi',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $order->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 100,
            'unit_cost' => 150.00,
            'batch_name' => 'BATCH-ALPHA',
            'batch_no' => 'BATCH-ALPHA',
            'tax_percent' => 18,
            'cgst_percent' => 9,
            'sgst_percent' => 9,
            'cgst_amount' => 1350,
            'sgst_amount' => 1350,
            'line_total' => 17700.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.orders.preview', $order));

        $response->assertOk();
        $response->assertSee('PO-2026-001');
        $response->assertSee('Apex Global Solutions Ltd');
        $response->assertSee('Bharat Steel & Supply Co');
        $response->assertSee('Heavy Duty Industrial Bolt M12');
        $response->assertSee('BATCH-ALPHA');
        $response->assertSee('17,700.00');
        $response->assertSee('Rupees Seventeen Thousand Seven Hundred Only');
        $response->assertSee('Print');
        $response->assertSee('PDF');
        $response->assertSee('Excel (.xlsx)');
        $response->assertSee('CSV');
    }

    public function test_purchase_order_exports_pdf_csv_and_excel(): void
    {
        $order = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-EXP-001',
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 1500.00,
            'tax_amount' => 270.00,
            'grand_total' => 1770.00,
            'created_by' => $this->user->id,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $order->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 10,
            'unit_cost' => 150.00,
            'batch_no' => 'B-001',
            'tax_percent' => 18,
            'line_total' => 1770.00,
        ]);

        // PDF Export
        $pdfRes = $this->actingAs($this->user)->get(route('purchasing.orders.export', ['order' => $order, 'format' => 'pdf']));
        $pdfRes->assertOk();
        $this->assertStringContainsString('application/pdf', $pdfRes->headers->get('content-type'));

        // CSV Export
        $csvRes = $this->actingAs($this->user)->get(route('purchasing.orders.export', ['order' => $order, 'format' => 'csv']));
        $csvRes->assertOk();
        $this->assertStringContainsString('text/csv', $csvRes->headers->get('content-type'));
        $csvContent = $csvRes->streamedContent();
        $this->assertStringContainsString('PO-EXP-001', $csvContent);
        $this->assertStringContainsString('Heavy Duty Industrial Bolt M12', $csvContent);

        // Excel Export
        $xlsxRes = $this->actingAs($this->user)->get(route('purchasing.orders.export', ['order' => $order, 'format' => 'xlsx']));
        $xlsxRes->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $xlsxRes->headers->get('content-type'));
        $this->assertGreaterThan(1000, strlen($xlsxRes->streamedContent()));
    }

    public function test_purchase_invoice_print_preview_and_exports(): void
    {
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-2026-009',
            'supplier_invoice_no' => 'BSS-INV-88',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 4500.00,
            'tax_amount' => 810.00,
            'grand_total' => 5310.00,
            'created_by' => $this->user->id,
            'qr_token' => 'token-pi-preview-test',
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 30,
            'unit_cost' => 150.00,
            'batch_no' => 'PI-BATCH-1',
            'tax_percent' => 18,
            'cgst_percent' => 9,
            'sgst_percent' => 9,
            'cgst_amount' => 405,
            'sgst_amount' => 405,
            'line_total' => 5310.00,
        ]);

        // Preview
        $previewRes = $this->actingAs($this->user)->get(route('purchasing.invoices.preview', $invoice));
        $previewRes->assertOk();
        $previewRes->assertSee('PI-2026-009');
        $previewRes->assertSee('Bharat Steel & Supply Co');
        $previewRes->assertSee('Rupees Five Thousand Three Hundred Ten Only');
        $previewRes->assertSee('Excel (.xlsx)');

        // CSV Export
        $csvRes = $this->actingAs($this->user)->get(route('purchasing.invoices.export', ['invoice' => $invoice, 'format' => 'csv']));
        $csvRes->assertOk();
        $this->assertStringContainsString('PI-2026-009', $csvRes->streamedContent());

        // Excel Export
        $xlsxRes = $this->actingAs($this->user)->get(route('purchasing.invoices.export', ['invoice' => $invoice, 'format' => 'xlsx']));
        $xlsxRes->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $xlsxRes->headers->get('content-type'));

        // PDF Export
        $pdfRes = $this->actingAs($this->user)->get(route('purchasing.invoices.export', ['invoice' => $invoice, 'format' => 'pdf']));
        $pdfRes->assertOk();
        $this->assertStringContainsString('application/pdf', $pdfRes->headers->get('content-type'));
    }

    public function test_sales_invoice_print_preview_and_exports(): void
    {
        $invoice = Invoice::create([
            'invoice_no' => 'INV-2026-055',
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'status' => 'approved',
            'subtotal' => 10000.00,
            'tax_amount' => 1800.00,
            'discount_amount' => 500.00,
            'grand_total' => 11300.00,
            'paid_amount' => 3000.00,
            'notes' => 'Thank you for your business.',
            'qr_token' => 'token-sales-inv-preview',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 50,
            'unit_price' => 200.00,
            'hsn_code' => '73181500',
            'batch_no' => 'SL-B-1',
            'discount_amount' => 500.00,
            'tax_amount' => 1800.00,
            'line_total' => 11300.00,
        ]);

        // Preview
        $previewRes = $this->actingAs($this->user)->get(route('invoices.preview', $invoice));
        $previewRes->assertOk();
        $previewRes->assertSee('INV-2026-055');
        $previewRes->assertSee('Om Retail Enterprises');
        $previewRes->assertSee('Apex Global Solutions Ltd');
        $previewRes->assertSee('Rupees Eleven Thousand Three Hundred Only');
        $previewRes->assertSee('Print');
        $previewRes->assertSee('PDF');
        $previewRes->assertSee('Excel (.xlsx)');
        $previewRes->assertSee('CSV');

        // CSV Export
        $csvRes = $this->actingAs($this->user)->get(route('invoices.export', ['invoice' => $invoice, 'format' => 'csv']));
        $csvRes->assertOk();
        $this->assertStringContainsString('INV-2026-055', $csvRes->streamedContent());

        // Excel Export
        $xlsxRes = $this->actingAs($this->user)->get(route('invoices.export', ['invoice' => $invoice, 'format' => 'xlsx']));
        $xlsxRes->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $xlsxRes->headers->get('content-type'));

        // PDF Export
        $pdfRes = $this->actingAs($this->user)->get(route('invoices.export', ['invoice' => $invoice, 'format' => 'pdf']));
        $pdfRes->assertOk();
        $this->assertStringContainsString('application/pdf', $pdfRes->headers->get('content-type'));
    }

    public function test_listing_bulk_exports_for_purchase_orders_and_invoices(): void
    {
        // 1. Purchase Orders Listing Export
        PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-LIST-01',
            'po_date' => now()->toDateString(),
            'status' => 'approved',
            'subtotal' => 2000.00,
            'tax_amount' => 360.00,
            'grand_total' => 2360.00,
            'created_by' => $this->user->id,
        ]);

        $poCsv = $this->actingAs($this->user)->get(route('purchasing.orders.index', ['export' => 'csv']));
        $poCsv->assertOk();
        $this->assertStringContainsString('PO-LIST-01', $poCsv->streamedContent());

        $poXlsx = $this->actingAs($this->user)->get(route('purchasing.orders.index', ['export' => 'xlsx']));
        $poXlsx->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $poXlsx->headers->get('content-type'));

        // 2. Purchase Invoices Listing Export
        PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-LIST-01',
            'supplier_invoice_no' => 'SUP-77',
            'invoice_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 3000.00,
            'tax_amount' => 540.00,
            'grand_total' => 3540.00,
            'created_by' => $this->user->id,
            'qr_token' => 'tok-pi-list',
        ]);

        $piCsv = $this->actingAs($this->user)->get(route('purchasing.invoices.index', ['export' => 'csv']));
        $piCsv->assertOk();
        $this->assertStringContainsString('PI-LIST-01', $piCsv->streamedContent());

        $piXlsx = $this->actingAs($this->user)->get(route('purchasing.invoices.index', ['export' => 'xlsx']));
        $piXlsx->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $piXlsx->headers->get('content-type'));

        // 3. Sales Invoices Listing Export
        Invoice::create([
            'invoice_no' => 'SI-LIST-01',
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'status' => 'approved',
            'subtotal' => 4000.00,
            'tax_amount' => 720.00,
            'grand_total' => 4720.00,
            'paid_amount' => 0.00,
            'qr_token' => 'tok-si-list',
        ]);

        $siCsv = $this->actingAs($this->user)->get(route('invoices.index', ['export' => 'csv']));
        $siCsv->assertOk();
        $this->assertStringContainsString('SI-LIST-01', $siCsv->streamedContent());

        $siXlsx = $this->actingAs($this->user)->get(route('invoices.index', ['export' => 'xlsx']));
        $siXlsx->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $siXlsx->headers->get('content-type'));
    }
}
