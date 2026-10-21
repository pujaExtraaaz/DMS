<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyBusinessGroupLinkTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected CustomerType $customerType;
    protected BusinessGroup $groupA;
    protected BusinessGroup $groupB;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->groupA = BusinessGroup::create([
            'name' => 'Tata Sons Group',
            'code' => 'TATA-GRP',
            'industry' => 'Conglomerate',
            'is_active' => true,
        ]);

        $this->groupB = BusinessGroup::create([
            'name' => 'Reliance Enterprises',
            'code' => 'RELIANCE-GRP',
            'industry' => 'Petrochemicals & Retail',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'business_group_id' => $this->groupA->id,
            'name' => 'Tata Motors Ltd',
            'code' => 'TATAMOT',
            'company_type' => 'Public Limited',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Party Admin',
            'email' => 'party.admin@example.com',
        ]);
        $this->user->assignRole($role);

        $this->customerType = CustomerType::create([
            'name' => 'Regular Dealer',
            'code' => 'REG-DLR',
            'is_active' => true,
        ]);
    }

    public function test_create_party_page_renders_business_group_field_with_add_button(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('masters.customers.create'));

        $response->assertStatus(200);
        $response->assertSee('Business Group', false);
        $response->assertSee('+ Add', false);
        $response->assertSee(route('organization.business-groups.index'), false);
        $response->assertSee('Tata Sons Group', false);
        $response->assertSee('TATA-GRP', false);
        $response->assertSee('Reliance Enterprises', false);
        $response->assertSee('RELIANCE-GRP', false);
        $response->assertSee('name="business_group_id"', false);

        // Dropdown positioning & anchoring assertions
        $response->assertSee('x-ref="businessGroupContainer"', false);
        $response->assertSee('x-ref="bgTriggerButton"', false);
        $response->assertSee('top-full mt-1.5', false);
        $response->assertSee('bottom-full mb-1.5', false);
        $response->assertSee('dropdownPlacement', false);
        $response->assertSee('closeBusinessGroupDropdown()', false);
        $response->assertSee('updateDropdownPlacement()', false);
    }

    public function test_business_groups_api_endpoint_returns_json_and_filters_by_name_and_code(): void
    {
        // 1. All active groups
        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.business-groups'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonFragment(['name' => 'Tata Sons Group', 'code' => 'TATA-GRP']);
        $response->assertJsonFragment(['name' => 'Reliance Enterprises', 'code' => 'RELIANCE-GRP']);

        // 2. Search by name
        $searchNameResponse = $this->actingAs($this->user)
            ->getJson(route('masters.customers.business-groups', ['search' => 'Tata']));
        $searchNameResponse->assertStatus(200);
        $searchNameResponse->assertJsonFragment(['name' => 'Tata Sons Group']);
        $searchNameResponse->assertJsonMissing(['name' => 'Reliance Enterprises']);

        // 3. Search by code
        $searchCodeResponse = $this->actingAs($this->user)
            ->getJson(route('masters.customers.business-groups', ['search' => 'RELIANCE']));
        $searchCodeResponse->assertStatus(200);
        $searchCodeResponse->assertJsonFragment(['code' => 'RELIANCE-GRP']);
        $searchCodeResponse->assertJsonMissing(['code' => 'TATA-GRP']);
    }

    public function test_can_create_party_with_selected_business_group(): void
    {
        $payload = [
            'name' => 'Apex Auto Distributing Corp',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'business_group_id' => $this->groupA->id,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9820011223',
            'email' => 'apex.auto@example.com',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('masters.customers.store'), $payload);

        $response->assertRedirect(route('masters.customers.index'));

        $party = Customer::where('name', 'Apex Auto Distributing Corp')->firstOrFail();
        $this->assertEquals($this->groupA->id, $party->business_group_id);
        $this->assertEquals('Tata Sons Group', $party->businessGroup->name);
        $this->assertTrue($this->groupA->customers->contains($party));
    }

    public function test_business_group_field_is_optional_on_party_creation(): void
    {
        $payload = [
            'name' => 'Independent Retailer',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'business_group_id' => null,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9820099887',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('masters.customers.store'), $payload);

        $response->assertRedirect(route('masters.customers.index'));

        $party = Customer::where('name', 'Independent Retailer')->firstOrFail();
        $this->assertNull($party->business_group_id);
        $this->assertNull($party->businessGroup);
    }

    public function test_validation_fails_when_submitting_non_existent_business_group_id(): void
    {
        $payload = [
            'name' => 'Invalid Group Party',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'business_group_id' => 999999, // non-existent
            'customer_type_id' => $this->customerType->id,
            'phone' => '9820000000',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->from(route('masters.customers.create'))
            ->post(route('masters.customers.store'), $payload);

        $response->assertRedirect(route('masters.customers.create'));
        $response->assertSessionHasErrors(['business_group_id']);
    }

    public function test_edit_party_form_populates_persisted_business_group(): void
    {
        $party = Customer::create([
            'name' => 'Southern Logistics Ltd',
            'code' => 'SL-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'business_group_id' => $this->groupB->id,
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('masters.customers.edit', $party));

        $response->assertStatus(200);
        $response->assertSee('Reliance Enterprises', false);
        $response->assertSee('RELIANCE-GRP', false);
        $response->assertSee((string) $this->groupB->id, false);
    }

    public function test_can_update_party_business_group(): void
    {
        $party = Customer::create([
            'name' => 'Northern Trading Corp',
            'code' => 'NT-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'business_group_id' => $this->groupA->id,
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        // Reassign to group B
        $response = $this->actingAs($this->user)
            ->put(route('masters.customers.update', $party), [
                'name' => 'Northern Trading Corp',
                'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
                'business_group_id' => $this->groupB->id,
                'customer_type_id' => $this->customerType->id,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('masters.customers.index'));
        $this->assertEquals($this->groupB->id, $party->fresh()->business_group_id);

        // Clear business group to null
        $response = $this->actingAs($this->user)
            ->put(route('masters.customers.update', $party), [
                'name' => 'Northern Trading Corp',
                'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
                'business_group_id' => null,
                'customer_type_id' => $this->customerType->id,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('masters.customers.index'));
        $this->assertNull($party->fresh()->business_group_id);
    }

    public function test_party_index_displays_linked_business_group_name(): void
    {
        Customer::create([
            'name' => 'Delta Aerospace Parts',
            'code' => 'DAP-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'business_group_id' => $this->groupA->id,
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('masters.customers.index'));

        $response->assertStatus(200);
        $response->assertSee('Delta Aerospace Parts');
        $response->assertSee('Tata Sons Group');
    }

    public function test_business_groups_page_displays_return_to_party_banner_when_return_url_present(): void
    {
        $returnUrl = route('masters.customers.create');
        $response = $this->actingAs($this->user)
            ->get(route('organization.business-groups.index', ['return_url' => $returnUrl]));

        $response->assertStatus(200);
        $response->assertSee('Returning to Party Form', false);
        $response->assertSee($returnUrl, false);
        $response->assertSee('Back to Party Form', false);
    }
}

