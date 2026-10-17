<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Imports\PriceMasterImport;
use App\Domains\Master\Imports\ProductsImport;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductPartNumberTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Uom $uom;
    protected Uom $boxUom;
    protected Brand $brand;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Alpha Corp',
            'code' => 'ALP-01',
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

        $this->uom = Uom::firstOrCreate(['code' => 'PCS'], [
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'is_active' => true,
        ]);

        $this->boxUom = Uom::firstOrCreate(['code' => 'BOX'], [
            'company_id' => $this->company->id,
            'name' => 'Box',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Brand Test',
            'code' => 'BR-01',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'company_id' => $this->company->id,
            'name' => 'Electronics',
            'code' => 'CAT-01',
            'is_active' => true,
        ]);
    }

    protected function createProduct(array $overrides = []): Product
    {
        static $counter = 100;
        $counter++;

        return Product::create(array_merge([
            'company_id' => $this->company->id,
            'name' => "Product {$counter}",
            'sku' => "PN-{$counter}",
            'serial_no' => "SN-{$counter}",
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'tax_rate' => 18,
            'is_active' => true,
        ], $overrides));
    }

    public function test_create_product_form_displays_part_number_label_and_placeholder(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.create'));

        $response->assertOk();
        $response->assertSee('Part Number');
        $response->assertSee('placeholder="Enter Part Number"', false);
        $response->assertSee('name="sku"', false);
    }

    public function test_edit_product_form_displays_part_number_label_and_value(): void
    {
        $product = $this->createProduct([
            'name' => 'Smart Display 55',
            'sku' => 'PN-SD-55-4K',
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.products.edit', $product));

        $response->assertOk();
        $response->assertSee('Part Number');
        $response->assertSee('PN-SD-55-4K');
        $response->assertSee('placeholder="Enter Part Number"', false);
    }

    public function test_products_listing_displays_part_number_table_header_and_search_placeholder(): void
    {
        $this->createProduct([
            'name' => 'Audio Mixer Pro',
            'sku' => 'PN-AMP-2026',
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.products.index'));

        $response->assertOk();
        $response->assertSee('Part Number');
        $response->assertSee('Search Name, Part Number, Serial No, Model...');
        $response->assertSee('PN-AMP-2026');
        $response->assertSee('Part Number:'); // In the delete confirmation modal
    }

    public function test_product_validation_errors_use_part_number_terminology(): void
    {
        // 1. Missing part number
        $responseMissing = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'New Product Missing Part Number',
            'sku' => '',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
        ]);

        $responseMissing->assertSessionHasErrors(['sku']);
        $errors = session('errors')->get('sku');
        $this->assertStringContainsString('part number', strtolower($errors[0]));

        // 2. Duplicate part number
        $this->createProduct([
            'name' => 'Existing Product',
            'sku' => 'PN-DUPLICATE-01',
        ]);

        $responseDuplicate = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Another Product',
            'sku' => 'PN-DUPLICATE-01',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
        ]);

        $responseDuplicate->assertSessionHasErrors(['sku']);
        $dupErrors = session('errors')->get('sku');
        $this->assertStringContainsString('part number', strtolower($dupErrors[0]));
    }

    public function test_product_can_be_created_using_part_number_input(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Wireless Headphones',
            'part_number' => 'PN-WH-1000XM5',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('masters.products.index'));
        $this->assertDatabaseHas('products', [
            'sku' => 'PN-WH-1000XM5',
        ]);
    }

    public function test_bulk_import_csv_templates_contain_part_number(): void
    {
        $productTemplate = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'products'));
        $productTemplate->assertOk();
        $this->assertStringContainsString('part_number', $productTemplate->streamedContent());

        $priceMasterTemplate = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'price-master'));
        $priceMasterTemplate->assertOk();
        $this->assertStringContainsString('part_number', $priceMasterTemplate->streamedContent());
    }

    public function test_bulk_import_supports_part_number_and_legacy_sku(): void
    {
        // 1. Test ProductsImport with part_number
        $import = new ProductsImport($this->company->id);
        $import->collection(collect([
            [
                'name' => 'Imported Widget One',
                'part_number' => 'PN-IMP-001',
                'uom_code' => 'PCS',
                'brand' => 'Brand Test',
                'category' => 'Electronics',
            ],
            [
                'name' => 'Imported Widget Two Legacy',
                'sku' => 'PN-LEGACY-002',
                'uom_code' => 'PCS',
                'brand' => 'Brand Test',
                'category' => 'Electronics',
            ],
        ]));

        $this->assertDatabaseHas('products', ['sku' => 'PN-IMP-001']);
        $this->assertDatabaseHas('products', ['sku' => 'PN-LEGACY-002']);

        // 2. Test PriceMasterImport with part_number and legacy sku
        CustomerType::firstOrCreate(['name' => 'RETAIL'], ['code' => 'RET', 'is_active' => true]);

        $priceImport = new PriceMasterImport($this->company->id);
        $priceImport->collection(collect([
            [
                'customer_type' => 'Retail',
                'part_number' => 'PN-IMP-001',
                'uom_code' => 'PCS',
                'rate' => 250.00,
                'min_qty' => 5,
            ],
            [
                'customer_type' => 'Retail',
                'sku' => 'PN-LEGACY-002',
                'uom_code' => 'PCS',
                'rate' => 350.00,
                'min_qty' => 10,
            ],
        ]));

        $this->assertSame(0, $priceImport->skipped);
        $this->assertDatabaseHas('price_masters', [
            'rate' => 250.00,
            'min_qty' => 5,
        ]);
        $this->assertDatabaseHas('price_masters', [
            'rate' => 350.00,
            'min_qty' => 10,
        ]);
    }
}
