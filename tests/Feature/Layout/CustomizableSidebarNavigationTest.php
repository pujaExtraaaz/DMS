<?php

namespace Tests\Feature\Layout;

use App\Models\User;
use App\Support\SidebarManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomizableSidebarNavigationTest extends TestCase
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

    public function test_default_sidebar_renders_all_sections_in_order_with_sequential_numbers(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('organization.company-profile'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Check customize toggle button exists
        $this->assertStringContainsString('Customize Order', $content);
        $this->assertStringContainsString('Reorder Sections', $content);

        // Check drag handles exist
        $this->assertStringContainsString('title="Drag to reorder section"', $content);
        $this->assertStringContainsString('title="Move Up"', $content);
        $this->assertStringContainsString('title="Move Down"', $content);

        // Check all 13 sections exist with data attributes
        foreach (SidebarManager::DEFAULT_SECTIONS as $key) {
            $this->assertStringContainsString("data-sidebar-section=\"{$key}\"", $content);
        }

        // Verify default order appearance in HTML: setup -> masters -> purchasing -> inventory -> sales_orders
        $posSetup = strpos($content, 'data-sidebar-section="setup"');
        $posMasters = strpos($content, 'data-sidebar-section="masters"');
        $posPurchasing = strpos($content, 'data-sidebar-section="purchasing"');
        $posInventory = strpos($content, 'data-sidebar-section="inventory"');
        $posSalesOrders = strpos($content, 'data-sidebar-section="sales_orders"');
        $posSalesTargets = strpos($content, 'data-sidebar-section="sales_targets"');
        $posHrCrm = strpos($content, 'data-sidebar-section="hr_crm"');

        $this->assertTrue($posSetup < $posMasters, 'Setup should precede Masters by default');
        $this->assertTrue($posMasters < $posPurchasing, 'Masters should precede Purchasing by default');
        $this->assertTrue($posPurchasing < $posInventory, 'Purchasing should precede Inventory by default');
        $this->assertTrue($posInventory < $posSalesOrders, 'Inventory should precede Sales & Orders by default');
        $this->assertTrue($posSalesTargets < $posHrCrm, 'Sales Targets should precede HR CRM by default');
    }

    public function test_user_can_save_custom_sidebar_order_via_api(): void
    {
        $customOrder = [
            'masters',
            'setup',
            'sales_targets',
            'purchasing',
            'inventory',
            'sales_orders',
            'logistics',
            'receivables',
            'commercials',
            'hr_crm',
            'accounting_books',
            'reports',
            'guide',
        ];

        $response = $this->actingAs($this->adminUser)->postJson(route('sidebar.preferences.update'), [
            'order' => $customOrder,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'order' => $customOrder,
            ]);

        // Preference saved in DB
        $saved = $this->adminUser->fresh()->getPreference(SidebarManager::PREFERENCE_KEY);
        $this->assertEquals($customOrder, $saved);

        // Subsequent page load reflects new order
        $viewResponse = $this->actingAs($this->adminUser)->get(route('organization.company-profile'));
        $content = $viewResponse->getContent();

        $posSetup = strpos($content, 'data-sidebar-section="setup"');
        $posMasters = strpos($content, 'data-sidebar-section="masters"');
        $posSalesTargets = strpos($content, 'data-sidebar-section="sales_targets"');

        $this->assertTrue($posMasters < $posSetup, 'Masters must now precede Setup in DOM');
        $this->assertTrue($posSetup < $posSalesTargets, 'Setup must precede Sales Targets in DOM');
    }

    public function test_user_can_reset_custom_sidebar_order(): void
    {
        // First set custom order
        $customOrder = [
            'reports',
            'setup',
            'masters',
        ];
        SidebarManager::saveUserOrder($this->adminUser, $customOrder);

        $this->assertNotNull($this->adminUser->fresh()->getPreference(SidebarManager::PREFERENCE_KEY));

        // Call reset endpoint
        $response = $this->actingAs($this->adminUser)->postJson(route('sidebar.preferences.reset'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'order' => SidebarManager::DEFAULT_SECTIONS,
            ]);

        $this->assertNull($this->adminUser->fresh()->getPreference(SidebarManager::PREFERENCE_KEY));

        // Page load should reflect default order
        $viewResponse = $this->actingAs($this->adminUser)->get(route('organization.company-profile'));
        $content = $viewResponse->getContent();

        $posSetup = strpos($content, 'data-sidebar-section="setup"');
        $posReports = strpos($content, 'data-sidebar-section="reports"');

        $this->assertTrue($posSetup < $posReports, 'Setup should precede Reports after reset');
    }

    public function test_preferences_are_isolated_per_user(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $userB = User::factory()->create([
            'name' => 'Second User',
            'email' => 'userb@dms.test',
        ]);
        $userB->assignRole($role);

        // Customize order for AdminUser only
        $customOrder = ['reports', 'guide', 'masters'];
        SidebarManager::saveUserOrder($this->adminUser, $customOrder);

        $orderA = SidebarManager::getOrderedSections($this->adminUser);
        $orderB = SidebarManager::getOrderedSections($userB);

        $this->assertEquals('reports', $orderA[0]);
        $this->assertEquals('setup', $orderB[0]);
    }

    public function test_unauthenticated_user_cannot_update_preferences(): void
    {
        $response = $this->postJson(route('sidebar.preferences.update'), [
            'order' => ['masters', 'setup'],
        ]);

        $response->assertStatus(401);
    }
}

