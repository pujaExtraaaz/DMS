<?php

namespace Tests\Feature\Order;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookOrderAddCustomerRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $salesUser;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles & permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $ordersCreatePerm = Permission::firstOrCreate(['name' => 'orders.create', 'guard_name' => 'web']);
        $ordersBookPerm = Permission::firstOrCreate(['name' => 'orders.book', 'guard_name' => 'web']);
        $ordersViewPerm = Permission::firstOrCreate(['name' => 'orders.view', 'guard_name' => 'web']);
        $customersViewPerm = Permission::firstOrCreate(['name' => 'customers.view', 'guard_name' => 'web']);
        $customersCreatePerm = Permission::firstOrCreate(['name' => 'customers.create', 'guard_name' => 'web']);

        $salesRole = Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);
        $salesRole->givePermissionTo([$ordersCreatePerm, $ordersBookPerm, $ordersViewPerm, $customersViewPerm, $customersCreatePerm]);

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TC-01',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($superAdminRole);

        $this->salesUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->salesUser->assignRole($salesRole);
    }

    public function test_book_order_page_renders_add_customer_link_pointing_to_party_create(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('orders.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check Add Customer link points to masters.customers.create with return_to=orders.create
        $expectedUrl = route('masters.customers.create', ['return_to' => 'orders.create']);
        $this->assertStringContainsString('+ Add Customer', $content);
        $this->assertStringContainsString($expectedUrl, $content);
        $this->assertStringContainsString('saveDraftAndRedirect', $content);
    }

    public function test_party_create_page_preserves_return_to_parameter_and_back_links(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.customers.create', ['return_to' => 'orders.create']));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Form has hidden input return_to
        $this->assertStringContainsString('<input type="hidden" name="return_to" value="orders.create">', $content);

        // 2. Header and Cancel buttons link back to orders.create
        $this->assertMatchesRegularExpression('/Back to Book Order/i', $content);
        $this->assertStringContainsString(route('orders.create'), $content);
    }

    public function test_submitting_party_create_with_return_to_redirects_to_orders_create_with_new_customer(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.customers.store'), [
                'return_to' => 'orders.create',
                'name' => 'Redirected Customer Private Ltd',
                'party_type' => 'customer',
                'state' => 'Maharashtra',
                'city' => 'Mumbai',
                'pincode' => '400001',
                'phone' => '9876543210',
                'email' => 'redirect@customer.test',
                'is_active' => '1',
            ]);

        // Find created customer
        $customer = Customer::where('name', 'Redirected Customer Private Ltd')->first();
        $this->assertNotNull($customer);

        // Redirects to orders.create with customer_id query parameter
        $response->assertRedirect(route('orders.create', ['customer_id' => $customer->id]));
        $response->assertSessionHas('status');
    }

    public function test_submitting_party_create_without_return_to_redirects_to_party_index(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.customers.store'), [
                'name' => 'Standard Customer Ltd',
                'party_type' => 'customer',
                'state' => 'Maharashtra',
                'city' => 'Pune',
                'pincode' => '411001',
                'is_active' => '1',
            ]);

        $customer = Customer::where('name', 'Standard Customer Ltd')->first();
        $this->assertNotNull($customer);

        // Standard flow redirects to masters.customers.index
        $response->assertRedirect(route('masters.customers.index'));
    }

    public function test_party_create_validation_failure_with_return_to_stays_on_form_with_errors(): void
    {
        $response = $this->from(route('masters.customers.create', ['return_to' => 'orders.create']))
            ->actingAs($this->adminUser)
            ->post(route('masters.customers.store'), [
                'return_to' => 'orders.create',
                'name' => '', // Missing name fails validation
                'party_type' => 'customer',
            ]);

        // Should redirect back to masters.customers.create and NOT to orders.create
        $response->assertRedirect(route('masters.customers.create', ['return_to' => 'orders.create']));
        $response->assertSessionHasErrors(['name']);
    }

    public function test_book_order_dropdown_preselects_customer_from_query_parameter(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Auto Selected Customer',
            'code' => 'CUST-AUTO-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('orders.create', ['customer_id' => $customer->id]));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Customer appears and is selected in dropdown
        $this->assertStringContainsString('Auto Selected Customer', $content);
        $this->assertStringContainsString('value="' . $customer->id . '"', $content);
        $this->assertStringContainsString('selected', $content);
    }
}
