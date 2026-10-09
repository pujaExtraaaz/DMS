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

class QuickAddCustomerInOrderTest extends TestCase
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

        $salesRole = Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);
        $salesRole->givePermissionTo([$ordersCreatePerm, $ordersBookPerm, $ordersViewPerm]);

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

    public function test_create_order_page_renders_add_customer_action_and_modal(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('orders.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Verify "+ Add Customer" button is present beside Customer label
        $this->assertStringContainsString('+ Add Customer', $content);
        $this->assertStringContainsString('$dispatch(\'open-quick-add-customer\')', $content);

        // 2. Verify Quick Add Customer modal is included
        $this->assertStringContainsString('Quick Add Customer', $content);
        $this->assertStringContainsString('customer_id', $content);
        $this->assertStringContainsString('quickAddCustomerModal', $content);
        $this->assertStringContainsString('Auto-generated if blank', $content);
    }

    public function test_quick_add_customer_endpoint_creates_customer_and_default_address(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('masters.customers.quick-add'), [
                'name' => 'Acme Corporation',
                'gstin' => '27AAACA1234A1Z5',
                'pan' => 'AAACA1234A',
                'phone' => '9876543210',
                'email' => 'contact@acme.test',
                'state' => 'Maharashtra',
                'city' => 'Mumbai',
                'pincode' => '400001',
                'address' => 'Plot 42, Nariman Point',
                'party_type' => 'sundry_debtors',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'party' => [
                'name' => 'Acme Corporation',
                'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
                'gstin' => '27AAACA1234A1Z5',
                'state' => 'Maharashtra',
                'city' => 'Mumbai',
                'phone' => '9876543210',
                'email' => 'contact@acme.test',
            ],
        ]);

        $partyId = $response->json('party.id');
        $this->assertNotNull($partyId);

        // Verify customer in database
        $customer = Customer::findOrFail($partyId);
        $this->assertEquals('Acme Corporation', $customer->name);
        $this->assertNotEmpty($customer->code);
        $this->assertEquals('27AAACA1234A1Z5', $customer->gstin);
        $this->assertEquals('AAACA1234A', $customer->pan);

        // Verify default address created
        $this->assertCount(1, $customer->addresses);
        $address = $customer->addresses->first();
        $this->assertEquals('Mumbai', $address->city);
        $this->assertEquals('Maharashtra', $address->state);
        $this->assertEquals('400001', $address->pincode);
        $this->assertTrue($address->is_default_billing);
        $this->assertTrue($address->is_default_delivery);
    }

    public function test_sales_user_with_order_create_permission_can_access_quick_add(): void
    {
        $response = $this->actingAs($this->salesUser)
            ->postJson(route('masters.customers.quick-add'), [
                'name' => 'Retail Buyer Ltd',
                'party_type' => 'sundry_debtors',
                'state' => 'Gujarat',
                'city' => 'Surat',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'party' => [
                'name' => 'Retail Buyer Ltd',
                'state' => 'Gujarat',
                'city' => 'Surat',
            ],
        ]);
    }

    public function test_quick_add_validates_required_customer_name(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('masters.customers.quick-add'), [
                'name' => '',
                'party_type' => 'sundry_debtors',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_quick_add_auto_generates_consecutive_unique_codes_if_blank(): void
    {
        $res1 = $this->actingAs($this->adminUser)
            ->postJson(route('masters.customers.quick-add'), [
                'name' => 'Alpha Client',
                'party_type' => 'sundry_debtors',
            ]);
        $res1->assertStatus(200);
        $code1 = $res1->json('party.code');

        $res2 = $this->actingAs($this->adminUser)
            ->postJson(route('masters.customers.quick-add'), [
                'name' => 'Beta Client',
                'party_type' => 'sundry_debtors',
            ]);
        $res2->assertStatus(200);
        $code2 = $res2->json('party.code');

        $this->assertNotEmpty($code1);
        $this->assertNotEmpty($code2);
        $this->assertNotEquals($code1, $code2);
    }

    public function test_gst_lookup_returns_party_details(): void
    {
        $response = $this->actingAs($this->salesUser)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAACA1234A1Z5']));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertNotEmpty($response->json('party.state'));
    }

    public function test_quick_add_with_classification_and_fetches_addresses(): void
    {
        $customerType = CustomerType::create([
            'name' => 'Wholesaler',
            'code' => 'WHL',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->salesUser)
            ->postJson(route('masters.customers.quick-add'), [
                'name' => 'Mega Retailers',
                'customer_type_id' => $customerType->id,
                'party_type' => 'sundry_debtors',
                'state' => 'Karnataka',
                'city' => 'Bengaluru',
                'pincode' => '560001',
                'address' => 'MG Road, Bengaluru',
            ]);

        $response->assertStatus(200);
        $customerId = $response->json('party.id');

        $customer = Customer::findOrFail($customerId);
        $this->assertEquals($customerType->id, $customer->customer_type_id);

        // Verify the customer addresses endpoint that Create Order's Alpine component calls
        $addrResponse = $this->actingAs($this->salesUser)
            ->getJson(route('masters.customers.addresses', ['customer' => $customerId]));

        $addrResponse->assertStatus(200);
        $addrResponse->assertJson([
            'success' => true,
        ]);
        $this->assertCount(1, $addrResponse->json('addresses'));
        $this->assertEquals('Bengaluru', $addrResponse->json('addresses.0.city'));
        $this->assertEquals('Karnataka', $addrResponse->json('addresses.0.state'));
        $this->assertNotNull($addrResponse->json('default_billing_id'));
        $this->assertNotNull($addrResponse->json('default_delivery_id'));
    }
}

