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
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseInvoiceReceiptCourierFieldsTest extends TestCase
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
            'name' => 'Omni Global Trading',
            'code' => 'OGT-01',
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

        $branch = Branch::create([
            'name' => 'HQ Branch',
            'code' => 'HQ-01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Depot',
            'code' => 'CD-01',
            'is_active' => true,
        ]);

        $cType = CustomerType::create([
            'name' => 'Vendor',
            'code' => 'VND-01',
            'is_active' => true,
        ]);

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Apex Industrial Components',
            'code' => 'AIC-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $cType->id,
            'credit_days' => 30,
            'phone' => '9876543210',
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Standard Fasteners Pack',
            'sku' => 'SFP-500',
            'serial_no' => 'MST-SFP-500',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 120.00,
            'tax_rate' => 18.00,
            'tracking_type' => 'none',
            'is_active' => true,
        ]);
    }

    public function test_purchase_invoice_create_and_edit_forms_render_courier_fields_and_omit_removed_sections(): void
    {
        // 1. Create form
        $createResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.create'));
        $createResponse->assertOk();
        $createContent = $createResponse->getContent();

        // Must see new section and renamed fields
        $this->assertStringContainsString('Receipt / Courier Details', $createContent);
        $this->assertStringContainsString('Courier / Docket Number', $createContent);
        $this->assertStringContainsString('Courier Name', $createContent);
        $this->assertStringContainsString('Motor Vehicle No.', $createContent);

        // Must NOT see removed labels/sections
        $this->assertStringNotContainsString('Receipt Note No(s)', $createContent);
        $this->assertStringNotContainsString('Order Details', $createContent);
        $this->assertStringNotContainsString('Carrier Name / Agent', $createContent);
        $this->assertStringNotContainsString('Bill of Lading / LR-RR No.', $createContent);
        $this->assertStringNotContainsString('Mode / Terms of Payment', $createContent);

        // 2. Create an invoice to test Edit form
        $invoice = PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-TEST-001',
            'supplier_invoice_no' => 'INV-SUP-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'due_date_basis' => 'invoice_date',
            'due_date_source_date' => now()->toDateString(),
            'status' => 'posted',
            'material_receipt_status' => PurchaseInvoice::MATERIAL_IN_TRANSIT,
            'subtotal' => 1200.00,
            'taxable_amount' => 1200.00,
            'tax_amount' => 216.00,
            'grand_total' => 1416.00,
            'total_amount' => 1416.00,
            'receipt_doc_no' => 'DKT-987654',
            'dispatched_through' => 'Blue Dart Express',
            'vehicle_number' => 'MH-12-AB-1234',
            'created_by' => $this->user->id,
        ]);

        $editResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.edit', $invoice));
        $editResponse->assertOk();
        $editContent = $editResponse->getContent();

        $this->assertStringContainsString('Receipt / Courier Details', $editContent);
        $this->assertStringContainsString('Courier / Docket Number', $editContent);
        $this->assertStringContainsString('Courier Name', $editContent);
        $this->assertStringContainsString('Motor Vehicle No.', $editContent);
        $this->assertStringContainsString('DKT-987654', $editContent);
        $this->assertStringContainsString('Blue Dart Express', $editContent);
        $this->assertStringContainsString('MH-12-AB-1234', $editContent);

        // Must NOT see removed labels/sections in edit form
        $this->assertStringNotContainsString('Receipt Note No(s)', $editContent);
        $this->assertStringNotContainsString('Order Details', $editContent);
        $this->assertStringNotContainsString('Carrier Name / Agent', $editContent);
        $this->assertStringNotContainsString('Bill of Lading / LR-RR No.', $editContent);
        $this->assertStringNotContainsString('Mode / Terms of Payment', $editContent);
    }

    public function test_save_and_update_purchase_invoice_persists_courier_details(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'SUP-INV-COURIER-01',
            'receipt_doc_no' => 'DKT-AIR-5544',
            'dispatched_through' => 'DTDC Air Cargo',
            'vehicle_number' => 'DL-01-XY-9999',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_cost' => 120.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'SUP-INV-COURIER-01')->firstOrFail();

        // Database verification
        $this->assertSame('DKT-AIR-5544', $invoice->receipt_doc_no);
        $this->assertSame('DKT-AIR-5544', $invoice->courier_docket_no);
        $this->assertSame('Dtdc Air Cargo', $invoice->dispatched_through);
        $this->assertSame('Dtdc Air Cargo', $invoice->courier_name);
        $this->assertSame('DL-01-XY-9999', $invoice->vehicle_number);

        // Update with new values
        $updatePayload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'SUP-INV-COURIER-01',
            'receipt_doc_no' => 'DKT-EXP-7788',
            'dispatched_through' => 'FedEx Priority',
            'vehicle_number' => 'KA-05-MM-1122',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_cost' => 120.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $updateResponse = $this->actingAs($this->user)->put(route('purchasing.invoices.update', $invoice), $updatePayload);
        $updateResponse->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertSame('DKT-EXP-7788', $invoice->receipt_doc_no);
        $this->assertSame('DKT-EXP-7788', $invoice->courier_docket_no);
        $this->assertSame('FedEx Priority', $invoice->dispatched_through);
        $this->assertSame('FedEx Priority', $invoice->courier_name);
        $this->assertSame('KA-05-MM-1122', $invoice->vehicle_number);
    }

    public function test_view_details_preview_and_export_display_renamed_courier_fields(): void
    {
        $invoice = PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-COURIER-VIEW',
            'supplier_invoice_no' => 'INV-SUP-VIEW',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'due_date_basis' => 'invoice_date',
            'due_date_source_date' => now()->toDateString(),
            'status' => 'posted',
            'material_receipt_status' => PurchaseInvoice::MATERIAL_IN_TRANSIT,
            'subtotal' => 1000.00,
            'taxable_amount' => 1000.00,
            'tax_amount' => 180.00,
            'grand_total' => 1180.00,
            'total_amount' => 1180.00,
            'receipt_doc_no' => 'TRACK-9911',
            'dispatched_through' => 'Trackon Couriers',
            'vehicle_number' => 'GJ-01-ZZ-3344',
            'created_by' => $this->user->id,
        ]);

        // 1. Show View
        $showResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $invoice));
        $showResponse->assertOk();
        $showContent = $showResponse->getContent();

        $this->assertStringContainsString('Receipt / Courier Details', $showContent);
        $this->assertStringContainsString('Courier / Docket Number', $showContent);
        $this->assertStringContainsString('TRACK-9911', $showContent);
        $this->assertStringContainsString('Courier Name', $showContent);
        $this->assertStringContainsString('Trackon Couriers', $showContent);
        $this->assertStringContainsString('Motor Vehicle No.', $showContent);
        $this->assertStringContainsString('GJ-01-ZZ-3344', $showContent);

        // Omit removed sections
        $this->assertStringNotContainsString('Receipt Note No(s)', $showContent);
        $this->assertStringNotContainsString('Order Details', $showContent);
        $this->assertStringNotContainsString('Carrier / Agent', $showContent);
        $this->assertStringNotContainsString('LR / RR No.', $showContent);

        // 2. Preview View
        $previewResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.preview', $invoice));
        $previewResponse->assertOk();
        $previewContent = $previewResponse->getContent();

        $this->assertStringContainsString('Courier / Docket Number:', $previewContent);
        $this->assertStringContainsString('TRACK-9911', $previewContent);
        $this->assertStringContainsString('Courier Name:', $previewContent);
        $this->assertStringContainsString('Trackon Couriers', $previewContent);
        $this->assertStringContainsString('Motor Vehicle No:', $previewContent);
        $this->assertStringContainsString('GJ-01-ZZ-3344', $previewContent);

        // 3. Export PDF
        $pdfResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.export', [
            'invoice' => $invoice,
            'format' => 'pdf',
        ]));
        $pdfResponse->assertOk();
        $this->assertSame('application/pdf', $pdfResponse->headers->get('Content-Type'));

        // 4. Export CSV
        $csvResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.export', [
            'invoice' => $invoice,
            'format' => 'csv',
        ]));
        $csvResponse->assertOk();
        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString('Courier / Docket Number', $csvContent);
        $this->assertStringContainsString('TRACK-9911', $csvContent);
        $this->assertStringContainsString('Courier Name', $csvContent);
        $this->assertStringContainsString('Trackon Couriers', $csvContent);
    }

    public function test_historical_invoice_with_legacy_fields_loads_without_errors(): void
    {
        // Historical invoice that has older fields filled
        $historical = PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-HISTORICAL-01',
            'supplier_invoice_no' => 'INV-HIST-01',
            'invoice_date' => now()->subDays(60)->toDateString(),
            'due_date' => now()->subDays(30)->toDateString(),
            'due_date_basis' => 'invoice_date',
            'due_date_source_date' => now()->subDays(60)->toDateString(),
            'status' => 'posted',
            'material_receipt_status' => PurchaseInvoice::MATERIAL_RECEIVED,
            'subtotal' => 2000.00,
            'taxable_amount' => 2000.00,
            'tax_amount' => 360.00,
            'grand_total' => 2360.00,
            'total_amount' => 2360.00,
            'receipt_note_nos' => 'RN-OLD-123',
            'receipt_doc_no' => 'HIST-DOC-456',
            'dispatched_through' => 'Old Logistics Co',
            'carrier_name' => 'Agent Smith',
            'lr_number' => 'LR-9988',
            'lr_date' => now()->subDays(60)->toDateString(),
            'vehicle_number' => 'MH-04-OLD-001',
            'order_nos' => 'PO-OLD-01',
            'payment_terms' => '30 Days Net',
            'other_references' => 'REF-OLD-99',
            'terms_of_delivery' => 'Ex-Works',
            'created_by' => $this->user->id,
        ]);

        // 1. Show page loads safely
        $showResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $historical));
        $showResponse->assertOk();
        $this->assertStringContainsString('HIST-DOC-456', $showResponse->getContent());
        $this->assertStringContainsString('Old Logistics Co', $showResponse->getContent());

        // 2. Edit page loads safely
        $editResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.edit', $historical));
        $editResponse->assertOk();
        $this->assertStringContainsString('HIST-DOC-456', $editResponse->getContent());
        $this->assertStringContainsString('Old Logistics Co', $editResponse->getContent());

        // 3. Preview page loads safely
        $previewResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.preview', $historical));
        $previewResponse->assertOk();
        $this->assertStringContainsString('HIST-DOC-456', $previewResponse->getContent());
        $this->assertStringContainsString('Old Logistics Co', $previewResponse->getContent());
    }
}
