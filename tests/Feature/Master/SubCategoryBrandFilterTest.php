<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubCategoryBrandFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Brand $brandSony;
    protected Brand $brandSamsung;
    protected Category $categoryAudioSony;
    protected Category $categoryTvSony;
    protected Category $categoryMobileSamsung;
    protected SubCategory $subCategoryHeadphones;
    protected SubCategory $subCategoryOled;
    protected SubCategory $subCategoryGalaxy;

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

        // Brand 1: Sony
        $this->brandSony = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Sony',
            'code' => 'BR-SONY',
            'is_active' => true,
        ]);

        // Brand 2: Samsung
        $this->brandSamsung = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Samsung',
            'code' => 'BR-SAMSUNG',
            'is_active' => true,
        ]);

        // Sony Categories
        $this->categoryAudioSony = Category::create([
            'brand_id' => $this->brandSony->id,
            'name' => 'Sony Audio',
            'is_active' => true,
        ]);

        $this->categoryTvSony = Category::create([
            'brand_id' => $this->brandSony->id,
            'name' => 'Sony Television',
            'is_active' => true,
        ]);

        // Samsung Category
        $this->categoryMobileSamsung = Category::create([
            'brand_id' => $this->brandSamsung->id,
            'name' => 'Samsung Mobile',
            'is_active' => true,
        ]);

        // Sub categories
        $this->subCategoryHeadphones = SubCategory::create([
            'category_id' => $this->categoryAudioSony->id,
            'name' => 'Wireless Headphones',
            'is_active' => true,
        ]);

        $this->subCategoryOled = SubCategory::create([
            'category_id' => $this->categoryTvSony->id,
            'name' => 'OLED TVs',
            'is_active' => true,
        ]);

        $this->subCategoryGalaxy = SubCategory::create([
            'category_id' => $this->categoryMobileSamsung->id,
            'name' => 'Galaxy S Series',
            'is_active' => true,
        ]);
    }

    /**
     * Requirement 1 & 2: Brand filter appears immediately before Category filter,
     * options load dynamically from Brand records, and "All Brands" option is provided.
     */
    public function test_brand_filter_renders_before_category_filter_with_dynamic_options(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.sub-categories.index'));

        $response->assertOk();

        $content = $response->getContent();

        // Brand filter dropdown exists with name="brand_id" and "All Brands"
        $this->assertStringContainsString('name="brand_id"', $content);
        $this->assertStringContainsString('All Brands', $content);
        $this->assertStringContainsString('name="category_id"', $content);

        // Ensure brand_id select appears before category_id select in the HTML
        $brandPos = strpos($content, 'name="brand_id"');
        $catPos = strpos($content, 'name="category_id"');
        $this->assertNotFalse($brandPos);
        $this->assertNotFalse($catPos);
        $this->assertLessThan($catPos, $brandPos, 'Brand filter select must appear before Category filter select.');

        // Verify dynamic brand options are rendered
        $this->assertStringContainsString((string) $this->brandSony->name, $content);
        $this->assertStringContainsString((string) $this->brandSamsung->name, $content);
    }

    /**
     * Requirement 3: Selecting a brand displays only subcategories associated with that brand.
     */
    public function test_selecting_brand_filters_subcategories_by_brand(): void
    {
        // Filter by Sony
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.sub-categories.index', ['brand_id' => $this->brandSony->id]));

        $response->assertOk();
        $response->assertSee($this->subCategoryHeadphones->name);
        $response->assertSee($this->subCategoryOled->name);
        $response->assertDontSee($this->subCategoryGalaxy->name);

        // Filter by Samsung
        $responseSamsung = $this->actingAs($this->adminUser)
            ->get(route('masters.sub-categories.index', ['brand_id' => $this->brandSamsung->id]));

        $responseSamsung->assertOk();
        $responseSamsung->assertSee($this->subCategoryGalaxy->name);
        $responseSamsung->assertDontSee($this->subCategoryHeadphones->name);
        $responseSamsung->assertDontSee($this->subCategoryOled->name);
    }

    /**
     * Requirement 3: Selecting a category further filters within the selected brand.
     */
    public function test_selecting_category_further_filters_within_selected_brand(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('masters.sub-categories.index', [
            'brand_id' => $this->brandSony->id,
            'category_id' => $this->categoryAudioSony->id,
        ]));

        $response->assertOk();
        $response->assertSee($this->subCategoryHeadphones->name);
        $response->assertDontSee($this->subCategoryOled->name);
        $response->assertDontSee($this->subCategoryGalaxy->name);
    }

    /**
     * Requirement 4: When brand is selected, Category dropdown only displays categories for that brand.
     */
    public function test_category_dropdown_only_displays_categories_associated_with_selected_brand(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.sub-categories.index', ['brand_id' => $this->brandSony->id]));

        $response->assertOk();
        $categoriesInView = $response->viewData('categories');

        $categoryIds = $categoriesInView->pluck('id')->all();
        $this->assertContains($this->categoryAudioSony->id, $categoryIds);
        $this->assertContains($this->categoryTvSony->id, $categoryIds);
        $this->assertNotContains($this->categoryMobileSamsung->id, $categoryIds);
    }

    /**
     * Requirement 4: If category does not belong to selected brand, category filter is reset.
     */
    public function test_category_filter_is_reset_if_it_does_not_belong_to_selected_brand(): void
    {
        // Pass Sony brand with Samsung Mobile category ID
        $response = $this->actingAs($this->adminUser)->get(route('masters.sub-categories.index', [
            'brand_id' => $this->brandSony->id,
            'category_id' => $this->categoryMobileSamsung->id,
        ]));

        $response->assertOk();
        // Controller should reset category_id to null
        $this->assertNull($response->viewData('categoryId'));

        // Both Sony subcategories should be returned since category filter was reset
        $response->assertSee($this->subCategoryHeadphones->name);
        $response->assertSee($this->subCategoryOled->name);
        $response->assertDontSee($this->subCategoryGalaxy->name);
    }

    /**
     * Requirement 3: Resetting brand restores all subcategories.
     */
    public function test_resetting_brand_filter_restores_all_subcategories(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.sub-categories.index', ['brand_id' => '']));

        $response->assertOk();
        $response->assertSee($this->subCategoryHeadphones->name);
        $response->assertSee($this->subCategoryOled->name);
        $response->assertSee($this->subCategoryGalaxy->name);
    }

    /**
     * Requirement 3: Brand, Category, Status, and Search work together harmoniously.
     */
    public function test_brand_category_status_and_search_filters_work_together(): void
    {
        // Create an inactive Sony Audio subcategory
        $inactiveHeadphones = SubCategory::create([
            'category_id' => $this->categoryAudioSony->id,
            'name' => 'Wired Earphones',
            'is_active' => false,
        ]);

        // Combined filter: brand=Sony, category=Sony Audio, status=inactive
        $response = $this->actingAs($this->adminUser)->get(route('masters.sub-categories.index', [
            'brand_id' => $this->brandSony->id,
            'category_id' => $this->categoryAudioSony->id,
            'status' => 'inactive',
        ]));

        $response->assertOk();
        $response->assertSee($inactiveHeadphones->name);
        $response->assertDontSee($this->subCategoryHeadphones->name);
        $response->assertDontSee($this->subCategoryOled->name);
        $response->assertDontSee($this->subCategoryGalaxy->name);

        // Combined filter with search: search="Wireless"
        $responseSearch = $this->actingAs($this->adminUser)->get(route('masters.sub-categories.index', [
            'brand_id' => $this->brandSony->id,
            'search' => 'Wireless',
        ]));

        $responseSearch->assertOk();
        $responseSearch->assertSee($this->subCategoryHeadphones->name);
        $responseSearch->assertDontSee($this->subCategoryOled->name);
        $responseSearch->assertDontSee($inactiveHeadphones->name);
    }

    /**
     * Export option respects brand and category filters.
     */
    public function test_export_option_respects_brand_filter(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('masters.sub-categories.index', [
            'brand_id' => $this->brandSamsung->id,
            'export' => 'csv',
        ]));

        $response->assertOk();
        $csvContent = $response->streamedContent();

        $this->assertStringContainsString($this->subCategoryGalaxy->name, $csvContent);
        $this->assertStringNotContainsString($this->subCategoryHeadphones->name, $csvContent);
        $this->assertStringNotContainsString($this->subCategoryOled->name, $csvContent);
    }
}
