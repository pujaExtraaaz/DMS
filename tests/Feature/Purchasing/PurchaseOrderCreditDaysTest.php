<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Models\User;
use App\Support\DocumentExporter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderCreditDaysTest extends TestCase
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
            'credit_days' => 30,
            'email' => 'supplier@example.com',
            'phone' => '9876543210',
            'address' => '123 Harbor Rd, Port Area',
            'is_active' => true,
        ]);

        $branch = Branch::create([
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
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);
    }

    public function test_create_po_form_renders_credit_days_quick_buttons_and_due_date_preview(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('purchasing.orders.create'));

        $response->assertStatus(200);

        // Header Credit Days field & quick buttons
        $response->assertSee('Credit Days');
        $response->assertSee('30 Days');
        $response->assertSee('45 Days');
        $response->assertSee('50 Days');

        // IDs and helper scripts
        $response->assertSee('id="po_credit_days"', false);
        $response->assertSee('id="po_due_date_hidden"', false);
        $response->assertSee('due_date_preview_badge', false);
        $response->assertSee('due_date_preview_text', false);
        $response->assertSee('setPoCreditDays(30)', false);
        $response->assertSee('setPoCreditDays(45)', false);
        $response->assertSee('setPoCreditDays(50)', false);
        $response->assertSee('recalcPoDueDate()', false);

        // Supplier option data-credit-days attribute
        $response->assertSee('data-credit-days="30"', false);
    }

    public function test_edit_po_form_renders_credit_days_quick_buttons_and_due_date_preview(): void
    {
        $po = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-TEST-001',
            'po_date' => '2026-10-13',
            'expected_date' => '2026-10-20',
            'credit_days' => 45,
            'due_date' => '2026-11-27',
            'status' => 'draft',
            'subtotal' => 500.00,
            'tax_amount' => 90.00,
            'grand_total' => 590.00,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.orders.edit', $po));

        $response->assertStatus(200);
        $response->assertSee('Credit Days');
        $response->assertSee('30 Days');
        $response->assertSee('45 Days');
        $response->assertSee('50 Days');
        $response->assertSee('value="45"', false);
        $response->assertSee('setPoCreditDays', false);
        $response->assertSee('recalcPoDueDate', false);
    }

    public function test_create_po_with_custom_credit_days_calculates_and_persists_due_date(): void
    {
        $poDate = '2026-10-13';
        $creditDays = 30;
        $expectedDueDate = Carbon::parse($poDate)->addDays($creditDays)->toDateString(); // 2026-11-12

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => $poDate,
            'expected_date' => '2026-10-20',
            'credit_days' => $creditDays,
            'items' => [
                [
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_cost' => 500.00,
                    'tax_percent' => 18,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                    'uom_id' => $this->uom->id,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('purchasing.orders.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $po = PurchaseOrder::latest('id')->first();
        $this->assertNotNull($po);
        $this->assertEquals(30, $po->credit_days);
        $this->assertEquals($expectedDueDate, $po->due_date?->toDateString());
        $this->assertEquals('2026-10-20', $po->expected_date?->toDateString());
    }

    public function test_create_po_with_quick_select_presets_45_and_50_days(): void
    {
        $poDate = '2026-10-13';

        // 45 days
        $payload45 = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => $poDate,
            'credit_days' => 45,
            'items' => [
                [
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 500.00,
                    'tax_percent' => 18,
                    'uom_id' => $this->uom->id,
                ],
            ],
        ];

        $this->actingAs($this->adminUser)->post(route('purchasing.orders.store'), $payload45);
        $po45 = PurchaseOrder::latest('id')->first();
        $this->assertEquals(45, $po45->credit_days);
        $this->assertEquals(Carbon::parse($poDate)->addDays(45)->toDateString(), $po45->due_date?->toDateString());

        // 50 days (crosses into December: 13 Oct + 50 days = 2 Dec)
        $payload50 = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => $poDate,
            'credit_days' => 50,
            'items' => [
                [
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 500.00,
                    'tax_percent' => 18,
                    'uom_id' => $this->uom->id,
                ],
            ],
        ];

        $this->actingAs($this->adminUser)->post(route('purchasing.orders.store'), $payload50);
        $po50 = PurchaseOrder::latest('id')->first();
        $this->assertEquals(50, $po50->credit_days);
        $this->assertEquals(Carbon::parse($poDate)->addDays(50)->toDateString(), $po50->due_date?->toDateString());
        $this->assertEquals('2026-12-02', $po50->due_date?->toDateString());
    }

    public function test_create_po_defaults_to_supplier_credit_days_when_credit_days_omitted(): void
    {
        // Supplier has credit_days = 30 configured
        $poDate = '2026-10-15';
        $expectedDueDate = Carbon::parse($poDate)->addDays(30)->toDateString();

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => $poDate,
            // credit_days omitted
            'items' => [
                [
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 500.00,
                    'tax_percent' => 18,
                    'uom_id' => $this->uom->id,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('purchasing.orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $po = PurchaseOrder::latest('id')->first();
        $this->assertEquals(30, $po->credit_days);
        $this->assertEquals($expectedDueDate, $po->due_date?->toDateString());
    }

    public function test_create_po_with_zero_credit_days_sets_due_date_equal_to_po_date(): void
    {
        $poDate = '2026-10-13';

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => $poDate,
            'credit_days' => 0,
            'items' => [
                [
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 500.00,
                    'tax_percent' => 18,
                    'uom_id' => $this->uom->id,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('purchasing.orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $po = PurchaseOrder::latest('id')->first();
        $this->assertEquals(0, $po->credit_days);
        $this->assertEquals($poDate, $po->due_date?->toDateString());
    }

    public function test_year_end_boundary_credit_days_calculation(): void
    {
        $poDate = '2026-12-25';
        $creditDays = 30;
        $expectedDueDate = Carbon::parse($poDate)->addDays($creditDays)->toDateString(); // 2027-01-24

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => $poDate,
            'credit_days' => $creditDays,
            'items' => [
                [
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 500.00,
                    'tax_percent' => 18,
                    'uom_id' => $this->uom->id,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('purchasing.orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $po = PurchaseOrder::latest('id')->first();
        $this->assertEquals(30, $po->credit_days);
        $this->assertEquals('2027-01-24', $po->due_date?->toDateString());
    }

    public function test_update_po_updates_credit_days_and_recalculates_due_date(): void
    {
        $po = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-TEST-002',
            'po_date' => '2026-10-10',
            'credit_days' => 30,
            'due_date' => '2026-11-09',
            'status' => 'draft',
            'subtotal' => 500.00,
            'tax_amount' => 90.00,
            'grand_total' => 590.00,
        ]);

        $newPoDate = '2026-10-15';
        $newCreditDays = 60;
        $expectedDueDate = Carbon::parse($newPoDate)->addDays(60)->toDateString(); // 2026-12-14

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => $newPoDate,
            'credit_days' => $newCreditDays,
            'items' => [
                [
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 500.00,
                    'tax_percent' => 18,
                    'uom_id' => $this->uom->id,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->put(route('purchasing.orders.update', $po), $payload);
        $response->assertSessionHasNoErrors();

        $po->refresh();
        $this->assertEquals(60, $po->credit_days);
        $this->assertEquals($expectedDueDate, $po->due_date?->toDateString());
    }

    public function test_po_show_view_displays_due_date_and_credit_days(): void
    {
        $po = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-TEST-003',
            'po_date' => '2026-10-13',
            'credit_days' => 45,
            'due_date' => '2026-11-27',
            'status' => 'draft',
            'subtotal' => 500.00,
            'tax_amount' => 90.00,
            'grand_total' => 590.00,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.orders.show', $po));

        $response->assertStatus(200);
        $response->assertSee('Due Date');
        $response->assertSee('27 Nov 2026');
        $response->assertSee('45 days');
    }

    public function test_po_preview_view_displays_due_date_and_credit_days(): void
    {
        $po = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-TEST-004',
            'po_date' => '2026-10-13',
            'credit_days' => 50,
            'due_date' => '2026-12-02',
            'status' => 'approved',
            'subtotal' => 500.00,
            'tax_amount' => 90.00,
            'grand_total' => 590.00,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.orders.preview', $po));

        $response->assertStatus(200);
        $response->assertSee('Payment Due Date:');
        $response->assertSee('02/12/2026');
        $response->assertSee('50 days');
    }

    public function test_document_exporter_includes_payment_due_date_in_export(): void
    {
        $po = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-TEST-005',
            'po_date' => '2026-10-13',
            'credit_days' => 30,
            'due_date' => '2026-11-12',
            'status' => 'approved',
            'subtotal' => 500.00,
            'tax_amount' => 90.00,
            'grand_total' => 590.00,
        ]);

        $response = DocumentExporter::exportPurchaseOrder($po, 'csv');

        $this->assertEquals(200, $response->getStatusCode());
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Payment Due Date', $content);
        $this->assertStringContainsString('12/11/2026 (30 days)', $content);
    }
}

