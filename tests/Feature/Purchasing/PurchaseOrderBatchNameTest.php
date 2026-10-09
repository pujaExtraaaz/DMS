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
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderBatchNameTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $supplier;
    protected Warehouse $warehouse;
    protected Uom $uom;
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
            'name' => 'Apex Suppliers Ltd',
            'code' => 'SUP-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $customerType->id,
            'phone' => '9876543210',
            'state' => 'Maharashtra',
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
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product1 = Product::create([
            'name' => 'Industrial Widget A',
            'sku' => 'WID-001',
            'serial_no' => 'SN-WID-001',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 150.00,
            'selling_price' => 200.00,
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'name' => 'Industrial Widget B',
            'sku' => 'WID-002',
            'serial_no' => 'SN-WID-002',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 300.00,
            'selling_price' => 450.00,
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);
    }

    public function test_new_purchase_order_page_displays_batch_name_between_product_and_uom(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchasing.orders.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Verify table header contains exact order: Product -> Batch Name -> UOM -> Qty -> Unit Cost -> Tax % -> CGST % -> SGST %
        $this->assertMatchesRegularExpression(
            '/<th[^>]*>Product<\/th>\s*<th[^>]*>Batch Name<\/th>\s*<th[^>]*>UOM<\/th>\s*<th[^>]*>Qty<\/th>\s*<th[^>]*>Unit Cost<\/th>\s*<th[^>]*>Tax %<\/th>\s*<th[^>]*>CGST %<\/th>\s*<th[^>]*>SGST %<\/th>/is',
            $content
        );

        // 2. Verify tbody initial row has Batch Name input immediately between product select and uom select
        $this->assertMatchesRegularExpression(
            '/name="items\[0\]\[product_id\]".*?name="items\[0\]\[batch_no\]".*?name="items\[0\]\[uom_id\]"/is',
            $content
        );

        // 3. Verify JavaScript addPoRow contains clone logic and handles batch input
        $this->assertStringContainsString('items[\' + poi + \']', $content);
        $this->assertStringContainsString('[batch_no]', $content);
    }

    public function test_purchase_order_saves_and_retrieves_multiple_batch_names(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'expected_date' => now()->addDays(7)->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'batch_no' => 'BATCH-ALPHA-2026',
                    'uom_id' => $this->uom->id,
                    'quantity' => 10,
                    'unit_cost' => 150.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
                [
                    'product_id' => $this->product2->id,
                    'batch_no' => 'BATCH-BETA-2026',
                    'uom_id' => $this->uom->id,
                    'quantity' => 5,
                    'unit_cost' => 300.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.orders.store'), $payload);

        $response->assertSessionHasNoErrors();
        $order = PurchaseOrder::where('supplier_id', $this->supplier->id)->latest('id')->first();
        $this->assertNotNull($order);

        $items = $order->items()->orderBy('id')->get();
        $this->assertCount(2, $items);

        $this->assertEquals('BATCH-ALPHA-2026', $items[0]->batch_no);
        $this->assertEquals('BATCH-ALPHA-2026', $items[0]->batch_name);

        $this->assertEquals('BATCH-BETA-2026', $items[1]->batch_no);
        $this->assertEquals('BATCH-BETA-2026', $items[1]->batch_name);

        // Verify show page displays Batch Name column between Product and UOM
        $showResponse = $this->actingAs($this->user)->get(route('purchasing.orders.show', $order));
        $showResponse->assertStatus(200);
        $showContent = $showResponse->getContent();

        $this->assertMatchesRegularExpression(
            '/<th[^>]*>Product<\/th>\s*<th[^>]*>Batch Name<\/th>\s*<th[^>]*>UOM<\/th>/is',
            $showContent
        );
        $this->assertStringContainsString('BATCH-ALPHA-2026', $showContent);
        $this->assertStringContainsString('BATCH-BETA-2026', $showContent);
    }

    public function test_purchase_order_accepts_batch_name_as_input_key(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'batch_name' => 'BATCH-NAME-KEY',
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_cost' => 150.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $order = PurchaseOrder::where('supplier_id', $this->supplier->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('BATCH-NAME-KEY', $order->items->first()->batch_no);
    }

    public function test_purchase_order_allows_optional_batch_name(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'batch_no' => '',
                    'uom_id' => $this->uom->id,
                    'quantity' => 4,
                    'unit_cost' => 150.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $order = PurchaseOrder::where('supplier_id', $this->supplier->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertNull($order->items->first()->batch_no);
    }

    public function test_validation_failure_preserves_batch_names_across_multiple_rows(): void
    {
        // Intentionally omit po_date to trigger validation failure
        $invalidPayload = [
            'supplier_id' => $this->supplier->id,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'batch_no' => 'PRESERVED-BATCH-1',
                    'uom_id' => $this->uom->id,
                    'quantity' => 10,
                    'unit_cost' => 150.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
                [
                    'product_id' => $this->product2->id,
                    'batch_no' => 'PRESERVED-BATCH-2',
                    'uom_id' => $this->uom->id,
                    'quantity' => 20,
                    'unit_cost' => 300.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.orders.store'), $invalidPayload);
        $response->assertSessionHasErrors(['po_date']);

        // Now follow redirect to create page with session old input
        $createResponse = $this->actingAs($this->user)
            ->withSession(['_old_input' => $invalidPayload])
            ->get(route('purchasing.orders.create'));

        $createResponse->assertStatus(200);
        $createContent = $createResponse->getContent();

        $this->assertStringContainsString('PRESERVED-BATCH-1', $createContent);
        $this->assertStringContainsString('PRESERVED-BATCH-2', $createContent);
    }

    public function test_batch_lookup_endpoint_returns_batches_for_product(): void
    {
        \App\Domains\Inventory\Models\ProductBatch::create([
            'product_id' => $this->product1->id,
            'warehouse_id' => $this->warehouse->id,
            'uom_id' => $this->uom->id,
            'batch_no' => 'EXISTING-BATCH-99',
            'quantity' => 50,
            'unit_cost' => 150.00,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('inventory.batches.lookup', ['product' => $this->product1->id, 'include_zero' => 1]));

        $response->assertStatus(200);
        $response->assertJson([
            'product_id' => $this->product1->id,
        ]);
        $this->assertStringContainsString('EXISTING-BATCH-99', json_encode($response->json('batches')));
    }
}

