<?php

namespace Tests\Feature\Layout;

use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AlpineTooltipScopeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $role->givePermissionTo(Permission::all());

        $this->company = Company::create([
            'name' => 'Test Corp',
            'code' => 'TC-01',
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);
    }

    public function test_tax_invoice_page_renders_with_tooltip_variables_in_root_alpine_scope(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));

        $response->assertStatus(200);

        // Root Alpine scope contains tooltip properties and methods
        $response->assertSee('tooltipVisible: false', false);
        $response->assertSee('tooltipText: \'\'', false);
        $response->assertSee('showTooltip(el, text)', false);
        $response->assertSee('hideTooltip()', false);

        // Reusable Alpine.data('tooltip') component is registered
        $response->assertSee('Alpine.data(\'tooltip\'', false);

        // Sidebar tooltip element is rendered and bound to declared properties
        $response->assertSee('x-show="tooltipVisible && $root.sidebarCollapsed"', false);
        $response->assertSee('x-text="tooltipText"', false);
    }

    public function test_dashboard_page_also_has_tooltip_scope_and_reusable_component(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);

        $response->assertSee('tooltipVisible: false', false);
        $response->assertSee('tooltipText: \'\'', false);
        $response->assertSee('Alpine.data(\'tooltip\'', false);
    }
}

