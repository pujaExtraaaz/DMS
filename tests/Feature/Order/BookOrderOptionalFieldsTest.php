<?php

namespace Tests\Feature\Order;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookOrderOptionalFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $salesperson;
    protected Company $company;
    protected Customer $customer;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $salespersonRole = Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sales-manager', 'guard_name' => 'web']);

        $perms = [
            'orders.create',
            'orders.edit',
            'orders.book',
            'orders.view',
            'customers.view',
        ];

        foreach ($perms as $perm) {
            $p = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $superAdminRole->givePermissionTo($p);
        }

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TC-OPT-01',
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
            'name' => 'Admin User',
        ]);
        $this->adminUser->assignRole($superAdminRole);

        $this->salesperson = User::create([
            'company_id' => $this->company->id,
            'name' => 'Kapil Dhir',
            'email' => 'kapil.dhir@dms.test',
            'password' => bcrypt('password'),
        ]);
        $this->salesperson->assignRole($salespersonRole);

        $customerType = CustomerType::create([
            'name' => 'Retailer',
            'code' => 'RET-OPT',
            'discount_percentage' => 0,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'name' => 'Acme Corporation',
            'code' => 'CUST-OPT-001',
            'credit_days' => 30,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Smart Energy Meter',
            'sku' => 'SEM-100',
            'serial_no' => 'PRD-OPT-0001',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        ProductUom::create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'conversion_factor' => 1,
            'is_base' => true,
            'selling_price' => 500.00,
        ]);
    }

    public function test_ui_shows_created_by_fulfilment_mode_and_salesperson_as_optional(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('orders.create'));
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Salesperson Name: no red asterisk, no required attribute
        $this->assertMatchesRegularExpression('/Salesperson Name\s*<\/label>/s', $content);
        $this->assertDoesNotMatchRegularExpression('/Salesperson Name\s*<span class="text-red-500">\*<\/span>/s', $content);
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="salesperson_id"[^>]*required/s', $content);

        // 2. Fulfilment Mode: no red asterisk, no required attribute
        $this->assertMatchesRegularExpression('/Fulfilment Mode\s*<\/label>/s', $content);
        $this->assertDoesNotMatchRegularExpression('/Fulfilment Mode\s*<span class="text-red-500">\*<\/span>/s', $content);
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="fulfilment_mode"[^>]*required/s', $content);

        // 3. Created By: no red asterisk, no required attribute
        $this->assertDoesNotMatchRegularExpression('/Created By\s*<span class="text-red-500">\*<\/span>/s', $content);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="created_by_name"[^>]*required/s', $content);

        // Check edit view as well
        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-OPT-VIEW-01',
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-03',
            'status' => 'pending',
            'subtotal' => 500,
            'grand_total' => 500,
            'salesperson_id' => null,
            'fulfilment_mode' => null,
            'created_by_name' => null,
        ]);

        $editResponse = $this->actingAs($this->adminUser)->get(route('orders.edit', $order));
        $editResponse->assertStatus(200);
        $editContent = $editResponse->getContent();

        $this->assertDoesNotMatchRegularExpression('/Salesperson Name\s*<span class="text-red-500">\*<\/span>/s', $editContent);
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="salesperson_id"[^>]*required/s', $editContent);
        $this->assertDoesNotMatchRegularExpression('/Fulfilment Mode\s*<span class="text-red-500">\*<\/span>/s', $editContent);
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="fulfilment_mode"[^>]*required/s', $editContent);
    }

    public function test_submit_order_with_all_three_fields_blank_saves_successfully(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-03',
            'created_by_name' => '', // blank
            'salesperson_id' => '', // blank
            'fulfilment_mode' => '', // blank
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 500,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $order = Order::latest('id')->firstOrFail();

        // Must NOT automatically assign values
        $this->assertNull($order->salesperson_id);
        $this->assertNull($order->salesperson_name);
        $this->assertNull($order->fulfilment_mode);
        $this->assertNull($order->created_by_name);
        $this->assertEquals(1000.00, (float) $order->grand_total);
    }

    public function test_submit_order_with_one_or_two_fields_populated(): void
    {
        // Case A: Only fulfilment_mode is provided
        $payloadA = [
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-03',
            'created_by_name' => '',
            'salesperson_id' => '',
            'fulfilment_mode' => 'warehouse',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ];

        $responseA = $this->actingAs($this->adminUser)->post(route('orders.store'), $payloadA);
        $responseA->assertSessionHasNoErrors();

        $orderA = Order::latest('id')->firstOrFail();
        $this->assertNull($orderA->salesperson_id);
        $this->assertNull($orderA->salesperson_name);
        $this->assertNull($orderA->created_by_name);
        $this->assertEquals('warehouse', $orderA->fulfilment_mode);

        // Case B: Only salesperson_id and created_by_name provided
        $payloadB = [
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-03',
            'created_by_name' => 'Custom Clerk',
            'salesperson_id' => $this->salesperson->id,
            'fulfilment_mode' => '',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ];

        $responseB = $this->actingAs($this->adminUser)->post(route('orders.store'), $payloadB);
        $responseB->assertSessionHasNoErrors();

        $orderB = Order::latest('id')->firstOrFail();
        $this->assertEquals($this->salesperson->id, $orderB->salesperson_id);
        $this->assertEquals('Kapil Dhir', $orderB->salesperson_name);
        $this->assertEquals('Custom Clerk', $orderB->created_by_name);
        $this->assertNull($orderB->fulfilment_mode);
    }

    public function test_submit_order_with_all_three_fields_populated_saves_correctly(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-03',
            'created_by_name' => 'Office Assistant',
            'salesperson_id' => $this->salesperson->id,
            'fulfilment_mode' => 'van',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 3,
                    'unit_price' => 500,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $order = Order::latest('id')->firstOrFail();
        $this->assertEquals($this->salesperson->id, $order->salesperson_id);
        $this->assertEquals('Kapil Dhir', $order->salesperson_name);
        $this->assertEquals('Office Assistant', $order->created_by_name);
        $this->assertEquals('van', $order->fulfilment_mode);
    }

    public function test_genuinely_required_fields_still_show_validation_errors(): void
    {
        $payload = [
            'customer_id' => '', // mandatory
            'order_date' => '', // mandatory
            'items' => [], // mandatory
        ];

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), $payload);
        $response->assertSessionHasErrors(['customer_id', 'order_date', 'items']);
        $this->assertEquals(0, Order::count());
    }

    public function test_reopen_and_edit_saved_order_with_blank_optional_fields(): void
    {
        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-BLANK-001',
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-03',
            'status' => 'pending',
            'subtotal' => 1000,
            'grand_total' => 1000,
            'salesperson_id' => null,
            'salesperson_name' => null,
            'fulfilment_mode' => null,
            'created_by_name' => null,
        ]);

        // 1. Show page renders cleanly
        $showResponse = $this->actingAs($this->adminUser)->get(route('orders.show', $order));
        $showResponse->assertStatus(200);

        // 2. PDF renders cleanly
        $pdfResponse = $this->actingAs($this->adminUser)->get(route('orders.pdf', ['q' => $order->order_no]));
        $pdfResponse->assertStatus(200);

        // 3. Edit page renders cleanly
        $editResponse = $this->actingAs($this->adminUser)->get(route('orders.edit', $order));
        $editResponse->assertStatus(200);

        // 4. Update order with blank optional fields
        $updatePayload = [
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-03',
            'salesperson_id' => '',
            'fulfilment_mode' => '',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 4,
                    'unit_price' => 500,
                ],
            ],
        ];

        $updateResponse = $this->actingAs($this->adminUser)->put(route('orders.update', $order), $updatePayload);
        $updateResponse->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertNull($order->salesperson_id);
        $this->assertNull($order->salesperson_name);
        $this->assertNull($order->fulfilment_mode);
        $this->assertEquals(2000.00, (float) $order->grand_total);
    }
}

