<?php

namespace Tests\Feature\Order;

use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockReservation;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderEditAndDeleteTest extends TestCase
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
            'orders.delete',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sales-manager', 'guard_name' => 'web']);
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

        $product1 = Product::create([
            'name' => 'Product Alpha',
            'sku' => 'SKU-ALPHA',
            'serial_no' => 'SN-001',
            'base_uom_id' => $uom->id,
            'is_active' => true,
        ]);

        ProductUom::create([
            'product_id' => $product1->id,
            'uom_id' => $uom->id,
            'conversion_factor' => 1,
            'is_base' => true,
        ]);

        $product2 = Product::create([
            'name' => 'Product Beta',
            'sku' => 'SKU-BETA',
            'serial_no' => 'SN-002',
            'base_uom_id' => $uom->id,
            'is_active' => true,
        ]);

        ProductUom::create([
            'product_id' => $product2->id,
            'uom_id' => $uom->id,
            'conversion_factor' => 1,
            'is_base' => true,
        ]);

        $customer = Customer::create([
            'customer_type_id' => $customerType->id,
            'name' => 'Acme Corporation',
            'code' => 'CUST-ACME',
            'is_active' => true,
        ]);

        PriceMaster::create([
            'customer_type_id' => $customerType->id,
            'product_id' => $product1->id,
            'uom_id' => $uom->id,
            'rate' => 100,
            'min_qty' => null,
        ]);

        PriceMaster::create([
            'customer_type_id' => $customerType->id,
            'product_id' => $product2->id,
            'uom_id' => $uom->id,
            'rate' => 200,
            'min_qty' => null,
        ]);

        return [$customer, $product1, $product2, $uom];
    }

    public function test_orders_index_displays_view_edit_and_delete_actions_and_confirmation_modal(): void
    {
        $user = $this->userWithPermissions([
            'orders.view',
            'orders.edit',
            'orders.delete',
        ]);

        [$customer, $product1, , $uom] = $this->catalog();

        $order = Order::create([
            'order_no' => 'ORD-INDEX-001',
            'customer_id' => $customer->id,
            'salesperson_id' => $user->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 100,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'uom_id' => $uom->id,
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('orders.index'));

        $response->assertOk();
        $response->assertSee('ORD-INDEX-001');
        $response->assertSee('View');
        $response->assertSee('Edit');
        $response->assertSee('Delete');
        $response->assertSee('Delete Order?');
        $response->assertSee('Are you sure you want to delete this order? Related stock reservations or transactions may be affected.');
        $response->assertSee('Cancel');
    }

    public function test_user_can_edit_draft_or_pending_order_and_totals_are_recalculated(): void
    {
        $user = $this->userWithPermissions([
            'orders.view',
            'orders.edit',
            'orders.manage',
        ]);

        [$customer, $product1, $product2, $uom] = $this->catalog();

        $order = Order::create([
            'order_no' => 'ORD-EDIT-001',
            'customer_id' => $customer->id,
            'salesperson_id' => $user->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 100,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'uom_id' => $uom->id,
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        // Access edit view
        $editViewResponse = $this
            ->actingAs($user)
            ->get(route('orders.edit', $order));

        $editViewResponse->assertOk();
        $editViewResponse->assertSee('ORD-EDIT-001');

        // Submit update with new items and discount
        $updateResponse = $this
            ->actingAs($user)
            ->put(route('orders.update', $order), [
                'customer_id' => $customer->id,
                'order_date' => now()->toDateString(),
                'discount_amount' => 50,
                'items' => [
                    [
                        'product_id' => $product1->id,
                        'uom_id' => $uom->id,
                        'quantity' => 2, // 2 * 100 = 200
                        'unit_price' => 100,
                    ],
                    [
                        'product_id' => $product2->id,
                        'uom_id' => $uom->id,
                        'quantity' => 1, // 1 * 200 = 200
                        'unit_price' => 200,
                    ],
                ],
            ]);

        $updateResponse->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame('ORD-EDIT-001', $order->order_no);
        $this->assertEquals(400.00, (float) $order->subtotal);
        $this->assertEquals(50.00, (float) $order->discount_amount);
        $this->assertEquals(350.00, (float) $order->grand_total);
        $this->assertCount(2, $order->items);
    }

    public function test_user_can_edit_approved_order_and_reservations_are_reconciled(): void
    {
        $user = $this->userWithPermissions([
            'orders.view',
            'orders.edit',
            'orders.approve',
            'orders.manage',
        ]);

        [$customer, $product1, , $uom] = $this->catalog();

        // Put stock on hand
        StockLevel::create([
            'product_id' => $product1->id,
            'uom_id' => $uom->id,
            'quantity' => 20,
        ]);

        $order = Order::create([
            'order_no' => 'ORD-RES-001',
            'customer_id' => $customer->id,
            'salesperson_id' => $user->id,
            'order_date' => now()->toDateString(),
            'fulfilment_mode' => 'warehouse',
            'status' => 'pending',
            'subtotal' => 200,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 200,
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'uom_id' => $uom->id,
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
        ]);

        // Approve order so it creates reservations
        $this->actingAs($user)->post(route('orders.approve', $order))->assertRedirect();
        $order->refresh();
        $this->assertSame('approved', $order->status);

        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'status' => 'active',
        ]);

        // Now edit the approved order to 5 qty
        $this->actingAs($user)
            ->put(route('orders.update', $order), [
                'customer_id' => $customer->id,
                'order_date' => now()->toDateString(),
                'fulfilment_mode' => 'warehouse',
                'discount_amount' => 0,
                'items' => [
                    [
                        'product_id' => $product1->id,
                        'uom_id' => $uom->id,
                        'quantity' => 5,
                        'unit_price' => 100,
                    ],
                ],
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertEquals(500.00, (float) $order->grand_total);

        // Previous reservations are released and new reservation is active for 5 qty
        $activeReservations = StockReservation::query()
            ->where('order_id', $order->id)
            ->where('status', 'active')
            ->sum('quantity');

        $this->assertEquals(5.0, (float) $activeReservations);
    }

    public function test_converted_order_cannot_be_edited(): void
    {
        $user = $this->userWithPermissions([
            'orders.view',
            'orders.edit',
            'orders.manage',
        ]);

        [$customer, $product1, , $uom] = $this->catalog();

        $order = Order::create([
            'order_no' => 'ORD-CONV-001',
            'customer_id' => $customer->id,
            'salesperson_id' => $user->id,
            'order_date' => now()->toDateString(),
            'status' => 'converted',
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 100,
        ]);

        // Edit view should redirect with error
        $editResponse = $this
            ->actingAs($user)
            ->get(route('orders.edit', $order));

        $editResponse->assertRedirect(route('orders.show', $order));
        $editResponse->assertSessionHas('error');

        // Update should fail
        $updateResponse = $this
            ->actingAs($user)
            ->put(route('orders.update', $order), [
                'customer_id' => $customer->id,
                'order_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $product1->id,
                        'uom_id' => $uom->id,
                        'quantity' => 1,
                        'unit_price' => 100,
                    ],
                ],
            ]);

        $updateResponse->assertSessionHas('error');
    }

    public function test_cancelled_order_cannot_be_edited(): void
    {
        $user = $this->userWithPermissions([
            'orders.view',
            'orders.edit',
            'orders.manage',
        ]);

        [$customer, $product1, , $uom] = $this->catalog();

        $order = Order::create([
            'order_no' => 'ORD-CANC-001',
            'customer_id' => $customer->id,
            'salesperson_id' => $user->id,
            'order_date' => now()->toDateString(),
            'status' => 'cancelled',
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 100,
        ]);

        $editResponse = $this
            ->actingAs($user)
            ->get(route('orders.edit', $order));

        $editResponse->assertRedirect(route('orders.show', $order));
        $editResponse->assertSessionHas('error');
    }

    public function test_user_can_delete_draft_or_pending_order(): void
    {
        $user = $this->userWithPermissions([
            'orders.view',
            'orders.delete',
        ]);

        [$customer, $product1, , $uom] = $this->catalog();

        $order = Order::create([
            'order_no' => 'ORD-DEL-001',
            'customer_id' => $customer->id,
            'salesperson_id' => $user->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 100,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'uom_id' => $uom->id,
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        $response = $this
            ->actingAs($user)
            ->deleteJson(route('orders.destroy', $order));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
    }

    public function test_user_can_delete_approved_order_and_active_reservations_are_released(): void
    {
        $user = $this->userWithPermissions([
            'orders.view',
            'orders.approve',
            'orders.delete',
            'orders.manage',
        ]);

        [$customer, $product1, , $uom] = $this->catalog();

        StockLevel::create([
            'product_id' => $product1->id,
            'uom_id' => $uom->id,
            'quantity' => 20,
        ]);

        $order = Order::create([
            'order_no' => 'ORD-DEL-APP-001',
            'customer_id' => $customer->id,
            'salesperson_id' => $user->id,
            'order_date' => now()->toDateString(),
            'fulfilment_mode' => 'warehouse',
            'status' => 'pending',
            'subtotal' => 200,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 200,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'uom_id' => $uom->id,
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
        ]);

        // Approve
        $this->actingAs($user)->post(route('orders.approve', $order))->assertRedirect();
        $order->refresh();
        $this->assertSame('approved', $order->status);

        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'quantity' => 2,
            'status' => 'active',
        ]);

        // Delete order
        $response = $this
            ->actingAs($user)
            ->deleteJson(route('orders.destroy', $order));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('stock_reservations', ['order_id' => $order->id]);
    }

    public function test_converted_or_invoiced_order_cannot_be_deleted(): void
    {
        $user = $this->userWithPermissions([
            'orders.view',
            'orders.delete',
            'orders.manage',
        ]);

        [$customer, $product1, , $uom] = $this->catalog();

        $order = Order::create([
            'order_no' => 'ORD-BLOCKED-001',
            'customer_id' => $customer->id,
            'salesperson_id' => $user->id,
            'order_date' => now()->toDateString(),
            'status' => 'converted',
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 100,
        ]);

        $response = $this
            ->actingAs($user)
            ->deleteJson(route('orders.destroy', $order));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_unauthorized_user_cannot_delete_order(): void
    {
        $user = $this->userWithPermissions([
            'orders.view',
        ]);

        [$customer] = $this->catalog();

        $order = Order::create([
            'order_no' => 'ORD-UNAUTH-001',
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
            ->deleteJson(route('orders.destroy', $order));

        $response->assertForbidden();
    }
}
