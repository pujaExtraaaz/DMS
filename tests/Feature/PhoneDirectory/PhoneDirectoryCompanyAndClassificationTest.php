<?php

namespace Tests\Feature\PhoneDirectory;

use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\Company;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PhoneDirectoryCompanyAndClassificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

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
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $superRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superRole->givePermissionTo(Permission::all());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    public function test_company_and_classification_dropdowns_display_actual_masters(): void
    {
        $company = Company::create([
            'name' => 'Acme Corporation Ltd',
            'code' => 'ACM01',
            'is_active' => true,
        ]);

        $classification = CustomerType::create([
            'name' => 'Key Enterprise Partner',
            'code' => 'KEP01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('phone-directory.contacts.create'));

        $response->assertOk();
        $response->assertSee('Acme Corporation Ltd');
        $response->assertSee('Key Enterprise Partner');
        $response->assertSee('Company / Organization');
        $response->assertSee('Party Classification');
    }

    public function test_can_create_contact_with_company_and_classification(): void
    {
        $company = Company::create([
            'name' => 'Global Logistics Hub',
            'code' => 'GLH01',
            'is_active' => true,
        ]);

        $classification = CustomerType::create([
            'name' => 'Tier 1 Distributor',
            'code' => 'T1D01',
            'is_active' => true,
        ]);

        $payload = [
            'contact_name' => 'Vikram Seth',
            'contact_type' => 'distributor',
            'mobile_number' => '9811223344',
            'company_id' => $company->id,
            'customer_type_id' => $classification->id,
            'email' => 'vikram@globallogistics.com',
            'city' => 'Pune',
            'state' => 'Maharashtra',
        ];

        $response = $this->actingAs($this->admin)->post(route('phone-directory.contacts.store'), $payload);

        $response->assertRedirect(route('phone-directory.index'));

        $this->assertDatabaseHas('phone_directory_contacts', [
            'contact_name' => 'Vikram Seth',
            'company_id' => $company->id,
            'customer_type_id' => $classification->id,
            'company_name' => 'Global Logistics Hub',
        ]);
    }

    public function test_can_load_and_update_contact_with_company_and_classification(): void
    {
        $companyA = Company::create(['name' => 'Company Alpha', 'code' => 'CA01', 'is_active' => true]);
        $companyB = Company::create(['name' => 'Company Beta', 'code' => 'CB01', 'is_active' => true]);

        $classA = CustomerType::create(['name' => 'Retail Client', 'code' => 'RC01', 'is_active' => true]);
        $classB = CustomerType::create(['name' => 'Wholesale Dealer', 'code' => 'WD01', 'is_active' => true]);

        $contact = PhoneDirectoryContact::create([
            'contact_name' => 'Neha Sharma',
            'contact_type' => 'customer',
            'mobile_number' => '9822334455',
            'company_id' => $companyA->id,
            'company_name' => $companyA->name,
            'customer_type_id' => $classA->id,
        ]);

        // Load edit view
        $editResponse = $this->actingAs($this->admin)->get(route('phone-directory.contacts.edit', $contact));
        $editResponse->assertOk();
        $editResponse->assertSee('Company Alpha');
        $editResponse->assertSee('Retail Client');

        // Update contact with Company B and Classification B
        $updateResponse = $this->actingAs($this->admin)->put(route('phone-directory.contacts.update', $contact), [
            'contact_name' => 'Neha Sharma Updated',
            'contact_type' => 'dealer',
            'mobile_number' => '9822334455',
            'company_id' => $companyB->id,
            'customer_type_id' => $classB->id,
        ]);

        $updateResponse->assertRedirect(route('phone-directory.index'));

        $contact->refresh();
        $this->assertEquals($companyB->id, $contact->company_id);
        $this->assertEquals('Company Beta', $contact->company_name);
        $this->assertEquals($classB->id, $contact->customer_type_id);
        $this->assertEquals('Company Beta', $contact->effective_company_name);
        $this->assertEquals('Wholesale Dealer', $contact->classification_name);
    }

    public function test_contact_listing_displays_company_and_classification_names(): void
    {
        $company = Company::create(['name' => 'Apex Manufacturing', 'code' => 'APX01', 'is_active' => true]);
        $classification = CustomerType::create(['name' => 'Strategic Partner', 'code' => 'SP01', 'is_active' => true]);

        PhoneDirectoryContact::create([
            'contact_name' => 'Karan Johar',
            'contact_type' => 'business_partner',
            'mobile_number' => '9988776655',
            'company_id' => $company->id,
            'company_name' => $company->name,
            'customer_type_id' => $classification->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('phone-directory.index'));

        $response->assertOk();
        $response->assertSee('Apex Manufacturing');
        $response->assertSee('Strategic Partner');
    }

    public function test_contact_listing_filters_by_classification(): void
    {
        $classA = CustomerType::create(['name' => 'Tier A Partner', 'code' => 'TA01', 'is_active' => true]);
        $classB = CustomerType::create(['name' => 'Tier B Vendor', 'code' => 'TB01', 'is_active' => true]);

        PhoneDirectoryContact::create([
            'contact_name' => 'Contact Alpha',
            'contact_type' => 'customer',
            'mobile_number' => '9000000001',
            'customer_type_id' => $classA->id,
        ]);

        PhoneDirectoryContact::create([
            'contact_name' => 'Contact Beta',
            'contact_type' => 'supplier',
            'mobile_number' => '9000000002',
            'customer_type_id' => $classB->id,
        ]);

        // Filter by Classification A
        $responseA = $this->actingAs($this->admin)->get(route('phone-directory.index', [
            'classification_id' => $classA->id,
        ]));
        $responseA->assertOk();
        $responseA->assertSee('Contact Alpha');
        $responseA->assertDontSee('Contact Beta');

        // Filter by Classification B
        $responseB = $this->actingAs($this->admin)->get(route('phone-directory.index', [
            'classification_id' => $classB->id,
        ]));
        $responseB->assertOk();
        $responseB->assertSee('Contact Beta');
        $responseB->assertDontSee('Contact Alpha');

        // All Classifications (empty filter)
        $responseAll = $this->actingAs($this->admin)->get(route('phone-directory.index'));
        $responseAll->assertOk();
        $responseAll->assertSee('Contact Alpha');
        $responseAll->assertSee('Contact Beta');
        $responseAll->assertSee('All Classifications');
    }

    public function test_show_endpoint_returns_company_and_classification(): void
    {
        $company = Company::create(['name' => 'Nexus Global', 'code' => 'NX01', 'is_active' => true]);
        $classification = CustomerType::create(['name' => 'VIP Client', 'code' => 'VIP01', 'is_active' => true]);

        $contact = PhoneDirectoryContact::create([
            'contact_name' => 'Rohan Mehra',
            'contact_type' => 'customer',
            'mobile_number' => '9111222333',
            'company_id' => $company->id,
            'customer_type_id' => $classification->id,
        ]);

        // HTML show view
        $htmlResponse = $this->actingAs($this->admin)->get(route('phone-directory.contacts.show', $contact));
        $htmlResponse->assertOk();
        $htmlResponse->assertSee($company->name);
        $htmlResponse->assertSee($classification->name);

        // JSON show endpoint (used by Quick View modal)
        $jsonResponse = $this->actingAs($this->admin)->getJson(route('phone-directory.contacts.show', $contact));
        $jsonResponse->assertOk();
        $jsonResponse->assertJsonPath('success', true);
        $jsonResponse->assertJsonPath('contact.company_name', $company->name);
        $jsonResponse->assertJsonPath('contact.company_id', $company->id);
        $jsonResponse->assertJsonPath('contact.classification_name', $classification->name);
        $jsonResponse->assertJsonPath('contact.classification_id', $classification->id);
    }

    public function test_existing_contacts_without_company_or_classification_continue_to_work(): void
    {
        // Legacy contact created without company_id or customer_type_id
        $contact = PhoneDirectoryContact::create([
            'contact_name' => 'Legacy Contact',
            'contact_type' => 'other',
            'mobile_number' => '9998887776',
            'company_name' => 'Old Free Text Co',
            'customer_type_id' => null,
            'company_id' => null,
        ]);

        // Listing view
        $listResponse = $this->actingAs($this->admin)->get(route('phone-directory.index'));
        $listResponse->assertOk();
        $listResponse->assertSee('Legacy Contact');
        $listResponse->assertSee('Old Free Text Co');

        // Edit view
        $editResponse = $this->actingAs($this->admin)->get(route('phone-directory.contacts.edit', $contact));
        $editResponse->assertOk();
        $editResponse->assertSee('Legacy Contact');

        // Update works
        $updateResponse = $this->actingAs($this->admin)->put(route('phone-directory.contacts.update', $contact), [
            'contact_name' => 'Legacy Contact Updated',
            'contact_type' => 'other',
            'mobile_number' => '9998887776',
        ]);
        $updateResponse->assertRedirect(route('phone-directory.index'));
        $this->assertDatabaseHas('phone_directory_contacts', [
            'id' => $contact->id,
            'contact_name' => 'Legacy Contact Updated',
        ]);
    }

    public function test_exports_respect_classification_filter_and_include_fields(): void
    {
        $classA = CustomerType::create(['name' => 'Gold Member', 'code' => 'GM01', 'is_active' => true]);
        $classB = CustomerType::create(['name' => 'Silver Member', 'code' => 'SM01', 'is_active' => true]);
        $company = Company::create(['name' => 'Export Corp', 'code' => 'EX01', 'is_active' => true]);

        PhoneDirectoryContact::create([
            'contact_name' => 'Gold Contact',
            'contact_type' => 'customer',
            'mobile_number' => '9870000001',
            'company_id' => $company->id,
            'customer_type_id' => $classA->id,
        ]);

        PhoneDirectoryContact::create([
            'contact_name' => 'Silver Contact',
            'contact_type' => 'customer',
            'mobile_number' => '9870000002',
            'customer_type_id' => $classB->id,
        ]);

        // CSV export with classification filter
        $csvResponse = $this->actingAs($this->admin)->get(route('phone-directory.export.csv', [
            'classification_id' => $classA->id,
        ]));
        $csvResponse->assertOk();
        $content = $csvResponse->streamedContent();
        $this->assertStringContainsString('Gold Contact', $content);
        $this->assertStringNotContainsString('Silver Contact', $content);
        $this->assertStringContainsString('Classification', $content);
        $this->assertStringContainsString('Export Corp', $content);
        $this->assertStringContainsString('Gold Member', $content);
    }
}
