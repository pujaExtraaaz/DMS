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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductCustomExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Brand $brandSony;
    protected Brand $brandApple;
    protected Category $categoryAudio;
    protected SubCategory $subCategoryHeadphones;
    protected ProductType $productTypeFinished;
    protected Uom $baseUomPieces;
    protected Uom $altUomBox;
    protected Product $productHeadphones;
    protected Product $productSpeaker;
    protected Product $productAppleAirpods;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);

        $this->company = Company::create([
            'name' => 'Global Electronics Ltd',
            'code' => 'GEL-01',
            'is_active' => true,
        ]);

        $this->brandSony = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Sony',
            'is_active' => true,
        ]);

        $this->brandApple = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Apple',
            'is_active' => true,
        ]);

        $this->categoryAudio = Category::create([
            'brand_id' => $this->brandSony->id,
            'name' => 'Audio & Sound',
            'is_active' => true,
        ]);

        $this->subCategoryHeadphones = SubCategory::create([
            'category_id' => $this->categoryAudio->id,
            'name' => 'Wireless Headphones',
            'is_active' => true,
        ]);

        $this->productTypeFinished = ProductType::create([
            'name' => 'Finished Goods',
            'is_active' => true,
        ]);

        $this->baseUomPieces = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->altUomBox = Uom::create([
            'name' => 'Box',
            'code' => 'BOX',
            'is_active' => true,
        ]);

        // Product 1: Sony WH-1000XM5
        $this->productHeadphones = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brandSony->id,
            'category_id' => $this->categoryAudio->id,
            'sub_category_id' => $this->subCategoryHeadphones->id,
            'product_type_id' => $this->productTypeFinished->id,
            'name' => 'WH-1000XM5 Noise Canceling',
            'sku' => 'SNY-WH-XM5',
            'series' => '1000X',
            'model' => 'XM5',
            'serial_no' => 'SNYXM50001',
            'hsn_code' => '85183000',
            'description' => 'Flagship Wireless Headphones',
            'specification' => '30hr Battery, Bluetooth 5.2',
            'warranty' => '1 Year Official',
            'base_uom_id' => $this->baseUomPieces->id,
            'alternate_uom_id' => $this->altUomBox->id,
            'conversion_factor' => 10,
            'tax_rate' => 18.00,
            'purchase_price' => 20000.00,
            'trade_price' => 24000.00,
            'selling_price' => 26990.00,
            'calculation_mrp' => 29990.00,
            'length_cm' => 20.0,
            'width_cm' => 18.0,
            'height_cm' => 8.0,
            'net_weight' => 0.25,
            'weight_unit' => 'kg',
            'costing_method' => 'fifo',
            'supply_type' => 'goods',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        // Product 2: Sony SRS-XB100 Speaker (Inactive)
        $this->productSpeaker = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brandSony->id,
            'category_id' => $this->categoryAudio->id,
            'sub_category_id' => $this->subCategoryHeadphones->id,
            'product_type_id' => $this->productTypeFinished->id,
            'name' => 'SRS-XB100 Wireless Speaker',
            'sku' => 'SNY-SRS-XB100',
            'model' => 'XB100',
            'serial_no' => 'SNYSPK0001',
            'base_uom_id' => $this->baseUomPieces->id,
            'tax_rate' => 18.00,
            'purchase_price' => 3500.00,
            'selling_price' => 4990.00,
            'calculation_mrp' => 5990.00,
            'is_active' => false,
            'is_hidden' => false,
        ]);

        // Product 3: Apple AirPods Pro (Different Brand)
        $this->productAppleAirpods = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brandApple->id,
            'name' => 'AirPods Pro 2',
            'sku' => 'APL-APP-2',
            'model' => 'Pro 2',
            'serial_no' => 'APLAPP0001',
            'base_uom_id' => $this->baseUomPieces->id,
            'tax_rate' => 18.00,
            'purchase_price' => 18000.00,
            'selling_price' => 24900.00,
            'calculation_mrp' => 24900.00,
            'is_active' => true,
            'is_hidden' => false,
        ]);
    }

    /**
     * Requirement A: The Products listing displays the Customize Export Columns trigger and modal.
     */
    public function test_products_listing_renders_customize_export_columns_elements(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('masters.products.index'));

        $response->assertOk();
        $response->assertSee('Customize Export Columns');
        $response->assertSee('Select All');
        $response->assertSee('Clear All');
        $response->assertSee('data-export-button', false);
        $response->assertSee('openExportModal()', false);
        $response->assertSee('closeExportModal()', false);
        $response->assertSee('submitExport()', false);
        $response->assertSee('Basic Details');
        $response->assertSee('Classification');
        $response->assertSee('Tax & Pricing');
        $response->assertSee('Inventory');
    }

    /**
     * Requirement B & C: Default export (without columns parameter) retains existing functionality.
     */
    public function test_default_export_without_custom_columns_returns_default_fields(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('masters.products.index', [
            'export' => 'csv',
        ]));

        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Product Name', $csv);
        $this->assertStringContainsString('Part Number', $csv);
        $this->assertStringContainsString('Model', $csv);
        $this->assertStringContainsString('Brand', $csv);
        $this->assertStringContainsString('Category', $csv);
        $this->assertStringContainsString('Base UOM', $csv);
        $this->assertStringContainsString($this->productHeadphones->name, $csv);
        $this->assertStringContainsString($this->productHeadphones->sku, $csv);
        $this->assertStringContainsString($this->brandSony->name, $csv);
    }

    /**
     * Requirement A & B: User can choose specific columns in a custom order.
     */
    public function test_export_includes_only_selected_columns_in_requested_order(): void
    {
        // User selects: Part Number, Selling Price, Brand, Product Name
        $response = $this->actingAs($this->adminUser)->get(route('masters.products.index', [
            'export' => 'csv',
            'columns' => 'sku,selling_price,brand,name',
        ]));

        $response->assertOk();
        $csv = $response->streamedContent();

        $lines = explode("\n", trim($csv));
        $headerLine = $lines[0];

        // Ensure headers only contain selected 4 columns
        $this->assertSame('"Part Number","Selling Price","Brand","Product Name"', $headerLine);

        // Ensure data rows follow exact column order
        $this->assertStringContainsString($this->productHeadphones->sku, $lines[1]);
        $this->assertStringContainsString('26990.00', $lines[1]);
        $this->assertStringContainsString($this->brandSony->name, $lines[1]);
        $this->assertStringContainsString($this->productHeadphones->name, $lines[1]);

        // Unselected columns must NOT be in the header
        $this->assertStringNotContainsString('Base UOM', $headerLine);
        $this->assertStringNotContainsString('GST Rate', $headerLine);
        $this->assertStringNotContainsString('HSN Code', $headerLine);
        $this->assertStringNotContainsString('Available Stock', $headerLine);
    }

    /**
     * Requirement C: Customized column export works for Excel (.xlsx).
     */
    public function test_customized_column_export_works_for_excel(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('masters.products.index', [
            'export' => 'xlsx',
            'columns' => 'name,sku,selling_price,mrp',
        ]));

        $response->assertOk();
        $this->assertTrue($response->headers->contains('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    /**
     * Requirement D: Respect currently applied filters (e.g. Brand filter) across entire dataset.
     */
    public function test_custom_export_respects_brand_and_search_filters(): void
    {
        // Filter by Sony brand
        $responseSony = $this->actingAs($this->adminUser)->get(route('masters.products.index', [
            'export' => 'csv',
            'brand_id' => $this->brandSony->id,
            'columns' => 'name,brand',
        ]));

        $responseSony->assertOk();
        $csvSony = $responseSony->streamedContent();

        $this->assertStringContainsString($this->productHeadphones->name, $csvSony);
        $this->assertStringContainsString($this->productSpeaker->name, $csvSony);
        $this->assertStringNotContainsString($this->productAppleAirpods->name, $csvSony);

        // Filter by search query "AirPods"
        $responseSearch = $this->actingAs($this->adminUser)->get(route('masters.products.index', [
            'export' => 'csv',
            'search' => 'AirPods',
            'columns' => 'name,brand',
        ]));

        $responseSearch->assertOk();
        $csvSearch = $responseSearch->streamedContent();

        $this->assertStringContainsString($this->productAppleAirpods->name, $csvSearch);
        $this->assertStringNotContainsString($this->productHeadphones->name, $csvSearch);
    }

    /**
     * Requirement D: Readable names for related models (Brand, Category, Subcategory, UOM).
     */
    public function test_export_displays_readable_names_for_related_records(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('masters.products.index', [
            'export' => 'csv',
            'columns' => 'name,brand,category,sub_category,product_type,base_uom,alternate_uom,supply_type',
        ]));

        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString($this->brandSony->name, $csv);
        $this->assertStringContainsString($this->categoryAudio->name, $csv);
        $this->assertStringContainsString($this->subCategoryHeadphones->name, $csv);
        $this->assertStringContainsString($this->productTypeFinished->name, $csv);
        $this->assertStringContainsString($this->baseUomPieces->name, $csv);
        $this->assertStringContainsString($this->altUomBox->name, $csv);
        $this->assertStringContainsString('Goods', $csv);
    }

    /**
     * Requirement B & D: Inventory columns (available stock, in-transit stock, total stock) compute correctly.
     */
    public function test_inventory_stock_columns_export_cleanly(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('masters.products.index', [
            'export' => 'csv',
            'columns' => 'name,sku,opening_stock,available_stock,in_transit_stock,total_stock',
        ]));

        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Opening Stock', $csv);
        $this->assertStringContainsString('Available Stock', $csv);
        $this->assertStringContainsString('In-Transit Stock', $csv);
        $this->assertStringContainsString('Total Stock', $csv);
        $this->assertStringContainsString('0.00', $csv);
    }

    /**
     * Requirement B: Empty or optional values handle gracefully without throwing errors.
     */
    public function test_empty_optional_fields_handle_gracefully(): void
    {
        // Apple AirPods has null category, null subcategory, null dimensions, null alternate UOM
        $response = $this->actingAs($this->adminUser)->get(route('masters.products.index', [
            'export' => 'csv',
            'columns' => 'name,category,sub_category,alternate_uom,dimension_cm,net_weight,warranty',
        ]));

        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString($this->productAppleAirpods->name, $csv);
    }
}
