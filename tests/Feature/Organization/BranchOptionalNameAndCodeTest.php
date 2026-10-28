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

    public function test_branch_form_ui_has_optional_name_and_code_and_required_company(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.branches.create'));

        $response->assertOk();

        // Company label has the required asterisk
        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/Company\s*<span class="text-red-500">\*<\/span>/', $content);

        // Name and Code labels must not have the required asterisk
        $this->assertDoesNotMatchRegularExpression('/Name\s*<span class="text-red-500">\*<\/span>/', $content);
        $this->assertDoesNotMatchRegularExpression('/Code\s*<span class="text-red-500">\*<\/span>/', $content);
    }

    public function test_branch_can_be_created_with_name_and_code_both_blank(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.branches.store'), [
                'company_id' => $this->company->id,
                'name' => '',
                'code' => '',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('organization.branches.index'));

        $branch = Branch::latest('id')->firstOrFail();
        $this->assertSame($this->company->id, $branch->company_id);
        $this->assertNull($branch->getRawOriginal('name'));
        $this->assertNull($branch->getRawOriginal('code'));
        $this->assertSame('Unnamed Branch', $branch->name);
        $this->assertSame('—', $branch->display_code);
    }

    public function test_multiple_branches_can_be_created_with_blank_codes_under_same_company(): void
    {
        $branch1 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Branch One',
            'code' => null,
            'is_active' => true,
        ]);

        $branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Branch Two',
            'code' => null,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('branches', ['id' => $branch1->id, 'name' => 'Branch One']);
        $this->assertDatabaseHas('branches', ['id' => $branch2->id, 'name' => 'Branch Two']);
        $this->assertNull($branch1->fresh()->getRawOriginal('code'));
        $this->assertNull($branch2->fresh()->getRawOriginal('code'));
    }

    public function test_branch_can_be_created_with_only_name(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.branches.store'), [
                'company_id' => $this->company->id,
                'name' => 'North Distribution Branch',
                'code' => '',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('organization.branches.index'));

        $branch = Branch::where('company_id', $this->company->id)->latest('id')->firstOrFail();
        $this->assertSame('North Distribution Branch', $branch->getRawOriginal('name'));
        $this->assertNull($branch->getRawOriginal('code'));
        $this->assertSame('North Distribution Branch', $branch->name);
        $this->assertSame('—', $branch->display_code);
    }

    public function test_branch_can_be_created_with_only_code(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.branches.store'), [
                'company_id' => $this->company->id,
                'name' => '',
                'code' => 'BR-ONLY-CODE-01',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('organization.branches.index'));

        $branch = Branch::where('company_id', $this->company->id)->latest('id')->firstOrFail();
        $this->assertNull($branch->getRawOriginal('name'));
        $this->assertSame('BR-ONLY-CODE-01', $branch->getRawOriginal('code'));
        $this->assertSame('Unnamed Branch', $branch->name);
        $this->assertSame('BR-ONLY-CODE-01', $branch->code);
    }

    public function test_branch_can_be_created_with_both_fields_populated(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.branches.store'), [
                'company_id' => $this->company->id,
                'name' => 'South Zone Branch',
                'code' => 'SZ-001',
                'state' => 'Maharashtra',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('organization.branches.index'));

        $branch = Branch::where('company_id', $this->company->id)->latest('id')->firstOrFail();
        $this->assertSame('South Zone Branch', $branch->getRawOriginal('name'));
        $this->assertSame('SZ-001', $branch->getRawOriginal('code'));
        $this->assertSame('Maharashtra', $branch->state);
    }

    public function test_existing_branches_can_be_edited_without_losing_saved_values(): void
    {
        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Original Branch Name',
            'code' => 'ORIG-BR-01',
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        // Edit with updated name, preserving code
        $response = $this->actingAs($this->adminUser)
            ->put(route('organization.branches.update', $branch), [
                'company_id' => $this->company->id,
                'name' => 'Updated Branch Name',
                'code' => 'ORIG-BR-01',
                'phone' => '9876543210',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('organization.branches.index'));
        $branch->refresh();
        $this->assertSame('Updated Branch Name', $branch->getRawOriginal('name'));
        $this->assertSame('ORIG-BR-01', $branch->getRawOriginal('code'));

        // Edit clearing code
        $response2 = $this->actingAs($this->adminUser)
            ->put(route('organization.branches.update', $branch), [
                'company_id' => $this->company->id,
                'name' => 'Updated Branch Name',
                'code' => '',
                'phone' => '9876543210',
                'is_active' => '1',
            ]);

        $response2->assertRedirect(route('organization.branches.index'));
        $branch->refresh();
        $this->assertSame('Updated Branch Name', $branch->getRawOriginal('name'));
        $this->assertNull($branch->getRawOriginal('code'));
    }

    public function test_company_remains_mandatory(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.branches.store'), [
                'company_id' => '',
                'name' => 'Branch With No Company',
                'code' => 'BNC-01',
            ]);

        $response->assertSessionHasErrors(['company_id']);
    }

    public function test_duplicate_non_blank_codes_within_same_company_are_still_rejected(): void
    {
        Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Branch Existing',
            'code' => 'DUP-CODE-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.branches.store'), [
                'company_id' => $this->company->id,
                'name' => 'Branch Another',
                'code' => 'DUP-CODE-01',
            ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_branch_index_displays_unnamed_branch_and_em_dash_for_blank_fields(): void
    {
        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => null,
            'code' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.branches.index'));

        $response->assertOk();
        $response->assertSee('Unnamed Branch');
        $response->assertSee('—');
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
