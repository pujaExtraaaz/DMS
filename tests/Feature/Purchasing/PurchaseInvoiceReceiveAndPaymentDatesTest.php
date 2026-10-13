<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Models\User;
use App\Support\DocumentExporter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseInvoiceReceiveAndPaymentDatesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $supplier;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Acme Trading Co',
            'code' => 'ATC-01',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $customerType = CustomerType::create([
            'name' => 'Vendor',
            'code' => 'VND',
            'is_active' => true,
        ]);

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Apex Logistics & Supplies',
            'code' => 'SUP-002',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $customerType->id,
            'credit_days' => 30,
            'phone' => '9876543210',
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name' => 'Main Branch',
            'code' => 'MB01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Hub',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Industrial Bearing B1',
            'sku' => 'BEAR-B1',
            'serial_no' => 'SN-BEAR-B1',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 200.00,
            'selling_price' => 300.00,
            'is_active' => true,
        ]);
    }

    public function test_create_invoice_page_renders_purchase_receive_date_and_payment_order_date_fields(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create'));

        $response->assertStatus(200);

        // Header fields
        $response->assertSee('Purchase Receive Date');
        $response->assertSee('Payment Order Date');
        $response->assertSee('name="purchase_receive_date"', false);
        $response->assertSee('name="payment_order_date"', false);
        $response->assertSee('x-model="purchaseReceiveDate"', false);
        $response->assertSee('x-model="paymentOrderDate"', false);

        // Existing fields remain intact
        $response->assertSee('Invoice Date *');
        $response->assertSee('Supplier Inv No.');
        $response->assertSee('Credit Days');
        $response->assertSee('Due Date Preview');
    }

    public function test_user_can_select_and_save_both_dates(): void
    {
        $invoiceDate = '2026-10-10';
        $receiveDate = '2026-10-08';
        $paymentOrderDate = '2026-10-09';

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => $invoiceDate,
            'purchase_receive_date' => $receiveDate,
            'payment_order_date' => $paymentOrderDate,
            'supplier_invoice_number' => 'INV-SUP-991',
            'credit_days' => 30,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_cost' => 200.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = PurchaseInvoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($receiveDate, $invoice->purchase_receive_date?->toDateString());
        $this->assertEquals($paymentOrderDate, $invoice->payment_order_date?->toDateString());
        $this->assertEquals($invoiceDate, $invoice->invoice_date?->toDateString());
        $this->assertEquals('INV-SUP-991', $invoice->supplier_invoice_no);
    }

    public function test_blank_dates_are_handled_correctly(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-10',
            // Omitted purchase_receive_date and payment_order_date
            'credit_days' => 15,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_cost' => 200.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = PurchaseInvoice::latest('id')->first();
        $this->assertNull($invoice->purchase_receive_date);
        $this->assertNull($invoice->payment_order_date);
    }

    public function test_dates_do_not_affect_due_date_or_credit_days(): void
    {
        $invoiceDate = '2026-10-10';
        $creditDays = 30;
        $expectedDueDate = Carbon::parse($invoiceDate)->addDays($creditDays)->toDateString(); // 2026-11-09

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => $invoiceDate,
            'purchase_receive_date' => '2026-10-01', // 9 days prior
            'payment_order_date' => '2026-10-05',
            'credit_days' => $creditDays,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 200.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = PurchaseInvoice::latest('id')->first();
        // Due Date must remain strictly based on Invoice Date + Credit Days
        $this->assertEquals($expectedDueDate, $invoice->due_date?->toDateString());
        $this->assertEquals($creditDays, $invoice->credit_days);
    }

    public function test_dates_do_not_trigger_grn_or_payment_records(): void
    {
        $initialInwardsCount = PurchaseInward::count();
        $initialPaymentsCount = Payment::count();

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-10',
            'purchase_receive_date' => '2026-10-08',
            'payment_order_date' => '2026-10-09',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                    'unit_cost' => 200.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        // Verify no GRN or Payment was created by setting these dates
        $this->assertEquals($initialInwardsCount, PurchaseInward::count());
        $this->assertEquals($initialPaymentsCount, Payment::count());
    }

    public function test_invoice_show_and_preview_display_both_dates(): void
    {
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-TEST-001',
            'supplier_invoice_no' => 'SUP-998',
            'invoice_date' => '2026-10-10',
            'purchase_receive_date' => '2026-10-08',
            'payment_order_date' => '2026-10-09',
            'due_date' => '2026-11-09',
            'credit_days' => 30,
            'status' => 'draft',
            'subtotal' => 600.00,
            'tax_amount' => 0.00,
            'grand_total' => 600.00,
        ]);

        // Show view
        $showResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $invoice));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Purchase Invoice Date');
        $showResponse->assertSee('10 Oct 2026');
        $showResponse->assertSee('Receive Date');
        $showResponse->assertSee('08 Oct 2026');
        $showResponse->assertSee('Payment Order Date');
        $showResponse->assertSee('09 Oct 2026');

        // Preview view
        $previewResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.preview', $invoice));
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('Receive Date:');
        $previewResponse->assertSee('08/10/2026');
        $previewResponse->assertSee('Payment Order Date:');
        $previewResponse->assertSee('09/10/2026');
    }

    public function test_document_exporter_includes_both_dates_in_csv_export(): void
    {
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-TEST-002',
            'invoice_date' => '2026-10-10',
            'purchase_receive_date' => '2026-10-08',
            'payment_order_date' => '2026-10-09',
            'status' => 'draft',
            'subtotal' => 400.00,
            'tax_amount' => 0.00,
            'grand_total' => 400.00,
        ]);

        $response = DocumentExporter::exportPurchaseInvoice($invoice, 'csv');
        $this->assertEquals(200, $response->getStatusCode());

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Purchase Receive Date', $content);
        $this->assertStringContainsString('08/10/2026', $content);
        $this->assertStringContainsString('Payment Order Date', $content);
        $this->assertStringContainsString('09/10/2026', $content);
    }

    public function test_prefill_receive_date_from_linked_purchase_order_with_inward(): void
    {
        $po = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-LINK-001',
            'po_date' => '2026-10-01',
            'status' => 'approved',
            'subtotal' => 1000.00,
            'tax_amount' => 0.00,
            'grand_total' => 1000.00,
        ]);

        PurchaseInward::create([
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'inward_no' => 'GRN-001',
            'inward_date' => '2026-10-07',
            'status' => 'posted',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create', ['purchase_order_id' => $po->id]));

        $response->assertStatus(200);
        // Should prefill the receive date from the latest inward record
        $response->assertSee('2026-10-07');
    }

    public function test_existing_invoices_without_dates_continue_to_display_properly(): void
    {
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-TEST-003',
            'invoice_date' => '2026-10-10',
            'status' => 'draft',
            'subtotal' => 200.00,
            'tax_amount' => 0.00,
            'grand_total' => 200.00,
        ]);

        $showResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $invoice));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Purchase Invoice Date');
        $showResponse->assertSee('10 Oct 2026');
        $showResponse->assertSee('Payment Order Date');
        $showResponse->assertSee('Receive Date');
        $showResponse->assertSee('—');

        $previewResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.preview', $invoice));
        $previewResponse->assertStatus(200);
    }

    public function test_view_invoice_displays_purchase_invoice_date_and_payment_order_date_matching_database(): void
    {
        $savedInvoiceDate = '2026-10-15';
        $savedPaymentOrderDate = '2026-10-18';

        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-VIEW-001',
            'invoice_date' => $savedInvoiceDate,
            'payment_order_date' => $savedPaymentOrderDate,
            'due_date' => '2026-11-14',
            'credit_days' => 30,
            'status' => 'posted',
            'subtotal' => 500.00,
            'tax_amount' => 0.00,
            'grand_total' => 500.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $invoice));
        $response->assertStatus(200);
        $response->assertSee('Purchase Invoice Date');
        $response->assertSee('15 Oct 2026');
        $response->assertSee('Payment Order Date');
        $response->assertSee('18 Oct 2026');
    }

    public function test_view_invoice_displays_dash_when_payment_order_date_is_null(): void
    {
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-VIEW-002',
            'invoice_date' => '2026-10-20',
            'payment_order_date' => null,
            'status' => 'draft',
            'subtotal' => 300.00,
            'tax_amount' => 0.00,
            'grand_total' => 300.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $invoice));
        $response->assertStatus(200);
        $response->assertSee('Purchase Invoice Date');
        $response->assertSee('20 Oct 2026');
        $response->assertSee('Payment Order Date');
        $response->assertSee('—');
    }
}
