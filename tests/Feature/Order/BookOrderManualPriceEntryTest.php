<?php

namespace Tests\Feature\Order;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookOrderManualPriceEntryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customer;
    protected Product $product1;
    protected Product $product2;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        foreach (['orders.create', 'orders.book', 'orders.edit', 'orders.view'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->company = Company::create([
            'name' => 'Avit Digital Corp',
            'code' => 'AVIT',
            'is_active' => true,
        ]);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_closed' => false,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);

        $customerType = CustomerType::create([
            'name' => 'Wholesaler',
            'code' => 'WHL',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'name' => 'Pinnacle Enterprises',
            'code' => 'PINN-001',
            'phone' => '9876543210',
            'customer_type_id' => $customerType->id,
            'credit_days' => 30,
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product1 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Digital Controller A1',
            'sku' => 'DCA-01',
            'serial_no' => 'PRD-DCA-01',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 500.00,
            'tax_rate' => 18.0,
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Optical Sensor X',
            'sku' => 'OSX-02',
            'serial_no' => 'PRD-OSX-02',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 200.00,
            'tax_rate' => 12.0,
            'is_active' => true,
        ]);

        // Seed default price master for product 1
        PriceMaster::create([
            'customer_type_id' => $customerType->id,
            'product_id' => $this->product1->id,
            'uom_id' => $this->uom->id,
            'price' => 500.00,
            'min_quantity' => 1,
            'is_active' => true,
        ]);
    }

    public function test_book_order_views_render_editable_price_input_without_readonly(): void
    {
        $response = $this->actingAs($this->user)->get(route('orders.create'));
        $response->assertOk();
        $content = $response->getContent();

        // Must have editable price input with @input="priceChanged(row)"
        $this->assertStringContainsString('priceChanged(row)', $content);
        $this->assertStringContainsString('x-model="row.unitPrice"', $content);
        $this->assertStringNotContainsString('Prices are always recalculated on the server when the order is saved', $content);
        $this->assertStringNotContainsString('The displayed price is only a convenience preview', $content);

        // Edit view check
        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-TEST-001',
            'customer_id' => $this->customer->id,
            'order_date' => Carbon::today()->toDateString(),
            'subtotal' => 500.00,
            'discount_amount' => 0,
            'tax_amount' => 90.00,
            'grand_total' => 590.00,
            'status' => 'pending',
            'credit_check_status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $editResponse = $this->actingAs($this->user)->get(route('orders.edit', $order));
        $editResponse->assertOk();
        $editContent = $editResponse->getContent();

        $this->assertStringContainsString('priceChanged(row)', $editContent);
        $this->assertStringContainsString('x-model="row.unitPrice"', $editContent);
        $this->assertStringNotContainsString('Prices are recalculated on the server upon saving', $editContent);
    }

    public function test_user_can_create_order_with_manual_price_different_from_catalog(): void
    {
        // Default catalog price is 500.00, but user enters custom manual price of 420.50
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(30)->toDateString(),
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 420.50, // Manual price entry
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('orders.store'), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertCount(1, $order->items);

        $item = $order->items->first();
        $this->assertEquals(420.50, (float) $item->unit_price);
        $this->assertEquals(841.00, (float) $item->line_total); // 2 * 420.50
        $this->assertEquals(841.00, (float) $order->subtotal);

        // Tax 18% on 841.00 = 151.38
        $this->assertEquals(151.38, (float) $order->tax_amount);
        $this->assertEquals(992.38, (float) $order->grand_total);
    }

    public function test_user_can_edit_order_and_change_price_manually(): void
    {
        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-EDIT-001',
            'customer_id' => $this->customer->id,
            'order_date' => Carbon::today()->toDateString(),
            'subtotal' => 500.00,
            'discount_amount' => 0,
            'tax_amount' => 90.00,
            'grand_total' => 590.00,
            'status' => 'pending',
            'credit_check_status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $order->items()->create([
            'product_id' => $this->product1->id,
            'uom_id' => $this->uom->id,
            'quantity' => 1,
            'unit_price' => 500.00,
            'line_total' => 500.00,
        ]);

        // Update with modified manual price of 350.00
        $updatePayload = [
            'customer_id' => $this->customer->id,
            'order_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(30)->toDateString(),
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 3,
                    'unit_price' => 350.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->put(route('orders.update', $order), $updatePayload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $order->refresh();
        $item = $order->items->first();
        $this->assertEquals(350.00, (float) $item->unit_price);
        $this->assertEquals(1050.00, (float) $item->line_total); // 3 * 350
        $this->assertEquals(1050.00, (float) $order->subtotal);
        $this->assertEquals(189.00, (float) $order->tax_amount); // 18% of 1050
        $this->assertEquals(1239.00, (float) $order->grand_total);
    }

    public function test_reopening_order_in_edit_view_preserves_manually_entered_price(): void
    {
        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-PERSIST-001',
            'customer_id' => $this->customer->id,
            'order_date' => Carbon::today()->toDateString(),
            'subtotal' => 640.00,
            'discount_amount' => 0,
            'tax_amount' => 115.20,
            'grand_total' => 755.20,
            'status' => 'pending',
            'credit_check_status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $order->items()->create([
            'product_id' => $this->product1->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_price' => 320.00, // custom price
            'line_total' => 640.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('orders.edit', $order));
        $response->assertOk();

        $viewData = $response->viewData('initialItems');
        $this->assertNotEmpty($viewData);
        $this->assertEquals(320.00, (float) $viewData[0]['unit_price']);

        $content = $response->getContent();
        $this->assertStringContainsString('320', $content);
    }

    public function test_manual_prices_with_discount_and_advance_calculation(): void
    {
        // Item 1: 2 * 100 = 200 (tax 18%)
        // Item 2: 1 * 200 = 200 (tax 12%)
        // Subtotal = 400
        // Discount = 40 (10%)
        // Item 1 discounted line = 180, tax 18% = 32.40
        // Item 2 discounted line = 180, tax 12% = 21.60
        // Total Tax = 54.00
        // Grand Total = 400 - 40 + 54 = 414.00
        // Advance Amount = 150.00
        // Balance = 414.00 - 150.00 = 264.00
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(30)->toDateString(),
            'discount_amount' => 40.00,
            'advance_amount' => 150.00,
            'payment_terms' => '50% advance, balance on delivery',
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 100.00,
                ],
                [
                    'product_id' => $this->product2->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 200.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('orders.store'), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals(400.00, (float) $order->subtotal);
        $this->assertEquals(40.00, (float) $order->discount_amount);
        $this->assertEquals(54.00, (float) $order->tax_amount);
        $this->assertEquals(414.00, (float) $order->grand_total);
        $this->assertEquals(150.00, (float) $order->advance_amount);
        $this->assertEquals(264.00, (float) $order->balance_amount);
        $this->assertEquals(36.23, (float) $order->advance_percentage); // 150 / 414 * 100
    }

    public function test_negative_unit_price_is_rejected_by_validation(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(30)->toDateString(),
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => -25.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('orders.store'), $payload);
        $response->assertSessionHasErrors(['items.0.unit_price']);
    }
}

