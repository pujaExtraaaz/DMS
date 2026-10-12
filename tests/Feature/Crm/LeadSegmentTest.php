<?php

namespace Tests\Feature\Crm;

use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\LeadActivity;
use App\Domains\Crm\Models\LeadFollowup;
use App\Domains\Crm\Models\LeadSegment;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeadSegmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['crm.view', 'crm.create', 'crm.edit', 'crm.manage', 'masters.customers.create', 'masters.customers.view'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(array $permissions = ['crm.view', 'crm.create', 'crm.edit', 'crm.manage']): User
    {
        $company = Company::firstOrCreate(
            ['code' => 'TESTCO'],
            ['name' => 'Test Company', 'currency' => 'INR', 'is_active' => true]
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    protected function createCustomer(User $user, string $name, string $code): Customer
    {
        $type = CustomerType::firstOrCreate(
            ['code' => 'GEN'],
            ['name' => 'General', 'is_active' => true]
        );

        return Customer::create([
            'company_id' => $user->company_id,
            'customer_type_id' => $type->id,
            'name' => $name,
            'code' => $code,
            'is_active' => true,
        ]);
    }

    public function test_user_can_view_segments_index_page(): void
    {
        $user = $this->makeUser();

        $segment = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'High Value Maharashtra Leads',
            'description' => 'Target group for Q4',
            'criteria' => ['state' => 'Maharashtra'],
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('crm.leads.segments.index'));

        $response->assertOk();
        $response->assertSee('Lead Segments');
        $response->assertSee('High Value Maharashtra Leads');
        $response->assertSee(route('crm.leads.segments.create'));
    }

    public function test_user_can_view_create_segment_page(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('crm.leads.segments.create'));

        $response->assertOk();
        $response->assertSee('Create Lead Segment');
        $response->assertSee('Industry & Region');
        $response->assertSee('Purchase History');
        $response->assertSee('Engagement Level');
        $response->assertSee('Live Audience Preview');
    }

    public function test_user_can_create_segment_with_criteria(): void
    {
        $user = $this->makeUser();

        $payload = [
            'name' => 'Manufacturing in West Region',
            'description' => 'Targeting manufacturing firms in MH & GJ',
            'criteria' => [
                'industry' => 'Manufacturing',
                'state' => 'Maharashtra',
                'city' => 'Pune',
                'category' => 'prospect',
                'purchase_status' => 'never_purchased',
                'engagement_level' => 'all',
            ],
        ];

        $response = $this->actingAs($user)->post(route('crm.leads.segments.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('lead_segments', [
            'company_id' => $user->company_id,
            'name' => 'Manufacturing in West Region',
        ]);

        $segment = LeadSegment::where('name', 'Manufacturing in West Region')->first();
        $this->assertNotNull($segment);
        $this->assertSame('Manufacturing', $segment->criteria['industry']);
        $this->assertSame('Maharashtra', $segment->criteria['state']);
        $this->assertSame('never_purchased', $segment->criteria['purchase_status']);
    }

    public function test_segment_filters_leads_by_industry_tag_and_organization(): void
    {
        $user = $this->makeUser();

        $lead1 = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Amit Sharma',
            'industry' => 'Manufacturing',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $lead2 = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Rahul Verma',
            'tag' => 'MANUFACTURING',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $lead3 = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Pooja Patil',
            'organization' => 'Apex Manufacturing Pvt Ltd',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $lead4 = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Dr. Suresh Kumar',
            'industry' => 'Healthcare',
            'organization' => 'City Hospital',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $segment = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'Manufacturing Group',
            'criteria' => [
                'industry' => 'Manufacturing',
            ],
        ]);

        $matchedIds = $segment->matchingLeadsQuery()->pluck('leads.id')->all();

        $this->assertContains($lead1->id, $matchedIds);
        $this->assertContains($lead2->id, $matchedIds);
        $this->assertContains($lead3->id, $matchedIds);
        $this->assertNotContains($lead4->id, $matchedIds);
        $this->assertSame(3, $segment->matchingLeadsCount());
    }

    public function test_segment_filters_leads_by_region_state_and_city(): void
    {
        $user = $this->makeUser();

        $leadMh = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Mumbai Lead',
            'state' => 'Maharashtra',
            'city' => 'Mumbai',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $leadPune = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Pune Lead',
            'state' => 'Maharashtra',
            'city' => 'Pune',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $leadGj = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Gujarat Lead',
            'state' => 'Gujarat',
            'city' => 'Surat',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $segment = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'Maharashtra State',
            'criteria' => [
                'state' => 'Maharashtra',
            ],
        ]);

        $matchedIds = $segment->matchingLeadsQuery()->pluck('leads.id')->all();
        $this->assertContains($leadMh->id, $matchedIds);
        $this->assertContains($leadPune->id, $matchedIds);
        $this->assertNotContains($leadGj->id, $matchedIds);

        // Filter also by city Pune
        $segment->update(['criteria' => ['state' => 'Maharashtra', 'city' => 'Pune']]);
        $matchedCityIds = $segment->matchingLeadsQuery()->pluck('leads.id')->all();
        $this->assertNotContains($leadMh->id, $matchedCityIds);
        $this->assertContains($leadPune->id, $matchedCityIds);
    }

    public function test_segment_filters_leads_by_purchase_history_has_purchased(): void
    {
        $user = $this->makeUser();

        $customer1 = $this->createCustomer($user, 'Customer With Orders', 'CUST-001');
        $customer2 = $this->createCustomer($user, 'Customer Without Orders', 'CUST-002');

        Order::create([
            'order_no' => 'SO-1001',
            'customer_id' => $customer1->id,
            'salesperson_id' => $user->id,
            'order_date' => now()->subDays(5),
            'grand_total' => 15000,
            'status' => 'confirmed',
        ]);

        $lead1 = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Converted Purchaser',
            'converted_customer_id' => $customer1->id,
            'status' => 'converted',
            'priority' => 'normal',
        ]);

        $lead2 = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Converted Non-Purchaser',
            'converted_customer_id' => $customer2->id,
            'status' => 'converted',
            'priority' => 'normal',
        ]);

        $lead3 = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Unconverted Lead',
            'converted_customer_id' => null,
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $segmentHasPurchased = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'Has Purchased Segment',
            'criteria' => ['purchase_status' => 'has_purchased'],
        ]);

        $matchedIds = $segmentHasPurchased->matchingLeadsQuery()->pluck('leads.id')->all();
        $this->assertContains($lead1->id, $matchedIds);
        $this->assertNotContains($lead2->id, $matchedIds);
        $this->assertNotContains($lead3->id, $matchedIds);
    }

    public function test_segment_filters_leads_by_never_purchased_and_unavailable(): void
    {
        $user = $this->makeUser();

        $customer1 = $this->createCustomer($user, 'Converted Customer Zero Orders', 'CUST-003');

        $leadNeverPurchased = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Converted Zero Purchases',
            'converted_customer_id' => $customer1->id,
            'status' => 'converted',
            'priority' => 'normal',
        ]);

        $leadUnavailable = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Unconverted Prospect',
            'converted_customer_id' => null,
            'status' => 'new',
            'priority' => 'normal',
        ]);

        // Never purchased
        $segmentNever = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'Never Purchased',
            'criteria' => ['purchase_status' => 'never_purchased'],
        ]);

        $matchedNever = $segmentNever->matchingLeadsQuery()->pluck('leads.id')->all();
        $this->assertContains($leadNeverPurchased->id, $matchedNever);
        $this->assertNotContains($leadUnavailable->id, $matchedNever);

        // Unavailable
        $segmentUnavail = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'Unavailable (Unlinked)',
            'criteria' => ['purchase_status' => 'unavailable'],
        ]);

        $matchedUnavail = $segmentUnavail->matchingLeadsQuery()->pluck('leads.id')->all();
        $this->assertNotContains($leadNeverPurchased->id, $matchedUnavail);
        $this->assertContains($leadUnavailable->id, $matchedUnavail);
    }

    public function test_segment_filters_by_purchase_inactivity_and_spend(): void
    {
        $user = $this->makeUser();

        $custOld = $this->createCustomer($user, 'Old Customer', 'CUST-OLD');
        $custRecent = $this->createCustomer($user, 'Recent Customer', 'CUST-NEW');

        Order::create([
            'order_no' => 'SO-OLD',
            'customer_id' => $custOld->id,
            'salesperson_id' => $user->id,
            'order_date' => now()->subDays(120),
            'grand_total' => 25000,
            'status' => 'delivered',
        ]);

        Order::create([
            'order_no' => 'SO-NEW',
            'customer_id' => $custRecent->id,
            'salesperson_id' => $user->id,
            'order_date' => now()->subDays(10),
            'grand_total' => 5000,
            'status' => 'delivered',
        ]);

        $leadOld = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Old Buyer Lead',
            'converted_customer_id' => $custOld->id,
            'status' => 'converted',
            'priority' => 'normal',
        ]);

        $leadRecent = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Recent Buyer Lead',
            'converted_customer_id' => $custRecent->id,
            'status' => 'converted',
            'priority' => 'normal',
        ]);

        // Inactive for 60 days
        $segmentInactive = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'Dormant Customers',
            'criteria' => ['purchase_status' => 'inactive_days', 'purchase_inactive_days' => 60],
        ]);

        $matchedInactive = $segmentInactive->matchingLeadsQuery()->pluck('leads.id')->all();
        $this->assertContains($leadOld->id, $matchedInactive);
        $this->assertNotContains($leadRecent->id, $matchedInactive);

        // Min spent 20,000
        $segmentSpend = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'High Spenders',
            'criteria' => ['min_total_spent' => 20000],
        ]);

        $matchedSpend = $segmentSpend->matchingLeadsQuery()->pluck('leads.id')->all();
        $this->assertContains($leadOld->id, $matchedSpend);
        $this->assertNotContains($leadRecent->id, $matchedSpend);
    }

    public function test_segment_filters_by_engagement_level(): void
    {
        $user = $this->makeUser();

        $leadHigh = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Highly Engaged Lead',
            'status' => 'follow_up',
            'priority' => 'high',
        ]);

        // Add 3 followups within 30 days
        for ($i = 0; $i < 3; $i++) {
            LeadFollowup::create([
                'lead_id' => $leadHigh->id,
                'user_id' => $user->id,
                'due_at' => now()->subDays(2),
                'channel' => 'call',
                'status' => 'completed',
                'completed_at' => now()->subDays(2),
            ]);
        }

        $leadUnknown = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'No Activity Lead',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $segmentHigh = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'High Engagement',
            'criteria' => ['engagement_level' => 'high'],
        ]);

        $matchedHigh = $segmentHigh->matchingLeadsQuery()->pluck('leads.id')->all();
        $this->assertContains($leadHigh->id, $matchedHigh);
        $this->assertNotContains($leadUnknown->id, $matchedHigh);

        $segmentUnknown = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'Unknown Engagement',
            'criteria' => ['engagement_level' => 'unknown'],
        ]);

        $matchedUnknown = $segmentUnknown->matchingLeadsQuery()->pluck('leads.id')->all();
        $this->assertNotContains($leadHigh->id, $matchedUnknown);
        $this->assertContains($leadUnknown->id, $matchedUnknown);
    }

    public function test_preview_ajax_endpoint_returns_accurate_audience_metrics(): void
    {
        $user = $this->makeUser();

        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Audience Lead 1',
            'industry' => 'Technology',
            'email' => 'tech1@example.com',
            'mobile' => '9876543210',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Audience Lead 2',
            'industry' => 'Technology',
            'email' => null,
            'mobile' => '9876543211',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($user)->postJson(route('crm.leads.segments.preview'), [
            'criteria' => [
                'industry' => 'Technology',
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'total_count' => 2,
            'with_email_count' => 1,
            'with_mobile_count' => 2,
        ]);
        $response->assertJsonStructure([
            'success',
            'total_count',
            'with_email_count',
            'with_mobile_count',
            'sample_leads',
        ]);
    }

    public function test_user_can_update_segment(): void
    {
        $user = $this->makeUser();

        $segment = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'Initial Segment Name',
            'criteria' => ['industry' => 'Retail'],
        ]);

        $response = $this->actingAs($user)->put(route('crm.leads.segments.update', $segment), [
            'name' => 'Updated Segment Name',
            'description' => 'New description',
            'criteria' => [
                'industry' => 'Retail & Wholesale',
                'state' => 'Delhi',
            ],
        ]);

        $response->assertRedirect(route('crm.leads.segments.index'));

        $segment->refresh();
        $this->assertSame('Updated Segment Name', $segment->name);
        $this->assertSame('New description', $segment->description);
        $this->assertSame('Retail & Wholesale', $segment->criteria['industry']);
        $this->assertSame('Delhi', $segment->criteria['state']);
    }

    public function test_user_can_delete_segment_without_deleting_leads(): void
    {
        $user = $this->makeUser();

        $lead1 = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Lead To Keep 1',
            'industry' => 'Construction',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $lead2 = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Lead To Keep 2',
            'industry' => 'Construction',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $segment = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'Construction Segment',
            'criteria' => ['industry' => 'Construction'],
        ]);

        $this->assertSame(2, $segment->matchingLeadsCount());

        $response = $this->actingAs($user)->delete(route('crm.leads.segments.destroy', $segment));

        $response->assertRedirect(route('crm.leads.segments.index'));
        $this->assertDatabaseMissing('lead_segments', ['id' => $segment->id]);

        // CRITICAL: Leads must still exist unharmed
        $this->assertDatabaseHas('leads', ['id' => $lead1->id]);
        $this->assertDatabaseHas('leads', ['id' => $lead2->id]);
    }

    public function test_user_can_export_segment_audience_as_csv(): void
    {
        $user = $this->makeUser();

        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Export Contact Name',
            'company_name' => 'Export Company Ltd',
            'industry' => 'Export Sector',
            'email' => 'exporter@example.com',
            'mobile' => '9988776655',
            'status' => 'qualified',
            'priority' => 'high',
        ]);

        $segment = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'Export Audience Segment',
            'criteria' => ['industry' => 'Export Sector'],
        ]);

        $response = $this->actingAs($user)->get(route('crm.leads.segments.export', $segment));

        $response->assertOk();
        $this->assertTrue(str_contains((string) $response->headers->get('content-type'), 'text/csv'));
        $this->assertTrue(str_contains((string) $response->headers->get('content-disposition'), 'attachment; filename='));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Lead ID', $content);
        $this->assertStringContainsString('Export Contact Name', $content);
        $this->assertStringContainsString('Export Company Ltd', $content);
        $this->assertStringContainsString('exporter@example.com', $content);
        $this->assertStringContainsString('9988776655', $content);
    }

    public function test_crm_leads_index_can_be_filtered_by_segment_id(): void
    {
        $user = $this->makeUser();

        $leadTarget = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Target Lead for Segment',
            'industry' => 'Automotive',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $leadOther = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Other Lead Outside Segment',
            'industry' => 'Textiles',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $segment = LeadSegment::create([
            'company_id' => $user->company_id,
            'name' => 'Automotive Segment',
            'criteria' => ['industry' => 'Automotive'],
        ]);

        $response = $this->actingAs($user)->get(route('crm.leads.index', ['segment_id' => $segment->id]));

        $response->assertOk();
        $response->assertSee('Target Lead for Segment');
        $response->assertDontSee('Other Lead Outside Segment');
    }
}

