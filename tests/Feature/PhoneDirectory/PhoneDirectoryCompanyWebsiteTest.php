<?php

namespace Tests\Feature\PhoneDirectory;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyContact;
use App\Domains\Organization\Models\Company;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PhoneDirectoryCompanyWebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $perms = [
            'phone_directory.view',
            'phone_directory.create',
            'phone_directory.edit',
            'phone_directory.archive',
            'phone_directory.delete',
            'phone_directory.import',
            'phone_directory.export',
            'phone_directory.manage_departments',
            'masters.parties.view',
            'masters.parties.create',
            'masters.parties.edit',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $superRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superRole->givePermissionTo(Permission::all());

        $this->company = Company::create([
            'name' => 'Test Apex Industries Ltd',
            'code' => 'APEX01',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->admin->assignRole('super-admin');
    }

    public function test_create_contact_form_renders_company_website_field(): void
    {
        $response = $this->actingAs($this->admin)->get(route('phone-directory.contacts.create'));

        $response->assertOk();
        $response->assertSee('Company Website');
        $response->assertSee('placeholder="https://www.example.com"', false);
        $response->assertSee('type="url"', false);
        $response->assertSee('name="company_website"', false);
    }

    public function test_can_save_contact_with_valid_https_website(): void
    {
        $payload = [
            'contact_name' => 'Aarav Patel',
            'contact_type' => 'customer',
            'mobile_number' => '9876543210',
            'company_name' => 'Apex Tech Solutions',
            'company_website' => 'https://www.apextech.com',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)->post(route('phone-directory.contacts.store'), $payload);

        $response->assertRedirect(route('phone-directory.index'));
        $this->assertDatabaseHas('phone_directory_contacts', [
            'contact_name' => 'Aarav Patel',
            'company_website' => 'https://www.apextech.com',
        ]);
    }

    public function test_can_save_contact_with_valid_http_website(): void
    {
        $payload = [
            'contact_name' => 'Pooja Sharma',
            'contact_type' => 'supplier',
            'mobile_number' => '9876543211',
            'company_name' => 'Sharma Logistics',
            'company_website' => 'http://sharmalogistics.in',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)->post(route('phone-directory.contacts.store'), $payload);

        $response->assertRedirect(route('phone-directory.index'));
        $this->assertDatabaseHas('phone_directory_contacts', [
            'contact_name' => 'Pooja Sharma',
            'company_website' => 'http://sharmalogistics.in',
        ]);
    }

    public function test_normalizes_domain_without_protocol_to_https(): void
    {
        $payload = [
            'contact_name' => 'Vikram Malhotra',
            'contact_type' => 'dealer',
            'mobile_number' => '9876543212',
            'company_name' => 'Malhotra Trading',
            'company_website' => 'www.malhotratrading.com',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)->post(route('phone-directory.contacts.store'), $payload);

        $response->assertRedirect(route('phone-directory.index'));
        $this->assertDatabaseHas('phone_directory_contacts', [
            'contact_name' => 'Vikram Malhotra',
            'company_website' => 'https://www.malhotratrading.com',
        ]);
    }

    public function test_company_website_is_optional_and_trims_whitespace_to_null(): void
    {
        $payload = [
            'contact_name' => 'Sunil Gupta',
            'contact_type' => 'other',
            'mobile_number' => '9876543213',
            'company_website' => '   ',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)->post(route('phone-directory.contacts.store'), $payload);

        $response->assertRedirect(route('phone-directory.index'));
        $this->assertDatabaseHas('phone_directory_contacts', [
            'contact_name' => 'Sunil Gupta',
            'company_website' => null,
        ]);
    }

    public function test_rejects_invalid_url_format(): void
    {
        $payload = [
            'contact_name' => 'Karan Mehta',
            'contact_type' => 'customer',
            'mobile_number' => '9876543214',
            'company_website' => 'htp://bad url with spaces',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)->post(route('phone-directory.contacts.store'), $payload);

        $response->assertSessionHasErrors(['company_website']);
    }

    public function test_edit_prepopulates_company_website_and_allows_updating(): void
    {
        $contact = PhoneDirectoryContact::create([
            'contact_name' => 'Neha Verma',
            'contact_type' => 'customer',
            'mobile_number' => '9876543215',
            'company_name' => 'Verma Enterprises',
            'company_website' => 'https://www.verma-ent.com',
            'status' => 'active',
        ]);

        $editViewResponse = $this->actingAs($this->admin)->get(route('phone-directory.contacts.edit', $contact));
        $editViewResponse->assertOk();
        $editViewResponse->assertSee('https://www.verma-ent.com');

        // Update website
        $updatePayload = [
            'contact_name' => 'Neha Verma',
            'contact_type' => 'customer',
            'mobile_number' => '9876543215',
            'company_website' => 'https://newsite.verma.com',
            'status' => 'active',
        ];

        $updateResponse = $this->actingAs($this->admin)->put(route('phone-directory.contacts.update', $contact), $updatePayload);
        $updateResponse->assertRedirect(route('phone-directory.index'));

        $this->assertDatabaseHas('phone_directory_contacts', [
            'id' => $contact->id,
            'company_website' => 'https://newsite.verma.com',
        ]);

        // Clear website
        $clearPayload = [
            'contact_name' => 'Neha Verma',
            'contact_type' => 'customer',
            'mobile_number' => '9876543215',
            'company_website' => '',
            'status' => 'active',
        ];

        $clearResponse = $this->actingAs($this->admin)->put(route('phone-directory.contacts.update', $contact), $clearPayload);
        $clearResponse->assertRedirect(route('phone-directory.index'));

        $this->assertDatabaseHas('phone_directory_contacts', [
            'id' => $contact->id,
            'company_website' => null,
        ]);
    }

    public function test_show_page_and_json_response_display_company_website(): void
    {
        $contact = PhoneDirectoryContact::create([
            'contact_name' => 'Rohan Joshi',
            'contact_type' => 'customer',
            'mobile_number' => '9876543216',
            'company_name' => 'Joshi Group',
            'company_website' => 'https://www.joshigroup.in',
            'status' => 'active',
        ]);

        $viewResponse = $this->actingAs($this->admin)->get(route('phone-directory.contacts.show', $contact));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Company Website');
        $viewResponse->assertSee('https://www.joshigroup.in');

        // Quick View JSON response
        $jsonResponse = $this->actingAs($this->admin)->getJson(route('phone-directory.contacts.show', $contact));
        $jsonResponse->assertOk();
        $jsonResponse->assertJsonPath('contact.company_website', 'https://www.joshigroup.in');
    }

    public function test_party_integration_preserves_contact_company_website_without_overwriting_party_website(): void
    {
        $contact = PhoneDirectoryContact::create([
            'contact_name' => 'Divya Reddy',
            'contact_type' => 'customer',
            'mobile_number' => '9876543217',
            'company_name' => 'Reddy Steel Corp',
            'company_website' => 'https://www.reddysteel.com',
            'status' => 'active',
        ]);

        // 1. Search Phone Directory Contacts from Party form returns company_website
        $searchResponse = $this->actingAs($this->admin)->getJson(route('masters.customers.phone-directory-contacts', ['q' => 'Divya']));
        $searchResponse->assertOk();
        $this->assertEquals('https://www.reddysteel.com', $searchResponse->json('contacts.0.company_website'));

        // 2. Party has its own distinct website
        $custType = CustomerType::create(['name' => 'Wholesaler', 'code' => 'WS01']);
        $party = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $custType->id,
            'name' => 'Global Infrastructure Ltd',
            'code' => 'GIL01',
            'website' => 'https://www.globalinfra.com',
            'is_active' => true,
        ]);

        // 3. Link contact to Party
        $partyContact = PartyContact::create([
            'customer_id' => $party->id,
            'phone_directory_contact_id' => $contact->id,
            'name' => 'Divya Reddy',
            'phone' => '9876543217',
            'role' => 'Procurement Head',
            'is_primary' => true,
        ]);

        // Effective website on PartyContact points to contact's company website
        $this->assertEquals('https://www.reddysteel.com', $partyContact->effective_company_website);

        // Party's own website remains intact and distinct
        $party->refresh();
        $this->assertEquals('https://www.globalinfra.com', $party->website);

        // Party show view displays both without collision
        $partyViewResponse = $this->actingAs($this->admin)->get(route('masters.customers.show', $party));
        $partyViewResponse->assertOk();
        $partyViewResponse->assertSee('https://www.globalinfra.com');
        $partyViewResponse->assertSee('https://www.reddysteel.com');
    }
}
