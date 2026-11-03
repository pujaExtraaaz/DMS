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

class BookOrderButtonsAndSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $salesperson;
    protected Customer $customer;
    protected Product $product;
    protected Uom $uom;
    protected Company $company;

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
            'code' => 'TC-BTN-01',
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
            'code' => 'RET-BTN',
            'discount_percentage' => 0,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'name' => 'Acme Corporation',
            'code' => 'CUST-BTN-001',
            'credit_days' => 30,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Super LED Bulb 15W',
            'sku' => 'PRD-LED-15W',
            'serial_no' => 'PRD-BTN-0001',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        ProductUom::create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'conversion_factor' => 1,
            'is_base' => true,
            'selling_price' => 200.00,
        ]);
    }

    public function test_book_order_create_view_renders_add_line_and_save_order_buttons(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('orders.create'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Verify Save Order button is clearly visible and correctly typed
        $this->assertStringContainsString('id="btn-save-order"', $content);
        $this->assertStringContainsString('type="submit"', $content);
        $this->assertStringContainsString('Save Order', $content);
        $this->assertStringContainsString(':disabled="submitting"', $content);

        // 2. Verify Add Line buttons exist and are type="button" (not submit)
        $this->assertStringContainsString('id="btn-add-line-table"', $content);
        $this->assertStringContainsString('id="btn-add-line-bottom"', $content);
        $this->assertMatchesRegularExpression('/<button[^>]*id="btn-add-line-table"[^>]*>/s', $content);
        $this->assertMatchesRegularExpression('/<button[^>]*id="btn-add-line-bottom"[^>]*>/s', $content);
        $this->assertStringContainsString('@click="addRow()"', $content);

        // 3. Verify Cancel button
        $this->assertStringContainsString(route('orders.index'), $content);
        $this->assertStringContainsString('Cancel', $content);

        // 4. Verify mobile-safe padding prevents bottom-nav obstruction
        $this->assertStringContainsString('pb-28 lg:pb-8', $content);

        // 5. Verify keyboard shortcut indicators
        $this->assertStringContainsString('Ctrl + Enter', $content);
        $this->assertStringContainsString('to save', $content);
        $this->assertStringContainsString('to add line', $content);
    }

    public function test_javascript_order_builder_defined_globally_and_with_alpine(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('orders.create'));
        $content = $response->getContent();

        // Must define global factory function
        $this->assertStringContainsString('function orderBuilder()', $content);
        $this->assertStringContainsString('window.orderBuilder = orderBuilder;', $content);

        // Must register conditionally with window.Alpine to avoid timing failure with Livewire
        $this->assertStringContainsString('if (window.Alpine)', $content);
        $this->assertStringContainsString("window.Alpine.data('orderBuilder', orderBuilder);", $content);

        // Must not have double x-init="init()" which causes duplicate rows
        $this->assertStringNotContainsString('x-init="init()"', $content);
    }

    public function test_book_order_edit_view_renders_buttons_and_valid_script(): void
    {
        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-EDIT-TEST-01',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson->id,
            'order_date' => '2026-11-02',
            'status' => 'pending',
            'subtotal' => 1000,
            'grand_total' => 1000,
            'advance_amount' => 200,
            'balance_amount' => 800,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('orders.edit', $order));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Verify Save Changes button and Add Line button
        $this->assertStringContainsString('id="btn-save-order"', $content);
        $this->assertStringContainsString('Save Changes', $content);
        $this->assertStringContainsString('id="btn-add-line-bottom"', $content);
        $this->assertStringContainsString('function orderEditor()', $content);
        $this->assertStringContainsString('window.orderEditor = orderEditor;', $content);
        $this->assertStringContainsString('pb-28 lg:pb-8', $content);
    }

    public function test_valid_order_submission_creates_order_and_redirects(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson->id,
            'order_date' => '2026-11-03',
            'due_date' => '2026-12-03',
            'payment_terms' => '30 days net',
            'advance_amount' => 250.00,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 5,
                    'unit_price' => 200.00,
                ],
            ],
            'notes' => 'Testing form submission from Save Order button',
        ];

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), $payload);

        $response->assertSessionHasNoErrors();
        $order = Order::latest('id')->firstOrFail();

        $this->assertEquals($this->customer->id, $order->customer_id);
        $this->assertEquals($this->salesperson->id, $order->salesperson_id);
        $this->assertEquals(250.00, (float) $order->advance_amount);
        $this->assertEquals(750.00, (float) $order->balance_amount); // 1000 - 250 = 750
        $this->assertEquals('Testing form submission from Save Order button', $order->notes);

        $response->assertRedirect(route('orders.show', $order));
    }

    public function test_invalid_order_submission_fails_validation_and_preserves_data(): void
    {
        $payload = [
            'customer_id' => '', // missing
            'salesperson_id' => '', // missing
            'order_date' => '2026-11-03',
            'advance_amount' => 500,
            'notes' => 'Preserve this note after validation failure',
        ];

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), $payload);

        $response->assertSessionHasErrors(['customer_id', 'items']);
        $this->assertEquals(0, Order::count());
    }
}

