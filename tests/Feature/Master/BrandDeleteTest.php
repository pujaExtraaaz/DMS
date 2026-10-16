<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Scheme\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BrandDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);

        $this->company = Company::create([
            'name' => 'Alpha Corporation',
            'code' => 'ALPHA-01',
            'is_active' => true,
        ]);
    }

    public function test_brands_listing_renders_delete_button_and_confirmation_modal(): void
    {
        $brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Sony Electronics',
            'code' => 'BR-SONY-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('masters.brands.index'));

        $response->assertStatus(200);
        $response->assertSee('Sony Electronics');
        $response->assertSee('data-delete-brand="' . $brand->id . '"', false);
        $response->assertSee('Delete Brand');
        $response->assertSee('Confirm Delete');
        $response->assertSee('Cancel');
    }

    public function test_can_delete_brand_without_dependencies_via_ajax(): void
    {
        $brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Standalone Brand',
            'code' => 'BR-STANDALONE-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.brands.destroy', $brand));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => "Brand 'Standalone Brand' deleted successfully.",
        ]);

        $this->assertDatabaseMissing('brands', [
            'id' => $brand->id,
        ]);
    }

    public function test_can_delete_brand_without_dependencies_via_form_redirect(): void
    {
        $brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Form Delete Brand',
            'code' => 'BR-FORM-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('masters.brands.destroy', $brand));

        $response->assertRedirect(route('masters.brands.index'));
        $response->assertSessionHas('status', "Brand 'Form Delete Brand' Deleted Successfully.");

        $this->assertDatabaseMissing('brands', [
            'id' => $brand->id,
        ]);
    }

    public function test_deleting_brand_referenced_by_products_and_schemes_disassociates_without_deleting_them(): void
    {
        $brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Samsung Brand',
            'code' => 'BR-SAMS-01',
            'is_active' => true,
        ]);

        $uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'base_uom_id' => $uom->id,
            'name' => 'Samsung Galaxy Phone',
            'sku' => 'SKU-SAM-001',
            'serial_no' => 'SN-SAM-001',
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        $category = Category::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'name' => 'Smartphones',
            'code' => 'CAT-PHONE-01',
            'is_active' => true,
        ]);

        $scheme = Scheme::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'name' => 'Samsung Q3 Rebate',
            'code' => 'SCH-SAM-Q3',
            'basis' => 'value',
            'status' => 'draft',
            'starts_on' => now()->startOfMonth(),
            'ends_on' => now()->endOfMonth(),
        ]);

        // Delete the brand
        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.brands.destroy', $brand));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => "Brand 'Samsung Brand' deleted successfully.",
        ]);

        // Brand should be deleted
        $this->assertDatabaseMissing('brands', [
            'id' => $brand->id,
        ]);

        // Dependent records must NOT be deleted, but rather safely disassociated (brand_id set to null)
        $product->refresh();
        $this->assertNotNull($product);
        $this->assertEquals('Samsung Galaxy Phone', $product->name);
        $this->assertNull($product->brand_id);

        $category->refresh();
        $this->assertNotNull($category);
        $this->assertEquals('Smartphones', $category->name);
        $this->assertNull($category->brand_id);

        $scheme->refresh();
        $this->assertNotNull($scheme);
        $this->assertEquals('Samsung Q3 Rebate', $scheme->name);
        $this->assertNull($scheme->brand_id);
    }

    public function test_unauthorized_user_cannot_delete_brand(): void
    {
        $unauthorizedUser = User::factory()->create();

        $brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Protected Brand',
            'code' => 'BR-PROT-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->deleteJson(route('masters.brands.destroy', $brand));

        $response->assertStatus(403);

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
        ]);
    }

    public function test_user_cannot_delete_brand_from_another_company(): void
    {
        $otherCompany = Company::create([
            'name' => 'Beta Industries',
            'code' => 'BETA-02',
            'is_active' => true,
        ]);

        $limitedRole = Role::firstOrCreate(['name' => 'staff-manager', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'masters.manage', 'guard_name' => 'web']);
        $limitedRole->givePermissionTo('masters.manage');

        $company1User = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $company1User->assignRole($limitedRole);

        $otherCompanyBrand = Brand::create([
            'company_id' => $otherCompany->id,
            'name' => 'Beta Brand',
            'code' => 'BR-BETA-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($company1User)
            ->deleteJson(route('masters.brands.destroy', $otherCompanyBrand));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized: This brand belongs to another company.',
        ]);

        $this->assertDatabaseHas('brands', [
            'id' => $otherCompanyBrand->id,
        ]);
    }

    public function test_search_and_filters_continue_to_work_on_brands_listing(): void
    {
        Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Apple Electronics',
            'code' => 'BR-APPL-01',
            'is_active' => true,
        ]);

        Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Dell Computers',
            'code' => 'BR-DELL-01',
            'is_active' => false,
        ]);

        // Search test
        $searchResponse = $this->actingAs($this->adminUser)
            ->get(route('masters.brands.index', ['search' => 'Apple']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Apple Electronics');
        $searchResponse->assertDontSee('Dell Computers');

        // Status filter test: active only
        $activeResponse = $this->actingAs($this->adminUser)
            ->get(route('masters.brands.index', ['status' => 'active']));
        $activeResponse->assertStatus(200);
        $activeResponse->assertSee('Apple Electronics');
        $activeResponse->assertDontSee('Dell Computers');

        // Status filter test: inactive only
        $inactiveResponse = $this->actingAs($this->adminUser)
            ->get(route('masters.brands.index', ['status' => 'inactive']));
        $inactiveResponse->assertStatus(200);
        $inactiveResponse->assertDontSee('Apple Electronics');
        $inactiveResponse->assertSee('Dell Computers');
    }
}
