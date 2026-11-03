<?php

namespace Tests\Feature\Order;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookOrderDueDateCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Customer $customer;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $ordersCreatePerm = Permission::firstOrCreate(['name' => 'orders.create', 'guard_name' => 'web']);
        $ordersEditPerm = Permission::firstOrCreate(['name' => 'orders.edit', 'guard_name' => 'web']);
        $ordersBookPerm = Permission::firstOrCreate(['name' => 'orders.book', 'guard_name' => 'web']);
        $ordersViewPerm = Permission::firstOrCreate(['name' => 'orders.view', 'guard_name' => 'web']);
        $customersViewPerm = Permission::firstOrCreate(['name' => 'customers.view', 'guard_name' => 'web']);

        $superAdminRole->givePermissionTo([
            $ordersCreatePerm,
            $ordersEditPerm,
            $ordersBookPerm,
            $ordersViewPerm,
            $customersViewPerm,
        ]);

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TC-DUE-01',
            'is_active' => true,
        ]);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_closed' => false,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($superAdminRole);

        $customerType = CustomerType::create([
            'name' => 'Wholesaler',
            'code' => 'WHL',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'name' => 'Test Credit Customer',
            'code' => 'CUST-CREDIT-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'credit_days' => 45,
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Test Widget',
            'sku' => 'WIDGET-01',
            'serial_no' => 'PRD-DUE-0001',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);
    }

    public function test_book_order_create_view_renders_due_date_quick_buttons_and_preview(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('orders.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Verify Quick Credit Period buttons
        $this->assertStringContainsString('30 Days', $content);
        $this->assertStringContainsString('45 Days', $content);
        $this->assertStringContainsString('50 Days', $content);

        // 2. Verify Live Preview container
        $this->assertStringContainsString('Due Date Preview:', $content);
        $this->assertStringContainsString('dueDatePreview', $content);

        // 3. Verify Date inputs and Alpine bindings
        $this->assertStringContainsString('name="order_date"', $content);
        $this->assertStringContainsString('name="due_date"', $content);
        $this->assertStringContainsString('orderDateChanged', $content);
        $this->assertStringContainsString('setCreditDays', $content);
        $this->assertStringContainsString('manualDueDateChanged', $content);
    }

    public function test_book_order_edit_view_renders_due_date_quick_buttons_and_preview(): void
    {
        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-2026-0001',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->adminUser->id,
            'order_date' => '2026-11-02',
            'due_date' => '2026-12-17', // 45 days
            'status' => 'pending',
            'fulfilment_mode' => 'warehouse',
            'created_by_name' => 'Admin User',
            'subtotal' => 100,
            'grand_total' => 100,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('orders.edit', $order));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('30 Days', $content);
        $this->assertStringContainsString('45 Days', $content);
        $this->assertStringContainsString('50 Days', $content);
        $this->assertStringContainsString('Due Date Preview:', $content);
        $this->assertStringContainsString('setCreditDays', $content);
    }

    public function test_store_order_saves_calculated_due_date_for_30_days(): void
    {
        $orderDate = '2026-11-02';
        $dueDate30 = Carbon::parse($orderDate)->addDays(30)->toDateString(); // 2026-12-02

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'order_date' => $orderDate,
            'due_date' => $dueDate30,
            'fulfilment_mode' => 'warehouse',
            'created_by_name' => 'Test User',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 50,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $order = Order::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('2026-11-02', $order->order_date->toDateString());
        $this->assertEquals('2026-12-02', $order->due_date->toDateString());
    }

    public function test_store_order_saves_calculated_due_date_for_45_and_50_days(): void
    {
        $orderDate = '2026-11-02';
        $dueDate45 = Carbon::parse($orderDate)->addDays(45)->toDateString(); // 2026-12-17
        $dueDate50 = Carbon::parse($orderDate)->addDays(50)->toDateString(); // 2026-12-22

        // 45 Days test
        $this->actingAs($this->adminUser)->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'order_date' => $orderDate,
            'due_date' => $dueDate45,
            'fulfilment_mode' => 'warehouse',
            'created_by_name' => 'Test User',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                ],
            ],
        ])->assertSessionHasNoErrors();

        $order45 = Order::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertEquals('2026-12-17', $order45->due_date->toDateString());

        // 50 Days test
        $this->actingAs($this->adminUser)->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'order_date' => $orderDate,
            'due_date' => $dueDate50,
            'fulfilment_mode' => 'warehouse',
            'created_by_name' => 'Test User',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                ],
            ],
        ])->assertSessionHasNoErrors();

        $order50 = Order::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertEquals('2026-12-22', $order50->due_date->toDateString());
    }

    public function test_store_order_rejects_due_date_prior_to_order_date(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-02',
            'due_date' => '2026-11-01', // Before order date
            'fulfilment_mode' => 'warehouse',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['due_date']);
    }

    public function test_customer_addresses_endpoint_returns_credit_days(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('masters.customers.addresses', $this->customer));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'customer' => [
                'id' => $this->customer->id,
                'credit_days' => 45,
            ],
        ]);
    }

    public function test_update_order_persists_recalculated_due_date(): void
    {
        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-2026-0002',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->adminUser->id,
            'order_date' => '2026-11-02',
            'due_date' => '2026-12-02', // Initially 30 days
            'status' => 'pending',
            'fulfilment_mode' => 'warehouse',
            'created_by_name' => 'Admin User',
            'subtotal' => 100,
            'grand_total' => 100,
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        // Update to 50 days (2026-12-22)
        $response = $this->actingAs($this->adminUser)->put(route('orders.update', $order), [
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-02',
            'due_date' => '2026-12-22',
            'fulfilment_mode' => 'warehouse',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertEquals('2026-12-22', $order->due_date->toDateString());
    }
}
