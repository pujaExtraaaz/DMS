<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductHierarchicalFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Uom $baseUom;
    protected ProductType $productType;

    protected Brand $sonyBrand;
    protected Brand $samsungBrand;
    protected Brand $appleBrand;

    protected Category $sonyDisplaysCategory;
    protected Category $sonyAudioCategory;
    protected Category $samsungMonitorsCategory;

    protected SubCategory $sonyEz20lSubCategory;
    protected SubCategory $sonyBz30lSubCategory;
    protected SubCategory $samsungGamingSubCategory;

    protected Product $sonyDisplayProduct1;
    protected Product $sonyDisplayProduct2;
    protected Product $sonyAudioProduct;
    protected Product $samsungMonitorProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Apex Electronics Corp',
            'code' => 'AEC-01',
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

        $this->baseUom = Uom::firstOrCreate(['code' => 'PCS'], [
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'is_active' => true,
        ]);

        $this->productType = ProductType::firstOrCreate(['name' => 'FINISHED GOODS'], [
            'company_id' => $this->company->id,
            'code' => 'FG',
            'is_active' => true,
        ]);

        // Brands
        $this->sonyBrand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Sony',
            'is_active' => true,
        ]);

        $this->samsungBrand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Samsung',
            'is_active' => true,
        ]);

        $this->appleBrand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Apple',
            'is_active' => true,
        ]);

        // Categories
        $this->sonyDisplaysCategory = Category::create([
            'brand_id' => $this->sonyBrand->id,
            'name' => 'Sony Professional Display',
            'code' => 'CAT-SONY-DISP',
            'is_active' => true,
        ]);

        $this->sonyAudioCategory = Category::create([
            'brand_id' => $this->sonyBrand->id,
            'name' => 'Sony Audio',
            'code' => 'CAT-SONY-AUD',
            'is_active' => true,
        ]);

        $this->samsungMonitorsCategory = Category::create([
            'brand_id' => $this->samsungBrand->id,
            'name' => 'Samsung Displays & Monitors',
            'code' => 'CAT-SAM-MON',
            'is_active' => true,
        ]);

        // SubCategories
        $this->sonyEz20lSubCategory = SubCategory::create([
            'category_id' => $this->sonyDisplaysCategory->id,
            'name' => 'EZ20L',
            'code' => 'SUB-EZ20L',
            'is_active' => true,
        ]);

        $this->sonyBz30lSubCategory = SubCategory::create([
            'category_id' => $this->sonyDisplaysCategory->id,
            'name' => 'BZ30L',
            'code' => 'SUB-BZ30L',
            'is_active' => true,
        ]);

        $this->samsungGamingSubCategory = SubCategory::create([
            'category_id' => $this->samsungMonitorsCategory->id,
            'name' => 'Odyssey Gaming',
            'code' => 'SUB-ODY',
            'is_active' => true,
        ]);

        // Products
        $this->sonyDisplayProduct1 = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->sonyBrand->id,
            'category_id' => $this->sonyDisplaysCategory->id,
            'sub_category_id' => $this->sonyEz20lSubCategory->id,
            'product_type_id' => $this->productType->id,
            'base_uom_id' => $this->baseUom->id,
            'name' => 'Sony FW-43EZ20L Professional Display',
            'sku' => 'FW-43EZ20L',
            'serial_no' => 'SN-FW43-01',
            'model' => 'FW-43EZ20L',
            'is_active' => true,
        ]);

        $this->sonyDisplayProduct2 = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->sonyBrand->id,
            'category_id' => $this->sonyDisplaysCategory->id,
            'sub_category_id' => $this->sonyBz30lSubCategory->id,
            'product_type_id' => $this->productType->id,
            'base_uom_id' => $this->baseUom->id,
            'name' => 'Sony FW-55BZ30L Bravia Display',
            'sku' => 'FW-55BZ30L',
            'serial_no' => 'SN-FW55-02',
            'model' => 'FW-55BZ30L',
            'is_active' => true,
        ]);

        $this->sonyAudioProduct = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->sonyBrand->id,
            'category_id' => $this->sonyAudioCategory->id,
            'sub_category_id' => null,
            'product_type_id' => $this->productType->id,
            'base_uom_id' => $this->baseUom->id,
            'name' => 'Sony WH-1000XM5 Headphones',
            'sku' => 'WH-1000XM5',
            'serial_no' => 'SN-WH-03',
            'model' => 'WH-1000XM5',
            'is_active' => true,
        ]);

        $this->samsungMonitorProduct = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->samsungBrand->id,
            'category_id' => $this->samsungMonitorsCategory->id,
            'sub_category_id' => $this->samsungGamingSubCategory->id,
            'product_type_id' => $this->productType->id,
            'base_uom_id' => $this->baseUom->id,
            'name' => 'Samsung Odyssey G9 Monitor',
            'sku' => 'ODYSSEY-G9',
            'serial_no' => 'SN-ODY-04',
            'model' => 'ODYSSEY-G9',
            'is_active' => true,
        ]);
    }

    public function test_product_index_renders_hierarchical_filter_controls(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.index'));

        $response->assertOk();
        $response->assertSee('data-hierarchical-filter-group', false);
        $response->assertSee('name="brand_id"', false);
        $response->assertSee('name="category_id"', false);
        $response->assertSee('name="sub_category_id"', false);
        $response->assertSee('Brand: All');
        $response->assertSee('Category: All');
        $response->assertSee('Subcategory: All');
    }

    public function test_filter_by_brand_alone_returns_only_matching_brand_products(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.index', [
            'brand_id' => $this->sonyBrand->id,
        ]));

        $response->assertOk();

        $items = $response->viewData('items');
        $this->assertCount(3, $items);
        $this->assertTrue($items->pluck('id')->contains($this->sonyDisplayProduct1->id));
        $this->assertTrue($items->pluck('id')->contains($this->sonyDisplayProduct2->id));
        $this->assertTrue($items->pluck('id')->contains($this->sonyAudioProduct->id));
        $this->assertFalse($items->pluck('id')->contains($this->samsungMonitorProduct->id));

        $response->assertSee($this->sonyDisplayProduct1->sku);
        $response->assertSee($this->sonyDisplayProduct2->sku);
        $response->assertSee($this->sonyAudioProduct->sku);
        $response->assertDontSee($this->samsungMonitorProduct->sku);
    }

    public function test_filter_by_category_alone_returns_matching_category_products_across_brands(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.index', [
            'category_id' => $this->sonyDisplaysCategory->id,
        ]));

        $response->assertOk();

        $items = $response->viewData('items');
        $this->assertCount(2, $items);
        $this->assertTrue($items->pluck('id')->contains($this->sonyDisplayProduct1->id));
        $this->assertTrue($items->pluck('id')->contains($this->sonyDisplayProduct2->id));
        $this->assertFalse($items->pluck('id')->contains($this->sonyAudioProduct->id));
        $this->assertFalse($items->pluck('id')->contains($this->samsungMonitorProduct->id));
    }

    public function test_filter_by_subcategory_alone_returns_matching_subcategory_products(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.index', [
            'sub_category_id' => $this->sonyEz20lSubCategory->id,
        ]));

        $response->assertOk();

        $items = $response->viewData('items');
        $this->assertCount(1, $items);
        $this->assertEquals($this->sonyDisplayProduct1->id, $items->first()->id);
        $response->assertSee($this->sonyDisplayProduct1->sku);
        $response->assertDontSee($this->sonyDisplayProduct2->sku);
    }

    public function test_combined_brand_category_and_subcategory_filter_returns_exact_products(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.index', [
            'brand_id' => $this->sonyBrand->id,
            'category_id' => $this->sonyDisplaysCategory->id,
            'sub_category_id' => $this->sonyEz20lSubCategory->id,
        ]));

        $response->assertOk();

        $items = $response->viewData('items');
        $this->assertCount(1, $items);
        $this->assertEquals($this->sonyDisplayProduct1->id, $items->first()->id);
        $response->assertSee($this->sonyDisplayProduct1->sku);
        $response->assertDontSee($this->sonyDisplayProduct2->sku);
        $response->assertDontSee($this->samsungMonitorProduct->sku);
    }

    public function test_changing_brand_resets_incompatible_category_and_subcategory(): void
    {
        // When user requests Samsung brand but sends Sony category and Sony subcategory
        $response = $this->actingAs($this->user)->get(route('masters.products.index', [
            'brand_id' => $this->samsungBrand->id,
            'category_id' => $this->sonyDisplaysCategory->id, // Incompatible with Samsung!
            'sub_category_id' => $this->sonyEz20lSubCategory->id,
        ]));

        $response->assertOk();

        // Backend resets incompatible category and subcategory to null,
        // returning Samsung products instead of 0 or breaking.
        $this->assertNull($response->viewData('categoryId'));
        $this->assertNull($response->viewData('subCategoryId'));

        $items = $response->viewData('items');
        $this->assertTrue($items->pluck('id')->contains($this->samsungMonitorProduct->id));
        $this->assertFalse($items->pluck('id')->contains($this->sonyDisplayProduct1->id));
    }

    public function test_changing_category_resets_incompatible_subcategory(): void
    {
        // When user selects Sony Audio category but sends EZ20L subcategory (which belongs to Sony Displays)
        $response = $this->actingAs($this->user)->get(route('masters.products.index', [
            'brand_id' => $this->sonyBrand->id,
            'category_id' => $this->sonyAudioCategory->id,
            'sub_category_id' => $this->sonyEz20lSubCategory->id, // Incompatible with Sony Audio!
        ]));

        $response->assertOk();

        $this->assertEquals($this->sonyAudioCategory->id, $response->viewData('categoryId'));
        $this->assertNull($response->viewData('subCategoryId'));

        $items = $response->viewData('items');
        $this->assertCount(1, $items);
        $this->assertEquals($this->sonyAudioProduct->id, $items->first()->id);
    }

    public function test_selecting_all_filters_returns_all_unfiltered_products(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.index', [
            'brand_id' => '',
            'category_id' => '',
            'sub_category_id' => '',
        ]));

        $response->assertOk();

        $items = $response->viewData('items');
        $this->assertCount(4, $items);
    }

    public function test_text_search_combines_with_hierarchical_filters_via_and_logic(): void
    {
        // Search for '43EZ20L' within Sony brand
        $matchingResponse = $this->actingAs($this->user)->get(route('masters.products.index', [
            'brand_id' => $this->sonyBrand->id,
            'search' => '43EZ20L',
        ]));

        $matchingResponse->assertOk();
        $matchingItems = $matchingResponse->viewData('items');
        $this->assertCount(1, $matchingItems);
        $this->assertEquals($this->sonyDisplayProduct1->id, $matchingItems->first()->id);

        // Search for 'Samsung' or 'Odyssey' with Sony brand filter should return 0 results
        $noMatchResponse = $this->actingAs($this->user)->get(route('masters.products.index', [
            'brand_id' => $this->sonyBrand->id,
            'search' => 'Odyssey',
        ]));

        $noMatchResponse->assertOk();
        $this->assertCount(0, $noMatchResponse->viewData('items'));
    }

    public function test_empty_state_rendered_with_reset_button_when_no_products_match(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.index', [
            'brand_id' => $this->appleBrand->id, // Apple has no products
        ]));

        $response->assertOk();
        $response->assertSee('No Matching Products Found');
        $response->assertSee('Reset Filters');
        $response->assertSee(route('masters.products.index'));
    }

    public function test_pagination_preserves_hierarchical_filter_query_parameters(): void
    {
        // Create 20 more products for Sony to trigger pagination (pagination limit is 15)
        for ($i = 10; $i < 30; $i++) {
            Product::create([
                'company_id' => $this->company->id,
                'brand_id' => $this->sonyBrand->id,
                'category_id' => $this->sonyDisplaysCategory->id,
                'sub_category_id' => $this->sonyEz20lSubCategory->id,
                'product_type_id' => $this->productType->id,
                'base_uom_id' => $this->baseUom->id,
                'name' => "Sony Display Model {$i}",
                'sku' => "FW-SONY-{$i}",
                'serial_no' => "SN-SONY-{$i}",
                'model' => "MD-{$i}",
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('masters.products.index', [
            'brand_id' => $this->sonyBrand->id,
            'category_id' => $this->sonyDisplaysCategory->id,
            'sub_category_id' => $this->sonyEz20lSubCategory->id,
        ]));

        $response->assertOk();

        // Verify pagination links retain brand_id, category_id, and sub_category_id
        $content = $response->getContent();
        $this->assertStringContainsString('brand_id=' . $this->sonyBrand->id, $content);
        $this->assertStringContainsString('category_id=' . $this->sonyDisplaysCategory->id, $content);
        $this->assertStringContainsString('sub_category_id=' . $this->sonyEz20lSubCategory->id, $content);
    }

    public function test_product_row_displays_brand_category_subcategory_hierarchy_subtitle(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.index', [
            'brand_id' => $this->sonyBrand->id,
            'category_id' => $this->sonyDisplaysCategory->id,
            'sub_category_id' => $this->sonyEz20lSubCategory->id,
        ]));

        $response->assertOk();

        // The subtitle in the table row should display the hierarchy
        $response->assertSee('Sony · Sony Professional Display · EZ20L');
    }
}
