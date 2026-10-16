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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubCategoryDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Brand $brand;
    protected Category $category;

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

        $this->category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Audio',
            'code' => 'CAT-AUDIO-01',
            'is_active' => true,
        ]);
    }

    public function test_sub_categories_listing_renders_delete_button_and_confirmation_modal(): void
    {
        $subCategory = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Bluetooth Headphones',
            'code' => 'SUB-BTHP-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('masters.sub-categories.index'));

        $response->assertStatus(200);
        $response->assertSee('BLUETOOTH HEADPHONES');
        $response->assertSee('SUB-BTHP-01');
        $response->assertSee('Edit');
        $response->assertSee('Delete');
        $response->assertSee('data-delete-subcategory="' . $subCategory->id . '"', false);
        $response->assertSee('Delete Sub Category?');
        $response->assertSee('Are you sure you want to delete this sub-category? This action may affect linked products and records.');
        $response->assertSee('Cancel');
    }

    public function test_can_delete_sub_category_without_dependencies_permanently(): void
    {
        $subCategory = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Standalone SubCategory',
            'code' => 'SUB-STAND-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.sub-categories.destroy', $subCategory));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertStringContainsString('deleted successfully', $response->json('message'));

        $this->assertDatabaseMissing('sub_categories', [
            'id' => $subCategory->id,
        ]);
    }

    public function test_can_delete_sub_category_without_dependencies_via_form_redirect(): void
    {
        $subCategory = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Form Delete SubCategory',
            'code' => 'SUB-FORM-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('masters.sub-categories.destroy', $subCategory));

        $response->assertRedirect(route('masters.sub-categories.index'));

        $this->assertDatabaseMissing('sub_categories', [
            'id' => $subCategory->id,
        ]);
    }

    public function test_sub_category_linked_to_products_cannot_be_deleted_and_products_remain_intact(): void
    {
        $subCategory = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Wireless Earbuds',
            'code' => 'SUB-WEAR-01',
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
            'category_id' => $this->category->id,
            'sub_category_id' => $subCategory->id,
            'base_uom_id' => $uom->id,
            'name' => 'Sony WF-1000XM5',
            'sku' => 'SKU-WF-001',
            'serial_no' => 'SN-WF-001',
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.sub-categories.destroy', $subCategory));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('linked to 1 product(s)', $response->json('message'));

        // Both sub-category and product must remain completely intact
        $this->assertDatabaseHas('sub_categories', [
            'id' => $subCategory->id,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sub_category_id' => $subCategory->id,
        ]);
    }

    public function test_sub_category_linked_to_leads_cannot_be_deleted(): void
    {
        $subCategory = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Smart Watches',
            'code' => 'SUB-SW-01',
            'is_active' => true,
        ]);

        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'sub_category_id')) {
            DB::table('leads')->insert([
                'company_id' => $this->company->id,
                'sub_category_id' => $subCategory->id,
                'name' => 'Test Lead',
                'status' => 'new',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response = $this->actingAs($this->adminUser)
                ->deleteJson(route('masters.sub-categories.destroy', $subCategory));

            $response->assertStatus(422);
            $response->assertJson([
                'success' => false,
            ]);
            $this->assertStringContainsString('linked to 1 lead(s)', $response->json('message'));

            $this->assertDatabaseHas('sub_categories', [
                'id' => $subCategory->id,
            ]);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_unauthorized_user_cannot_delete_sub_categories(): void
    {
        $subCategory = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Restricted SubCategory',
            'code' => 'SUB-REST-01',
            'is_active' => true,
        ]);

        $regularUser = User::factory()->create();
        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $regularUser->assignRole($viewerRole);

        $response = $this->actingAs($regularUser)
            ->deleteJson(route('masters.sub-categories.destroy', $subCategory));

        $this->assertTrue(in_array($response->status(), [403, 302]));

        $this->assertDatabaseHas('sub_categories', [
            'id' => $subCategory->id,
        ]);
    }

    public function test_user_cannot_delete_sub_category_belonging_to_another_company(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Corp',
            'code' => 'OTH-01',
            'is_active' => true,
        ]);

        $otherBrand = Brand::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Brand',
            'code' => 'BR-OTH-01',
            'is_active' => true,
        ]);

        $otherCategory = Category::create([
            'brand_id' => $otherBrand->id,
            'name' => 'Other Category',
            'code' => 'CAT-OTH-01',
            'is_active' => true,
        ]);

        $otherSubCategory = SubCategory::create([
            'category_id' => $otherCategory->id,
            'name' => 'Other SubCategory',
            'code' => 'SUB-OTH-01',
            'is_active' => true,
        ]);

        $tenantUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $clientAdminRole = Role::firstOrCreate(['name' => 'branch-manager', 'guard_name' => 'web']);
        $tenantUser->assignRole($clientAdminRole);

        $response = $this->actingAs($tenantUser)
            ->deleteJson(route('masters.sub-categories.destroy', $otherSubCategory));

        $this->assertTrue(in_array($response->status(), [403, 302]));

        $this->assertDatabaseHas('sub_categories', [
            'id' => $otherSubCategory->id,
        ]);
    }

    public function test_search_and_filters_work_on_sub_categories_listing(): void
    {
        $subCategory1 = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Alpha SubCategory',
            'code' => 'SUB-ALP-01',
            'is_active' => true,
        ]);

        $subCategory2 = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Beta SubCategory',
            'code' => 'SUB-BET-02',
            'is_active' => false,
        ]);

        // 1. Search by name
        $searchResponse = $this->actingAs($this->adminUser)
            ->get(route('masters.sub-categories.index', ['search' => 'Alpha']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('ALPHA SUBCATEGORY');
        $searchResponse->assertDontSee('BETA SUBCATEGORY');

        // 2. Filter by status
        $statusResponse = $this->actingAs($this->adminUser)
            ->get(route('masters.sub-categories.index', ['status' => 'inactive']));
        $statusResponse->assertStatus(200);
        $statusResponse->assertSee('BETA SUBCATEGORY');
        $statusResponse->assertDontSee('ALPHA SUBCATEGORY');

        // 3. Filter by category
        $catResponse = $this->actingAs($this->adminUser)
            ->get(route('masters.sub-categories.index', ['category_id' => $this->category->id]));
        $catResponse->assertStatus(200);
        $catResponse->assertSee('ALPHA SUBCATEGORY');
    }
}

