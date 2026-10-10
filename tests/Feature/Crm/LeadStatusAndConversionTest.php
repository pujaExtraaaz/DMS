<?php

namespace Tests\Feature\Crm;

use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\LeadActivity;
use App\Domains\Crm\Models\LeadConversion;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\PartyContact;
use App\Domains\Organization\Models\ActivityLog;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadStatusAndConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['crm.view', 'crm.create', 'crm.edit', 'crm.manage', 'customers.view', 'customers.create', 'customers.edit', 'masters.view', 'masters.create'] as $perm) {
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

    /*
     |--------------------------------------------------------------------------
     | Status Management Tests
     |--------------------------------------------------------------------------
     */

    public function test_crm_leads_page_displays_current_status_badge_and_change_status_interaction(): void
    {
        $user = $this->makeUser();
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Status Lead',
            'email' => 'status@example.com',
            'mobile' => '9876543210',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->get(route('crm.leads.index'));

        $response->assertOk();
        $response->assertSee('New');
        $response->assertSee('Change');
        $response->assertSee('data-change-status="' . $lead->id . '"', false);
    }

    public function test_can_change_status_new_to_contacted(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.edit']);
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Contacted Candidate',
            'email' => 'contacted@example.com',
            'mobile' => '9876543211',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->patchJson(route('crm.leads.update-status', $lead), [
            'status' => 'contacted',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'contacted',
            'status_label' => 'Contacted',
        ]);

        $this->assertEquals('contacted', $lead->fresh()->status);
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'status_change',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'action' => 'updated',
        ]);
    }

    public function test_can_change_status_contacted_to_follow_up(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.edit']);
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Followup Candidate',
            'email' => 'followup@example.com',
            'mobile' => '9876543212',
            'priority' => 'normal',
            'status' => 'contacted',
        ]);

        $response = $this->actingAs($user)->patchJson(route('crm.leads.update-status', $lead), [
            'status' => 'follow_up',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'follow_up',
            'status_label' => 'Follow-up',
        ]);

        $this->assertEquals('follow_up', $lead->fresh()->status);
    }

    public function test_can_change_status_follow_up_to_qualified(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.edit']);
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Qualified Candidate',
            'email' => 'qualified@example.com',
            'mobile' => '9876543213',
            'priority' => 'normal',
            'status' => 'follow_up',
        ]);

        $response = $this->actingAs($user)->patchJson(route('crm.leads.update-status', $lead), [
            'status' => 'qualified',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'qualified',
            'status_label' => 'Qualified',
        ]);

        $this->assertEquals('qualified', $lead->fresh()->status);
    }

    public function test_can_change_status_to_lost(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.edit']);
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Lost Candidate',
            'email' => 'lost@example.com',
            'mobile' => '9876543214',
            'priority' => 'normal',
            'status' => 'qualified',
        ]);

        $response = $this->actingAs($user)->patchJson(route('crm.leads.update-status', $lead), [
            'status' => 'lost',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'lost',
            'status_label' => 'Lost',
        ]);

        $this->assertEquals('lost', $lead->fresh()->status);
    }

    public function test_submitting_invalid_status_returns_validation_error(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.edit']);
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Invalid Status Lead',
            'email' => 'invalid@example.com',
            'mobile' => '9876543215',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->patchJson(route('crm.leads.update-status', $lead), [
            'status' => 'completely_bogus_status',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
        $this->assertEquals('new', $lead->fresh()->status);
    }

    public function test_unauthorized_user_cannot_update_status(): void
    {
        $user = $this->makeUser(['crm.view']); // No crm.edit or crm.manage
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Forbidden Lead',
            'email' => 'forbidden@example.com',
            'mobile' => '9876543216',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->patchJson(route('crm.leads.update-status', $lead), [
            'status' => 'contacted',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('new', $lead->fresh()->status);
    }

    public function test_status_filter_and_pagination_compatibility(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.edit']);

        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Lead New 1',
            'mobile' => '9800000001',
            'status' => 'new',
            'priority' => 'normal',
        ]);
        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Lead Followup 1',
            'mobile' => '9800000002',
            'status' => 'follow_up',
            'priority' => 'normal',
        ]);
        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Lead Qualified 1',
            'mobile' => '9800000003',
            'status' => 'qualified',
            'priority' => 'normal',
        ]);

        // Filter by qualified
        $response = $this->actingAs($user)->get(route('crm.leads.index', ['status' => 'qualified']));
        $response->assertOk();
        $response->assertSee('Lead Qualified 1');
        $response->assertDontSee('Lead New 1');
        $response->assertDontSee('Lead Followup 1');

        // Filter by follow_up
        $response = $this->actingAs($user)->get(route('crm.leads.index', ['status' => 'follow_up']));
        $response->assertOk();
        $response->assertSee('Lead Followup 1');
        $response->assertDontSee('Lead New 1');
        $response->assertDontSee('Lead Qualified 1');
    }

    /*
     |--------------------------------------------------------------------------
     | Convert to Customer Tests
     |--------------------------------------------------------------------------
     */

    public function test_convert_to_customer_button_enabled_only_for_qualified_leads(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.edit']);

        $qualifiedLead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Eligible Lead',
            'company_name' => 'Acme Solar Corp',
            'mobile' => '9811111111',
            'status' => 'qualified',
            'priority' => 'high',
        ]);

        $newLead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Ineligible Lead',
            'mobile' => '9822222222',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($user)->get(route('crm.leads.index'));

        $response->assertOk();
        $response->assertSee('data-convert-lead="' . $qualifiedLead->id . '"', false);
        $response->assertSee('Conversion unavailable: Lead must have Qualified status');
    }

    public function test_successful_conversion_creates_customer_and_maps_fields(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.manage']);

        CustomerType::firstOrCreate(
            ['code' => 'GEN'],
            ['name' => 'General', 'is_active' => true]
        );

        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Ramesh Patel',
            'company_name' => 'Patel Power Systems',
            'title' => 'Managing Director',
            'mobile' => '9833333333',
            'email' => 'ramesh@patelpower.com',
            'street' => 'Plot 45, Industrial Area',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'zip' => '380001',
            'status' => 'qualified',
            'priority' => 'high',
            'assigned_to' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson(route('crm.leads.convert', $lead), [
            'notes' => 'Qualified enterprise client converted to party.',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        // Customer created in MASTER -> PARTIES
        $customer = Customer::where('phone', '9833333333')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Patel Power Systems', $customer->name);
        $this->assertEquals('ramesh@patelpower.com', $customer->email);
        $this->assertEquals('Gujarat', $customer->state);
        $this->assertEquals('380001', $customer->pincode);
        $this->assertEquals($user->id, $customer->salesperson_id);
        $this->assertStringStartsWith('PTY-', $customer->code);

        // Child contact mapped
        $this->assertDatabaseHas('party_contacts', [
            'customer_id' => $customer->id,
            'name' => 'Ramesh Patel',
            'role' => 'Managing Director',
            'phone' => '9833333333',
            'email' => 'ramesh@patelpower.com',
            'is_primary' => true,
        ]);

        // Child address mapped
        $this->assertDatabaseHas('party_addresses', [
            'customer_id' => $customer->id,
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'pincode' => '380001',
            'is_default_billing' => true,
        ]);

        // Original lead preserved and updated
        $freshLead = $lead->fresh();
        $this->assertEquals('converted', $freshLead->status);
        $this->assertEquals($customer->id, $freshLead->converted_customer_id);
        $this->assertNotNull($freshLead->converted_at);

        // Lead conversion relationship record
        $this->assertDatabaseHas('lead_conversions', [
            'lead_id' => $lead->id,
            'customer_id' => $customer->id,
            'converted_by' => $user->id,
        ]);

        // Audit log recorded
        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'action' => 'converted',
        ]);
    }

    public function test_lead_cannot_be_converted_twice(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.manage']);

        CustomerType::firstOrCreate(
            ['code' => 'GEN'],
            ['name' => 'General', 'is_active' => true]
        );

        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'One Time Lead',
            'company_name' => 'Unique Solar',
            'mobile' => '9844444444',
            'email' => 'unique@example.com',
            'status' => 'qualified',
            'priority' => 'normal',
        ]);

        // First conversion succeeds
        $res1 = $this->actingAs($user)->postJson(route('crm.leads.convert', $lead));
        $res1->assertOk();

        // Second conversion fails
        $res2 = $this->actingAs($user)->postJson(route('crm.leads.convert', $lead));
        $res2->assertStatus(422);
        $res2->assertJson([
            'success' => false,
            'message' => 'Lead has already been converted to a customer.',
        ]);
    }

    public function test_unqualified_lead_cannot_be_converted(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.manage']);

        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Unqualified Lead',
            'mobile' => '9855555555',
            'status' => 'contacted', // not qualified
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($user)->postJson(route('crm.leads.convert', $lead));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Only leads with Qualified status can be converted to a customer.',
        ]);
    }

    public function test_duplicate_party_detection_and_link_existing(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.manage']);

        $type = CustomerType::firstOrCreate(
            ['code' => 'GEN'],
            ['name' => 'General', 'is_active' => true]
        );

        // Pre-existing party in master parties
        $existingCustomer = Customer::create([
            'company_id' => $user->company_id,
            'customer_type_id' => $type->id,
            'name' => 'Pre-existing Solar Power Ltd',
            'code' => 'PTY-1-00099',
            'phone' => '9866666666',
            'email' => 'duplicate@example.com',
            'is_active' => true,
        ]);

        // Lead with matching phone number
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Duplicate Contact Lead',
            'company_name' => 'Pre-existing Solar Power Ltd',
            'mobile' => '9866666666',
            'email' => 'duplicate@example.com',
            'status' => 'qualified',
            'priority' => 'high',
        ]);

        // Attempting normal convert detects duplicate and returns 409
        $response = $this->actingAs($user)->postJson(route('crm.leads.convert', $lead), [
            'link_existing' => false,
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'duplicate_detected' => true,
        ]);

        // Now confirm linking to existing customer
        $linkResponse = $this->actingAs($user)->postJson(route('crm.leads.convert', $lead), [
            'link_existing' => true,
        ]);

        $linkResponse->assertOk();
        $this->assertEquals('converted', $lead->fresh()->status);
        $this->assertEquals($existingCustomer->id, $lead->fresh()->converted_customer_id);

        // Verify no duplicate Customer row was created
        $this->assertEquals(1, Customer::where('phone', '9866666666')->count());
    }

    public function test_unauthorized_user_cannot_convert_lead(): void
    {
        $user = $this->makeUser(['crm.view']); // No manage/edit permission

        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Protected Convert Lead',
            'mobile' => '9877777777',
            'status' => 'qualified',
            'priority' => 'high',
        ]);

        $response = $this->actingAs($user)->postJson(route('crm.leads.convert', $lead));

        $response->assertStatus(403);
        $this->assertEquals('qualified', $lead->fresh()->status);
    }

    public function test_super_admin_can_convert_any_lead(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        CustomerType::firstOrCreate(
            ['code' => 'GEN'],
            ['name' => 'General', 'is_active' => true]
        );

        $otherCompany = Company::create([
            'code' => 'CO_OTHER',
            'name' => 'Other Co',
            'currency' => 'INR',
            'is_active' => true,
        ]);

        $lead = Lead::create([
            'company_id' => $otherCompany->id,
            'name' => 'Super Admin Convert Lead',
            'company_name' => 'Global Energy',
            'mobile' => '9888888888',
            'status' => 'qualified',
            'priority' => 'urgent',
        ]);

        $response = $this->actingAs($admin)->postJson(route('crm.leads.convert', $lead));

        $response->assertOk();
        $this->assertEquals('converted', $lead->fresh()->status);
    }

    public function test_customer_create_page_prefills_lead_details_when_lead_id_provided(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.edit', 'customers.create']);

        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Vikas Sharma',
            'company_name' => 'Sharma Logistics Pvt Ltd',
            'email' => 'vikas@sharmalogistics.com',
            'mobile' => '9899999991',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'zip' => '400001',
            'street' => '12 Marine Drive',
            'status' => 'qualified',
            'priority' => 'high',
        ]);

        $response = $this->actingAs($user)->get(route('masters.customers.create', ['lead_id' => $lead->id]));

        $response->assertOk();
        $response->assertSee('Convert Lead to Customer');
        $response->assertSee('Converting Lead: Vikas Sharma (Sharma Logistics Pvt Ltd)');
        $response->assertSee('Sharma Logistics Pvt Ltd');
        $response->assertSee('9899999991');
        $response->assertSee('vikas@sharmalogistics.com');
        $response->assertSee('Back to Leads');
        $response->assertSee('name="lead_id" value="' . $lead->id . '"', false);
    }

    public function test_customer_create_page_rejects_unqualified_or_already_converted_lead(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.edit', 'customers.create']);

        $unqualifiedLead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Unqualified Person',
            'mobile' => '9899999992',
            'status' => 'contacted',
            'priority' => 'normal',
        ]);

        $response1 = $this->actingAs($user)->get(route('masters.customers.create', ['lead_id' => $unqualifiedLead->id]));
        $response1->assertRedirect(route('crm.leads.index'));
        $response1->assertSessionHas('error', 'Only leads with Qualified status can be converted to a customer.');

        $convertedLead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Already Converted',
            'mobile' => '9899999993',
            'status' => 'converted',
            'priority' => 'normal',
        ]);

        $response2 = $this->actingAs($user)->get(route('masters.customers.create', ['lead_id' => $convertedLead->id]));
        $response2->assertRedirect(route('crm.leads.index'));
        $response2->assertSessionHas('error', 'This lead has already been converted to a customer.');
    }

    public function test_saving_customer_from_prefilled_form_converts_lead_and_creates_party(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.edit', 'customers.create']);

        $type = CustomerType::firstOrCreate(
            ['code' => 'GEN'],
            ['name' => 'General', 'is_active' => true]
        );

        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Pooja Verma',
            'company_name' => 'Verma Enterprises',
            'email' => 'pooja@verma.com',
            'mobile' => '9899999994',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'zip' => '411001',
            'street' => 'FC Road',
            'status' => 'qualified',
            'priority' => 'urgent',
        ]);

        $postData = [
            'lead_id' => $lead->id,
            'name' => 'Verma Enterprises',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $type->id,
            'phone' => '9899999994',
            'email' => 'pooja@verma.com',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'credit_limit' => 50000,
            'credit_days' => 30,
            'credit_status' => 'open',
            'addresses' => [
                [
                    'label' => 'Primary Office',
                    'type' => 'both',
                    'contact_person' => 'Pooja Verma',
                    'contact_phone' => '9899999994',
                    'address_line_1' => 'FC Road',
                    'city' => 'Pune',
                    'state' => 'Maharashtra',
                    'pincode' => '411001',
                    'is_default_billing' => 1,
                    'is_default_delivery' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('masters.customers.store'), $postData);

        $response->assertRedirect(route('masters.customers.index'));
        $response->assertSessionHas('status');

        $customer = Customer::where('name', 'Verma Enterprises')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Maharashtra', $customer->state);

        // Verify lead was converted and linked
        $freshLead = $lead->fresh();
        $this->assertEquals('converted', $freshLead->status);
        $this->assertEquals($customer->id, $freshLead->converted_customer_id);
        $this->assertNotNull($freshLead->converted_at);

        // Verify LeadConversion record
        $this->assertDatabaseHas('lead_conversions', [
            'lead_id' => $lead->id,
            'customer_id' => $customer->id,
        ]);

        // Verify activity log
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'status_change',
        ]);
    }

    public function test_canceling_or_not_saving_form_leaves_lead_unconverted(): void
    {
        $user = $this->makeUser(['crm.view', 'crm.edit', 'customers.create']);

        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Hesitant Lead',
            'company_name' => 'Hesitant Corp',
            'mobile' => '9899999995',
            'status' => 'qualified',
            'priority' => 'normal',
        ]);

        // User views the prefilled form
        $response = $this->actingAs($user)->get(route('masters.customers.create', ['lead_id' => $lead->id]));
        $response->assertOk();

        // Lead remains unconverted
        $freshLead = $lead->fresh();
        $this->assertEquals('qualified', $freshLead->status);
        $this->assertNull($freshLead->converted_customer_id);
        $this->assertNull($freshLead->converted_at);

        // No customer record was created
        $this->assertEquals(0, Customer::where('name', 'Hesitant Corp')->count());
    }
}

