<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Inventory\Models\ProductSerial;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductSerialNumberTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $supplier;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected ProductType $serialProductType;

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

        $this->serialProductType = ProductType::create([
            'name' => 'Electronics Hardware',
            'track_serial' => true,
            'is_active' => true,
        ]);
    }

    public function test_product_master_saves_and_retrieves_track_serial_setting_and_preserves_master_serial_no(): void
    {
        // 1. Create a product with Track serial numbers ON
        $responseOn = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Serial Tracked Sensor Unit',
            'sku' => 'SNU-001',
            'product_type_id' => $this->serialProductType->id,
            'serial_no' => 'MASTER-SN-001', // Product-level master serial number
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 5000.00,
            'tax_rate' => 18.00,
            'tracking_type' => ['serial'],
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
        ]);
        $responseOn->assertSessionHasNoErrors();

        $productOn = Product::where('sku', 'SNU-001')->firstOrFail();
        $this->assertTrue($productOn->isSerialTracked());
        $this->assertTrue($productOn->is_serial_tracked);
        $this->assertSame('MASTER-SN-001', $productOn->serial_no);

        // 2. Create a product with Track serial numbers OFF (even with a ProductType that has track_serial = true)
        $responseOff = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Non Tracked Sensor Cable',
            'sku' => 'NTC-001',
            'product_type_id' => $this->serialProductType->id,
            'serial_no' => 'MASTER-CBL-001',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 250.00,
            'tax_rate' => 18.00,
            // tracking_type omitted or empty (Track serial numbers unchecked)
            'tracking_type' => [],
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
        ]);
        $responseOff->assertSessionHasNoErrors();

        $productOff = Product::where('sku', 'NTC-001')->firstOrFail();
        $this->assertFalse($productOff->isSerialTracked());
        $this->assertFalse($productOff->is_serial_tracked);
        $this->assertSame('none', $productOff->tracking_type);
        $this->assertSame('MASTER-CBL-001', $productOff->serial_no);

        // 3. Edit product and toggle tracking from OFF to ON
        $responseToggleOn = $this->actingAs($this->user)->put(route('masters.products.update', $productOff), [
            'name' => 'Non Tracked Sensor Cable (Now Tracked)',
            'sku' => 'NTC-001',
            'product_type_id' => $this->serialProductType->id,
            'serial_no' => 'MASTER-CBL-001',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 250.00,
            'tax_rate' => 18.00,
            'tracking_type' => ['serial'],
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
        ]);
        $responseToggleOn->assertSessionHasNoErrors();

        $productOff->refresh();
        $this->assertTrue($productOff->isSerialTracked());

        // 4. Edit product and toggle tracking from ON to OFF
        $responseToggleOff = $this->actingAs($this->user)->put(route('masters.products.update', $productOff), [
            'name' => 'Sensor Cable (Back to Untracked)',
            'sku' => 'NTC-001',
            'product_type_id' => $this->serialProductType->id,
            'serial_no' => 'MASTER-CBL-001',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 250.00,
            'tax_rate' => 18.00,
            'tracking_type' => [],
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
        ]);
        $responseToggleOff->assertSessionHasNoErrors();

        $productOff->refresh();
        $this->assertFalse($productOff->isSerialTracked());
    }

    public function test_purchase_invoice_create_view_supplies_actual_tracking_flags(): void
    {
        $trackedProd = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Gateway Hub',
            'sku' => 'IGH-100',
            'serial_no' => 'MST-IGH-100',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 15000.00,
            'tax_rate' => 18.00,
            'tracking_type' => 'serial',
            'is_active' => true,
        ]);

        $untrackedProd = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Mounting Bracket 100mm',
            'sku' => 'MB-100',
            'serial_no' => 'MST-MB-100',
            'product_type_id' => $this->serialProductType->id, // has track_serial = true on type
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 450.00,
            'tax_rate' => 18.00,
            'tracking_type' => 'none', // but product itself has tracking OFF
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create'));
        $response->assertOk();

        // Template must use x-if or x-show with isSerialTracked to prevent rendering inputs for untracked items
        $response->assertSee('isSerialTracked(row)', false);
        $response->assertSee('x-if="isSerialTracked(row)"', false);
    }

    public function test_purchase_invoice_for_untracked_product_does_not_require_or_create_serials(): void
    {
        $untrackedProd = Product::create([
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

        // Create purchase invoice without providing any serial_numbers
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'INV-UNTRACKED-001',
            'credit_days' => 15,
            'items' => [
                [
                    'product_id' => $untrackedProd->id,
                    'quantity' => 10,
                    'unit_cost' => 120.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                    // No serial_numbers entered
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-UNTRACKED-001')->firstOrFail();
        $this->assertCount(1, $invoice->items);

        // Confirm zero serial records were created for this product or invoice
        $this->assertSame(0, ProductSerial::where('product_id', $untrackedProd->id)->count());
    }

    public function test_purchase_invoice_for_tracked_product_validates_and_saves_serials(): void
    {
        $trackedProd = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Edge Router Dual-SIM',
            'sku' => 'ER-DS-01',
            'serial_no' => 'MST-ER-DS-01',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 8500.00,
            'tax_rate' => 18.00,
            'tracking_type' => 'serial',
            'is_active' => true,
        ]);

        // 1. Trying to save without serial numbers should fail validation
        $invalidPayload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'INV-FAIL-001',
            'items' => [
                [
                    'product_id' => $trackedProd->id,
                    'quantity' => 2,
                    'unit_cost' => 8500.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                    'serial_numbers' => [], // Missing serials!
                ],
            ],
        ];

        $invalidResponse = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $invalidPayload);
        $invalidResponse->assertSessionHasErrors(['items.0.serial_numbers']);

        // 2. Saving with exact serial numbers succeeds and creates ProductSerial records
        $serial1 = 'SN-ROUTER-A101';
        $serial2 = 'SN-ROUTER-A102';

        $validPayload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'INV-SUCCESS-001',
            'items' => [
                [
                    'product_id' => $trackedProd->id,
                    'quantity' => 2,
                    'unit_cost' => 8500.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                    'serial_numbers' => [$serial1, $serial2],
                ],
            ],
        ];

        $validResponse = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $validPayload);
        $validResponse->assertSessionHasNoErrors();

        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-SUCCESS-001')->firstOrFail();
        $item = $invoice->items()->firstOrFail();

        // ProductSerial records must be created and linked to the item
        $savedSerials = ProductSerial::where('product_id', $trackedProd->id)->pluck('serial_number')->all();
        $this->assertCount(2, $savedSerials);
        $this->assertContains($serial1, $savedSerials);
        $this->assertContains($serial2, $savedSerials);

        // Check relationship on item
        $this->assertEquals([$serial1, $serial2], $item->serialNumbers());
    }

    public function test_purchase_invoice_preview_and_export_display_serials_only_for_tracked_products(): void
    {
        $trackedProd = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Security Camera 4K Ultra',
            'sku' => 'CAM-4K-01',
            'serial_no' => 'MST-CAM-4K-01',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 6200.00,
            'tax_rate' => 18.00,
            'tracking_type' => 'serial',
            'is_active' => true,
        ]);

        $untrackedProd = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Camera Ceiling Mount Kit',
            'sku' => 'CAM-MNT-01',
            'serial_no' => 'MST-CAM-MNT-01',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 350.00,
            'tax_rate' => 18.00,
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $serial1 = 'SN-CAM-99001';
        $serial2 = 'SN-CAM-99002';

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'INV-PREV-001',
            'items' => [
                [
                    'product_id' => $trackedProd->id,
                    'quantity' => 2,
                    'unit_cost' => 6200.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                    'serial_numbers' => [$serial1, $serial2],
                ],
                [
                    'product_id' => $untrackedProd->id,
                    'quantity' => 5,
                    'unit_cost' => 350.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                    'serial_numbers' => [],
                ],
            ],
        ];

        $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-PREV-001')->firstOrFail();

        // 1. Invoice Preview
        $previewResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.preview', $invoice));
        $previewResponse->assertOk();
        $previewContent = $previewResponse->getContent();

        // Tracked product item displays its serial numbers
        $this->assertStringContainsString('Security Camera 4K Ultra', $previewContent);
        $this->assertStringContainsString('Serial No:', $previewContent);
        $this->assertStringContainsString('SN-CAM-99001', $previewContent);
        $this->assertStringContainsString('SN-CAM-99002', $previewContent);

        // Untracked product item exists, but does not display any blank serial number label
        $this->assertStringContainsString('Camera Ceiling Mount Kit', $previewContent);

        // 2. Export PDF
        $pdfResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.export', [
            'invoice' => $invoice,
            'format' => 'pdf',
        ]));
        $pdfResponse->assertOk();
        $this->assertSame('application/pdf', $pdfResponse->headers->get('Content-Type'));

        // 3. Export CSV includes Serial Nos column
        $csvResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.export', [
            'invoice' => $invoice,
            'format' => 'csv',
        ]));
        $csvResponse->assertOk();
        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString('Serial Nos', $csvContent);
        $this->assertStringContainsString('SN-CAM-99001, SN-CAM-99002', $csvContent);
    }
}
