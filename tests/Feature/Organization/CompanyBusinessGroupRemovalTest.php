<?php

namespace Tests\Feature\Organization;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyBusinessGroupRemovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected BusinessGroup $businessGroup;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $roleAdmin->givePermissionTo(['organization.view', 'organization.manage']);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($roleAdmin);

        $this->businessGroup = BusinessGroup::create([
            'name' => 'Omni Enterprises Group',
            'code' => 'OEG-01',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'business_group_id' => $this->businessGroup->id,
            'name' => 'Apex Global Solutions',
            'legal_name' => 'Apex Global Solutions Private Limited',
            'code' => 'APX-GLB',
            'company_type' => 'Private Limited Company',
            'gstin' => '27ABCDE1234F1Z5',
            'pan' => 'ABCDE1234F',
            'is_active' => true,
        ]);

        $this->adminUser->update(['company_id' => $this->company->id]);
    }

    public function test_create_company_form_does_not_display_business_group_dropdown(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.companies.create'));

        $response->assertOk();
        $response->assertSee('Trade Name');
        $response->assertSee('Legal Name');
        $response->assertSee('Company Type');
        $response->assertDontSee('name="business_group_id"', false);
        $response->assertDontSee('id="business_group_id"', false);
        $response->assertDontSee('Business Group');
    }

    public function test_edit_company_form_does_not_display_business_group_dropdown(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.companies.edit', $this->company));

        $response->assertOk();
        $response->assertSee('Apex Global Solutions');
        $response->assertSee('Trade Name');
        $response->assertSee('Legal Name');
        $response->assertSee('Company Type');
        $response->assertDontSee('name="business_group_id"', false);
        $response->assertDontSee('id="business_group_id"', false);
        $response->assertDontSee('Business Group');
    }

    public function test_company_details_show_page_does_not_display_business_group(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.companies.show', $this->company));

        $response->assertOk();
        $response->assertSee('Apex Global Solutions');
        $response->assertSee('APX-GLB');
        $response->assertDontSee('Business Group:');
        $response->assertDontSee('Omni Enterprises Group');
    }

    public function test_can_create_new_company_without_business_group(): void
    {
        $payload = [
            'name' => 'Nova Logistics Pvt Ltd',
            'legal_name' => 'Nova Logistics Private Limited',
            'company_type' => 'Private Limited Company',
            'gstin' => '24ABCDE5678G1Z9',
            'pan' => 'ABCDE5678G',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.companies.store'), $payload);

        $response->assertRedirect(route('organization.companies.index'));

        $this->assertDatabaseHas('companies', [
            'name' => 'Nova Logistics Pvt Ltd',
            'gstin' => '24ABCDE5678G1Z9',
            'business_group_id' => null,
        ]);
    }

    public function test_can_edit_existing_company_and_preserves_existing_business_group_data(): void
    {
        $payload = [
            'name' => 'Apex Global Solutions Updated',
            'legal_name' => 'Apex Global Solutions Private Limited',
            'company_type' => 'Private Limited Company',
            'gstin' => '27ABCDE1234F1Z5',
            'pan' => 'ABCDE1234F',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('organization.companies.update', $this->company), $payload);

        $response->assertRedirect(route('organization.companies.index'));

        $fresh = $this->company->fresh();
        $this->assertEquals('Apex Global Solutions Updated', $fresh->name);
        // Preserves existing business_group_id in database
        $this->assertEquals($this->businessGroup->id, $fresh->business_group_id);
    }

    public function test_existing_companies_remain_accessible_and_viewable(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.companies.index'));

        $response->assertOk();
        $response->assertSee('Apex Global Solutions');
    }
}
