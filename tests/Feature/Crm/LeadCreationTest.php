<?php

namespace Tests\Feature\Crm;

use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\LeadCampaign;
use App\Domains\Crm\Models\LeadSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeadCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['crm.view', 'crm.create', 'crm.edit', 'crm.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(array $permissions = ['crm.view', 'crm.create']): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        return $user;
    }

    public function test_crm_leads_page_displays_new_lead_button(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('crm.leads.index'));

        $response->assertOk();
        $response->assertSee('New Lead');
        $response->assertSee(route('crm.leads.create'));
    }

    public function test_user_can_view_create_lead_form(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('crm.leads.create'));

        $response->assertOk();
        $response->assertSee('Create CRM Lead');
        $response->assertSee('Full Name');
        $response->assertSee('Mobile Number');
    }

    public function test_permitted_user_can_manually_create_lead(): void
    {
        $user = $this->makeUser();
        $salesperson = User::factory()->create();
        $source = LeadSource::create(['name' => 'Trade Exhibition', 'code' => 'EXH', 'is_active' => true]);
        $campaign = LeadCampaign::create(['name' => 'Diwali Promo', 'platform' => 'offline', 'is_active' => true]);

        $payload = [
            'name' => 'Rajesh Sharma',
            'mobile' => '9876543210',
            'email' => 'rajesh@example.com',
            'organization' => 'Sharma Enterprises',
            'city' => 'Jaipur',
            'state' => 'Rajasthan',
            'lead_source_id' => $source->id,
            'lead_campaign_id' => $campaign->id,
            'priority' => 'high',
            'status' => 'new',
            'assigned_to' => $salesperson->id,
            'notes' => 'Looking for 50 commercial inverters.',
        ];

        $response = $this->actingAs($user)->post(route('crm.leads.store'), $payload);

        $lead = Lead::where('mobile', '9876543210')->first();
        $this->assertNotNull($lead);
        $this->assertEquals('Rajesh Sharma', $lead->name);
        $this->assertEquals('Sharma Enterprises', $lead->organization);
        $this->assertEquals($salesperson->id, $lead->assigned_to);

        $response->assertRedirect(route('crm.leads.show', $lead));
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'creation',
        ]);
        $this->assertDatabaseHas('lead_assignments', [
            'lead_id' => $lead->id,
            'assigned_to' => $salesperson->id,
        ]);
    }

    public function test_lead_creation_validation_requires_name(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post(route('crm.leads.store'), [
            'mobile' => '9876543210',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_duplicate_contact_blocks_creation_with_validation_error(): void
    {
        $user = $this->makeUser();
        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Existing Customer Lead',
            'mobile' => '9988776655',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($user)->post(route('crm.leads.store'), [
            'name' => 'New Inquirer',
            'mobile' => '9988776655',
            'priority' => 'high',
            'status' => 'new',
        ]);

        $response->assertSessionHasErrors([
            'mobile' => 'This mobile/contact number is already registered with another lead.',
        ]);
        $this->assertDatabaseCount('leads', 1);
    }

    public function test_unauthorized_user_cannot_access_lead_creation(): void
    {
        $user = User::factory()->create(); // No CRM permissions

        $response = $this->actingAs($user)->get(route('crm.leads.create'));
        $response->assertForbidden();

        $postResponse = $this->actingAs($user)->post(route('crm.leads.store'), [
            'name' => 'Hacker Lead',
            'priority' => 'normal',
            'status' => 'new',
        ]);
        $postResponse->assertForbidden();
    }
}

