<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Designation;
use App\Domains\Master\Models\PartyContact;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DesignationMasterManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected Company $company;
    protected Branch $branch;
    protected CustomerType $customerType;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);

        $this->regularUser = User::factory()->create();

        $this->company = Company::create([
            'name' => 'Apex Enterprises',
            'code' => 'APEX-01',
            'is_active' => true,
        ]);

        $this->adminUser->update(['company_id' => $this->company->id]);
        $this->regularUser->update(['company_id' => $this->company->id]);

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

    public function test_party_create_form_displays_create_designation_trigger(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('masters.customers.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('+ Create Designation', $content);
        $this->assertStringContainsString('openDesignationModal', $content);
        $this->assertStringContainsString('designationModalOpen', $content);
        $this->assertStringContainsString('designationForm', $content);
    }

    public function test_can_create_new_designation_dynamically_via_quick_store(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson(route('masters.designations.quick-store'), [
            'name' => 'Chief Technology Officer',
            'code' => 'CTO',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'item' => [
                'name' => 'Chief Technology Officer',
                'code' => 'CTO',
                'is_active' => true,
            ],
        ]);

        $this->assertDatabaseHas('designations', [
            'name' => 'Chief Technology Officer',
            'code' => 'CTO',
            'is_active' => 1,
        ]);
    }

    public function test_quick_store_validates_required_name_and_prevents_duplicate_names(): void
    {
        // 1. Required name
        $resEmpty = $this->actingAs($this->adminUser)->postJson(route('masters.designations.quick-store'), [
            'name' => '   ',
        ]);
        $resEmpty->assertStatus(422);
        $resEmpty->assertJson(['success' => false]);

        // 2. Create first designation
        Designation::create([
            'name' => 'Lead Architect',
            'code' => 'LARC',
            'is_active' => true,
        ]);

        // 3. Case-insensitive duplicate check
        $resDup = $this->actingAs($this->adminUser)->postJson(route('masters.designations.quick-store'), [
            'name' => 'lead architect',
        ]);
        $resDup->assertStatus(422);
        $resDup->assertJson([
            'success' => false,
            'message' => 'A designation with this name already exists.',
        ]);
    }

    public function test_can_edit_existing_designation_and_it_updates_associated_party_contacts(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Corp',
            'code' => 'CORP-01',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        $designation = Designation::create([
            'name' => 'Old Title',
            'code' => 'OLD',
            'is_active' => true,
        ]);

        $contact = PartyContact::create([
            'customer_id' => $customer->id,
            'name' => 'Jane Doe',
            'designation_id' => $designation->id,
            'role' => 'Old Title',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->putJson(route('masters.designations.quick-update', $designation), [
            'name' => 'Updated Director Title',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'item' => [
                'id' => $designation->id,
                'name' => 'Updated Director Title',
            ],
        ]);

        $this->assertDatabaseHas('designations', [
            'id' => $designation->id,
            'name' => 'Updated Director Title',
        ]);

        // Contact person reflects updated name through relationship and role sync
        $contact->refresh();
        $this->assertSame($designation->id, $contact->designation_id);
        $this->assertSame('Updated Director Title', $contact->role);
        $this->assertSame('Updated Director Title', $contact->designation->name);
    }

    public function test_can_delete_unused_designation(): void
    {
        $designation = Designation::create([
            'name' => 'Temporary Contractor',
            'code' => 'TEMP',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->deleteJson(route('masters.designations.quick-destroy', $designation));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('designations', [
            'id' => $designation->id,
        ]);
    }

    public function test_cannot_delete_designation_in_use_by_party_contact(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Global Retailers',
            'code' => 'GLOB-01',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        $designation = Designation::create([
            'name' => 'Regional Head',
            'code' => 'RHD',
            'is_active' => true,
        ]);

        PartyContact::create([
            'customer_id' => $customer->id,
            'name' => 'Robert Smith',
            'designation_id' => $designation->id,
            'role' => 'Regional Head',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->deleteJson(route('masters.designations.quick-destroy', $designation));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'in_use' => true,
            'can_deactivate' => true,
        ]);

        // The designation is still safely in database
        $this->assertDatabaseHas('designations', [
            'id' => $designation->id,
        ]);
    }

    public function test_can_deactivate_and_activate_designation(): void
    {
        $designation = Designation::create([
            'name' => 'Legacy Role',
            'code' => 'LEGACY',
            'is_active' => true,
        ]);

        // Deactivate
        $resDeact = $this->actingAs($this->adminUser)->patchJson(route('masters.designations.toggle-active', $designation), [
            'is_active' => false,
        ]);
        $resDeact->assertStatus(200);
        $this->assertFalse($designation->fresh()->is_active);

        // List excludes inactive by default
        $listRes = $this->actingAs($this->adminUser)->getJson(route('masters.designations.list'));
        $listRes->assertStatus(200);
        $items = collect($listRes->json('items'));
        $this->assertFalse($items->contains('id', $designation->id));

        // Activate
        $resAct = $this->actingAs($this->adminUser)->patchJson(route('masters.designations.toggle-active', $designation), [
            'is_active' => true,
        ]);
        $resAct->assertStatus(200);
        $this->assertTrue($designation->fresh()->is_active);
    }

    public function test_centralized_designation_master_screens(): void
    {
        $designation = Designation::create([
            'name' => 'Supply Chain Lead',
            'code' => 'SCL',
            'is_active' => true,
        ]);

        // Index screen with search filter
        $resIndex = $this->actingAs($this->adminUser)->get(route('masters.designations.index', ['search' => 'Supply Chain Lead']));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('Supply Chain Lead');

        // Create screen
        $resCreate = $this->actingAs($this->adminUser)->get(route('masters.designations.create'));
        $resCreate->assertStatus(200);
        $resCreate->assertSee('Create Designation');

        // Edit screen
        $resEdit = $this->actingAs($this->adminUser)->get(route('masters.designations.edit', $designation));
        $resEdit->assertStatus(200);
        $resEdit->assertSee('Edit Designation');
        $resEdit->assertSee('Supply Chain Lead');
    }

    public function test_party_creation_with_contact_stores_designation_foreign_key(): void
    {
        $designation = Designation::create([
            'name' => 'Executive Director',
            'code' => 'ED',
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Apex Construction Co',
            'party_type' => 'both',
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'contacts' => [
                [
                    'name' => 'Vikram Sharma',
                    'designation_id' => $designation->id,
                    'designation' => 'Executive Director',
                    'role' => 'Executive Director',
                    'phone' => '9876543210',
                    'email' => 'vikram@apex.test',
                    'is_primary' => 1,
                ],
            ],
            'addresses' => [
                [
                    'label' => 'Main Office',
                    'address_line_1' => '123 Commercial St',
                    'type' => 'both',
                    'is_default_billing' => 1,
                    'is_default_delivery' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('masters.customers.store'), $payload);
        $response->assertRedirect(route('masters.customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'Apex Construction Co',
        ]);

        $this->assertDatabaseHas('party_contacts', [
            'name' => 'Vikram Sharma',
            'designation_id' => $designation->id,
            'role' => 'Executive Director',
        ]);
    }
}
