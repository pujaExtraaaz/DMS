<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyContact;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use App\Support\ContactDesignations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyContactDesignationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;
    protected CustomerType $customerType;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);

        $this->company = Company::create([
            'name' => 'Apex Enterprises',
            'code' => 'APEX-01',
            'is_active' => true,
        ]);

        $this->adminUser->update(['company_id' => $this->company->id]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'is_active' => true,
        ]);

        $this->customerType = CustomerType::create([
            'company_id' => $this->company->id,
            'name' => 'Dealer',
            'code' => 'DLR-01',
            'is_active' => true,
        ]);

        $this->area = Area::create([
            'company_id' => $this->company->id,
            'name' => 'Metro Area',
            'code' => 'AREA-METRO',
            'is_active' => true,
        ]);
    }

    public function test_designation_options_contains_all_16_exact_options(): void
    {
        $expected = [
            'Accounts',
            'Architect',
            'ASM',
            'Branch Manager',
            'CEO',
            'Customer',
            'Director',
            'Gen. Manger',
            'Logistics',
            'Marketing',
            'MD',
            'Partner',
            'Purchase Dept.',
            'Salesman',
            'Store Manager',
            'Other',
        ];

        $this->assertSame($expected, ContactDesignations::all());
        $this->assertCount(16, ContactDesignations::all());
        $this->assertTrue(ContactDesignations::isStandard('Accounts'));
        $this->assertTrue(ContactDesignations::isStandard('Gen. Manger'));
        $this->assertFalse(ContactDesignations::isStandard('Other'));
        $this->assertFalse(ContactDesignations::isStandard('Custom Job Title'));
    }

    public function test_contact_designations_resolve_helper(): void
    {
        // Standard designation
        $resStandard = ContactDesignations::resolve('Architect');
        $this->assertSame('Architect', $resStandard['designation']);
        $this->assertSame('', $resStandard['other_designation']);

        // Other keyword
        $resOther = ContactDesignations::resolve('Other');
        $this->assertSame('Other', $resOther['designation']);
        $this->assertSame('', $resOther['other_designation']);

        // Custom free-text previously saved
        $resCustom = ContactDesignations::resolve('Senior Site Engineer');
        $this->assertSame('Other', $resCustom['designation']);
        $this->assertSame('Senior Site Engineer', $resCustom['other_designation']);

        // Blank/null
        $resBlank = ContactDesignations::resolve('');
        $this->assertSame('', $resBlank['designation']);
        $this->assertSame('', $resBlank['other_designation']);
    }

    public function test_create_party_form_contains_designation_dropdown_component_and_options(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('masters.customers.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('dmsContactDesignation', $content);
        $this->assertStringContainsString('designationOptions', $content);
        $this->assertStringContainsString('Select Designation', $content);
        $this->assertStringContainsString('Specify Designation *', $content);
        $this->assertStringContainsString('Gen. Manger', $content);
        $this->assertStringContainsString('Purchase Dept.', $content);
    }

    public function test_store_party_with_predefined_designation(): void
    {
        $payload = [
            'name' => 'Acme Corporation',
            'party_type' => 'both',
            'code' => 'PTY-ACME-01',
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'is_active' => true,
            'contacts' => [
                [
                    'name' => 'Vikram Seth',
                    'role' => 'Architect',
                    'designation' => 'Architect',
                    'phone' => '9876543210',
                    'is_primary' => 1,
                ],
            ],
            'addresses' => [
                [
                    'type' => 'both',
                    'address_line_1' => 'Plot 42, Tech Park',
                    'state' => 'Maharashtra',
                    'pincode' => '400001',
                    'is_default_billing' => 1,
                    'is_default_delivery' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('masters.customers.store'), $payload);

        $response->assertRedirect(route('masters.customers.index'));

        $customer = Customer::where('name', 'Acme Corporation')->first();
        $this->assertNotNull($customer);

        $contact = $customer->contacts()->first();
        $this->assertNotNull($contact);
        $this->assertSame('Vikram Seth', $contact->name);
        $this->assertSame('Architect', $contact->role);
        $this->assertTrue((bool) $contact->is_primary);
    }

    public function test_store_party_with_other_and_custom_designation(): void
    {
        $payload = [
            'name' => 'Zenith Buildtech',
            'party_type' => 'customer',
            'code' => 'PTY-ZENITH-01',
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'is_active' => true,
            'contacts' => [
                [
                    'name' => 'Pooja Hegde',
                    'role' => 'Other',
                    'designation' => 'Other',
                    'custom_role' => 'Chief Sustainability Officer',
                    'phone' => '9123456780',
                    'is_primary' => 1,
                ],
            ],
            'addresses' => [
                [
                    'type' => 'both',
                    'address_line_1' => 'Lane 5',
                    'state' => 'Maharashtra',
                    'pincode' => '411001',
                    'is_default_billing' => 1,
                    'is_default_delivery' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('masters.customers.store'), $payload);

        $response->assertRedirect(route('masters.customers.index'));

        $customer = Customer::where('name', 'Zenith Buildtech')->first();
        $this->assertNotNull($customer);

        $contact = $customer->contacts()->first();
        $this->assertNotNull($contact);
        $this->assertSame('Pooja Hegde', $contact->name);
        $this->assertSame('Chief Sustainability Officer', $contact->role);
    }

    public function test_store_party_with_multiple_contacts_having_different_designations(): void
    {
        $payload = [
            'name' => 'Apex Infra Solutions',
            'party_type' => 'supplier',
            'code' => 'PTY-APEXINFRA-01',
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'is_active' => true,
            'contacts' => [
                [
                    'name' => 'Aditya Birla',
                    'role' => 'CEO',
                    'designation' => 'CEO',
                    'phone' => '9820011111',
                    'is_primary' => 1,
                ],
                [
                    'name' => 'Sunil Gupta',
                    'role' => 'Accounts',
                    'designation' => 'Accounts',
                    'phone' => '9820022222',
                    'is_primary' => 0,
                ],
                [
                    'name' => 'Kavita Rao',
                    'role' => 'Other',
                    'designation' => 'Other',
                    'custom_role' => 'Site Safety Inspector',
                    'phone' => '9820033333',
                    'is_primary' => 0,
                ],
            ],
            'addresses' => [
                [
                    'type' => 'both',
                    'address_line_1' => 'Midc Industrial Area',
                    'state' => 'Maharashtra',
                    'pincode' => '400701',
                    'is_default_billing' => 1,
                    'is_default_delivery' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('masters.customers.store'), $payload);

        $response->assertRedirect(route('masters.customers.index'));

        $customer = Customer::where('name', 'Apex Infra Solutions')->first();
        $this->assertNotNull($customer);

        $contacts = $customer->contacts()->get();
        $this->assertCount(3, $contacts);

        $ceo = $contacts->firstWhere('name', 'Aditya Birla');
        $this->assertSame('CEO', $ceo->role);
        $this->assertTrue((bool) $ceo->is_primary);

        $acc = $contacts->firstWhere('name', 'Sunil Gupta');
        $this->assertSame('Accounts', $acc->role);
        $this->assertFalse((bool) $acc->is_primary);

        $other = $contacts->firstWhere('name', 'Kavita Rao');
        $this->assertSame('Site Safety Inspector', $other->role);
        $this->assertFalse((bool) $other->is_primary);
    }

    public function test_edit_party_form_resolves_standard_and_custom_designations(): void
    {
        $customer = Customer::create([
            'name' => 'Heritage Traders',
            'code' => 'PTY-HERITAGE-01',
            'party_type' => 'customer',
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'is_active' => true,
        ]);

        PartyContact::create([
            'customer_id' => $customer->id,
            'name' => 'Mohit Jain',
            'role' => 'Store Manager',
            'phone' => '9988776655',
            'is_primary' => true,
            'level' => 'party',
            'is_active' => true,
        ]);

        PartyContact::create([
            'customer_id' => $customer->id,
            'name' => 'Sneha Patil',
            'role' => 'Lead Lighting Consultant',
            'phone' => '9988776644',
            'is_primary' => false,
            'level' => 'party',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('masters.customers.edit', $customer));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Contact 1: standard designation Store Manager
        $this->assertStringContainsString('"designation":"Store Manager"', $content);
        $this->assertStringContainsString('"other_designation":""', $content);

        // Contact 2: custom designation Lead Lighting Consultant
        $this->assertStringContainsString('"designation":"Other"', $content);
        $this->assertStringContainsString('"other_designation":"Lead Lighting Consultant"', $content);
    }

    public function test_update_party_designation_and_clear_option(): void
    {
        $customer = Customer::create([
            'name' => 'Metro Retail',
            'code' => 'PTY-METRO-01',
            'party_type' => 'customer',
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'is_active' => true,
        ]);

        PartyContact::create([
            'customer_id' => $customer->id,
            'name' => 'Rakesh Patel',
            'role' => 'Salesman',
            'phone' => '9870001111',
            'is_primary' => true,
            'level' => 'party',
            'is_active' => true,
        ]);

        // Update to Branch Manager
        $payload = [
            'name' => 'Metro Retail',
            'party_type' => 'customer',
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'is_active' => true,
            'contacts' => [
                [
                    'name' => 'Rakesh Patel',
                    'role' => 'Branch Manager',
                    'designation' => 'Branch Manager',
                    'phone' => '9870001111',
                    'is_primary' => 1,
                ],
            ],
            'addresses' => [
                [
                    'type' => 'both',
                    'address_line_1' => 'Station Road',
                    'state' => 'Maharashtra',
                    'pincode' => '400050',
                    'is_default_billing' => 1,
                    'is_default_delivery' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->put(route('masters.customers.update', $customer), $payload);
        $response->assertRedirect(route('masters.customers.index'));

        $contact = $customer->fresh()->contacts()->first();
        $this->assertSame('Branch Manager', $contact->role);

        // Now clear the designation
        $payload['contacts'][0]['role'] = '';
        $payload['contacts'][0]['designation'] = '';
        $payload['contacts'][0]['custom_role'] = '';

        $response2 = $this->actingAs($this->adminUser)->put(route('masters.customers.update', $customer), $payload);
        $response2->assertRedirect(route('masters.customers.index'));

        $contactCleared = $customer->fresh()->contacts()->first();
        $this->assertNull($contactCleared->role);
    }

    public function test_show_party_view_displays_contact_designation(): void
    {
        $customer = Customer::create([
            'name' => 'Global Logistics Hub',
            'code' => 'PTY-GLOBAL-01',
            'party_type' => 'both',
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'is_active' => true,
        ]);

        PartyContact::create([
            'customer_id' => $customer->id,
            'name' => 'Anil Deshmukh',
            'role' => 'Logistics',
            'phone' => '9819998888',
            'is_primary' => true,
            'level' => 'party',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('masters.customers.show', $customer));

        $response->assertStatus(200);
        $response->assertSee('Anil Deshmukh');
        $response->assertSee('(Logistics)');
    }
}

