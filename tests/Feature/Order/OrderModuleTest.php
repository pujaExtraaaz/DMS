<?php

namespace Tests\Feature\Order;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Inventory\Models\StockLevel;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.book',
            'orders.approve',
            'orders.convert',
            'orders.manage',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    public function test_salesperson_can_book_an_order(): void
    {
        $user = $this->userWithPermissions([
            'orders.view',
            'orders.create',
            'orders.book',
        ]);

        [$customer, $product, $uom] = $this->catalog();

        PriceMaster::create([
            'customer_type_id' => $customer->customer_type_id,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'rate' => 100,
            'min_qty' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('orders.store'), [
                'customer_id' => $customer->id,
                'order_date' => now()->toDateString(),
                'discount_amount' => 0,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'uom_id' => $uom->id,
                        'quantity' => 2,
                        'unit_price' => 100,
                    ],
                ],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'status' => 'pending',
            'grand_total' => 200,
            'created_by_name' => $user->name,
        ]);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'status' => 'pending',
            'created_by_name' => $user->name,
        ]);
    }

    public function test_salesperson_cannot_approve_order(): void
    {
        $user = $this->userWithPermissions([
            'orders.view',
            'orders.create',
            'orders.book',
        ]);

        [$customer] = $this->catalog();

        $order = Order::create([
            'order_no' => 'ORD-TEST-0001',
            'customer_id' => $customer->id,
            'salesperson_id' => $user->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 100,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('orders.approve', $order));

        $response->assertForbidden();
    }

    public function test_duplicate_product_and_uom_lines_are_rejected(): void
    {
        $user = $this->userWithPermissions([
            'orders.view',
            'orders.create',
            'orders.book',
        ]);

        [$customer, $product, $uom] = $this->catalog();

        $response = $this
            ->actingAs($user)
            ->post(route('orders.store'), [
                'customer_id' => $customer->id,
                'order_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $product->id,
                        'uom_id' => $uom->id,
                        'quantity' => 1,
                    ],
                    [
                        'product_id' => $product->id,
                        'uom_id' => $uom->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors(
            'items.1.product_id'
        );
    }

    public function test_pending_order_can_be_approved(): void
    {
        $manager = $this->userWithPermissions([
            'orders.view',
            'orders.approve',
            'orders.manage',
        ]);

        [$customer] = $this->catalog();

        $order = Order::create([
            'order_no' => 'ORD-TEST-0003',
            'customer_id' => $customer->id,
            'salesperson_id' => $manager->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 100,
        ]);

        $response = $this
            ->actingAs($manager)
            ->post(route('orders.approve', $order));

        $response->assertSessionHas('status');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'approved',
            'approved_by' => $manager->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'approved',
            // 'approved_by' => $manager->id,
            'approved_by_name' => $manager->name,
        ]);
    }

    protected function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test-' . uniqid() . '@example.com',
        ]);

        $role = Role::firstOrCreate([
            'name' => 'test-role-' . uniqid(),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);

        $user->assignRole($role);

        return $user;
    }

    public function test_approved_order_can_be_converted_to_invoice_only_once(): void
    {
        $manager = $this->userWithPermissions([
            'orders.view',
            'orders.approve',
            'orders.convert',
            'orders.manage',
        ]);

        [$customer, $product, $uom] = $this->catalog();

        StockLevel::create([
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'quantity' => 10,
        ]);

        PriceMaster::create([
            'customer_type_id' => $customer->customer_type_id,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'rate' => 100,
            'min_qty' => null,
        ]);

        $createResponse = $this
            ->actingAs($manager)
            ->post(route('orders.store'), [
                'customer_id' => $customer->id,
                'order_date' => now()->toDateString(),
                'discount_amount' => 0,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'uom_id' => $uom->id,
                        'quantity' => 2,
                        'unit_price' => 100,
                    ],
                ],
            ]);

        $createResponse->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();

        $this
            ->actingAs($manager)
            ->post(route('orders.approve', $order), [
                'approved_by_name' => $manager->name,
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame('approved', $order->status);

        $response = $this
            ->actingAs($manager)
            ->post(route('orders.convert', $order), [
                'converted_by_name' => $manager->name,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('invoices', 1);

        $invoice = Invoice::query()
            ->where('order_id', $order->id)
            ->firstOrFail();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'converted',
            'converted_by_name' => $manager->name,
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'grand_total' => 200,
        ]);

        $this->assertDatabaseCount('invoice_items', 1);

        $secondResponse = $this
            ->actingAs($manager)
            ->post(route('orders.convert', $order->fresh()), [
                'converted_by_name' => $manager->name,
            ]);

        $secondResponse->assertRedirect();

        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);

        $this->assertDatabaseHas('stock_levels', [
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'quantity' => 8,
        ]);
    }
        protected function catalog(): array
        {
            $customerType = CustomerType::create([
                'name' => 'Retail',
                'code' => 'RETAIL',
            ]);

            $uom = Uom::create([
                'name' => 'Piece',
                'code' => 'PCS',
            ]);

            $product = Product::create([
                'name' => 'Test Product',
                'sku' => 'TEST-001',
                'base_uom_id' => $uom->id,
                'is_active' => true,
            ]);

            ProductUom::create([
                'product_id' => $product->id,
                'uom_id' => $uom->id,
                'conversion_factor' => 1,
                'is_base' => true,
            ]);

            $customer = Customer::create([
                'customer_type_id' => $customerType->id,
                'name' => 'Test Customer',
                'code' => 'CUST-001',
                'is_active' => true,
            ]);

            return [$customer, $product, $uom];
        }
    }