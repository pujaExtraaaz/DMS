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
use Tests\TestCase;

class ProductCatalogHierarchySafetyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Uom $baseUom;
    protected Brand $brand;
    protected Category $category;
    protected SubCategory $subCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Hierarchy Safety Co',
            'code' => 'HSC',
            'is_active' => true,
        ]);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole('super-admin');

        $this->baseUom = Uom::firstOrCreate(
            ['name' => 'PCS'],
            ['code' => 'PCS', 'is_active' => true]
        );

        $this->brand = Brand::create([
            'name' => 'Sony Electronics',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Displays',
            'brand_id' => $this->brand->id,
            'is_active' => true,
        ]);

        $this->subCategory = SubCategory::create([
            'name' => 'OLED',
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);
    }

    public function test_product_update_executes_catalog_hierarchy_without_undefined_method_error(): void
    {
        $product = Product::create([
            'name' => 'Sony Bravia 55',
            'sku' => 'SNY-BRV-55',
            'serial_no' => 'SER-BRV-55',
            'base_uom_id' => $this->baseUom->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'company_id' => $this->company->id,
            'is_active' => true,
            'is_hidden' => false,
        ]);

        // Execute PUT update on the product
        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), [
            'name' => 'Sony Bravia 55 Inch OLED',
            'sku' => 'SNY-BRV-55',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_not_applicable' => 1,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'skip_dimensions' => 1,
            'is_active' => 1,
        ]);

        // Must not encounter Call to undefined method assertCatalogHierarchy()
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Sony Bravia 55 Inch OLED',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
        ]);
    }
}
