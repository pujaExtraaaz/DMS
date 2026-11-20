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
use App\Domains\Purchasing\Models\FreightBill;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RemoveGrnAndLandedCostTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $ordinaryUser;
    protected Company $company;
    protected Customer $supplier;
    protected Warehouse $warehouse;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $purchaserRole = Role::firstOrCreate(['name' => 'purchaser', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'purchases.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.manage', 'guard_name' => 'web']);

        $purchaserRole->syncPermissions([
            'purchases.view',
            'purchases.create',
            'purchases.edit',
            'purchase-orders.view',
            'purchase-orders.create',
            'purchase-orders.manage',
        ]);

        $this->company = Company::create([
            'name' => 'Acme Trading Co',
            'code' => 'ATC-01',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($superAdminRole);

        $this->ordinaryUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->ordinaryUser->assignRole($purchaserRole);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
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
            'name' => 'Central Warehouse',
            'code' => 'WH-MAIN',
            'is_active' => true,
        ]);

        $customerType = CustomerType::create([
            'name' => 'Vendor',
            'code' => 'VND',
            'is_active' => true,
        ]);

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'name' => 'Apex Components Pvt Ltd',
            'code' => 'SUP-APEX',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $brand = Brand::create([
            'name' => 'OmniCore',
            'code' => 'OMNI',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Hydraulic Valve',
            'sku' => 'HV-100',
            'serial_no' => 'SN-HV-100',
            'brand_id' => $brand->id,
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 2000.00,
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);
    }

    public function test_sidebar_does_not_contain_goods_receipts(): void
    {
        $response = $this->actingAs($this->ordinaryUser)->get(route('purchasing.orders.index'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Must preserve Purchase Orders, Material Comparison, Receipt Notes, Purchase Invoices, Landed Costs, Freight Bills
        $this->assertStringContainsString('Purchase Orders', $content);
        $this->assertStringContainsString('Material Comparison', $content);
        $this->assertStringContainsString('Receipt Notes', $content);
        $this->assertStringContainsString('Purchase Invoices', $content);
        $this->assertStringContainsString('Landed Costs', $content);
        $this->assertStringContainsString('Freight Bills', $content);

        // Must NOT contain Goods Receipts or purchasing.inwards route
        $this->assertStringNotContainsString('Goods Receipts', $content);
        $this->assertStringNotContainsString('purchasing.inwards', $content);
    }

    public function test_admin_sidebar_also_does_not_contain_goods_receipts(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('purchasing.orders.index'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringNotContainsString('Goods Receipts', $content);
        $this->assertStringNotContainsString('purchasing.inwards', $content);
    }

    public function test_direct_url_to_goods_receipts_returns_404_for_all_users(): void
    {
        // Ordinary user
        $response = $this->actingAs($this->ordinaryUser)->get(route('purchasing.inwards.index'));
        $response->assertStatus(404);

        $responseRaw = $this->actingAs($this->ordinaryUser)->get('/purchasing/inwards');
        $responseRaw->assertStatus(404);

        // Admin user
        $adminResponse = $this->actingAs($this->adminUser)->get(route('purchasing.inwards.index'));
        $adminResponse->assertStatus(404);

        $adminResponseRaw = $this->actingAs($this->adminUser)->get('/purchasing/inwards');
        $adminResponseRaw->assertStatus(404);
    }

    public function test_landed_costs_remains_protected_and_accessible(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('purchasing.landed-costs.index'));
        $response->assertStatus(200);

        $createResponse = $this->actingAs($this->adminUser)->get(route('purchasing.landed-costs.create'));
        $createResponse->assertStatus(200);
    }

    public function test_purchase_orders_remain_fully_functional(): void
    {
        // 1. Create order
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'expected_date' => now()->addDays(7)->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'batch_no' => 'BATCH-TEST-01',
                    'uom_id' => $this->uom->id,
                    'quantity' => 5,
                    'unit_cost' => 2000.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $postResponse = $this->actingAs($this->ordinaryUser)->post(route('purchasing.orders.store'), $payload);
        $postResponse->assertStatus(302);

        $order = PurchaseOrder::where('supplier_id', $this->supplier->id)->first();
        $this->assertNotNull($order);

        // 2. View order
        $showResponse = $this->actingAs($this->ordinaryUser)->get(route('purchasing.orders.show', $order));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($order->po_no);
        $showResponse->assertSee('Hydraulic Valve');
    }

    public function test_purchase_invoices_and_historical_inward_records_remain_functional(): void
    {
        // Ensure historical purchase inward can exist in database without breaking invoice view
        $inward = PurchaseInward::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'inward_no' => 'GRN-HIST-001',
            'inward_date' => now()->toDateString(),
            'status' => 'posted',
            'created_by' => $this->adminUser->id,
        ]);

        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'purchase_inward_id' => $inward->id,
            'invoice_no' => 'PINV-2026-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'posted',
            'subtotal' => 2000,
            'tax_amount' => 360,
            'grand_total' => 2360,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.invoices.show', $invoice));
        $response->assertStatus(200);
        $response->assertSee('PINV-2026-001');

        $previewResponse = $this->actingAs($this->adminUser)->get(route('purchasing.invoices.preview', $invoice));
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('GRN-HIST-001');
    }

    public function test_freight_bills_remain_functional(): void
    {
        $freightBill = FreightBill::create([
            'freight_no' => 'FB-2026-001',
            'bill_date' => now()->toDateString(),
            'transporter_name' => 'Speed Logistics',
            'amount' => 1500,
            'tax_amount' => 0,
            'total_amount' => 1500,
            'status' => 'draft',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.freight-bills.show', $freightBill));
        $response->assertStatus(200);
        $response->assertSee('Speed Logistics');
    }
}
