<?php

namespace Tests\Feature\Organization;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessGroupModalLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected BusinessGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.edit', 'guard_name' => 'web']);

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->group = BusinessGroup::firstOrCreate(
            ['code' => 'ALPHA-GRP'],
            [
                'name' => 'Alpha Holdings',
                'description' => 'Alpha Holdings conglomerate',
                'is_active' => true,
            ]
        );

        $this->company = Company::create([
            'business_group_id' => $this->group->id,
            'name' => 'Alpha Industries Ltd',
            'code' => 'ALP-01',
            'legal_name' => 'Alpha Industries Limited',
            'company_type' => 'Public Limited',
            'gstin' => '27AAACA1234A1Z5',
            'pan' => 'AAACA1234A',
            'relationship_type' => 'Parent',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Admin Controller',
            'email' => 'admin.bgmodal@example.com',
        ]);
        $this->adminUser->assignRole($roleAdmin);
    }

    public function test_business_groups_page_renders_with_sidebar_safe_modal_layout(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.business-groups.index'));

        $response->assertStatus(200);
        $response->assertViewIs('organization.business-groups.index');

        // Modal teleported to body and centered in full viewport above sidebar
        $response->assertSee('x-teleport="body"', false);
        $response->assertSee('fixed inset-0 z-[100]', false);
        $response->assertSee('min-h-screen items-center justify-center', false);
        $response->assertDontSee('left-0 lg:left-64', false);

        // Modal dimension and scrolling rules
        $response->assertSee('max-h-[88vh]', false);
        $response->assertSee('overflow-y-auto', false);
        $response->assertSee('sticky top-0 z-10', false);
        $response->assertSee('sticky bottom-0 z-10', false);

        // Ensure all required fields exist in modal form via Alpine data model and labels
        $response->assertSee('x-model="form.name"', false);
        $response->assertSee('Group Name');
        $response->assertSee('x-model="form.code"', false);
        $response->assertSee('Group Code');
        $response->assertSee('x-model="parentCompanySearch"', false);
        $response->assertSee('Parent Company');
        $response->assertSee('x-model="form.incorporation_date"', false);
        $response->assertSee('Incorporation Date');
        $response->assertSee('x-model="form.headquarters"', false);
        $response->assertSee('Headquarters');
        $response->assertSee('x-model="form.country"', false);
        $response->assertSee('Country');
        $response->assertSee('x-model="form.industry"', false);
        $response->assertSee('Industry');
        $response->assertSee('x-model="form.website"', false);
        $response->assertSee('Website');
        $response->assertSee('x-model="form.email"', false);
        $response->assertSee('Email');
        $response->assertSee('x-model="form.phone"', false);
        $response->assertSee('Phone');
        $response->assertSee('x-model="form.is_active"', false);
        $response->assertSee('Status');
        $response->assertSee('x-model="form.description"', false);
        $response->assertSee('Description');
        $response->assertSee('x-model="form.notes"', false);
        $response->assertSee('Notes');

        // Ensure logo emoji container wraps properly
        $response->assertSee('flex items-center gap-1 flex-wrap', false);
    }

    public function test_view_and_delete_modals_also_use_sidebar_safe_boundary(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.business-groups.index'));

        $response->assertStatus(200);

        // All 3 modals (Add/Edit, View, Delete) teleport to body with full viewport stacking
        $content = $response->getContent();
        $needle = 'x-teleport="body"';
        $occurrences = substr_count($content, $needle);
        $this->assertGreaterThanOrEqual(3, $occurrences, 'Add/Edit, View, and Delete modals should all teleport to body.');
        $response->assertDontSee('left-0 lg:left-64', false);
    }

    public function test_can_create_business_group_successfully(): void
    {
        $payload = [
            'name' => 'Titan Enterprises',
            'code' => 'TITAN-GRP',
            'parent_company_id' => $this->company->id,
            'incorporation_date' => '2019-04-10',
            'headquarters' => 'Mumbai, MH',
            'country' => 'India',
            'industry' => 'Manufacturing & Industrial',
            'website' => 'https://titan-enterprises.example.com',
            'email' => 'contact@titan-enterprises.example.com',
            'phone' => '+91 98200 12345',
            'logo_emoji' => '🏭',
            'description' => 'Heavy industries and engineering cluster.',
            'notes' => 'Key sister concern holding company.',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.business-groups.store'), $payload);

        $response->assertRedirect(route('organization.business-groups.index'));

        $this->assertDatabaseHas('business_groups', [
            'name' => 'Titan Enterprises',
            'code' => 'TITAN-GRP',
            'parent_company_id' => $this->company->id,
            'headquarters' => 'Mumbai, MH',
            'industry' => 'Manufacturing & Industrial',
            'logo_emoji' => '🏭',
            'is_active' => true,
        ]);
    }
}

