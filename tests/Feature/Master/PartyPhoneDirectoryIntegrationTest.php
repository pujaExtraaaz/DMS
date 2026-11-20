<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Designation;
use App\Domains\Master\Models\PartyContact;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryCategory;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryContact;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyPhoneDirectoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;
    protected CustomerType $customerType;
    protected Designation $designation;
    protected PhoneDirectoryDepartment $department;
    protected PhoneDirectoryCategory $category;

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
            'name' => 'Wholesale Dealer',
            'code' => 'WD-01',
            'is_active' => true,
        ]);

        $this->designation = Designation::firstOrCreate(
            ['name' => 'Operations Director'],
            ['code' => 'DIR-OPS', 'is_active' => true]
        );

        $this->department = PhoneDirectoryDepartment::firstOrCreate(
            ['name' => 'Management'],
            ['is_active' => true]
        );

        $this->category = PhoneDirectoryCategory::firstOrCreate(
            ['name' => 'Executive'],
            ['is_active' => true]
        );
    }

    public function test_can_search_phone_directory_contacts_via_ajax(): void
    {
        $contact = PhoneDirectoryContact::create([
            'contact_name' => 'Suresh Raina',
            'mobile_number' => '9876543210',
            'email' => 'suresh@csk.com',
            'designation_id' => $this->designation->id,
            'designation' => 'Operations Director',
            'company_id' => $this->company->id,
            'company_name' => 'Apex Enterprises',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('masters.customers.phone-directory-contacts', ['q' => 'Suresh']));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(1, $response->json('contacts'));
        $this->assertEquals('Suresh Raina', $response->json('contacts.0.name'));
        $this->assertEquals('9876543210', $response->json('contacts.0.phone'));
        $this->assertEquals('Apex Enterprises', $response->json('contacts.0.company_name'));
    }

    public function test_can_save_and_clear_party_draft_in_session(): void
    {
        $draftData = [
            'name' => 'Draft In Progress Party Ltd',
            'party_type' => 'customer',
            'gstin' => '27ABCDE1234F1Z5',
            'phone' => '9988776655',
            'contacts' => [],
        ];

        $saveResponse = $this->actingAs($this->adminUser)
            ->postJson(route('masters.customers.draft.save'), ['draft' => $draftData]);

        $saveResponse->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals('Draft In Progress Party Ltd', session('party_form_draft.name'));

        $clearResponse = $this->actingAs($this->adminUser)
            ->post(route('masters.customers.draft.clear'));

        $clearResponse->assertRedirect();
        $this->assertNull(session('party_form_draft'));
    }

    public function test_phone_directory_contact_creation_with_return_to_party_redirects_and_auto_selects(): void
    {
        // 1. User drafts a party and navigates to create contact
        session(['party_form_draft' => [
            'name' => 'Acme Corporation',
            'trade_name' => 'Acme Supplies',
            'party_type' => 'customer',
            'gstin' => '27AAACA1234A1Z5',
            'phone' => '9876500000',
            'email' => 'orders@acme.com',
            'contacts' => [],
        ]]);

        // 2. Submits new phone directory contact with return_to = party
        $response = $this->actingAs($this->adminUser)->post(route('phone-directory.contacts.store'), [
            'contact_name' => 'Vikram Sharma',
            'mobile_number' => '9123456780',
            'email' => 'vikram@acme.com',
            'designation_id' => $this->designation->id,
            'department_id' => $this->department->id,
            'category_id' => $this->category->id,
            'contact_type' => 'business',
            'return_to' => 'party',
        ]);

        $newContact = PhoneDirectoryContact::where('contact_name', 'Vikram Sharma')->first();
        $this->assertNotNull($newContact);

        $response->assertRedirect(route('masters.customers.create', ['selected_contact_id' => $newContact->id]));

        // 3. User lands back on create party form with selected_contact_id
        $createPageResponse = $this->actingAs($this->adminUser)
            ->get(route('masters.customers.create', ['selected_contact_id' => $newContact->id]));

        $createPageResponse->assertOk();
        $createPageResponse->assertSee('Acme Corporation');
        $createPageResponse->assertSee('Vikram Sharma');
        $createPageResponse->assertSee('In-Progress Party Draft Restored');
    }

    public function test_party_can_be_saved_with_phone_directory_contact_and_primary_selection(): void
    {
        $phoneContact1 = PhoneDirectoryContact::create([
            'contact_name' => 'Rohan Mehra',
            'mobile_number' => '9811122233',
            'email' => 'rohan@mehra.com',
            'designation_id' => $this->designation->id,
            'company_name' => 'Mehra Corp',
            'status' => 'active',
        ]);

        $phoneContact2 = PhoneDirectoryContact::create([
            'contact_name' => 'Ananya Roy',
            'mobile_number' => '9822233344',
            'email' => 'ananya@mehra.com',
            'company_name' => 'Mehra Corp',
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'Mehra Enterprises',
            'party_type' => 'customer',
            'phone' => '9800011122',
            'contacts' => [
                [
                    'phone_directory_contact_id' => $phoneContact1->id,
                    'name' => $phoneContact1->contact_name,
                    'phone' => $phoneContact1->mobile_number,
                    'email' => $phoneContact1->email,
                    'company_name' => $phoneContact1->company_name,
                    'is_primary' => true,
                ],
                [
                    'phone_directory_contact_id' => $phoneContact2->id,
                    'name' => $phoneContact2->contact_name,
                    'phone' => $phoneContact2->mobile_number,
                    'email' => $phoneContact2->email,
                    'company_name' => $phoneContact2->company_name,
                    'is_primary' => false,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('masters.customers.store'), $payload);
        $response->assertRedirect(route('masters.customers.index'));

        $customer = Customer::where('name', 'Mehra Enterprises')->first();
        $this->assertNotNull($customer);
        $this->assertCount(2, $customer->contacts);

        $primaryContact = $customer->contacts()->where('is_primary', true)->first();
        $this->assertNotNull($primaryContact);
        $this->assertEquals($phoneContact1->id, $primaryContact->phone_directory_contact_id);
        $this->assertEquals('Rohan Mehra', $primaryContact->effective_name);
        $this->assertEquals('9811122233', $primaryContact->effective_phone);
        $this->assertEquals('Mehra Corp', $primaryContact->effective_company_name);

        $secondaryContact = $customer->contacts()->where('is_primary', false)->first();
        $this->assertNotNull($secondaryContact);
        $this->assertEquals($phoneContact2->id, $secondaryContact->phone_directory_contact_id);
        $this->assertEquals('Ananya Roy', $secondaryContact->effective_name);
    }

    public function test_updating_phone_directory_contact_reflects_in_party_effective_attributes(): void
    {
        $phoneContact = PhoneDirectoryContact::create([
            'contact_name' => 'Kavita Iyer',
            'mobile_number' => '9900011122',
            'email' => 'kavita@iyer.com',
            'designation_id' => $this->designation->id,
            'company_name' => 'Iyer Logistics',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Iyer Logistics Pvt Ltd',
            'party_type' => 'customer',
            'code' => 'IYER-01',
        ]);

        $partyContact = PartyContact::create([
            'customer_id' => $customer->id,
            'phone_directory_contact_id' => $phoneContact->id,
            'name' => 'Kavita Iyer',
            'phone' => '9900011122',
            'email' => 'kavita@iyer.com',
            'is_primary' => true,
        ]);

        $this->assertEquals('Kavita Iyer', $partyContact->effective_name);

        // Update PhoneDirectoryContact
        $phoneContact->update([
            'contact_name' => 'Dr. Kavita Iyer',
            'mobile_number' => '9999988888',
            'email' => 'dr.kavita@iyer.com',
        ]);

        $partyContact->refresh();
        $this->assertEquals('Dr. Kavita Iyer', $partyContact->effective_name);
        $this->assertEquals('9999988888', $partyContact->effective_phone);
        $this->assertEquals('dr.kavita@iyer.com', $partyContact->effective_email);
    }

    public function test_deleting_party_preserves_phone_directory_contact(): void
    {
        $phoneContact = PhoneDirectoryContact::create([
            'contact_name' => 'Deepak Verma',
            'mobile_number' => '9877766655',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Verma Traders',
            'party_type' => 'customer',
            'code' => 'VERMA-01',
        ]);

        $customer->contacts()->create([
            'phone_directory_contact_id' => $phoneContact->id,
            'name' => $phoneContact->contact_name,
            'phone' => $phoneContact->mobile_number,
            'is_primary' => true,
        ]);

        $customer->delete();

        // Phone directory contact remains completely intact!
        $this->assertDatabaseHas('phone_directory_contacts', [
            'id' => $phoneContact->id,
            'contact_name' => 'Deepak Verma',
        ]);
    }

    public function test_multiple_parties_can_link_to_the_same_phone_directory_contact(): void
    {
        $sharedContact = PhoneDirectoryContact::create([
            'contact_name' => 'Manish Gupta',
            'mobile_number' => '9812300000',
            'email' => 'manish@group.com',
            'status' => 'active',
        ]);

        $partyA = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Group Company A',
            'party_type' => 'customer',
            'code' => 'GRP-A',
        ]);
        $partyA->contacts()->create([
            'phone_directory_contact_id' => $sharedContact->id,
            'name' => $sharedContact->contact_name,
            'phone' => $sharedContact->mobile_number,
            'is_primary' => true,
        ]);

        $partyB = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Group Company B',
            'party_type' => 'customer',
            'code' => 'GRP-B',
        ]);
        $partyB->contacts()->create([
            'phone_directory_contact_id' => $sharedContact->id,
            'name' => $sharedContact->contact_name,
            'phone' => $sharedContact->mobile_number,
            'is_primary' => true,
        ]);

        $this->assertCount(2, $sharedContact->partyContacts);
        $this->assertCount(2, $sharedContact->parties);
    }

    public function test_can_quick_store_custom_contact_to_phone_directory(): void
    {
        $payload = [
            'contact_name' => 'Amitabh Bachchan',
            'mobile_number' => '9820011223',
            'whatsapp_number' => '9820011223',
            'email' => 'amitabh@abcorp.com',
            'designation' => 'Managing Director',
            'company_name' => 'AB Corp Ltd',
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('masters.customers.phone-directory-quick-store'), $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'already_exists' => false,
            ]);

        $this->assertEquals('Amitabh Bachchan', $response->json('contact.name'));
        $this->assertEquals('9820011223', $response->json('contact.whatsapp_number'));
        $this->assertEquals('Managing Director', $response->json('contact.designation'));
        $this->assertEquals('Ab Corp Ltd', $response->json('contact.company_name'));

        $this->assertDatabaseHas('phone_directory_contacts', [
            'contact_name' => 'Amitabh Bachchan',
            'mobile_number' => '9820011223',
            'whatsapp_number' => '9820011223',
            'email' => 'amitabh@abcorp.com',
        ]);
    }

    public function test_quick_store_detects_existing_contact_by_mobile_number(): void
    {
        $existing = PhoneDirectoryContact::create([
            'contact_name' => 'Shah Rukh Khan',
            'mobile_number' => '9820099887',
            'whatsapp_number' => '9820099887',
            'email' => 'srk@redchillies.com',
            'company_name' => 'Red Chillies Ent',
            'status' => 'active',
        ]);

        $payload = [
            'contact_name' => 'SRK Alternate',
            'mobile_number' => '9820099887',
            'whatsapp_number' => '9820099887',
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('masters.customers.phone-directory-quick-store'), $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'already_exists' => true,
            ]);

        $this->assertEquals($existing->id, $response->json('contact.id'));
        $this->assertEquals('Shah Rukh Khan', $response->json('contact.name'));
    }

    public function test_search_phone_directory_matches_whatsapp_and_designation(): void
    {
        PhoneDirectoryContact::create([
            'contact_name' => 'Deepika Padukone',
            'mobile_number' => '9810012345',
            'whatsapp_number' => '9819988776',
            'designation' => 'Executive Producer',
            'email' => 'deepika@ka.com',
            'company_name' => 'Ka Productions',
            'status' => 'active',
        ]);

        // Search by WhatsApp number
        $waResponse = $this->actingAs($this->adminUser)
            ->getJson(route('masters.customers.phone-directory-contacts', ['q' => '9819988776']));

        $waResponse->assertOk();
        $this->assertCount(1, $waResponse->json('contacts'));
        $this->assertEquals('Deepika Padukone', $waResponse->json('contacts.0.name'));
        $this->assertEquals('9819988776', $waResponse->json('contacts.0.whatsapp_number'));

        // Search by Designation
        $desigResponse = $this->actingAs($this->adminUser)
            ->getJson(route('masters.customers.phone-directory-contacts', ['q' => 'Producer']));

        $desigResponse->assertOk();
        $this->assertCount(1, $desigResponse->json('contacts'));
        $this->assertEquals('Deepika Padukone', $desigResponse->json('contacts.0.name'));
    }

    public function test_party_saves_and_syncs_contacts_with_whatsapp_and_custom_entries(): void
    {
        $phoneContact = PhoneDirectoryContact::create([
            'contact_name' => 'Aamir Khan',
            'mobile_number' => '9821122334',
            'whatsapp_number' => '9821122334',
            'email' => 'aamir@akp.com',
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'AKP Films Pvt Ltd',
            'party_type' => 'customer',
            'phone' => '9820000000',
            'contacts' => [
                [
                    'phone_directory_contact_id' => $phoneContact->id,
                    'name' => 'Aamir Khan',
                    'phone' => '9821122334',
                    'whatsapp_number' => '9821122334',
                    'email' => 'aamir@akp.com',
                    'is_primary' => true,
                ],
                [
                    'phone_directory_contact_id' => null, // Custom party contact
                    'name' => 'Custom Assistant',
                    'role' => 'Coordinator',
                    'phone' => '9811199988',
                    'whatsapp_number' => '9811199988',
                    'email' => 'assistant@akp.com',
                    'is_primary' => false,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.customers.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('masters.customers.index'));

        $customer = Customer::latest('id')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('AKP FILMS PVT LTD', strtoupper($customer->name));
        $this->assertCount(2, $customer->contacts);

        $dirContact = $customer->contacts()->where('phone_directory_contact_id', $phoneContact->id)->first();
        $this->assertNotNull($dirContact);
        $this->assertEquals('9821122334', $dirContact->effective_whatsapp_number);
        $this->assertTrue((bool)$dirContact->is_primary);

        $customContact = $customer->contacts()->whereNull('phone_directory_contact_id')->first();
        $this->assertNotNull($customContact);
        $this->assertEquals('9811199988', $customContact->whatsapp_number);
        $this->assertEquals('Coordinator', $customContact->role);
        $this->assertFalse((bool)$customContact->is_primary);
    }
}
