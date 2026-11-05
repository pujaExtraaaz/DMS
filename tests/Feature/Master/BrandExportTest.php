<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BrandExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'masters.view', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Alpha Corporation',
            'code' => 'ALPHA01',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($roleAdmin);
    }

    public function test_brands_listing_renders_export_dropdown_menu_with_all_options(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.brands.index'));

        $response->assertOk();
        $response->assertSee('Export');
        $response->assertSee('Excel');
        $response->assertSee('(.xlsx)');
        $response->assertSee('CSV');
        $response->assertSee('(.csv)');
        $response->assertSee('PDF');
        $response->assertSee('(.pdf)');
        $response->assertSee('data-export-format="xlsx"', false);
        $response->assertSee('data-export-format="csv"', false);
        $response->assertSee('data-export-format="pdf"', false);
        // Ensure left alignment classes and positioning logic exist
        $response->assertSee('alignLeft', false);
        $response->assertSee('left-0 origin-top-left', false);
        $response->assertSee('dropUp', false);
    }

    public function test_can_export_brands_to_csv(): void
    {
        Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Sony Electronics',
            'detail' => 'Audio and visual hardware',
            'is_active' => true,
        ]);

        Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Panasonic Legacy',
            'detail' => 'Discontinued product line',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.brands.index', ['export' => 'csv']));

        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('Name', $content);
        $this->assertStringContainsString('Details', $content);
        $this->assertStringContainsString('Status', $content);
        $this->assertStringContainsString('Sony Electronics', $content);
        $this->assertStringContainsString('Active', $content);
        $this->assertStringContainsString('Panasonic Legacy', $content);
        $this->assertStringContainsString('Inactive', $content);
    }

    public function test_can_export_brands_to_excel_xlsx(): void
    {
        Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Sony Electronics',
            'detail' => 'Audio and visual hardware',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.brands.index', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertTrue(str_contains(
            $response->headers->get('content-type'),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ));
    }

    public function test_can_export_brands_to_pdf(): void
    {
        Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Sony Electronics',
            'detail' => 'Audio and visual hardware',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.brands.index', ['export' => 'pdf']));

        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'application/pdf'));
    }

    public function test_export_respects_search_and_status_filters(): void
    {
        Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Apple Inc',
            'detail' => 'Phones and tablets',
            'is_active' => true,
        ]);

        Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Samsung Mobile',
            'detail' => 'Smartphones',
            'is_active' => true,
        ]);

        Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Apple Audio Old',
            'detail' => 'Legacy headphones',
            'is_active' => false,
        ]);

        // Search for 'Apple' with active status only
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.brands.index', [
                'search' => 'Apple',
                'status' => 'active',
                'export' => 'csv',
            ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Apple Inc', $content);
        $this->assertStringNotContainsString('Samsung Mobile', $content);
        $this->assertStringNotContainsString('Apple Audio Old', $content);
    }
}
