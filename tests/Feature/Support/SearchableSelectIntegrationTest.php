<?php

namespace Tests\Feature\Support;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchableSelectIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole('super-admin');
    }

    public function test_searchable_select_script_file_exists_and_contains_core_features(): void
    {
        $scriptPath = public_path('js/searchable-select.js');
        $this->assertFileExists($scriptPath);

        $content = file_get_contents($scriptPath);

        // Core search & filter logic
        $this->assertStringContainsString('filterOptions', $content);
        $this->assertStringContainsString('dms-searchable-search-input', $content);
        $this->assertStringContainsString('dms-searchable-empty', $content);
        $this->assertStringContainsString('No matching options', $content);

        // Keyboard navigation
        $this->assertStringContainsString('ArrowDown', $content);
        $this->assertStringContainsString('ArrowUp', $content);
        $this->assertStringContainsString('Enter', $content);
        $this->assertStringContainsString('Escape', $content);

        // Zero-clipping fixed popover layout mounted to body
        $this->assertStringContainsString('position: fixed', $content);
        $this->assertStringContainsString('z-index: 99999', $content);
        $this->assertStringContainsString('document.body.appendChild', $content);

        // Event synchronization & HTML5 validation
        $this->assertStringContainsString('dms:select-value-updated', $content);
        $this->assertStringContainsString('new Event(\'change\'', $content);
        $this->assertStringContainsString('new Event(\'input\'', $content);
        $this->assertStringContainsString('MutationObserver', $content);
        $this->assertStringContainsString('invalid', $content);

        // Clear button
        $this->assertStringContainsString('dms-searchable-clear-btn', $content);
    }

    public function test_dms_and_app_layouts_include_searchable_select_script(): void
    {
        $dmsLayout = file_get_contents(resource_path('views/layouts/dms.blade.php'));
        $this->assertStringContainsString('js/searchable-select.js', $dmsLayout);

        $appLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('js/searchable-select.js', $appLayout);
    }

    public function test_ui_select_component_renders_with_attributes_and_placeholder(): void
    {
        $view = $this->blade(
            '<x-ui.select name="test_field" label="Test Label" placeholder="Choose item" required>
                <option value="1">Option One</option>
                <option value="2">Option Two</option>
            </x-ui.select>'
        );

        $view->assertSee('Test Label');
        $view->assertSee('name="test_field"', false);
        $view->assertSee('id="test_field"', false);
        $view->assertSee('Choose Item');
        $view->assertSee('Option One');
        $view->assertSee('Option Two');
    }

    public function test_masters_customers_create_renders_with_searchable_select_script_and_dropdowns(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.customers.create'));
        $response->assertOk();
        $response->assertSee('js/searchable-select.js');
        $response->assertSee('party_type');
    }

    public function test_sister_concern_transactions_renders_with_searchable_select_script_and_dropdowns(): void
    {
        $response = $this->actingAs($this->user)->get(route('organization.sister-concerns.transactions'));
        $response->assertOk();
        $response->assertSee('js/searchable-select.js');
        $response->assertSee('transaction_type');
    }

    public function test_organization_companies_create_renders_with_searchable_select_script(): void
    {
        $response = $this->actingAs($this->user)->get(route('organization.companies.create'));
        $response->assertOk();
        $response->assertSee('js/searchable-select.js');
    }
}
