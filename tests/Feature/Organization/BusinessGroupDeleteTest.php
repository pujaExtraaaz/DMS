<?php

namespace Tests\Feature\Organization;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessGroupDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected BusinessGroup $defaultGroup;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.edit', 'guard_name' => 'web']);

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $roleRegular = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->defaultGroup = BusinessGroup::firstOrCreate(
            ['code' => 'DEFAULT'],
            [
                'name' => 'Default Group',
                'description' => 'System default business group',
                'is_active' => true,
            ]
        );

        $this->company = Company::firstOrCreate(
            ['code' => 'ADC-01'],
            [
                'business_group_id' => $this->defaultGroup->id,
                'name' => 'Avit Digital Central Ltd',
                'is_active' => true,
            ]
        );

        if (! $this->defaultGroup->companies()->where('companies.id', $this->company->id)->exists()) {
            $this->defaultGroup->companies()->attach($this->company->id);
        }

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($roleAdmin);

        $this->regularUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->regularUser->assignRole($roleRegular);
    }

    /**
     * Test 1: The Delete button appears beside Edit for eligible groups and shows confirmation modal.
     */
    public function test_delete_button_and_confirmation_modal_appear_for_eligible_groups(): void
    {
        $customGroup = BusinessGroup::create([
            'name' => 'Northern Operations Group',
            'code' => 'NOG-01',
            'description' => 'Regional group',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.business-groups.index'));

        $response->assertOk();
        $response->assertSee('Northern Operations Group');
        $response->assertSee(route('organization.business-groups.edit', $customGroup));
        $response->assertSee('data-delete-group="' . $customGroup->id . '"', false);
        $response->assertSee('Delete Sister Concern Group');
        $response->assertSee('Are you sure you want to delete this group? This action cannot be undone.');
        $response->assertSee('Cancel');
        $response->assertSee('Delete');
    }

    /**
     * Test 2: The Default Group is protected with a disabled Delete button explaining why.
     */
    public function test_default_group_has_disabled_delete_button_and_explanation(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.business-groups.index'));

        $response->assertOk();
        $response->assertSee('Default Group');
        $response->assertSee('Default Group cannot be deleted as it is a required system group.');
        $response->assertSee('disabled', false);

        // Verify Default Group is not deletable on the model
        $this->assertTrue($this->defaultGroup->isDefault());
        $this->assertFalse($this->defaultGroup->canBeDeleted());
        $this->assertStringContainsString('Default Group', (string) $this->defaultGroup->getDeleteBlockReason());
    }

    /**
     * Test 3: Backend blocks deletion of Default Group even if requested directly.
     */
    public function test_backend_blocks_deletion_of_default_group(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.business-groups.destroy', $this->defaultGroup));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Cannot delete the Default Group as it is a required system group.',
        ]);

        $this->assertDatabaseHas('business_groups', [
            'id' => $this->defaultGroup->id,
            'code' => 'DEFAULT',
        ]);
    }

    /**
     * Test 4: Cancel closes the dialog without deleting anything.
     */
    public function test_canceling_confirmation_does_not_delete_group(): void
    {
        $group = BusinessGroup::create([
            'name' => 'Preserved Group',
            'code' => 'PG-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.business-groups.index'));

        $response->assertOk();
        $response->assertSee('Preserved Group');

        $this->assertDatabaseHas('business_groups', [
            'id' => $group->id,
            'name' => 'Preserved Group',
        ]);
    }

    /**
     * Test 5: Confirming deletion removes the selected eligible group from the database.
     */
    public function test_confirming_deletion_removes_eligible_group(): void
    {
        $group = BusinessGroup::create([
            'name' => 'Eastern Logistics Group',
            'code' => 'ELG-01',
            'is_active' => true,
        ]);

        $this->assertTrue($group->canBeDeleted());
        $this->assertNull($group->getDeleteBlockReason());

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.business-groups.destroy', $group));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => "Sister concern group 'Eastern Logistics Group' deleted successfully.",
        ]);

        $this->assertDatabaseMissing('business_groups', [
            'id' => $group->id,
        ]);
    }

    /**
     * Test 6: Deletion targets exact database ID when multiple groups have similar names.
     */
    public function test_deletion_deletes_exact_id_even_with_similar_names(): void
    {
        $group1 = BusinessGroup::create([
            'name' => 'Alpha Industrial Group',
            'code' => 'AIG-01',
            'is_active' => true,
        ]);

        $group2 = BusinessGroup::create([
            'name' => 'Alpha Industrial Group',
            'code' => 'AIG-02',
            'is_active' => true,
        ]);

        $group3 = BusinessGroup::create([
            'name' => 'Alpha Industrial Group 2',
            'code' => 'AIG-03',
            'is_active' => true,
        ]);

        // Delete group1 specifically by ID
        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.business-groups.destroy', $group1));

        $response->assertOk();

        // Group 1 is gone
        $this->assertDatabaseMissing('business_groups', ['id' => $group1->id]);

        // Group 2 and 3 remain completely intact
        $this->assertDatabaseHas('business_groups', ['id' => $group2->id, 'code' => 'AIG-02']);
        $this->assertDatabaseHas('business_groups', ['id' => $group3->id, 'code' => 'AIG-03']);
    }

    /**
     * Test 7: Deletion works for eligible groups regardless of active or inactive status.
     */
    public function test_deletion_works_for_both_active_and_inactive_groups(): void
    {
        $activeGroup = BusinessGroup::create([
            'name' => 'Active Clean Group',
            'code' => 'ACG-01',
            'is_active' => true,
        ]);

        $inactiveGroup = BusinessGroup::create([
            'name' => 'Inactive Clean Group',
            'code' => 'ICG-01',
            'is_active' => false,
        ]);

        $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.business-groups.destroy', $activeGroup))
            ->assertOk();

        $this->assertDatabaseMissing('business_groups', ['id' => $activeGroup->id]);

        $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.business-groups.destroy', $inactiveGroup))
            ->assertOk();

        $this->assertDatabaseMissing('business_groups', ['id' => $inactiveGroup->id]);
    }

    /**
     * Test 8: Deletion is blocked with a clear user error if companies are attached to the group.
     */
    public function test_deletion_is_blocked_if_companies_are_attached(): void
    {
        $groupWithCompany = BusinessGroup::create([
            'name' => 'Attached Group Ltd',
            'code' => 'AGL-01',
            'is_active' => true,
        ]);

        $attachedCo = Company::create([
            'business_group_id' => $groupWithCompany->id,
            'name' => 'Attached Company Ltd',
            'code' => 'ACL-01',
            'is_active' => true,
        ]);

        $groupWithCompany->companies()->attach($attachedCo->id);

        $this->assertFalse($groupWithCompany->canBeDeleted());
        $this->assertStringContainsString('company(ies) are currently attached', (string) $groupWithCompany->getDeleteBlockReason());

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.business-groups.destroy', $groupWithCompany));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Cannot delete sister concern group because 1 company(ies) are currently attached to it. Please reassign or detach the companies before deleting this group.',
        ]);

        // Group must still exist
        $this->assertDatabaseHas('business_groups', ['id' => $groupWithCompany->id]);

        // Attached company must remain untouched
        $this->assertDatabaseHas('companies', [
            'id' => $attachedCo->id,
            'business_group_id' => $groupWithCompany->id,
            'name' => 'Attached Company Ltd',
        ]);
    }

    /**
     * Test 9: Deleting a group does not delete companies, branches, warehouses, or financial years.
     */
    public function test_deleting_group_does_not_cascade_delete_other_modules(): void
    {
        $group = BusinessGroup::create([
            'name' => 'Isolated Concern Group',
            'code' => 'ICG-99',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Independent Branch',
            'code' => 'IND-BR',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Independent Warehouse',
            'code' => 'IND-WH',
            'is_active' => true,
        ]);

        $fy = FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27 Independent',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.business-groups.destroy', $group));

        $response->assertOk();

        // Group is deleted
        $this->assertDatabaseMissing('business_groups', ['id' => $group->id]);

        // All related and unrelated records in other modules remain intact
        $this->assertDatabaseHas('companies', ['id' => $this->company->id]);
        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
        $this->assertDatabaseHas('financial_years', ['id' => $fy->id]);
    }

    /**
     * Test 10: Standard web form redirect DELETE request succeeds with flash message.
     */
    public function test_standard_web_redirect_delete_succeeds(): void
    {
        $group = BusinessGroup::create([
            'name' => 'Web Form Group',
            'code' => 'WFG-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('organization.business-groups.destroy', $group));

        $response->assertRedirect(route('organization.business-groups.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('business_groups', ['id' => $group->id]);
    }

    /**
     * Test 11: Unauthorized users cannot delete sister concern groups.
     */
    public function test_unauthorized_user_cannot_delete_group(): void
    {
        $group = BusinessGroup::create([
            'name' => 'Protected Group',
            'code' => 'PG-02',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->deleteJson(route('organization.business-groups.destroy', $group));

        $response->assertStatus(403);
        $this->assertDatabaseHas('business_groups', ['id' => $group->id]);
    }

    /**
     * Test 12: Deleting a non-existent group returns 404.
     */
    public function test_deleting_non_existent_group_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/organization/business-groups/999999');

        $response->assertStatus(404);
    }
}
