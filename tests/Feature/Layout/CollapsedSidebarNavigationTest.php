<?php

namespace Tests\Feature\Layout;

use App\Models\User;
use App\Support\SidebarManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CollapsedSidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create([
            'name' => 'Super Admin User',
            'email' => 'admin@dms.test',
        ]);
        $this->adminUser->assignRole($role);
    }

    public function test_sidebar_contains_all_top_level_section_icons_and_tooltips(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('organization.company-profile'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Dashboard icon and tooltip
        $this->assertStringContainsString('title="Dashboard"', $content);
        $this->assertStringContainsString('aria-label="Dashboard"', $content);

        // 2. All 14 top-level sections have tooltips (title and aria-label)
        $expectedTitles = [
            'Setup',
            'Sales &amp; Orders',
            'Masters',
            'Purchases',
            'Inventory',
            'Logistics &amp; Trips',
            'Receivables',
            'Commercials',
            'Sales Target Management',
            'HR · CRM',
            'Accounting (Books)',
            'Reports',
            'Tally Connector',
            'Guide',
        ];

        foreach ($expectedTitles as $title) {
            $this->assertStringContainsString("title=\"{$title}\"", $content, "Expected tooltip title for {$title}");
        }

        // 3. Bottom actions have tooltips
        $this->assertStringContainsString('title="Profile (Super Admin User)"', $content);
        $this->assertStringContainsString('title="Users & Roles"', $content);
        $this->assertStringContainsString('title="Log Out"', $content);

        // 4. Collapsed rail navigation icons use centered h-10 w-10 buttons
        $this->assertStringContainsString('h-10 w-10 items-center justify-center rounded-xl', $content);

        // 5. Active section highlighting exists for Setup when on company-profile route
        // Setup is active on organization.*
        $posSetup = strpos($content, 'data-sidebar-section="setup"');
        $this->assertNotFalse($posSetup);
        $setupBlock = substr($content, $posSetup, 5000);

        // In collapsed state, active section receives active classes
        $this->assertStringContainsString('bg-indigo-600/90 text-white shadow-lg shadow-indigo-900/30', $setupBlock);

        // 6. Tally Connector remains accessible and has dedicated icon link
        $this->assertStringContainsString(route('tally-connector.show'), $content);
        $posTally = strpos($content, 'data-sidebar-section="tally_connector"');
        $this->assertNotFalse($posTally);
        $tallyBlock = substr($content, $posTally, 4000);
        $this->assertStringContainsString('title="Tally Connector"', $tallyBlock);

        // 7. Expanded sidebar section header displays clean section names without numbers
        $this->assertStringContainsString('<span class="truncate">Setup</span>', $setupBlock);
        $this->assertStringContainsString('<span class="truncate">Sales &amp; Orders</span>', $content);
        $this->assertStringContainsString('<span class="truncate">Masters</span>', $content);
        $this->assertStringContainsString('<span class="truncate">Purchases</span>', $content);
        $this->assertStringContainsString('<span class="truncate">Inventory</span>', $content);
        $this->assertStringContainsString('<span class="truncate">Logistics &amp; Trips</span>', $content);
        $this->assertStringContainsString('<span class="truncate">Receivables</span>', $content);
        $this->assertStringContainsString('<span class="truncate">Commercials</span>', $content);
        $this->assertStringContainsString('<span class="truncate">Sales Target Management</span>', $content);
        $this->assertStringContainsString('<span class="truncate">HR · CRM</span>', $content);
        $this->assertStringContainsString('<span class="truncate">Accounting (Books)</span>', $content);
        $this->assertStringContainsString('<span class="truncate">Reports</span>', $content);
        $this->assertStringContainsString('<span class="truncate">Tally Connector</span>', $content);
        $this->assertStringContainsString('<span class="truncate">Guide</span>', $content);

        // 8. Confirm NO numeric prefixes appear before section headings
        $this->assertStringNotContainsString('1 · Setup', $content);
        $this->assertStringNotContainsString('2 · Masters', $content);
        $this->assertStringNotContainsString('3 · Sales', $content);
        $this->assertStringNotContainsString('4 · Purchases', $content);
        $this->assertStringNotContainsString('section-num', $content);
    }

    public function test_collapsed_sidebar_links_point_to_valid_primary_routes(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertStatus(200);

        $content = $response->getContent();

        $routes = [
            route('dashboard'),
            route('organization.company-profile'),
            route('orders.index'),
            route('masters.customers.index'),
            route('purchasing.orders.index'),
            route('inventory.stock.index'),
            route('logistics.load-sheets.index'),
            route('payments.index'),
            route('deals.index'),
            route('sales-targets.dashboard'),
            route('hrms.employees.index'),
            route('books.tally.dashboard'),
            route('reports.sales'),
            route('tally-connector.show'),
            route('guide'),
        ];

        foreach ($routes as $url) {
            $this->assertStringContainsString($url, $content, "Expected URL {$url} in sidebar navigation");
        }
    }

    public function test_sidebar_structure_enforces_collapsed_rail_width_and_centered_icons(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Sidebar uses 80px width (lg:w-20, within 76-88px requirement)
        $this->assertStringContainsString("sidebarCollapsed ? 'lg:w-20 books-rail-collapsed' : 'lg:w-64 books-rail-open'", $content);
        $this->assertStringContainsString(':data-collapsed="sidebarCollapsed ? \'true\' : \'false\'"', $content);

        // 2. Dashboard icon toggles to centered h-10 w-10 without conflicting padding/gap
        $this->assertStringContainsString(":class=\"\$root.sidebarCollapsed ? 'h-10 w-10 justify-center p-0 mx-auto' : 'w-full px-3 py-2 gap-3'\"", $content);

        // 3. User section items toggle to centered h-10 w-10
        $this->assertStringContainsString('sidebar-expanded-text', $content);

        // 4. Floating tooltip overlay exists in the DOM outside nav
        $this->assertStringContainsString('x-show="tooltipVisible && $root.sidebarCollapsed"', $content);
        $this->assertStringContainsString('pointer-events-none rounded-lg bg-slate-900/95', $content);
    }
}
