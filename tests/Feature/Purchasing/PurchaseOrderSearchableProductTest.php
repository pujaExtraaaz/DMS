<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderSearchableProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $supplier;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Brand $brand1;
    protected Brand $brand2;
    protected Product $product1;
    protected Product $product2;

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

        \App\Domains\Organization\Models\FinancialYear::create([
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
            'customer_type_id' => $customerType->id,
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'name' => 'Apex Components Pvt Ltd',
            'code' => 'SUP-APEX',
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
            'name' => 'Central Warehouse',
            'code' => 'WH-MAIN',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->brand1 = Brand::create([
            'name' => 'OmniCore',
            'code' => 'OMNI',
            'is_active' => true,
        ]);

        $this->brand2 = Brand::create([
            'name' => 'TitanFlow',
            'code' => 'TITAN',
            'is_active' => true,
        ]);

        $this->product1 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Hydraulic Valve',
            'sku' => 'IHV-9000',
            'serial_no' => 'SN-IHV-9000',
            'brand_id' => $this->brand1->id,
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 2500.00,
            'selling_price' => 3200.00,
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Pneumatic Actuator Cylinder',
            'sku' => 'PAC-4500',
            'serial_no' => 'SN-PAC-4500',
            'brand_id' => $this->brand1->id,
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 1800.00,
            'selling_price' => 2400.00,
            'tax_rate' => 12.00,
            'is_active' => true,
        ]);
    }

    public function test_create_page_renders_searchable_product_elements(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchasing.orders.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Must render Brand header before Product
        $brandPos = strpos($content, '<th class="px-3 py-2 text-left w-44">Brand</th>');
        $productPos = strpos($content, '<th class="px-3 py-2 text-left min-w-[250px]">Product</th>');
        $this->assertNotFalse($brandPos);
        $this->assertNotFalse($productPos);
        $this->assertTrue($brandPos < $productPos, 'Brand column must appear before Product column');

        // Must render Brand dropdown for line item
        $this->assertStringContainsString('items[0][brand_id]', $content);
        $this->assertStringContainsString('OmniCore', $content);
        $this->assertStringContainsString('TitanFlow', $content);

        // Product input must be initially disabled with placeholder "Select brand first"
        $this->assertStringContainsString('placeholder="Select brand first"', $content);

        // Must contain searchable product picker and input
        $this->assertStringContainsString('po-product-picker-container', $content);
        $this->assertStringContainsString('po-product-search', $content);
        $this->assertStringContainsString('po-clear-product', $content);
        $this->assertStringContainsString('po-dropdown-toggle', $content);

        // Must serialize brand_id in poProductsList
        $this->assertStringContainsString('poProductsList', $content);
        $this->assertStringContainsString('"brand_id":"' . $this->brand1->id . '"', $content);
        $this->assertStringContainsString('Industrial Hydraulic Valve', $content);
        $this->assertStringContainsString('IHV-9000', $content);
        $this->assertStringContainsString('Pneumatic Actuator Cylinder', $content);
        $this->assertStringContainsString('PAC-4500', $content);
    }

    public function test_validation_failure_preserves_selected_product_in_search_field(): void
    {
        $invalidPayload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => '', // missing po_date causes validation failure
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'batch_no' => 'BATCH-ALPHA',
                    'uom_id' => $this->uom->id,
                    'quantity' => 10,
                    'unit_cost' => 2500.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $postResponse = $this->actingAs($this->user)->post(route('purchasing.orders.store'), $invalidPayload);
        $postResponse->assertSessionHasErrors(['po_date']);

        $createResponse = $this->actingAs($this->user)
            ->withSession(['_old_input' => $invalidPayload])
            ->get(route('purchasing.orders.create'));

        $createResponse->assertStatus(200);
        $content = $createResponse->getContent();

        // Product name should be prefilled into the value attribute of po-product-search
        $this->assertStringContainsString('value="Industrial Hydraulic Valve"', $content);
    }

    public function test_purchase_order_can_be_stored_successfully_with_selected_products(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'expected_date' => now()->addDays(7)->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'batch_no' => 'BATCH-TEST-01',
                    'uom_id' => $this->uom->id,
                    'quantity' => 5,
                    'unit_cost' => 2500.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
                [
                    'product_id' => $this->product2->id,
                    'batch_no' => 'BATCH-TEST-02',
                    'uom_id' => $this->uom->id,
                    'quantity' => 3,
                    'unit_cost' => 1800.00,
                    'tax_percent' => 12.00,
                    'cgst_percent' => 6.00,
                    'sgst_percent' => 6.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.orders.store'), $payload);

        $response->assertStatus(302);
        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $po = PurchaseOrder::where('supplier_id', $this->supplier->id)->first();
        $this->assertNotNull($po);
        $this->assertCount(2, $po->items);
    }

    public function test_purchase_order_rejects_product_that_does_not_belong_to_selected_brand(): void
    {
        // product1 belongs to brand1 (OmniCore), but we submit brand2 (TitanFlow)
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'expected_date' => now()->addDays(7)->toDateString(),
            'items' => [
                [
                    'brand_id' => $this->brand2->id,
                    'product_id' => $this->product1->id,
                    'batch_no' => 'BATCH-TEST-MISMATCH',
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_cost' => 2500.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.orders.store'), $payload);

        $response->assertSessionHasErrors(['items.0.product_id']);
    }

    public function test_purchase_order_stores_successfully_with_matching_brand_and_product(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'expected_date' => now()->addDays(7)->toDateString(),
            'items' => [
                [
                    'brand_id' => $this->brand1->id,
                    'product_id' => $this->product1->id,
                    'batch_no' => 'BATCH-TEST-MATCH',
                    'uom_id' => $this->uom->id,
                    'quantity' => 4,
                    'unit_cost' => 2500.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.orders.store'), $payload);

        $response->assertStatus(302);
        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
        ]);
    }
}
