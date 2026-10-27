<?php

namespace Tests\Feature\Organization;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SisterConcernDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected BusinessGroup $defaultGroup;
    protected Company $company1;
    protected Company $company2;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);

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

        $this->company1 = Company::create([
            'business_group_id' => $this->defaultGroup->id,
            'name' => 'Avit Digital Central Ltd',
            'code' => 'ADC-01',
            'legal_name' => 'Avit Digital Central Private Limited',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->company2 = Company::create([
            'business_group_id' => $this->defaultGroup->id,
            'name' => 'Sam Manufacturing Ltd',
            'code' => 'SML-02',
            'legal_name' => 'Sam Manufacturing Private Limited',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Admin Manager',
            'email' => 'admin.sc@example.com',
        ]);
        $this->adminUser->assignRole($roleAdmin);

        $this->regularUser = User::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Plain Staff',
            'email' => 'staff.sc@example.com',
        ]);
        $this->regularUser->assignRole($roleRegular);

        $branch = Branch::create([
            'company_id' => $this->company1->id,
            'name' => 'Head Office Branch',
            'code' => 'HOB-01',
            'is_active' => true,
        ]);

        Warehouse::create([
            'company_id' => $this->company1->id,
            'branch_id' => $branch->id,
            'name' => 'Central Hub Warehouse',
            'code' => 'CHW-01',
            'is_active' => true,
        ]);
    }

    public function test_sister_concern_dashboard_renders_successfully_as_default_landing_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('organization.sister-concerns.dashboard');

        // A. Header Banner Assertions
        $response->assertSee('Sister Concern Management');
        $response->assertSee('Business Groups • Sister Concerns • Inter-Company Transactions');

        // B. Navigation Tabs Assertions
        $response->assertSee('Dashboard');
        $response->assertSee('Business Groups');
        $response->assertSee('Sister Concerns');
        $response->assertSee('Transactions');

        // C. Summary Cards Assertions
        $response->assertSee('Total Business Groups');
        $response->assertSee('Total Sister Concerns');
        $response->assertSee('Total Head Offices');
        $response->assertSee('Total Turnover');
        $response->assertSee('Total Employees');
        $response->assertSee('Active Transactions');
        $response->assertSee('Total Transaction Value');
        $response->assertSee('Settled Transactions');

        // D. Charts Canvas Assertions
        $response->assertSee('groupTurnoverChart');
        $response->assertSee('entityDistributionChart');
        $response->assertSee('Group-wise Turnover (₹ Lakhs)');
        $response->assertSee('Entity Type Distribution');
    }

    public function test_dashboard_redirect_route_opens_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.dashboard.redirect'));

        $response->assertStatus(200);
        $response->assertViewIs('organization.sister-concerns.dashboard');
    }

    public function test_sidebar_sister_concern_link_points_to_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString(route('organization.sister-concerns.dashboard'), $content);
    }

    public function test_business_groups_tab_renders_with_unified_header_and_tabs(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.business-groups.index'));

        $response->assertStatus(200);
        $response->assertViewIs('organization.business-groups.index');
        $response->assertSee('Sister Concern Management');
        $response->assertSee('Business Groups • Sister Concerns • Inter-Company Transactions');
        $response->assertSee('Dashboard');
        $response->assertSee('Default Group');
    }

    public function test_sister_concerns_companies_tab_renders_and_lists_entities(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.companies'));

        $response->assertStatus(200);
        $response->assertViewIs('organization.sister-concerns.companies');
        $response->assertSee('Sister Concern Management');
        $response->assertSee('Avit Digital Central Ltd');
        $response->assertSee('Sam Manufacturing Ltd');
    }

    public function test_transactions_tab_renders_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.transactions'));

        $response->assertStatus(200);
        $response->assertViewIs('organization.sister-concerns.transactions');
        $response->assertSee('Sister Concern Management');
        $response->assertSee('Transactions');
    }

    public function test_summary_cards_and_charts_reflect_live_database_data(): void
    {
        // Add a second group
        $group2 = BusinessGroup::create([
            'name' => 'Trojan Industrial Group',
            'code' => 'TIG-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('stats', function ($stats) {
            return $stats['business_groups_count'] === BusinessGroup::count()
                && $stats['companies_count'] === Company::count()
                && $stats['head_offices_count'] === 1;
        });

        $response->assertViewHas('groupTurnoverLabels', function ($labels) {
            return in_array('Default Group', $labels)
                && in_array('Trojan Industrial Group', $labels);
        });
    }

    public function test_unauthorized_user_cannot_access_sister_concerns_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('organization.sister-concerns.dashboard'));

        $response->assertStatus(403);
    }
}

