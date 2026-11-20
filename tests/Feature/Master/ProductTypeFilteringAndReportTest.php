<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTypeFilteringAndReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::create([
            'code' => 'TESTCO',
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);

        $this->uom = Uom::firstOrCreate(
            ['code' => 'PCS'],
            ['name' => 'Piece', 'is_active' => true]
        );
    }

    public function test_default_page_loads_with_all_active_brands_and_assigned_products_report(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.product-types.index'));

        $response->assertOk();
        $response->assertViewIs('masters.product-types.index');
        $response->assertViewHasAll([
            'items',
            'brands',
            'categories',
            'subCategories',
            'typesForFilter',
            'reportProducts',
            'reportProductsCount',
            'totalAssignedProducts',
        ]);
        $response->assertSee('Find & Filter by Catalog Hierarchy', false);
        $response->assertSee('Assigned Products Report');
    }

    public function test_cascading_filters_brand_narrows_categories(): void
    {
        $brand1 = Brand::create(['name' => 'Brand Alpha ' . uniqid(), 'is_active' => true]);
        $brand2 = Brand::create(['name' => 'Brand Beta ' . uniqid(), 'is_active' => true]);

        $cat1 = Category::create(['name' => 'Category A ' . uniqid(), 'brand_id' => $brand1->id, 'is_active' => true]);
        $cat2 = Category::create(['name' => 'Category B ' . uniqid(), 'brand_id' => $brand2->id, 'is_active' => true]);

        $response = $this->actingAs($this->user)->get(route('masters.product-types.index', [
            'brand_id' => $brand1->id,
        ]));

        $response->assertOk();
        $categoriesInView = $response->viewData('categories');
        $this->assertTrue($categoriesInView->contains('id', $cat1->id));
        $this->assertFalse($categoriesInView->contains('id', $cat2->id));
    }

    public function test_cascading_filters_category_narrows_subcategories(): void
    {
        $brand = Brand::create(['name' => 'Brand ' . uniqid(), 'is_active' => true]);
        $cat1 = Category::create(['name' => 'Cat 1 ' . uniqid(), 'brand_id' => $brand->id, 'is_active' => true]);
        $cat2 = Category::create(['name' => 'Cat 2 ' . uniqid(), 'brand_id' => $brand->id, 'is_active' => true]);

        $sub1 = SubCategory::create(['name' => 'Sub 1 ' . uniqid(), 'category_id' => $cat1->id, 'is_active' => true]);
        $sub2 = SubCategory::create(['name' => 'Sub 2 ' . uniqid(), 'category_id' => $cat2->id, 'is_active' => true]);

        $response = $this->actingAs($this->user)->get(route('masters.product-types.index', [
            'brand_id' => $brand->id,
            'category_id' => $cat1->id,
        ]));

        $response->assertOk();
        $subCatsInView = $response->viewData('subCategories');
        $this->assertTrue($subCatsInView->contains('id', $sub1->id));
        $this->assertFalse($subCatsInView->contains('id', $sub2->id));
    }

    public function test_parent_filter_change_resets_invalid_dependent_filters(): void
    {
        $brand1 = Brand::create(['name' => 'Brand One ' . uniqid(), 'is_active' => true]);
        $brand2 = Brand::create(['name' => 'Brand Two ' . uniqid(), 'is_active' => true]);

        $cat1 = Category::create(['name' => 'Category One ' . uniqid(), 'brand_id' => $brand1->id, 'is_active' => true]);
        $cat2 = Category::create(['name' => 'Category Two ' . uniqid(), 'brand_id' => $brand2->id, 'is_active' => true]);
        $sub2 = SubCategory::create(['name' => 'Sub Two ' . uniqid(), 'category_id' => $cat2->id, 'is_active' => true]);

        // Request with Brand 1, but passing Category 2 (which belongs to Brand 2)
        $response = $this->actingAs($this->user)->get(route('masters.product-types.index', [
            'brand_id' => $brand1->id,
            'category_id' => $cat2->id,
            'sub_category_id' => $sub2->id,
        ]));

        $response->assertOk();
        $this->assertSame($brand1->id, $response->viewData('brandId'));
        $this->assertNull($response->viewData('categoryId'), 'Category should be reset to null when it does not belong to the selected brand.');
        $this->assertNull($response->viewData('subCategoryId'), 'Subcategory should be reset to null when parent category is reset.');
    }

    public function test_matching_product_report_displays_only_matching_products_and_accurate_counts(): void
    {
        $brand = Brand::create(['name' => 'Sony Test ' . uniqid(), 'is_active' => true]);
        $category = Category::create(['name' => 'Sony Pro Display ' . uniqid(), 'brand_id' => $brand->id, 'is_active' => true]);
        $subcategory = SubCategory::create(['name' => 'BZ30L', 'category_id' => $category->id, 'is_active' => true]);

        $type1 = ProductType::create(['name' => 'Finished Goods ' . uniqid(), 'code' => 'FG' . rand(100, 999), 'is_active' => true]);
        $type2 = ProductType::create(['name' => 'Spare Parts ' . uniqid(), 'code' => 'SP' . rand(100, 999), 'is_active' => true]);

        // Matching product 1 (explicit sub_category_id)
        $p1 = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'sub_category_id' => $subcategory->id,
            'product_type_id' => $type1->id,
            'name' => 'Sony FW-43BZ30L Test',
            'sku' => 'SKU-' . uniqid(),
            'model' => 'FW-43BZ30L',
            'serial_no' => 'SR-' . uniqid(),
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        // Matching product 2 (name/model matching subcategory name when sub_category_id was not explicitly populated)
        $p2 = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'sub_category_id' => null,
            'product_type_id' => $type1->id,
            'name' => 'Sony FW-55BZ30L Test',
            'sku' => 'SKU-' . uniqid(),
            'model' => 'FW-55BZ30L',
            'serial_no' => 'SR-' . uniqid(),
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        // Unrelated product in same brand & category, but different subcategory
        $otherSub = SubCategory::create(['name' => 'EZ20L', 'category_id' => $category->id, 'is_active' => true]);
        $pUnrelated = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'sub_category_id' => $otherSub->id,
            'product_type_id' => $type2->id,
            'name' => 'Sony FW-50EZ20L Test',
            'sku' => 'SKU-' . uniqid(),
            'model' => 'FW-50EZ20L',
            'serial_no' => 'SR-' . uniqid(),
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.product-types.index', [
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'sub_category_id' => $subcategory->id,
        ]));

        $response->assertOk();
        $reportProducts = $response->viewData('reportProducts');
        $reportCount = $response->viewData('reportProductsCount');

        $this->assertSame(2, $reportCount);
        $this->assertTrue($reportProducts->contains('id', $p1->id));
        $this->assertTrue($reportProducts->contains('id', $p2->id));
        $this->assertFalse($reportProducts->contains('id', $pUnrelated->id), 'Unrelated EZ20L product should not appear when BZ30L is filtered.');

        // Product Types table in view should show Finished Goods with count 2
        $items = $response->viewData('items');
        $fgItem = $items->firstWhere('id', $type1->id);
        $this->assertNotNull($fgItem);
        $this->assertSame(2, $fgItem->products_count);
    }

    public function test_filter_by_specific_product_type(): void
    {
        $brand = Brand::create(['name' => 'Brand ' . uniqid(), 'is_active' => true]);
        $cat = Category::create(['name' => 'Cat ' . uniqid(), 'brand_id' => $brand->id, 'is_active' => true]);
        $sub = SubCategory::create(['name' => 'Sub ' . uniqid(), 'category_id' => $cat->id, 'is_active' => true]);

        $type1 = ProductType::create(['name' => 'Type 1 ' . uniqid(), 'code' => 'T1' . rand(100, 999), 'is_active' => true]);
        $type2 = ProductType::create(['name' => 'Type 2 ' . uniqid(), 'code' => 'T2' . rand(100, 999), 'is_active' => true]);

        $p1 = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'sub_category_id' => $sub->id,
            'product_type_id' => $type1->id,
            'name' => 'Product Type 1',
            'sku' => 'SKU-' . uniqid(),
            'serial_no' => 'SR-' . uniqid(),
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        $p2 = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'sub_category_id' => $sub->id,
            'product_type_id' => $type2->id,
            'name' => 'Product Type 2',
            'sku' => 'SKU-' . uniqid(),
            'serial_no' => 'SR-' . uniqid(),
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.product-types.index', [
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'sub_category_id' => $sub->id,
            'product_type_id' => $type1->id,
        ]));

        $response->assertOk();
        $reportProducts = $response->viewData('reportProducts');
        $this->assertSame(1, $response->viewData('reportProductsCount'));
        $this->assertTrue($reportProducts->contains('id', $p1->id));
        $this->assertFalse($reportProducts->contains('id', $p2->id));
    }

    public function test_products_without_assigned_product_type_are_excluded(): void
    {
        $brand = Brand::create(['name' => 'Brand ' . uniqid(), 'is_active' => true]);
        $cat = Category::create(['name' => 'Cat ' . uniqid(), 'brand_id' => $brand->id, 'is_active' => true]);
        $sub = SubCategory::create(['name' => 'Sub ' . uniqid(), 'category_id' => $cat->id, 'is_active' => true]);

        $type = ProductType::create(['name' => 'Type ' . uniqid(), 'code' => 'T' . rand(100, 999), 'is_active' => true]);

        $pWith = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'sub_category_id' => $sub->id,
            'product_type_id' => $type->id,
            'name' => 'Assigned Product',
            'sku' => 'SKU-' . uniqid(),
            'serial_no' => 'SR-' . uniqid(),
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        $pWithout = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'sub_category_id' => $sub->id,
            'product_type_id' => null,
            'product_type' => null,
            'name' => 'Unassigned Product',
            'sku' => 'SKU-' . uniqid(),
            'serial_no' => 'SR-' . uniqid(),
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.product-types.index', [
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'sub_category_id' => $sub->id,
        ]));

        $response->assertOk();
        $reportProducts = $response->viewData('reportProducts');
        $this->assertTrue($reportProducts->contains('id', $pWith->id));
        $this->assertFalse($reportProducts->contains('id', $pWithout->id), 'Products without an assigned product type must not appear in filtered results.');
    }
}
