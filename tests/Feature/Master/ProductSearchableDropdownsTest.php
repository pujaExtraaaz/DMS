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

class ProductSearchableDropdownsTest extends TestCase
{
    use RefreshDatabase, \Illuminate\Foundation\Testing\Concerns\InteractsWithViews;

    protected User $user;
    protected Company $company;
    protected Uom $baseUom;
    protected Uom $boxUom;
    protected Brand $sonyBrand;
    protected Brand $samsungBrand;
    protected Category $tvCategory;
    protected Category $monitorCategory;
    protected SubCategory $oledSubCategory;
    protected SubCategory $gamingMonitorSubCategory;
    protected ProductType $finishedGoodsType;

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

        $this->boxUom = Uom::firstOrCreate(['code' => 'BOX'], [
            'company_id' => $this->company->id,
            'name' => 'Box',
            'is_active' => true,
        ]);

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

        $this->tvCategory = Category::create([
            'brand_id' => $this->sonyBrand->id,
            'name' => 'Televisions',
            'code' => 'CAT-TV',
            'is_active' => true,
        ]);

        $this->monitorCategory = Category::create([
            'brand_id' => $this->samsungBrand->id,
            'name' => 'Monitors',
            'code' => 'CAT-MON',
            'is_active' => true,
        ]);

        $this->oledSubCategory = SubCategory::create([
            'category_id' => $this->tvCategory->id,
            'name' => 'OLED TVs',
            'code' => 'SUB-OLED',
            'is_active' => true,
        ]);

        $this->gamingMonitorSubCategory = SubCategory::create([
            'category_id' => $this->monitorCategory->id,
            'name' => 'Gaming Monitors',
            'code' => 'SUB-GM',
            'is_active' => true,
        ]);

        $this->finishedGoodsType = ProductType::firstOrCreate(['name' => 'FINISHED GOODS'], [
            'company_id' => $this->company->id,
            'code' => 'FG',
            'is_active' => true,
        ]);
    }

    public function test_create_product_page_renders_all_searchable_dropdowns(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('masters.products.create'));

        $response->assertOk();

        // 1. Product Type
        $response->assertSee('Product Type');
        $response->assertSee('productTypeManager');
        $response->assertSee('Select Product Type');

        // 2. Base UOM
        $response->assertSee('Base UOM');
        $response->assertSee('base_uom_id_search_input');
        $response->assertSee('Select Base UOM');

        // Alternative UOM
        $response->assertSee('Alternative UOM');
        $response->assertSee('alternate_uom_id_search_input');
        $response->assertSee('Select Alternative UOM');
        $response->assertSee('Conversion:');

        // 3. Brand
        $response->assertSee('Brand');
        $response->assertSee('brand_id_search_input');
        $response->assertSee('Select Brand');

        // 4. Category
        $response->assertSee('Category');
        $response->assertSee('category_id_search_input');
        $response->assertSee('Select Category');

        // 5. Sub Category
        $response->assertSee('Sub Category');
        $response->assertSee('sub_category_id_search_input');
        $response->assertSee('Select Sub Category');

        // 6. Tracking Type Search
        $response->assertSee('Search tracking options...');
        $response->assertSee('Select All');
        $response->assertSee('Serial-wise');
        $response->assertSee('Batch-wise');

        // Catalog Manager Script & Dependencies
        $response->assertSee('productCatalogManager');
        $response->assertSee('searchableUnitRow');
    }

    public function test_can_create_product_with_all_searchable_dropdown_values(): void
    {
        $payload = [
            'name' => 'Sony Bravia XR 65',
            'sku' => 'XR-65A80L',
            'model' => 'A80L',
            'product_type_id' => $this->finishedGoodsType->id,
            'product_type' => $this->finishedGoodsType->name,
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'brand_id' => $this->sonyBrand->id,
            'category_id' => $this->tvCategory->id,
            'sub_category_id' => $this->oledSubCategory->id,
            'tracking_type' => ['serial', 'batch'],
            'tax_rate' => 18,
            'purchase_price' => 120000,
            'selling_price' => 165000,
            'calculation_mrp' => 180000,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('masters.products.store'), $payload);

        $response->assertRedirect(route('masters.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'SONY BRAVIA XR 65',
            'sku' => 'XR-65A80L',
            'model' => 'A80L',
            'product_type_id' => $this->finishedGoodsType->id,
            'product_type' => 'FINISHED GOODS',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'brand_id' => $this->sonyBrand->id,
            'category_id' => $this->tvCategory->id,
            'sub_category_id' => $this->oledSubCategory->id,
        ]);
    }

    public function test_edit_product_page_prefills_all_dropdown_selections(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Samsung Odyssey G9',
            'sku' => 'SM-G95SC',
            'serial_no' => 'PRD-SN-G9',
            'model' => 'G95SC',
            'product_type_id' => $this->finishedGoodsType->id,
            'product_type' => 'FINISHED GOODS',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 5,
            'brand_id' => $this->samsungBrand->id,
            'category_id' => $this->monitorCategory->id,
            'sub_category_id' => $this->gamingMonitorSubCategory->id,
            'tracking_type' => 'serial',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('masters.products.edit', $product));

        $response->assertOk();

        // Check values are embedded in initial config for Alpine
        $response->assertSee((string) $this->baseUom->id);
        $response->assertSee((string) $this->boxUom->id);
        $response->assertSee((string) $this->samsungBrand->id);
        $response->assertSee((string) $this->monitorCategory->id);
        $response->assertSee((string) $this->gamingMonitorSubCategory->id);
        $response->assertSee('SAMSUNG');
        $response->assertSee('MONITORS');
        $response->assertSee('GAMING MONITORS');
    }

    public function test_can_update_product_dropdown_values_successfully(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Display Monitor',
            'sku' => 'DISP-MON-01',
            'serial_no' => 'PRD-SN-DM01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 5,
            'brand_id' => $this->sonyBrand->id,
            'category_id' => $this->tvCategory->id,
            'sub_category_id' => $this->oledSubCategory->id,
            'is_active' => true,
        ]);

        $updatePayload = [
            'name' => 'Display Monitor Updated',
            'sku' => 'DISP-MON-01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 12,
            'brand_id' => $this->samsungBrand->id,
            'category_id' => $this->monitorCategory->id,
            'sub_category_id' => $this->gamingMonitorSubCategory->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('masters.products.update', $product), $updatePayload);

        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertSame($this->baseUom->id, (int) $product->base_uom_id);
        $this->assertSame($this->boxUom->id, (int) $product->alternate_uom_id);
        $this->assertEquals(12, (float) $product->conversion_factor);
        $this->assertSame($this->samsungBrand->id, (int) $product->brand_id);
        $this->assertSame($this->monitorCategory->id, (int) $product->category_id);
        $this->assertSame($this->gamingMonitorSubCategory->id, (int) $product->sub_category_id);
    }

    public function test_reusable_searchable_select_blade_component_renders(): void
    {
        $options = [
            ['id' => 1, 'name' => 'Option One', 'code' => 'OPT1'],
            ['id' => 2, 'name' => 'Option Two', 'code' => 'OPT2'],
        ];

        $view = $this->blade('<x-ui.searchable-select name="test_field" label="Test Field" :options="$options" value="1" />', [
            'options' => $options,
        ]);

        $view->assertSee('Test Field');
        $view->assertSee('test_field');
        $view->assertSee('Option One');
        $view->assertSee('OPT1');
        $view->assertSee('dmsSearchableSelect');
    }
}
