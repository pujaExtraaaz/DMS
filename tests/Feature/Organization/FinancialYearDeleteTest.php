<?php

namespace Tests\Feature\Organization;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancialYearDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $ownerUser;
    protected User $regularUser;
    protected BusinessGroup $businessGroup;
    protected Company $activeCompany;
    protected Company $inactiveCompany;
    protected Company $otherCompany;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.edit', 'guard_name' => 'web']);

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $roleOwner = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $roleRegular = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->businessGroup = BusinessGroup::create([
            'name' => 'FY Test Group',
            'code' => 'FYTG01',
            'is_active' => true,
        ]);

        $this->activeCompany = Company::create([
            'business_group_id' => $this->businessGroup->id,
            'name' => 'Active Company Ltd',
            'code' => 'ACL01',
            'is_active' => true,
        ]);

        $this->inactiveCompany = Company::create([
            'business_group_id' => $this->businessGroup->id,
            'name' => 'Inactive Company Ltd',
            'code' => 'ICL02',
            'is_active' => false,
        ]);

        $this->otherCompany = Company::create([
            'business_group_id' => $this->businessGroup->id,
            'name' => 'Other Company Ltd',
            'code' => 'OCL03',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->activeCompany->id,
        ]);
        $this->adminUser->assignRole($roleAdmin);

        $this->ownerUser = User::factory()->create([
            'company_id' => $this->activeCompany->id,
        ]);
        $this->ownerUser->assignRole($roleOwner);
        $this->ownerUser->givePermissionTo('organization.manage');

        $this->regularUser = User::factory()->create([
            'company_id' => $this->activeCompany->id,
        ]);
        $this->regularUser->assignRole($roleRegular);
    }

    /**
     * Requirement 1: Red Delete button with trash icon appears alongside existing actions for all records (Current, Open, Closed).
     */
    public function test_delete_button_appears_alongside_existing_actions_for_all_records(): void
    {
        $currentFy = FinancialYear::create([
            'company_id' => $this->activeCompany->id,
            'name' => '2026-27 Current',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);

        $openFy = FinancialYear::create([
            'company_id' => $this->activeCompany->id,
            'name' => '2027-28 Open',
            'starts_on' => '2027-04-01',
            'ends_on' => '2028-03-31',
            'is_current' => false,
            'is_closed' => false,
        ]);

        $closedFy = FinancialYear::create([
            'company_id' => $this->inactiveCompany->id,
            'name' => '2025-26 Closed',
            'starts_on' => '2025-04-01',
            'ends_on' => '2026-03-31',
            'is_current' => false,
            'is_closed' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.financial-years.index'));

        $response->assertOk();
        $response->assertSee('2026-27 Current');
        $response->assertSee('2027-28 Open');
        $response->assertSee('2025-26 Closed');

        // Existing buttons remain present
        $response->assertSee(route('organization.financial-years.edit', $currentFy));
        $response->assertSee(route('organization.financial-years.edit', $openFy));
        $response->assertSee(route('organization.financial-years.edit', $closedFy));
        $response->assertSee('Set Current');
        $response->assertSee('Close');
        $response->assertSee('Re-open');

        // Delete buttons with trash icon and data attributes present for all three
        $response->assertSee('data-delete-financial-year="' . $currentFy->id . '"', false);
        $response->assertSee('data-delete-financial-year="' . $openFy->id . '"', false);
        $response->assertSee('data-delete-financial-year="' . $closedFy->id . '"', false);
        $response->assertSee('Delete Financial Year?');
    }

    /**
     * Requirement 2: Confirmation dialog elements and warning text are rendered.
     */
    public function test_confirmation_dialog_elements_rendered_in_view(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.financial-years.index'));

        $response->assertOk();
        $response->assertSee('Delete Financial Year?');
        $response->assertSee('Deleting this financial year may affect accounting records associated with this period. Please confirm that you want to continue.');
        $response->assertSee('Delete Financial Year');
        $response->assertSee('Cancel');
    }

    /**
     * Requirement 2b: Canceling confirmation does not delete financial year.
     */
    public function test_canceling_confirmation_does_not_delete_financial_year(): void
    {
        $fy = FinancialYear::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Preserved FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => false,
            'is_closed' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.financial-years.index'));

        $response->assertOk();
        $response->assertSee('Preserved FY 2026-27');

        $this->assertDatabaseHas('financial_years', [
            'id' => $fy->id,
            'name' => 'Preserved FY 2026-27',
        ]);
    }

    /**
     * Requirement 3: An eligible financial year can be deleted successfully via JSON and standard request.
     */
    public function test_eligible_financial_year_can_be_deleted_successfully(): void
    {
        $fy = FinancialYear::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Eligible FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => false,
            'is_closed' => false,
        ]);

        $this->assertTrue($fy->canBeDeleted());
        $this->assertNull($fy->getDeleteBlockReason());

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.financial-years.destroy', $fy));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => "Financial year 'Eligible FY 2026-27' deleted successfully.",
        ]);

        $this->assertDatabaseMissing('financial_years', ['id' => $fy->id]);
    }

    /**
     * Requirement 4: Financial year deletion is allowed regardless of company status (Active or Inactive)
     * and without requiring the user to close the year first.
     */
    public function test_deletion_allowed_for_inactive_companies_and_unclosed_years(): void
    {
        // 4a. Active company with Open (unclosed) FY
        $fyOpen = FinancialYear::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Open FY in Active Co',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_closed' => false,
            'is_current' => false,
        ]);

        $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.financial-years.destroy', $fyOpen))
            ->assertOk();
        $this->assertDatabaseMissing('financial_years', ['id' => $fyOpen->id]);

        // 4b. Inactive company with Open FY
        $fyInactiveCo = FinancialYear::create([
            'company_id' => $this->inactiveCompany->id,
            'name' => 'Open FY in Inactive Co',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_closed' => false,
            'is_current' => false,
        ]);

        $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.financial-years.destroy', $fyInactiveCo))
            ->assertOk();
        $this->assertDatabaseMissing('financial_years', ['id' => $fyInactiveCo->id]);

        // 4c. Inactive company with Closed FY
        $fyClosed = FinancialYear::create([
            'company_id' => $this->inactiveCompany->id,
            'name' => 'Closed FY in Inactive Co',
            'starts_on' => '2025-04-01',
            'ends_on' => '2026-03-31',
            'is_closed' => true,
            'is_current' => false,
        ]);

        $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.financial-years.destroy', $fyClosed))
            ->assertOk();
        $this->assertDatabaseMissing('financial_years', ['id' => $fyClosed->id]);
    }

    /**
     * Requirement 5: When current financial year is deleted, safely transition current FY reference and clear cache.
     */
    public function test_deleting_current_financial_year_updates_current_reference_and_clears_cache(): void
    {
        $currentFy = FinancialYear::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Current FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);

        $nextFy = FinancialYear::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Upcoming FY 2027-28',
            'starts_on' => '2027-04-01',
            'ends_on' => '2028-03-31',
            'is_current' => false,
            'is_closed' => false,
        ]);

        // Prime the current_fy_pill cache
        Cache::put('current_fy_pill', $currentFy, 60);
        $this->assertTrue(Cache::has('current_fy_pill'));

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.financial-years.destroy', $currentFy));

        $response->assertOk();
        $this->assertDatabaseMissing('financial_years', ['id' => $currentFy->id]);

        // Next FY was automatically made current
        $nextFy->refresh();
        $this->assertTrue($nextFy->is_current);

        // Cache was cleared
        $this->assertFalse(Cache::has('current_fy_pill'));
    }

    /**
     * Requirement 5b: Deleting the only financial year in a company leaves no current FY without error.
     */
    public function test_deleting_only_current_financial_year_leaves_no_current_without_error(): void
    {
        $soleFy = FinancialYear::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Sole FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.financial-years.destroy', $soleFy));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => "Financial year 'Sole FY 2026-27' deleted successfully.",
        ]);
        $this->assertDatabaseMissing('financial_years', ['id' => $soleFy->id]);
        $this->assertDatabaseMissing('financial_years', ['company_id' => $this->activeCompany->id]);
    }

    /**
     * Requirement 6: Unauthorized users and cross-company tenant deletions are blocked with 403.
     */
    public function test_unauthorized_users_and_cross_company_deletions_are_blocked(): void
    {
        $fy = FinancialYear::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Protected FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => false,
            'is_closed' => false,
        ]);

        // Regular user without permissions gets 403 (route middleware or policy)
        $response1 = $this->actingAs($this->regularUser)
            ->deleteJson(route('organization.financial-years.destroy', $fy));

        $response1->assertStatus(403);
        $this->assertDatabaseHas('financial_years', ['id' => $fy->id]);

        // Viewer user with read-only organization.view permission gets 403 from controller destroy check
        $viewerUser = User::factory()->create([
            'company_id' => $this->activeCompany->id,
        ]);
        $viewerUser->givePermissionTo('organization.view');

        $responseViewer = $this->actingAs($viewerUser)
            ->deleteJson(route('organization.financial-years.destroy', $fy));

        $responseViewer->assertStatus(403);
        $responseViewer->assertJson([
            'success' => false,
            'message' => 'Unauthorized to delete financial year.',
        ]);
        $this->assertDatabaseHas('financial_years', ['id' => $fy->id]);

        // Manager of Company A cannot delete financial year belonging to Company B
        $otherFy = FinancialYear::create([
            'company_id' => $this->otherCompany->id,
            'name' => 'Other Co FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => false,
            'is_closed' => false,
        ]);

        $managerUser = User::factory()->create([
            'company_id' => $this->activeCompany->id,
        ]);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerRole->givePermissionTo('organization.manage');
        $managerUser->assignRole($managerRole);

        $response2 = $this->actingAs($managerUser)
            ->deleteJson(route('organization.financial-years.destroy', $otherFy));

        $response2->assertStatus(403);
        $response2->assertJson([
            'success' => false,
            'message' => 'Unauthorized: This financial year belongs to another company.',
        ]);
        $this->assertDatabaseHas('financial_years', ['id' => $otherFy->id]);
    }

    /**
     * Requirement 7: Protected historical records block deletion and return a clear error reason.
     */
    public function test_protected_records_prevent_accidental_deletion(): void
    {
        $fy = FinancialYear::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Audited FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => false,
            'is_closed' => false,
        ]);

        // If document sequence with issued documents exists
        if (Schema::hasTable('document_sequences')) {
            DB::table('document_sequences')->insert([
                'prefix' => 'INV',
                'sequence_date' => '2026-05-01',
                'last_number' => 15,
                'financial_year_id' => $fy->id,
                'company_id' => $this->activeCompany->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertFalse($fy->canBeDeleted());
            $this->assertNotNull($fy->getDeleteBlockReason());

            $response = $this->actingAs($this->adminUser)
                ->deleteJson(route('organization.financial-years.destroy', $fy));

            $response->assertStatus(422);
            $response->assertJson([
                'success' => false,
                'message' => 'Cannot delete financial year because document sequences have already issued numbered documents.',
            ]);
            $this->assertDatabaseHas('financial_years', ['id' => $fy->id]);
        }
    }

    /**
     * Requirement 8: Search, sorting, and existing actions (Edit, Set Current, Close) continue to work.
     */
    public function test_search_sorting_and_existing_actions_continue_to_work(): void
    {
        $fy1 = FinancialYear::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Alpha FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => false,
            'is_closed' => false,
        ]);

        $fy2 = FinancialYear::create([
            'company_id' => $this->activeCompany->id,
            'name' => 'Beta FY 2027-28',
            'starts_on' => '2027-04-01',
            'ends_on' => '2028-03-31',
            'is_current' => false,
            'is_closed' => false,
        ]);

        // Search test
        $resSearch = $this->actingAs($this->adminUser)
            ->get(route('organization.financial-years.index', ['search' => 'Alpha']));
        $resSearch->assertOk();
        $resSearch->assertSee('Alpha FY 2026-27');
        $resSearch->assertDontSee('Beta FY 2027-28');

        // Edit page loads successfully
        $resEdit = $this->actingAs($this->adminUser)
            ->get(route('organization.financial-years.edit', $fy1));
        $resEdit->assertOk();
        $resEdit->assertSee('Edit Financial Year');
        $resEdit->assertSee('2026-04-01');

        // Set Current works
        $resSetCurrent = $this->actingAs($this->adminUser)
            ->post(route('organization.financial-years.set-current', $fy1));
        $resSetCurrent->assertRedirect(route('organization.financial-years.index'));
        $fy1->refresh();
        $this->assertTrue($fy1->is_current);

        // Close works
        $resClose = $this->actingAs($this->adminUser)
            ->post(route('organization.financial-years.close', $fy1));
        $resClose->assertRedirect(route('organization.financial-years.index'));
        $fy1->refresh();
        $this->assertTrue($fy1->is_closed);
        $this->assertFalse($fy1->is_current);
    }
}
