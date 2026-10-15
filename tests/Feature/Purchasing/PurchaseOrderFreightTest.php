<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Models\User;
use App\Support\DocumentExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderFreightTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Customer $supplier;
    protected Warehouse $warehouse;
    protected Brand $brand;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'purchaser', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'purchases.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.manage', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Acme Trading Co',
            'code' => 'ATC-01',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($superAdminRole);

        $customerType = CustomerType::create([
            'name' => 'Vendor Type',
            'code' => 'VT',
        ]);

        $this->supplier = Customer::create([
            'name' => 'Global Logistics & Supplies Ltd',
            'code' => 'SUP-APEX-01',
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'email' => 'supplier@example.com',
            'phone' => '9876543210',
            'address' => '123 Harbor Rd, Port Area',
            'is_active' => true,
        ]);

        $branch = \App\Domains\Organization\Models\Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Hub',
            'code' => 'CH-01',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Apex Industrial',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'code' => 'PCS',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brand->id,
            'base_uom_id' => $this->uom->id,
            'name' => 'Hydraulic Valve A1',
            'sku' => 'VALVE-A1',
            'serial_no' => 'SN-VALVE-A1',
            'purchase_price' => 500.00,
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->startOfYear()->toDateString(),
            'ends_on' => now()->endOfYear()->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);
    }

    public function test_can_create_purchase_order_with_freight_charges_and_freight_tax(): void
    {
        // 2 items @ 500 = 1000 subtotal, 18% tax = 180 tax_amount => item total 1180
        // Freight amount = 400, tax rate = 18% => freight tax = 72, freight total = 472
        // Grand total = 1180 + 472 = 1652
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'notes' => 'Urgent procurement',
            'is_freight_applicable' => '1',
            'freight_amount' => '400.00',
            'freight_tax_rate' => '18.00',
            'freight_notes' => 'ABC Transport LR #99881',
            'items' => [
                [
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_cost' => 500.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('purchasing.orders.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $this->supplier->id,
            'is_freight_applicable' => 1,
            'freight_amount' => 400.00,
            'freight_tax_rate' => 18.00,
            'freight_tax_amount' => 72.00,
            'freight_total' => 472.00,
            'freight_notes' => 'ABC Transport LR #99881',
            'subtotal' => 1000.00,
            'tax_amount' => 180.00,
            'grand_total' => 1652.00,
        ]);
    }

    public function test_can_create_purchase_order_without_freight_charges(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'is_freight_applicable' => '0',
            'freight_amount' => '0',
            'freight_tax_rate' => '0',
            'items' => [
                [
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_cost' => 500.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('purchasing.orders.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $this->supplier->id,
            'is_freight_applicable' => 0,
            'freight_amount' => 0.00,
            'freight_tax_amount' => 0.00,
            'freight_total' => 0.00,
            'subtotal' => 1000.00,
            'tax_amount' => 180.00,
            'grand_total' => 1180.00,
        ]);
    }

    public function test_can_update_purchase_order_freight_charges_and_recalculates_grand_total(): void
    {
        $order = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-TEST-001',
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'is_freight_applicable' => false,
            'freight_amount' => 0,
            'freight_tax_rate' => 0,
            'freight_tax_amount' => 0,
            'freight_total' => 0,
            'subtotal' => 1000.00,
            'tax_amount' => 180.00,
            'grand_total' => 1180.00,
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_cost' => 500.00,
            'line_total' => 1000.00,
            'tax_percent' => 18.00,
            'cgst_percent' => 9.00,
            'sgst_percent' => 9.00,
        ]);

        $updatePayload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'is_freight_applicable' => '1',
            'freight_amount' => '300.00',
            'freight_tax_rate' => '12.00',
            'freight_notes' => 'Updated transporter note: To-Pay basis',
            'items' => [
                [
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_cost' => 500.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->put(route('purchasing.orders.update', $order), $updatePayload);

        $response->assertRedirect();

        $order->refresh();
        $this->assertTrue((bool) $order->is_freight_applicable);
        $this->assertEquals(300.00, (float) $order->freight_amount);
        $this->assertEquals(12.00, (float) $order->freight_tax_rate);
        $this->assertEquals(36.00, (float) $order->freight_tax_amount);
        $this->assertEquals(336.00, (float) $order->freight_total);
        $this->assertEquals('Updated transporter note: To-Pay basis', $order->freight_notes);
        // 1000 subtotal + 180 tax + 336 freight total = 1516
        $this->assertEquals(1516.00, (float) $order->grand_total);
    }

    public function test_purchase_order_show_page_displays_freight_breakdown(): void
    {
        $order = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-TEST-SHOW-01',
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'is_freight_applicable' => true,
            'freight_amount' => 250.00,
            'freight_tax_rate' => 18.00,
            'freight_tax_amount' => 45.00,
            'freight_total' => 295.00,
            'freight_notes' => 'Special fragile delivery handled by Express',
            'subtotal' => 1000.00,
            'tax_amount' => 180.00,
            'grand_total' => 1475.00,
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_cost' => 500.00,
            'line_total' => 1000.00,
            'tax_percent' => 18.00,
            'cgst_percent' => 9.00,
            'sgst_percent' => 9.00,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Freight Charges');
        $response->assertSee('250.00');
        $response->assertSee('Freight Tax');
        $response->assertSee('45.00');
        $response->assertSee('Freight Total');
        $response->assertSee('295.00');
        $response->assertSee('Special fragile delivery handled by Express');
    }

    public function test_purchase_order_preview_displays_freight_breakdown(): void
    {
        $order = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-TEST-PREV-01',
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'is_freight_applicable' => true,
            'freight_amount' => 350.00,
            'freight_tax_rate' => 18.00,
            'freight_tax_amount' => 63.00,
            'freight_total' => 413.00,
            'freight_notes' => 'Transporter: QuickLogistics LR: 112233',
            'subtotal' => 1000.00,
            'tax_amount' => 180.00,
            'grand_total' => 1593.00,
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_cost' => 500.00,
            'line_total' => 1000.00,
            'tax_percent' => 18.00,
            'cgst_percent' => 9.00,
            'sgst_percent' => 9.00,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.orders.preview', $order));

        $response->assertOk();
        $response->assertSee('Freight Charges');
        $response->assertSee('350.00');
        $response->assertSee('Freight Tax (GST 18.0%)');
        $response->assertSee('63.00');
        $response->assertSee('Transporter: QuickLogistics LR: 112233');
    }

    public function test_document_exporter_csv_export_includes_freight_breakdown(): void
    {
        $order = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-TEST-EXP-01',
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'is_freight_applicable' => true,
            'freight_amount' => 500.00,
            'freight_tax_rate' => 18.00,
            'freight_tax_amount' => 90.00,
            'freight_total' => 590.00,
            'subtotal' => 1000.00,
            'tax_amount' => 180.00,
            'grand_total' => 1770.00,
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_cost' => 500.00,
            'line_total' => 1000.00,
            'tax_percent' => 18.00,
            'cgst_percent' => 9.00,
            'sgst_percent' => 9.00,
        ]);

        $response = DocumentExporter::exportPurchaseOrder($order, 'csv');
        $this->assertEquals(200, $response->getStatusCode());

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Freight Charges', $content);
        $this->assertStringContainsString('500.00', $content);
        $this->assertStringContainsString('Freight Tax (18.0%)', $content);
        $this->assertStringContainsString('90.00', $content);
        $this->assertStringContainsString('Freight Total', $content);
        $this->assertStringContainsString('590.00', $content);
        $this->assertStringContainsString('1,770.00', $content);
    }

    public function test_purchase_order_freight_allocations_relationship(): void
    {
        $order = new PurchaseOrder();
        $relation = $order->freightAllocations();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $relation);
    }

    public function test_purchase_order_create_form_renders_add_line_button_before_freight_charges(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('purchasing.orders.create'));

        $response->assertOk();
        $content = $response->getContent();

        $tablePos = strpos($content, 'id="po-lines"');
        $addLinePos = strpos($content, 'onclick="addPoRow()"');
        $freightPos = strpos($content, 'Freight Charges');
        $orderSummaryPos = strpos($content, 'Order Summary');
        $saveBtnPos = strpos($content, 'Save PO');

        $this->assertNotFalse($tablePos, 'PO lines table should be present');
        $this->assertNotFalse($addLinePos, 'Add Line button should be present');
        $this->assertNotFalse($freightPos, 'Freight Charges section should be present');
        $this->assertNotFalse($orderSummaryPos, 'Order Summary section should be present');
        $this->assertNotFalse($saveBtnPos, 'Save PO button should be present');

        // Form layout order: Table -> Add Line -> Freight Charges -> Order Summary -> Save PO
        $this->assertTrue($tablePos < $addLinePos, 'PO lines table must appear before Add Line button');
        $this->assertTrue($addLinePos < $freightPos, 'Add Line button must appear before Freight Charges section');
        $this->assertTrue($freightPos < $orderSummaryPos, 'Freight Charges must appear before Order Summary');
        $this->assertTrue($orderSummaryPos < $saveBtnPos, 'Order Summary must appear before Save PO button');
    }

    public function test_purchase_order_edit_form_renders_add_line_button_before_freight_charges(): void
    {
        $order = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-TEST-LAYOUT-01',
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'is_freight_applicable' => true,
            'freight_amount' => 150.00,
            'freight_tax_rate' => 18.00,
            'freight_tax_amount' => 27.00,
            'freight_total' => 177.00,
            'subtotal' => 1000.00,
            'tax_amount' => 180.00,
            'grand_total' => 1357.00,
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_cost' => 500.00,
            'line_total' => 1000.00,
            'tax_percent' => 18.00,
            'cgst_percent' => 9.00,
            'sgst_percent' => 9.00,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.orders.edit', $order));

        $response->assertOk();
        $content = $response->getContent();

        $tablePos = strpos($content, 'id="po-lines"');
        $addLinePos = strpos($content, 'onclick="addPoRow()"');
        $freightPos = strpos($content, 'Freight Charges');
        $orderSummaryPos = strpos($content, 'Order Summary');
        $updateBtnPos = strpos($content, 'Update Purchase Order');

        $this->assertNotFalse($tablePos, 'PO lines table should be present');
        $this->assertNotFalse($addLinePos, 'Add Line button should be present');
        $this->assertNotFalse($freightPos, 'Freight Charges section should be present');
        $this->assertNotFalse($orderSummaryPos, 'Order Summary section should be present');
        $this->assertNotFalse($updateBtnPos, 'Update Purchase Order button should be present');

        // Form layout order: Table -> Add Line -> Freight Charges -> Order Summary -> Update Purchase Order
        $this->assertTrue($tablePos < $addLinePos, 'PO lines table must appear before Add Line button');
        $this->assertTrue($addLinePos < $freightPos, 'Add Line button must appear before Freight Charges section');
        $this->assertTrue($freightPos < $orderSummaryPos, 'Freight Charges must appear before Order Summary');
        $this->assertTrue($orderSummaryPos < $updateBtnPos, 'Order Summary must appear before Update Purchase Order button');
    }

    public function test_multiple_products_with_freight_calculates_freight_strictly_at_order_level(): void
    {
        $product2 = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brand->id,
            'base_uom_id' => $this->uom->id,
            'name' => 'Hydraulic Pump B2',
            'sku' => 'PUMP-B2',
            'serial_no' => 'SN-PUMP-B2',
            'purchase_price' => 1500.00,
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);

        // Item 1: 3 @ 500 = 1500, Tax 18% = 270
        // Item 2: 2 @ 1500 = 3000, Tax 18% = 540
        // Items Subtotal = 4500.00, Items Tax = 810.00
        // Freight Amount = 500.00, Freight Tax 18% = 90.00, Freight Total = 590.00
        // Grand Total = 4500 + 810 + 590 = 5900.00
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'is_freight_applicable' => '1',
            'freight_amount' => '500.00',
            'freight_tax_rate' => '18.00',
            'items' => [
                [
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 3,
                    'unit_cost' => 500.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
                [
                    'brand_id' => $this->brand->id,
                    'product_id' => $product2->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_cost' => 1500.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('purchasing.orders.store'), $payload);
        $response->assertRedirect();

        $po = PurchaseOrder::where('supplier_id', $this->supplier->id)->latest('id')->first();
        $this->assertNotNull($po);
        $this->assertEquals(4500.00, (float) $po->subtotal);
        $this->assertEquals(810.00, (float) $po->tax_amount);
        $this->assertEquals(500.00, (float) $po->freight_amount);
        $this->assertEquals(90.00, (float) $po->freight_tax_amount);
        $this->assertEquals(590.00, (float) $po->freight_total);
        $this->assertEquals(5900.00, (float) $po->grand_total);
        $this->assertCount(2, $po->items);
    }
}
