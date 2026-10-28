<?php

namespace Tests\Feature\Organization;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyCreateAndEditWithoutCodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected BusinessGroup $businessGroup;

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
            'name' => 'Global Group',
            'code' => 'GLB',
            'is_active' => true,
        ]);
    }

    public function test_create_company_form_does_not_contain_company_code_input(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.companies.create'));

        $response->assertOk();
        $response->assertSee('Trade Name');
        $response->assertSee('Legal Name');
        $response->assertSee('Company Type');
        $response->assertDontSee('name="code"', false);
        $response->assertDontSee('label="Company Code"', false);
    }

    public function test_edit_company_form_does_not_contain_company_code_input(): void
    {
        $company = Company::create([
            'name' => 'Original Corp',
            'code' => 'CMP-ORIG',
            'business_group_id' => $this->businessGroup->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.companies.edit', $company));

        $response->assertOk();
        $response->assertSee('Trade Name');
        $response->assertSee('Original Corp');
        $response->assertDontSee('name="code"', false);
        $response->assertDontSee('label="Company Code"', false);
    }

    public function test_company_can_be_created_without_providing_code_and_auto_generates_code(): void
    {
        $payload = [
            'name' => 'Beta Technologies Pvt Ltd',
            'legal_name' => 'Beta Technologies Private Limited',
            'company_type' => 'Private Limited Company',
            'business_group_id' => $this->businessGroup->id,
            'gstin' => '27ABCDE1234F1Z5',
            'pan' => 'ABCDE1234F',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.companies.store'), $payload);

        $response->assertRedirect(route('organization.companies.index'));

        $this->assertDatabaseHas('companies', [
            'name' => 'Beta Technologies Pvt Ltd',
            'legal_name' => 'Beta Technologies Private Limited',
            'gstin' => '27ABCDE1234F1Z5',
        ]);

        $created = Company::where('name', 'Beta Technologies Pvt Ltd')->firstOrFail();
        $this->assertNotEmpty($created->code);
        $this->assertStringStartsWith('CMP-', $created->code);
    }

    public function test_updating_company_without_code_preserves_existing_code(): void
    {
        $company = Company::create([
            'name' => 'Gamma Innovations',
            'code' => 'CMP-EXISTING-99',
            'business_group_id' => $this->businessGroup->id,
            'gstin' => '27AABCG1234F1Z5',
            'pan' => 'AABCG1234F',
            'company_type' => 'Private Limited Company',
            'is_active' => true,
        ]);

        $updatePayload = [
            'name' => 'Gamma Innovations Global',
            'legal_name' => 'Gamma Innovations Global LLP',
            'company_type' => 'Limited Liability Partnership (LLP)',
            'business_group_id' => $this->businessGroup->id,
            'gstin' => '27AABCG1234F1Z5',
            'pan' => 'AABCG1234F',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('organization.companies.update', $company), $updatePayload);

        $response->assertRedirect(route('organization.companies.index'));

        $company->refresh();
        $this->assertSame('Gamma Innovations Global', $company->name);
        $this->assertSame('CMP-EXISTING-99', $company->code);
    }

    public function test_default_company_toggle_and_dependent_branches_warehouses_work(): void
    {
        $company = Company::create([
            'name' => 'Default Testing Corp',
            'code' => 'CMP-DEF-01',
            'business_group_id' => $this->businessGroup->id,
            'is_active' => true,
            'is_default' => false,
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'HQ Branch',
            'code' => 'BR-HQ-01',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Warehouse',
            'is_active' => true,
        ]);

        // Set as default
        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.companies.set-default', $company));

        $response->assertRedirect();
        $company->refresh();
        $this->assertTrue((bool) $company->is_default);

        // Dependent relationships intact
        $this->assertCount(1, $company->branches);
        $this->assertCount(1, $company->warehouses);
        $this->assertSame('Central Warehouse', $company->warehouses->first()->name);
    }
}
