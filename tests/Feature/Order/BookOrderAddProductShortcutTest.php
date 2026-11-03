<?php

namespace Tests\Feature\Order;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookOrderAddProductShortcutTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Uom $uom;
    protected Brand $brand;
    protected Category $category;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $ordersCreatePerm = Permission::firstOrCreate(['name' => 'orders.create', 'guard_name' => 'web']);
        $ordersBookPerm = Permission::firstOrCreate(['name' => 'orders.book', 'guard_name' => 'web']);
        $ordersViewPerm = Permission::firstOrCreate(['name' => 'orders.view', 'guard_name' => 'web']);
        $ordersManagePerm = Permission::firstOrCreate(['name' => 'orders.manage', 'guard_name' => 'web']);
        $productsViewPerm = Permission::firstOrCreate(['name' => 'products.view', 'guard_name' => 'web']);
        $productsCreatePerm = Permission::firstOrCreate(['name' => 'products.create', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TC-01',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($superAdminRole);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $this->uom = Uom::firstOrCreate(['code' => 'PCS'], [
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Brand Test',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Category Test',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Acme Corp',
            'code' => 'CUST-001',
            'party_type' => 'customer',
            'phone' => '9876543210',
            'is_active' => true,
        ]);
    }

    public function test_book_order_create_page_renders_add_product_shortcut_links(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('orders.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for + Add Product shortcut link and button in create view
        $this->assertStringContainsString('+ Add Product', $content);
        $this->assertStringContainsString('saveDraftAndRedirectToProduct', $content);
        $this->assertStringContainsString(route('masters.products.create', ['return_to' => 'orders.create']), $content);
    }

    public function test_product_create_page_preserves_return_to_parameter_and_back_links(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.products.create', [
                'return_to' => 'orders.create',
                'target_row' => 2,
            ]));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Form has hidden inputs return_to and target_row
        $this->assertStringContainsString('<input type="hidden" name="return_to" value="orders.create">', $content);
        $this->assertStringContainsString('<input type="hidden" name="target_row" value="2">', $content);

        // 2. Header and Cancel buttons link back to Book Order
        $this->assertMatchesRegularExpression('/Back to Book Order/i', $content);
        $this->assertStringContainsString(route('orders.create'), $content);
    }

    public function test_product_create_page_preserves_edit_order_return_parameter_and_back_links(): void
    {
        $order = Order::create([
            'order_no' => 'ORD-TEST-001',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->adminUser->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'grand_total' => 0,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.products.create', [
                'return_to' => 'orders.edit',
                'order_id' => $order->id,
                'target_row' => 1,
            ]));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('<input type="hidden" name="return_to" value="orders.edit">', $content);
        $this->assertStringContainsString('<input type="hidden" name="order_id" value="' . $order->id . '">', $content);
        $this->assertStringContainsString('<input type="hidden" name="target_row" value="1">', $content);
        $this->assertMatchesRegularExpression('/Back to Edit Order/i', $content);
        $this->assertStringContainsString(route('orders.edit', $order->id), $content);
    }

    public function test_submitting_product_create_with_return_to_orders_create_redirects_back_with_new_product_id(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.products.store'), [
                'return_to' => 'orders.create',
                'target_row' => '1',
                'name' => 'Brand New Widget',
                'sku' => 'WIDGET-001',
                'base_uom_id' => $this->uom->id,
                'alternate_uom_not_applicable' => 1,
                'dimensions_not_applicable' => 1,
                'brand_id' => $this->brand->id,
                'category_id' => $this->category->id,
                'tax_rate' => 18,
                'is_active' => 1,
            ]);

        $product = Product::where('sku', 'WIDGET-001')->first();
        $this->assertNotNull($product);

        $expectedUrl = route('orders.create', [
            'new_product_id' => $product->id,
            'target_row' => '1',
        ]);

        $response->assertRedirect($expectedUrl);
        $response->assertSessionHas('success');
    }

    public function test_submitting_product_create_with_return_to_orders_edit_redirects_back_with_new_product_id(): void
    {
        $order = Order::create([
            'order_no' => 'ORD-TEST-002',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->adminUser->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'grand_total' => 0,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.products.store'), [
                'return_to' => 'orders.edit',
                'order_id' => $order->id,
                'target_row' => '0',
                'name' => 'Brand New Edit Widget',
                'sku' => 'WIDGET-EDIT-001',
                'base_uom_id' => $this->uom->id,
                'alternate_uom_not_applicable' => 1,
                'dimensions_not_applicable' => 1,
                'brand_id' => $this->brand->id,
                'category_id' => $this->category->id,
                'tax_rate' => 18,
                'is_active' => 1,
            ]);

        $product = Product::where('sku', 'WIDGET-EDIT-001')->first();
        $this->assertNotNull($product);

        $expectedUrl = route('orders.edit', [
            'order' => $order->id,
            'new_product_id' => $product->id,
            'target_row' => '0',
        ]);

        $response->assertRedirect($expectedUrl);
        $response->assertSessionHas('success');
    }

    public function test_submitting_product_create_without_return_to_redirects_to_products_index(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.products.store'), [
                'name' => 'Standard Product Flow',
                'sku' => 'WIDGET-STD-001',
                'base_uom_id' => $this->uom->id,
                'alternate_uom_not_applicable' => 1,
                'dimensions_not_applicable' => 1,
                'brand_id' => $this->brand->id,
                'category_id' => $this->category->id,
                'tax_rate' => 18,
                'is_active' => 1,
            ]);

        $product = Product::where('sku', 'WIDGET-STD-001')->first();
        $this->assertNotNull($product);

        $response->assertRedirect(route('masters.products.index'));
        $response->assertSessionHas('status');
    }

    public function test_book_order_create_loads_newly_created_product_when_new_product_id_in_query(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Created For Order',
            'sku' => 'SKU-FOR-ORDER-001',
            'serial_no' => 'PRD-GLOBAL-99991',
            'base_uom_id' => $this->uom->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('orders.create', [
                'new_product_id' => $product->id,
                'target_row' => '0',
            ]));

        $response->assertStatus(200);
        $response->assertViewHas('newProduct');
        $response->assertViewHas('targetRow', 0);

        $content = $response->getContent();
        $this->assertStringContainsString('Created For Order', $content);
    }
}
