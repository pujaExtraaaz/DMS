<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);

        $this->company = Company::create([
            'name' => 'Electronics Corp',
            'code' => 'ELEC-01',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Sony',
            'code' => 'BR-SONY-01',
            'is_active' => true,
        ]);
    }

    public function test_categories_listing_renders_delete_button_and_confirmation_modal(): void
    {
        $category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Headphones',
            'code' => 'CAT-HDPH-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('masters.categories.index'));

        $response->assertStatus(200);
        $response->assertSee('HEADPHONES');
        $response->assertSee('data-delete-category="' . $category->id . '"', false);
        $response->assertSee('Delete Category');
        $response->assertSee('Confirm Delete');
        $response->assertSee('Cancel');
    }

    public function test_can_delete_category_without_dependencies_permanently(): void
    {
        $category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Standalone Category',
            'code' => 'CAT-STAND-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.categories.destroy', $category));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => "Category 'STANDALONE CATEGORY' deleted successfully.",
            'archived' => false,
        ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_can_delete_category_without_dependencies_via_form_redirect(): void
    {
        $category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Form Delete Category',
            'code' => 'CAT-FORM-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('masters.categories.destroy', $category));

        $response->assertRedirect(route('masters.categories.index'));
        $response->assertSessionHas('status', "Category 'Form Delete Category' Deleted Successfully.");

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_category_linked_to_subcategories_is_safely_archived_not_permanently_deleted(): void
    {
        $category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Audio Systems',
            'code' => 'CAT-AUDIO-01',
            'is_active' => true,
        ]);

        $subCategory = SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Wireless Speakers',
            'code' => 'SUB-WSPK-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.categories.destroy', $category));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'archived' => true,
        ]);
        $this->assertStringContainsString('safely archived', $response->json('message'));

        // Category and subcategory still exist in database, safely archived (is_active = false)
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('sub_categories', [
            'id' => $subCategory->id,
            'is_active' => false,
        ]);
    }

    public function test_category_linked_to_products_is_safely_archived_preserving_product_associations(): void
    {
        $category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Television',
            'code' => 'CAT-TV-01',
            'is_active' => true,
        ]);

        $uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Unit',
            'code' => 'UNT',
            'is_active' => true,
        ]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brand->id,
            'category_id' => $category->id,
            'base_uom_id' => $uom->id,
            'name' => 'Bravia 55 OLED',
            'sku' => 'SKU-BRAV-055',
            'serial_no' => 'SN-BRAV-055',
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.categories.destroy', $category));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'archived' => true,
        ]);

        // Category is archived
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);

        // Product remains completely intact with its category_id preserved
        $product->refresh();
        $this->assertNotNull($product);
        $this->assertEquals($category->id, $product->category_id);
    }

    public function test_unauthorized_user_cannot_delete_category(): void
    {
        $unauthorizedUser = User::factory()->create();

        $category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Restricted Category',
            'code' => 'CAT-REST-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->deleteJson(route('masters.categories.destroy', $category));

        $response->assertStatus(403);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_user_cannot_delete_category_belonging_to_another_company(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Corp',
            'code' => 'OTHR-02',
            'is_active' => true,
        ]);

        $otherBrand = Brand::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Brand',
            'code' => 'BR-OTHR-01',
            'is_active' => true,
        ]);

        $limitedRole = Role::firstOrCreate(['name' => 'catalog-manager', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'masters.manage', 'guard_name' => 'web']);
        $limitedRole->givePermissionTo('masters.manage');

        $userCompany1 = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $userCompany1->assignRole($limitedRole);

        $otherCategory = Category::create([
            'brand_id' => $otherBrand->id,
            'name' => 'Cross Company Category',
            'code' => 'CAT-CROSS-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($userCompany1)
            ->deleteJson(route('masters.categories.destroy', $otherCategory));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized: This category belongs to another company.',
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $otherCategory->id,
        ]);
    }

    public function test_search_and_filters_work_on_categories_listing(): void
    {
        $cat1 = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Smart Home',
            'code' => 'CAT-SMART-01',
            'is_active' => true,
        ]);

        $cat2 = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Legacy Cables',
            'code' => 'CAT-CBL-01',
            'is_active' => false,
        ]);

        // Search test
        $searchResponse = $this->actingAs($this->adminUser)
            ->get(route('masters.categories.index', ['search' => 'Smart']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('SMART HOME');
        $searchResponse->assertDontSee('LEGACY CABLES');

        // Status filter: active
        $activeResponse = $this->actingAs($this->adminUser)
            ->get(route('masters.categories.index', ['status' => 'active']));
        $activeResponse->assertStatus(200);
        $activeResponse->assertSee('SMART HOME');
        $activeResponse->assertDontSee('LEGACY CABLES');

        // Status filter: inactive
        $inactiveResponse = $this->actingAs($this->adminUser)
            ->get(route('masters.categories.index', ['status' => 'inactive']));
        $inactiveResponse->assertStatus(200);
        $inactiveResponse->assertDontSee('SMART HOME');
        $inactiveResponse->assertSee('LEGACY CABLES');
    }
}

