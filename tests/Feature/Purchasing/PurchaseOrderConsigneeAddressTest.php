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
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderConsigneeAddressTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;
    protected Warehouse $warehouse;
    protected Customer $supplier;
    protected Brand $brand;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'purchases.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.manage', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Avit Digital Private Limited',
            'legal_name' => 'Avit Digital PVT LTD',
            'code' => 'AD',
            'gstin' => '03ABGPG1954N1ZN',
            'pan' => 'ABGPG1954N',
            'phone' => '9876120738',
            'email' => 'info@avitdigital.in',
            'address' => 'Plot 101, Industrial Area Phase 1, Chandigarh',
            'state' => 'Punjab',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($superAdminRole);

        $this->branch = Branch::create([
            'name' => 'Ludhiana Central Branch',
            'code' => 'LDH-01',
            'company_id' => $this->company->id,
            'address' => 'GT Road, Miller Ganj, Ludhiana, Punjab 141003',
            'state' => 'Punjab',
            'gstin' => '03ABGPG1954N1ZN',
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Focal Point Distribution Center',
            'code' => 'WH-FP-01',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'address' => 'Shed No. 14, Phase 5, Focal Point, Ludhiana, Punjab 141010',
            'phone' => '9814012345',
            'is_active' => true,
        ]);

        $customerType = CustomerType::create([
            'name' => 'Supplier Type',
            'code' => 'ST',
        ]);

        $this->supplier = Customer::create([
            'name' => 'Supreme Vendor Technologies',
            'code' => 'SUP-SVT-99',
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'address' => 'Plot 88, Okhla Industrial Area Phase 3, New Delhi, Delhi 110020',
            'state' => 'Delhi',
            'gstin' => '07AAACS1429B1Z8',
            'pan' => 'AAACS1429B',
            'phone' => '9999011223',
            'email' => 'orders@supremevendor.com',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'name' => 'Samsung',
            'code' => 'SAM',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Galaxy Display Panel 55 Inch',
            'sku' => 'SAM-DISP-55',
            'serial_no' => 'SN-DISP-55',
            'brand_id' => $this->brand->id,
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 25000.00,
            'selling_price' => 32000.00,
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => '2026-2027',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);
    }

    public function test_resolve_consignee_details_uses_warehouse_address_and_company_info(): void
    {
        $order = PurchaseOrder::create([
            'po_no' => 'PO-2026-0001',
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'branch_id' => $this->branch->id,
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'billing_address' => $this->supplier->address,
            'subtotal' => 25000,
            'tax_amount' => 4500,
            'grand_total' => 29500,
            'created_by' => $this->adminUser->id,
        ]);

        $consignee = $order->resolveConsigneeDetails($this->company);
        $supplierDetails = $order->resolveSupplierDetails();

        // Consignee delivery address must match warehouse address, NOT supplier address
        $this->assertEquals($this->warehouse->address, $consignee['delivery_address']);
        $this->assertEquals('Focal Point Distribution Center', $consignee['warehouse_name']);
        $this->assertEquals('Ludhiana Central Branch', $consignee['branch_name']);
        $this->assertEquals('Avit Digital Private Limited', $consignee['company_name']);
        $this->assertEquals('03ABGPG1954N1ZN', $consignee['gstin']);
        $this->assertEquals('ABGPG1954N', $consignee['pan']);
        $this->assertEquals($this->branch->phone, $consignee['phone']);

        // Supplier details must match supplier's billing details
        $this->assertEquals($this->supplier->address, $supplierDetails['billing_address']);
        $this->assertEquals('Supreme Vendor Technologies', $supplierDetails['name']);
        $this->assertEquals('07AAACS1429B1Z8', $supplierDetails['gstin']);
        $this->assertEquals('AAACS1429B', $supplierDetails['pan']);

        // Assert strictly different addresses
        $this->assertNotEquals($consignee['delivery_address'], $supplierDetails['billing_address']);
        $this->assertStringNotContainsString('Okhla', $consignee['delivery_address']);
        $this->assertStringContainsString('Focal Point', $consignee['delivery_address']);
    }

    public function test_consignee_falls_back_to_branch_address_when_warehouse_address_empty(): void
    {
        $warehouseNoAddr = Warehouse::create([
            'name' => 'Virtual Transit Warehouse',
            'code' => 'WH-VTR-01',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'address' => null,
            'is_active' => true,
        ]);

        $order = PurchaseOrder::create([
            'po_no' => 'PO-2026-0002',
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $warehouseNoAddr->id,
            'branch_id' => $this->branch->id,
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'billing_address' => $this->supplier->address,
            'created_by' => $this->adminUser->id,
        ]);

        $consignee = $order->resolveConsigneeDetails($this->company);

        $this->assertEquals($this->branch->address, $consignee['delivery_address']);
        $this->assertStringContainsString('Miller Ganj', $consignee['delivery_address']);
        $this->assertNotEquals($this->supplier->address, $consignee['delivery_address']);
    }

    public function test_consignee_falls_back_to_company_address_when_warehouse_and_branch_empty(): void
    {
        $branchNoAddr = Branch::create([
            'name' => 'Remote Liaison Branch',
            'code' => 'RLB-01',
            'company_id' => $this->company->id,
            'address' => null,
            'is_active' => true,
        ]);

        $warehouseNoAddr = Warehouse::create([
            'name' => 'Liaison Storage',
            'code' => 'WH-LS-01',
            'company_id' => $this->company->id,
            'branch_id' => $branchNoAddr->id,
            'address' => null,
            'is_active' => true,
        ]);

        $order = PurchaseOrder::create([
            'po_no' => 'PO-2026-0003',
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $warehouseNoAddr->id,
            'branch_id' => $branchNoAddr->id,
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'billing_address' => $this->supplier->address,
            'created_by' => $this->adminUser->id,
        ]);

        $consignee = $order->resolveConsigneeDetails($this->company);

        $this->assertEquals($this->company->address, $consignee['delivery_address']);
        $this->assertStringContainsString('Plot 101, Industrial Area Phase 1', $consignee['delivery_address']);
        $this->assertNotEquals($this->supplier->address, $consignee['delivery_address']);
    }

    public function test_consignee_returns_address_not_provided_when_all_delivery_sources_are_empty(): void
    {
        $companyEmptyAddr = Company::create([
            'name' => 'No Address Enterprise',
            'code' => 'NAE',
            'gstin' => '03NAE0000000001',
            'pan' => 'NAE0000000',
            'address' => null,
            'is_active' => true,
        ]);

        $order = PurchaseOrder::create([
            'po_no' => 'PO-2026-0004',
            'company_id' => $companyEmptyAddr->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => null,
            'branch_id' => null,
            'shipping_address' => null,
            'billing_address' => $this->supplier->address,
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'created_by' => $this->adminUser->id,
        ]);

        $consignee = $order->resolveConsigneeDetails($companyEmptyAddr);

        $this->assertEquals('Address not provided', $consignee['delivery_address']);
        $this->assertNotEquals($this->supplier->address, $consignee['delivery_address']);
    }

    public function test_custom_delivery_address_entered_on_po_is_preserved(): void
    {
        $customSiteAddress = 'Project Site 7, Sector 82, JLPL Industrial Area, Mohali 140308';

        $order = PurchaseOrder::create([
            'po_no' => 'PO-2026-0005',
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'branch_id' => $this->branch->id,
            'shipping_address' => $customSiteAddress,
            'billing_address' => $this->supplier->address,
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'created_by' => $this->adminUser->id,
        ]);

        $consignee = $order->resolveConsigneeDetails($this->company);

        $this->assertEquals($customSiteAddress, $consignee['delivery_address']);
        $this->assertNotEquals($this->supplier->address, $consignee['delivery_address']);
    }

    public function test_legacy_copied_supplier_address_is_ignored_and_falls_back_to_warehouse_address(): void
    {
        // Simulate legacy PO where same_as_billing was checked, copying supplier address to shipping_address
        $order = PurchaseOrder::create([
            'po_no' => 'PO-2026-0006',
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'branch_id' => $this->branch->id,
            'shipping_address' => $this->supplier->address, // Copied supplier address
            'billing_address' => $this->supplier->address,
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'created_by' => $this->adminUser->id,
        ]);

        $consignee = $order->resolveConsigneeDetails($this->company);

        // It must detect that shipping_address is equal to the supplier address and use warehouse address instead
        $this->assertEquals($this->warehouse->address, $consignee['delivery_address']);
        $this->assertNotEquals($this->supplier->address, $consignee['delivery_address']);
    }

    public function test_po_service_create_defaults_shipping_address_to_warehouse(): void
    {
        $service = app(PurchaseOrderService::class);

        $data = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'credit_days' => 30,
            'billing_address' => $this->supplier->address,
            'shipping_address' => '', // Left empty by user
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_cost' => 25000,
                    'tax_percent' => 18,
                ],
            ],
        ];

        $order = $service->create($data, $this->adminUser);

        // Service must auto-fill shipping_address from warehouse address, NOT supplier address
        $this->assertEquals($this->warehouse->address, $order->shipping_address);
        $this->assertEquals($this->supplier->address, $order->billing_address);
        $this->assertNotEquals($order->shipping_address, $order->billing_address);
    }

    public function test_preview_blade_renders_distinct_supplier_and_consignee_sections(): void
    {
        $order = PurchaseOrder::create([
            'po_no' => 'PO-2026-0007',
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'branch_id' => $this->branch->id,
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'billing_address' => $this->supplier->address,
            'shipping_address' => $this->warehouse->address,
            'subtotal' => 25000,
            'tax_amount' => 4500,
            'grand_total' => 29500,
            'created_by' => $this->adminUser->id,
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 1,
            'unit_cost' => 25000,
            'tax_percent' => 18,
            'line_total' => 29500,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.orders.preview', $order));

        $response->assertOk();
        $response->assertSee('Supplier / Vendor (Order Placed To)');
        $response->assertSee('Consignee / Delivery Destination');
        $response->assertSee('Supreme Vendor Technologies');
        $response->assertSee('Avit Digital Private Limited');
        $response->assertSee('Focal Point Distribution Center');
        $response->assertSee('Shed No. 14, Phase 5, Focal Point');

        $content = $response->getContent();
        // Consignee section must contain warehouse address
        $consigneeSection = substr($content, strpos($content, 'Consignee / Delivery Destination'));
        $this->assertStringContainsString('Shed No. 14, Phase 5, Focal Point', $consigneeSection);
        $this->assertStringNotContainsString('Okhla Industrial Area', $consigneeSection);
    }

    public function test_show_blade_renders_distinct_addresses(): void
    {
        $order = PurchaseOrder::create([
            'po_no' => 'PO-2026-0008',
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'branch_id' => $this->branch->id,
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'billing_address' => $this->supplier->address,
            'shipping_address' => $this->warehouse->address,
            'subtotal' => 25000,
            'tax_amount' => 4500,
            'grand_total' => 29500,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Billing Address (Supplier)');
        $response->assertSee('Delivery / Dispatch Address (Consignee)');
        $response->assertSee('Shed No. 14, Phase 5, Focal Point');
        $response->assertSee('Plot 88, Okhla');
    }

    public function test_pdf_export_renders_successfully_with_distinct_consignee_address(): void
    {
        $order = PurchaseOrder::create([
            'po_no' => 'PO-2026-0009',
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'branch_id' => $this->branch->id,
            'po_date' => now()->toDateString(),
            'status' => 'approved',
            'billing_address' => $this->supplier->address,
            'shipping_address' => $this->warehouse->address,
            'subtotal' => 25000,
            'tax_amount' => 4500,
            'grand_total' => 29500,
            'created_by' => $this->adminUser->id,
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 1,
            'unit_cost' => 25000,
            'tax_percent' => 18,
            'line_total' => 29500,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.orders.export', ['order' => $order, 'format' => 'pdf']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
