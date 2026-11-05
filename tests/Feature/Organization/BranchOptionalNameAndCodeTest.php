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

class BranchOptionalNameAndCodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

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

        $bg = BusinessGroup::create([
            'name' => 'Apex Group',
            'code' => 'APX',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'business_group_id' => $bg->id,
            'name' => 'Apex Corp',
            'code' => 'CMP-APEX-01',
            'company_type' => 'Private Limited Company',
            'gstin' => '27AAACA1234A1Z5',
            'pan' => 'AAACA1234A',
            'is_active' => true,
        ]);
    }

    public function test_branch_form_does_not_show_code_and_requires_company(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.branches.create'));

        $response->assertOk();

        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/Company\s*<span class="text-red-500">\*<\/span>/', $content);
        $this->assertDoesNotMatchRegularExpression('/Name\s*<span class="text-red-500">\*<\/span>/', $content);
        $response->assertDontSee('name="code"', false);
    }

    public function test_branch_can_be_created_with_blank_name_and_auto_code(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.branches.store'), [
                'company_id' => $this->company->id,
                'name' => '',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('organization.branches.index'));

        $branch = Branch::latest('id')->firstOrFail();
        $this->assertSame($this->company->id, $branch->company_id);
        $this->assertNull($branch->getRawOriginal('name'));
        $this->assertNotNull($branch->getRawOriginal('code'));
        $this->assertStringStartsWith('BRN-'.$this->company->id.'-', (string) $branch->getRawOriginal('code'));
        $this->assertSame('Unnamed Branch', $branch->name);
    }

    public function test_branch_can_be_created_with_only_name_and_auto_code(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.branches.store'), [
                'company_id' => $this->company->id,
                'name' => 'North Distribution Branch',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('organization.branches.index'));

        $branch = Branch::where('company_id', $this->company->id)->latest('id')->firstOrFail();
        $this->assertSame('North Distribution Branch', $branch->getRawOriginal('name'));
        $this->assertNotNull($branch->getRawOriginal('code'));
        $this->assertStringStartsWith('BRN-'.$this->company->id.'-', (string) $branch->getRawOriginal('code'));
    }

    public function test_existing_branch_code_is_preserved_on_edit(): void
    {
        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Original Branch Name',
            'code' => 'ORIG-BR-01',
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('organization.branches.update', $branch), [
                'company_id' => $this->company->id,
                'name' => 'Updated Branch Name',
                'phone' => '9876543210',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('organization.branches.index'));
        $branch->refresh();
        $this->assertSame('Updated Branch Name', $branch->getRawOriginal('name'));
        $this->assertSame('ORIG-BR-01', $branch->getRawOriginal('code'));
    }

    public function test_company_remains_mandatory(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.branches.store'), [
                'company_id' => '',
                'name' => 'Branch With No Company',
            ]);

        $response->assertSessionHasErrors(['company_id']);
    }

    public function test_branch_index_displays_unnamed_branch_without_code_column(): void
    {
        Branch::create([
            'company_id' => $this->company->id,
            'name' => null,
            'code' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.branches.index'));

        $response->assertOk();
        $response->assertSee('Unnamed Branch');
        $response->assertDontSee('>Code</', false);
        $response->assertDontSee('Search Branch Name, Code, City', false);
    }

    public function test_blank_values_do_not_break_relationships_and_warehouses(): void
    {
        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => null,
            'code' => null,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Hub Warehouse',
            'is_active' => true,
        ]);

        $this->assertSame($branch->id, $warehouse->branch_id);
        $this->assertSame($this->company->id, $branch->company->id);
        $this->assertCount(1, $branch->warehouses);
        $this->assertSame('Central Hub Warehouse', $branch->warehouses->first()->name);
    }
}
