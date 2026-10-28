<?php

namespace Tests\Feature\Organization;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyDefaultFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected BusinessGroup $businessGroup;
    protected Company $companyA;
    protected Company $companyB;
    protected Company $inactiveCompany;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $roleRegular = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->businessGroup = BusinessGroup::create([
            'name' => 'Acme Group',
            'code' => 'ACM',
            'is_active' => true,
        ]);

        $this->companyA = Company::create([
            'business_group_id' => $this->businessGroup->id,
            'name' => 'Company Alpha',
            'code' => 'ALP01',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->companyB = Company::create([
            'business_group_id' => $this->businessGroup->id,
            'name' => 'Company Beta',
            'code' => 'BET01',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->inactiveCompany = Company::create([
            'business_group_id' => $this->businessGroup->id,
            'name' => 'Company Inactive',
            'code' => 'INA01',
            'is_active' => false,
            'is_default' => false,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => null, // Unrestricted admin
        ]);
        $this->adminUser->assignRole($roleAdmin);

        $this->regularUser = User::factory()->create([
            'company_id' => $this->companyB->id, // Restricted to Company B
        ]);
        $this->regularUser->assignRole($roleRegular);
    }

    public function test_user_with_permission_can_set_company_as_default(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('organization.companies.set-default', $this->companyA));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => "{$this->companyA->name} set as default company.",
        ]);

        $this->assertTrue($this->companyA->fresh()->is_default);
        $this->assertFalse($this->companyB->fresh()->is_default);
        $this->assertEquals($this->companyA->id, Company::defaultCompanyId());
    }

    public function test_setting_another_company_as_default_replaces_previous_default(): void
    {
        // Set Alpha as default first
        $this->companyA->setAsDefault();
        $this->assertTrue($this->companyA->fresh()->is_default);

        // Switch default to Beta
        $response = $this->actingAs($this->adminUser)
            ->from(route('organization.companies.index'))
            ->post(route('organization.companies.set-default', $this->companyB));

        $response->assertRedirect(route('organization.companies.index'));
        $this->assertFalse($this->companyA->fresh()->is_default);
        $this->assertTrue($this->companyB->fresh()->is_default);

        // Ensure only 1 company has is_default = true in the whole database
        $this->assertEquals(1, Company::where('is_default', true)->count());
        $this->assertEquals($this->companyB->id, Company::defaultCompanyId());
    }

    public function test_cannot_set_inactive_company_as_default(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('organization.companies.set-default', $this->inactiveCompany));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Cannot set an inactive company as the default company.',
        ]);

        $this->assertFalse($this->inactiveCompany->fresh()->is_default);
    }

    public function test_unauthorized_user_cannot_set_default_company(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson(route('organization.companies.set-default', $this->companyA));

        $response->assertStatus(403);
        $this->assertFalse($this->companyA->fresh()->is_default);
    }

    public function test_default_badge_and_actions_in_companies_index(): void
    {
        $this->companyA->setAsDefault();

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.companies.index'));

        $response->assertOk();
        // Default badge
        $response->assertSee('Default');
        // Default company button indicator for Company Alpha
        $response->assertSee('Default Company');
        // Set as Default button for Company Beta
        $response->assertSee('Set as Default');
    }

    public function test_cannot_delete_default_company(): void
    {
        $this->companyA->setAsDefault();

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.companies.destroy', $this->companyA));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Cannot delete the default company. Please set another company as default first.',
        ]);

        $this->assertDatabaseHas('companies', ['id' => $this->companyA->id]);
    }

    public function test_cannot_deactivate_default_company_via_update(): void
    {
        $this->companyA->setAsDefault();

        $response = $this->actingAs($this->adminUser)
            ->put(route('organization.companies.update', $this->companyA), [
                'name' => 'Company Alpha Updated',
                'company_type' => 'Private Limited Company',
                'gstin' => '27ABCDE1234F1Z5',
                'pan' => 'ABCDE1234F',
                'is_active' => '0', // Attempt to deactivate
            ]);

        $response->assertSessionHas('error', 'Cannot deactivate the default company. Please set another active company as default before deactivating this one.');
        $this->assertTrue($this->companyA->fresh()->is_active);
    }

    public function test_resolve_current_respects_user_assigned_company_and_falls_back_to_default(): void
    {
        $this->companyA->setAsDefault();

        // 1. Regular user assigned to Company B should resolve Company B
        $this->actingAs($this->regularUser);
        $this->assertEquals($this->companyB->id, Company::resolveCurrent()->id);

        // 2. Unrestricted admin user should resolve Default Company (Company Alpha)
        $this->actingAs($this->adminUser);
        $this->assertEquals($this->companyA->id, Company::resolveCurrent()->id);
    }

    public function test_branch_and_warehouse_create_preselects_default_company(): void
    {
        $this->companyA->setAsDefault();

        // Branch create
        $branchRes = $this->actingAs($this->adminUser)
            ->get(route('organization.branches.create'));
        $branchRes->assertOk();
        $this->assertEquals($this->companyA->id, $branchRes->viewData('item')->company_id);

        // Warehouse create
        $warehouseRes = $this->actingAs($this->adminUser)
            ->get(route('organization.warehouses.create'));
        $warehouseRes->assertOk();
        $this->assertEquals($this->companyA->id, $warehouseRes->viewData('item')->company_id);
    }
}

