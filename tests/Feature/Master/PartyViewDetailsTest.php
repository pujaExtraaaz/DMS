<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\PartyBankAccount;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyViewDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected CustomerType $customerType;
    protected BusinessGroup $businessGroup;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->businessGroup = BusinessGroup::create([
            'name' => 'Apex Conglomerate',
            'code' => 'APEX-GRP',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'business_group_id' => $this->businessGroup->id,
            'name' => 'Apex Distribution Corp',
            'code' => 'APEX-DIST',
            'company_type' => 'Private Limited',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Admin Manager',
            'email' => 'admin@apex.test',
        ]);
        $this->user->assignRole($role);

        $this->customerType = CustomerType::create([
            'name' => 'Wholesaler / Distributor',
            'code' => 'WHOLESALE',
            'is_active' => true,
        ]);

        $this->area = Area::create([
            'name' => 'North District Industrial Zone',
            'code' => 'ND-IZ',
            'is_active' => true,
        ]);
    }

    public function test_parties_listing_displays_view_action_in_correct_order(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Sunrise Trading Co',
            'code' => 'CUST-SUN-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'phone' => '9876543210',
            'gstin' => '27ABCDE1234F1Z5',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.customers.index'));
        $response->assertOk();

        $content = $response->getContent();

        // 1. View button and icon should be present
        $this->assertStringContainsString('data-view-party="' . $customer->id . '"', $content);
        $this->assertStringContainsString(route('masters.customers.show', $customer), $content);

        // 2. Edit and Delete actions should be present
        $this->assertStringContainsString('data-edit-party="' . $customer->id . '"', $content);
        $this->assertStringContainsString('data-delete-customer="' . $customer->id . '"', $content);

        // 3. Verify order: View appears before Edit, Edit appears before Delete
        $posView = strpos($content, 'data-view-party="' . $customer->id . '"');
        $posEdit = strpos($content, 'data-edit-party="' . $customer->id . '"');
        $posDelete = strpos($content, 'data-delete-customer="' . $customer->id . '"');

        $this->assertNotFalse($posView, 'View action must exist in row');
        $this->assertNotFalse($posEdit, 'Edit action must exist in row');
        $this->assertNotFalse($posDelete, 'Delete action must exist in row');

        $this->assertLessThan($posEdit, $posView, 'View action must appear before Edit action');
        $this->assertLessThan($posDelete, $posEdit, 'Edit action must appear before Delete action');
    }

    public function test_view_action_opens_party_details_page_successfully(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'business_group_id' => $this->businessGroup->id,
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'name' => 'Trojan Distributors Pvt Ltd',
            'trade_name' => 'Trojan Supplies',
            'code' => 'CUST-TRJ-002',
            'party_type' => Customer::PARTY_TYPE_BOTH,
            'phone' => '9890123456',
            'email' => 'contact@trojandistributors.com',
            'gstin' => '27AAACT2727Q1ZW',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'credit_limit' => 500000.00,
            'credit_days' => 30,
            'credit_status' => 'active',
            'interest_rate' => 18.00,
            'payment_terms' => '30 Days Net from Invoice Date',
            'credit_notes' => 'Key partner distributor for western region.',
            'is_active' => true,
        ]);

        // Add an address
        PartyAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Central Hub & Warehouse',
            'type' => 'both',
            'contact_person' => 'Ramesh Gupta',
            'contact_phone' => '9890123450',
            'address_line_1' => 'Plot 42, MIDC Industrial Estate',
            'address_line_2' => 'Near Central Plaza',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'is_default_billing' => true,
            'is_default_delivery' => true,
            'is_active' => true,
        ]);

        // Add a bank account
        PartyBankAccount::create([
            'customer_id' => $customer->id,
            'bank_name' => 'State Bank of India',
            'account_holder_name' => 'Trojan Distributors Pvt Ltd',
            'account_number' => '12345678901234',
            'ifsc_code' => 'SBIN0001234',
            'branch_name' => 'Nariman Point',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.customers.show', $customer));
        $response->assertOk();

        // 1. Verify Header and Navigation
        $response->assertSeeText('Back To Parties');
        $response->assertSeeText('Edit Party');
        $response->assertSee(route('masters.customers.index'));
        $response->assertSee(route('masters.customers.edit', $customer));

        // 2. Verify Basic details
        $response->assertSeeText('Trojan Distributors Pvt Ltd');
        $response->assertSeeText('Trojan Supplies');
        $response->assertSeeText('CUST-TRJ-002');
        $response->assertSeeText('Wholesaler / Distributor');
        $response->assertSeeText('Apex Conglomerate');

        // 3. Verify Contact details
        $response->assertSeeText('9890123456');
        $response->assertSeeText('contact@trojandistributors.com');
        $response->assertSeeText('Ramesh Gupta');

        // 4. Verify Tax details
        $response->assertSeeText('27AAACT2727Q1ZW');
        $response->assertSeeText('AAACT2727Q'); // PAN
        $response->assertSeeText('Maharashtra');
        $response->assertSeeText('400001');

        // 5. Verify Business details
        $response->assertSeeText('500,000.00');
        $response->assertSeeText('30 Days');
        $response->assertSeeText('18.00%');
        $response->assertSeeText('30 Days Net From Invoice Date');

        // 6. Verify Address details
        $response->assertSeeText('Central Hub & Warehouse');
        $response->assertSeeText('Plot 42, MIDC Industrial Estate');

        // 7. Verify Bank details (Masked account number!)
        $response->assertSeeText('State Bank Of India');
        $response->assertSeeText('SBIN0001234');
        $response->assertSeeText('Nariman Point');
        $response->assertSeeText('1234'); // last 4 digits
        $response->assertDontSee('12345678901234'); // Full plaintext account number must be masked

        // 8. Verify Notes
        $response->assertSeeText('Key Partner Distributor For Western Region.');
    }

    public function test_missing_fields_render_dash_placeholder(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Minimal Party Org',
            'code' => 'MIN-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.customers.show', $customer));
        $response->assertOk();

        // Should render — for unpopulated fields without throwing any errors
        $response->assertSeeText('—');
        $response->assertSeeText('Minimal Party Org');
        $response->assertSeeText('MIN-001');
    }

    public function test_nonexistent_or_deleted_party_returns_404(): void
    {
        // 1. Non-existent ID
        $response = $this->actingAs($this->user)->get('/masters/customers/999999');
        $response->assertNotFound();

        // 2. Soft-deleted customer
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Deleted Party',
            'code' => 'DEL-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);
        $customer->delete();

        $deletedResponse = $this->actingAs($this->user)->get(route('masters.customers.show', $customer));
        $deletedResponse->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_access_party_details(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Protected Party',
            'code' => 'PROT-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $response = $this->get(route('masters.customers.show', $customer));
        $response->assertRedirect(route('login'));
    }

    public function test_show_endpoint_supports_json_request(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'API Party',
            'code' => 'API-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('masters.customers.show', $customer));
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'party' => [
                    'id' => $customer->id,
                    'name' => 'API Party',
                    'code' => 'API-001',
                ],
            ]);
    }
}
