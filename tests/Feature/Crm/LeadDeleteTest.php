<?php

namespace Tests\Feature\Crm;

use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\LeadActivity;
use App\Domains\Crm\Models\LeadAssignment;
use App\Domains\Crm\Models\LeadConversion;
use App\Domains\Crm\Models\LeadFollowup;
use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\ActivityLog;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['crm.view', 'crm.create', 'crm.edit', 'crm.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(array $permissions = ['crm.view', 'crm.edit'], ?Company $company = null): User
    {
        $company = $company ?? Company::firstOrCreate(
            ['code' => 'TESTCO'],
            ['name' => 'Test Company', 'currency' => 'INR', 'is_active' => true]
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        if (! empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    public function test_crm_leads_index_page_displays_delete_button_and_modal(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.edit']);
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'mobile' => '9876543210',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->get(route('crm.leads.index'));

        $response->assertOk();
        $response->assertSee('data-delete-lead="' . $lead->id . '"', false);
        $response->assertSee('data-lead-name="John Doe"', false);
        $response->assertSee('title="View Lead"', false);
        $response->assertSee('aria-label="View Lead"', false);
        $response->assertSee('title="Edit Lead"', false);
        $response->assertSee('aria-label="Edit Lead"', false);
        $response->assertSee('title="Delete Lead"', false);
        $response->assertSee('aria-label="Delete Lead"', false);
        $response->assertSee(route('crm.leads.show', $lead), false);
        $response->assertSee(route('crm.leads.edit', $lead), false);
        $response->assertSee(route('crm.leads.destroy', $lead), false);
        $response->assertSee('Are you sure you want to delete this lead? This action cannot be undone.');
    }

    public function test_permitted_user_can_delete_lead_via_ajax_request(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.manage']);
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'mobile' => '9876543211',
            'priority' => 'high',
            'status' => 'contacted',
        ]);

        $response = $this->actingAs($user)->deleteJson(route('crm.leads.destroy', $lead));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Lead deleted successfully.',
        ]);

        $this->assertDatabaseMissing('leads', [
            'id' => $lead->id,
        ]);
    }

    public function test_permitted_user_can_delete_lead_via_standard_web_request(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.edit']);
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Robert Paul',
            'email' => 'robert.paul@example.com',
            'mobile' => '9876543212',
            'priority' => 'urgent',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->delete(route('crm.leads.destroy', $lead));

        $response->assertRedirect(route('crm.leads.index'));
        $response->assertSessionHas('status', 'Lead deleted successfully.');

        $this->assertDatabaseMissing('leads', [
            'id' => $lead->id,
        ]);
    }

    public function test_lead_deletion_cascades_and_removes_dependent_records(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.manage']);
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Cascading Test Lead',
            'email' => 'cascade@example.com',
            'mobile' => '9876543213',
            'priority' => 'normal',
            'status' => 'qualified',
        ]);

        // Attach dependent records
        $activity = LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'activity_type' => 'call',
            'body' => 'Called client for requirement discussion.',
        ]);

        $assignment = LeadAssignment::create([
            'lead_id' => $lead->id,
            'assigned_to' => $user->id,
            'assigned_by' => $user->id,
            'method' => 'manual',
            'notes' => 'Assigned for follow up',
        ]);

        $followup = LeadFollowup::create([
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'due_at' => now()->addDays(2),
            'status' => 'pending',
            'notes' => 'Follow up meeting scheduled',
        ]);

        $type = \App\Domains\Master\Models\CustomerType::firstOrCreate(
            ['code' => 'RET'],
            ['name' => 'Retail', 'is_active' => true]
        );

        $customer = Customer::create([
            'company_id' => $user->company_id,
            'customer_type_id' => $type->id,
            'code' => 'CUST-001',
            'name' => 'Converted Customer',
            'phone' => '9876543213',
            'email' => 'cascade@example.com',
        ]);

        $conversion = LeadConversion::create([
            'lead_id' => $lead->id,
            'customer_id' => $customer->id,
            'converted_by' => $user->id,
            'converted_at' => now(),
            'notes' => 'Converted successfully',
        ]);

        $response = $this->actingAs($user)->deleteJson(route('crm.leads.destroy', $lead));

        $response->assertOk();
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
        $this->assertDatabaseMissing('lead_activities', ['lead_id' => $lead->id]);
        $this->assertDatabaseMissing('lead_assignments', ['lead_id' => $lead->id]);
        $this->assertDatabaseMissing('lead_followups', ['lead_id' => $lead->id]);
        $this->assertDatabaseMissing('lead_conversions', ['lead_id' => $lead->id]);
    }

    public function test_super_admin_can_delete_lead_across_tenants(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $otherCompany = Company::create([
            'code' => 'OTHERCO',
            'name' => 'Other Company Ltd',
            'currency' => 'INR',
            'is_active' => true,
        ]);

        $lead = Lead::create([
            'company_id' => $otherCompany->id,
            'name' => 'Super Admin Target',
            'email' => 'satarget@example.com',
            'mobile' => '9876543214',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response = $this->actingAs($admin)->deleteJson(route('crm.leads.destroy', $lead));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_unauthorized_user_without_permission_cannot_delete_lead(): void
    {
        // Only crm.view, neither crm.manage nor crm.edit
        $user = $this->makeUser(['crm.view']);
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Protected Lead',
            'email' => 'protected@example.com',
            'mobile' => '9876543215',
            'priority' => 'low',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->deleteJson(route('crm.leads.destroy', $lead));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You do not have permission to delete leads.',
        ]);
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_user_cannot_delete_lead_belonging_to_another_company(): void
    {
        $companyA = Company::firstOrCreate(
            ['code' => 'COMPA'],
            ['name' => 'Company A', 'currency' => 'INR', 'is_active' => true]
        );
        $companyB = Company::create([
            'code' => 'COMPB',
            'name' => 'Company B',
            'currency' => 'INR',
            'is_active' => true,
        ]);

        $user = $this->makeUser(['crm.view', 'crm.manage'], $companyA);

        $lead = Lead::create([
            'company_id' => $companyB->id,
            'name' => 'Company B Lead',
            'email' => 'companyb@example.com',
            'mobile' => '9876543216',
            'priority' => 'high',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->deleteJson(route('crm.leads.destroy', $lead));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized action for this company.',
        ]);
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_user_cannot_delete_lead_belonging_to_disallowed_branch(): void
    {
        $company = Company::firstOrCreate(
            ['code' => 'TESTCO'],
            ['name' => 'Test Company', 'currency' => 'INR', 'is_active' => true]
        );

        $branch1 = Branch::create([
            'company_id' => $company->id,
            'name' => 'Branch One',
            'code' => 'BR1',
            'is_active' => true,
        ]);
        $branch2 = Branch::create([
            'company_id' => $company->id,
            'name' => 'Branch Two',
            'code' => 'BR2',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch1->id,
        ]);
        $user->givePermissionTo(['crm.view', 'crm.manage']);

        $lead = Lead::create([
            'company_id' => $company->id,
            'branch_id' => $branch2->id,
            'name' => 'Branch Two Lead',
            'email' => 'branch2@example.com',
            'mobile' => '9876543217',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->deleteJson(route('crm.leads.destroy', $lead));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized action for this branch.',
        ]);
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_deleting_nonexistent_lead_returns_404(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.manage']);

        $response = $this->actingAs($user)->deleteJson(route('crm.leads.destroy', 999999));

        $response->assertStatus(404);
    }

    public function test_deletion_records_audit_log(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.manage']);
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Audited Lead',
            'email' => 'audited@example.com',
            'mobile' => '9876543218',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->deleteJson(route('crm.leads.destroy', $lead));

        $response->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'action' => 'deleted',
            'user_id' => $user->id,
        ]);
    }
}
