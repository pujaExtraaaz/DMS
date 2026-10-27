<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Imports\ProductsImport;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductRemoveSeriesAndColorVariantTest extends TestCase
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
            'name' => 'Tech Master Corp',
            'code' => 'TMC-01',
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
            'name' => 'Sony Electronics',
            'code' => 'SNY-01',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'company_id' => $this->company->id,
            'name' => 'Displays',
            'code' => 'DSP-01',
            'is_active' => true,
        ]);
    }

    protected function createProduct(array $overrides = []): Product
    {
        static $counter = 200;
        $counter++;

        return Product::create(array_merge([
            'company_id' => $this->company->id,
            'name' => "Product {$counter}",
            'sku' => "PN-TEST-{$counter}",
            'serial_no' => "SN-TEST-{$counter}",
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'is_active' => true,
        ], $overrides));
    }

    public function test_create_product_form_does_not_contain_series_and_color_variant_fields(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.create'));

        $response->assertOk();
        $response->assertDontSee('name="series"', false);
        $response->assertDontSee('label="Series"', false);
        $response->assertDontSee('name="color_variant"', false);
        $response->assertDontSee('label="Color / Variant"', false);
    }

    public function test_edit_product_form_does_not_contain_series_and_color_variant_fields(): void
    {
        $product = $this->createProduct([
            'name' => 'Bravia Pro 65',
            'series' => 'Historical Series X',
            'color_variant' => 'Historical Matte Black',
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.products.edit', $product));

        $response->assertOk();
        $response->assertDontSee('name="series"', false);
        $response->assertDontSee('name="color_variant"', false);
    }

    public function test_can_create_product_without_series_and_color_variant(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Studio Monitor 27',
            'sku' => 'PN-SM-27',
            'model' => 'SM27-4K',
            'product_type' => 'Hardware',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'PN-SM-27')->first();
        $this->assertNotNull($product);
        $this->assertNull($product->series);
        $this->assertNull($product->color_variant);
        $this->assertSame('SM27-4K', $product->model);
        $this->assertSame('Hardware', $product->product_type);
    }

    public function test_editing_existing_product_preserves_historical_series_and_color_variant(): void
    {
        $product = $this->createProduct([
            'name' => 'Old Model Display',
            'sku' => 'PN-OLD-01',
            'series' => 'Legacy Series Alpha',
            'color_variant' => 'Space Grey',
            'tax_rate' => 18,
        ]);

        // Submit edit form WITHOUT series or color_variant (as they are no longer in the form)
        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), [
            'name' => 'Updated Model Display',
            'sku' => 'PN-OLD-01',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'tax_rate' => 28,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertSame(28.0, (float) $product->tax_rate);
        // Verify historical values were NOT wiped out
        $this->assertSame('Legacy Series Alpha', $product->series);
        $this->assertSame('Space Grey', $product->color_variant);
    }

    public function test_products_listing_does_not_display_series_column(): void
    {
        $this->createProduct([
            'name' => 'Cinema Projector 4K',
            'sku' => 'PN-CP-4K',
            'series' => 'Cinema Grand Series',
            'model' => 'CP-4000',
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.products.index'));

        $response->assertOk();
        // Should have Model column, but not Series column
        $response->assertSee('Models');
        $response->assertSee('CP-4000');
        $response->assertDontSee('<th class="px-4 py-3 text-left font-semibold text-slate-600">Series</th>', false);
        $response->assertDontSee('column="series"', false);
    }

    public function test_bulk_import_csv_template_excludes_color_variant(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'products'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringNotContainsString('color_variant', $content);
        $this->assertStringNotContainsString('Red', $content);
        $this->assertStringContainsString('part_number', $content);
    }

    public function test_bulk_import_handles_both_new_and_legacy_formats(): void
    {
        $import = new ProductsImport($this->company->id);

        // 1. Without series and color_variant (modern format)
        $import->collection(collect([
            [
                'name' => 'Modern Product Clean',
                'part_number' => 'PN-MOD-01',
                'uom_code' => 'PCS',
            ],
            // 2. With legacy series and color_variant columns
            [
                'name' => 'Legacy Product Columns',
                'part_number' => 'PN-LEG-01',
                'uom_code' => 'PCS',
                'series' => 'Legacy V Series',
                'color_variant' => 'Titanium Silver',
            ],
        ]));

        $modern = Product::where('sku', 'PN-MOD-01')->first();
        $this->assertNotNull($modern);
        $this->assertNull($modern->series);
        $this->assertNull($modern->color_variant);

        $legacy = Product::where('sku', 'PN-LEG-01')->first();
        $this->assertNotNull($legacy);
        $this->assertSame('Legacy V Series', $legacy->series);
        $this->assertSame('Titanium Silver', $legacy->color_variant);
    }
}

