<?php

namespace Tests\Feature\Order;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
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

class BookOrderCustomerDropdownTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected CustomerType $customerType;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'orders.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'orders.book', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'orders.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'orders.view', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Acme Corporation',
            'code' => 'ACME',
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

        $this->customerType = CustomerType::create([
            'name' => 'Retailer',
            'code' => 'RET',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'sku' => 'SKU-001',
            'serial_no' => 'PRD-TEST-001',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);
    }

    public function test_search_customers_by_full_and_partial_name_case_insensitive_and_trimmed(): void
    {
        Customer::create([
            'name' => 'City Stores Pvt Ltd',
            'code' => 'PTY-1-00005',
            'phone' => '9876588294',
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        // Search by full name
        $response1 = $this->actingAs($this->user)->getJson(route('orders.search.customers', ['q' => 'City Stores Pvt Ltd']));
        $response1->assertOk();
        $response1->assertJsonCount(1);
        $response1->assertJsonPath('0.name', 'City Stores Pvt Ltd');
        $response1->assertJsonPath('0.label', 'City Stores Pvt Ltd - 9876588294');

        // Search by partial name and different case with spaces
        $response2 = $this->actingAs($this->user)->getJson(route('orders.search.customers', ['q' => '  cItY stOreS  ']));
        $response2->assertOk();
        $response2->assertJsonCount(1);
        $response2->assertJsonPath('0.name', 'City Stores Pvt Ltd');
    }

    public function test_search_customers_by_full_and_partial_mobile_number(): void
    {
        Customer::create([
            'name' => 'Deepak Dealer',
            'code' => 'CUST-003',
            'phone' => '9876543210',
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        // Search by full mobile number
        $response1 = $this->actingAs($this->user)->getJson(route('orders.search.customers', ['q' => '9876543210']));
        $response1->assertOk();
        $response1->assertJsonCount(1);
        $response1->assertJsonPath('0.name', 'Deepak Dealer');
        $response1->assertJsonPath('0.label', 'Deepak Dealer - 9876543210');

        // Search by partial mobile number
        $response2 = $this->actingAs($this->user)->getJson(route('orders.search.customers', ['q' => '543210']));
        $response2->assertOk();
        $response2->assertJsonCount(1);
        $response2->assertJsonPath('0.name', 'Deepak Dealer');
    }

    public function test_customer_without_mobile_shows_name_only_without_hyphen(): void
    {
        $cust = Customer::create([
            'name' => 'Alpha Traders',
            'code' => 'ALPHA-001',
            'phone' => null,
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('orders.search.customers', ['q' => 'Alpha']));
        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.name', 'Alpha Traders');
        $response->assertJsonPath('0.label', 'Alpha Traders');
        $this->assertStringNotContainsString('-', $response->json('0.label'));
    }

    public function test_book_order_create_view_displays_customer_name_and_phone_without_internal_codes(): void
    {
        $custWithPhone = Customer::create([
            'name' => 'City Stores Pvt Ltd',
            'code' => 'PTY-INTERNAL-99999',
            'phone' => '9876588294',
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        $custWithoutPhone = Customer::create([
            'name' => 'Solo Merchant',
            'code' => 'SOLO-SECRET-11111',
            'phone' => null,
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('orders.create'));
        $response->assertOk();

        $content = $response->getContent();

        // Check options display format
        $this->assertStringContainsString('City Stores Pvt Ltd - 9876588294', $content);
        $this->assertStringContainsString('Solo Merchant', $content);

        // Ensure internal customer codes are NOT rendered in options or customer labels
        $this->assertStringNotContainsString('PTY-INTERNAL-99999', $content);
        $this->assertStringNotContainsString('SOLO-SECRET-11111', $content);

        // Check searchable customer UI input exists
        $this->assertStringContainsString('id="customer_search_input"', $content);
        $this->assertStringContainsString('filteredCustomers', $content);
    }

    public function test_submitting_order_with_selected_customer_persists_customer_id(): void
    {
        $customer = Customer::create([
            'name' => 'Abc Enterprises',
            'code' => 'ABC-001',
            'phone' => '9876543210',
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'order_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(30)->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                ],
            ],
            'discount_type' => 'flat',
            'discount_value' => 0,
        ];

        $response = $this->actingAs($this->user)->post(route('orders.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
        ]);
    }

    public function test_book_order_edit_view_formats_customer_display_and_updates_successfully(): void
    {
        $customer = Customer::create([
            'name' => 'Metro Retailers',
            'code' => 'METRO-SECRET-CODE',
            'phone' => '9988776655',
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-2026-TEST',
            'customer_id' => $customer->id,
            'order_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(30)->toDateString(),
            'subtotal' => 200,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 200,
            'status' => 'draft',
            'credit_check_status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
        ]);

        $response = $this->actingAs($this->user)->get(route('orders.edit', $order));
        $response->assertOk();

        $content = $response->getContent();

        // Formatted option exists
        $this->assertStringContainsString('Metro Retailers - 9988776655', $content);

        // Internal code not present in options
        $this->assertStringNotContainsString('METRO-SECRET-CODE', $content);

        // Updating the order works cleanly
        $newDueDate = Carbon::today()->addDays(45)->toDateString();
        $updatePayload = [
            'customer_id' => $customer->id,
            'order_date' => Carbon::today()->toDateString(),
            'due_date' => $newDueDate,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 3,
                    'unit_price' => 100,
                ],
            ],
            'discount_type' => 'flat',
            'discount_value' => 0,
        ];

        $updateResponse = $this->actingAs($this->user)->put(route('orders.update', $order), $updatePayload);
        $updateResponse->assertSessionHasNoErrors();
        $updateResponse->assertRedirect();

        $order->refresh();
        $this->assertEquals($customer->id, $order->customer_id);
        $this->assertEquals($newDueDate, $order->due_date->toDateString());
    }

    public function test_show_order_view_renders_phone_instead_of_code(): void
    {
        $customer = Customer::create([
            'name' => 'Elite Supplies',
            'code' => 'ELITE-CODE-HIDDEN',
            'phone' => '9123456780',
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-SHOW-TEST',
            'customer_id' => $customer->id,
            'order_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(30)->toDateString(),
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 100,
            'status' => 'draft',
            'credit_check_status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('orders.show', $order));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('Elite Supplies', $content);
        $this->assertStringContainsString('9123456780', $content);
        $this->assertStringNotContainsString('ELITE-CODE-HIDDEN', $content);
    }

    public function test_search_customers_without_query_returns_active_customers(): void
    {
        Customer::create([
            'name' => 'Active Customer 1',
            'code' => 'CUST-A1',
            'phone' => '9876500001',
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);
        Customer::create([
            'name' => 'Inactive Customer',
            'code' => 'CUST-INACT',
            'phone' => '9876500002',
            'customer_type_id' => $this->customerType->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('orders.search.customers'));
        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $names = collect($data)->pluck('name')->all();
        $this->assertContains('Active Customer 1', $names);
        $this->assertNotContains('Inactive Customer', $names);
    }

    public function test_search_products_without_query_returns_active_products(): void
    {
        Product::create([
            'company_id' => $this->company->id,
            'name' => 'Active Product 2',
            'sku' => 'SKU-002',
            'serial_no' => 'PRD-TEST-002',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);
        Product::create([
            'company_id' => $this->company->id,
            'name' => 'Inactive Product',
            'sku' => 'SKU-003',
            'serial_no' => 'PRD-TEST-003',
            'base_uom_id' => $this->uom->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('orders.search.products'));
        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $names = collect($data)->pluck('name')->all();
        $this->assertContains('Active Product 2', $names);
        $this->assertNotContains('Inactive Product', $names);
    }

    public function test_search_uoms_returns_base_uom_if_product_has_no_product_uoms(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('orders.search.uoms', ['product_id' => $this->product->id]));
        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals((string) $this->uom->id, $data[0]['id']);
        $this->assertEquals('Piece (PCS)', $data[0]['label']);
    }
}
