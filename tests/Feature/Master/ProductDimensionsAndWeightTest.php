<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductDimensionsAndWeightTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Uom $baseUom;
    protected Uom $altUom;
    protected Brand $brand;
    protected Category $category;
    protected ProductType $productType;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'ElectroTech Industries',
            'code' => 'ETI-01',
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

        $this->altUom = Uom::firstOrCreate(['code' => 'BOX'], [
            'company_id' => $this->company->id,
            'name' => 'Box',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Dell',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Monitors',
            'code' => 'MON',
            'is_active' => true,
        ]);

        $this->productType = ProductType::firstOrCreate(['name' => 'FINISHED GOODS'], [
            'company_id' => $this->company->id,
            'code' => 'FG',
            'is_active' => true,
        ]);
    }

    protected function createProduct(array $overrides = []): Product
    {
        static $counter = 500;
        $counter++;

        return Product::create(array_merge([
            'company_id' => $this->company->id,
            'name' => "Product {$counter}",
            'sku' => "SKU-DIM-{$counter}",
            'serial_no' => "SN-DIM-{$counter}",
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'is_active' => true,
        ], $overrides));
    }

    public function test_create_product_form_renders_dimensions_and_weight_fields(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.create'));

        $response->assertOk();
        $response->assertSee('Product Dimensions &amp; Weight', false);
        $response->assertSee('name="has_dimensions"', false);
        $response->assertSee('name="height_cm"', false);
        $response->assertSee('name="height_in"', false);
        $response->assertSee('name="length_cm"', false);
        $response->assertSee('name="length_in"', false);
        $response->assertSee('name="width_cm"', false);
        $response->assertSee('name="width_in"', false);
        $response->assertSee('name="net_weight"', false);
        $response->assertSee('name="weight_unit"', false);
        $response->assertSee('productDimensionsManager', false);
    }

    public function test_edit_product_form_renders_dimensions_and_weight_prefilled(): void
    {
        $product = $this->createProduct([
            'name' => 'Dell Ultrasharp U2723QE',
            'sku' => 'U2723QE',
            'height_cm' => 68.00,
            'height_in' => 26.77,
            'length_cm' => 107.00,
            'length_in' => 42.13,
            'width_cm' => 14.00,
            'width_in' => 5.51,
            'net_weight' => 2.500,
            'weight_unit' => 'KG',
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.products.edit', $product));

        $response->assertOk();
        $response->assertSee('Product Dimensions &amp; Weight', false);
        $response->assertSee('68.00');
        $response->assertSee('26.77');
        $response->assertSee('107.00');
        $response->assertSee('42.13');
        $response->assertSee('14.00');
        $response->assertSee('5.51');
        $response->assertSee('2.500');
        $response->assertSee('KG');
    }

    public function test_can_create_product_with_cm_dimensions_and_net_weight_auto_calculating_inches(): void
    {
        $sku = 'PRD-DIM-CM-01';
        $payload = [
            'name' => 'Industrial Display 32 Inch',
            'sku' => $sku,
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'has_dimensions' => 1,
            'height_cm' => 68.00,
            'length_cm' => 107.00,
            'width_cm' => 14.00,
            'net_weight' => 4.750,
            'weight_unit' => 'KG',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', $sku)->first();
        $this->assertNotNull($product);

        $this->assertEquals(68.00, (float) $product->height_cm);
        $this->assertEquals(26.77, (float) $product->height_in); // 68 / 2.54 = 26.77

        $this->assertEquals(107.00, (float) $product->length_cm);
        $this->assertEquals(42.13, (float) $product->length_in); // 107 / 2.54 = 42.13

        $this->assertEquals(14.00, (float) $product->width_cm);
        $this->assertEquals(5.51, (float) $product->width_in); // 14 / 2.54 = 5.51

        $this->assertEquals(4.750, (float) $product->net_weight);
        $this->assertSame('KG', $product->weight_unit);
    }

    public function test_can_create_product_with_inches_dimensions_auto_calculating_cm(): void
    {
        $sku = 'PRD-DIM-IN-02';
        $payload = [
            'name' => 'Compact Server Chassis',
            'sku' => $sku,
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'has_dimensions' => 1,
            'height_in' => 26.77,
            'length_in' => 42.13,
            'width_in' => 5.51,
            'net_weight' => 850.00,
            'weight_unit' => 'Grams',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', $sku)->first();
        $this->assertNotNull($product);

        $this->assertEquals(68.00, (float) $product->height_cm); // 26.77 * 2.54 = 67.9958 -> 68.00
        $this->assertEquals(26.77, (float) $product->height_in);

        $this->assertEquals(107.01, (float) $product->length_cm); // 42.13 * 2.54 = 107.0102 -> 107.01
        $this->assertEquals(42.13, (float) $product->length_in);

        $this->assertEquals(14.00, (float) $product->width_cm); // 5.51 * 2.54 = 13.9954 -> 14.00
        $this->assertEquals(5.51, (float) $product->width_in);

        $this->assertEquals(850.000, (float) $product->net_weight);
        $this->assertSame('Grams', $product->weight_unit);
    }

    public function test_dimensions_and_net_weight_are_required_for_form_submissions(): void
    {
        $payload = [
            'name' => 'Product Missing Dimensions',
            'sku' => 'PRD-ERR-DIM-01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'has_dimensions' => 1,
            // height, length, width, net_weight left empty
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertSessionHasErrors(['height_cm', 'length_cm', 'width_cm', 'net_weight']);
    }

    public function test_dimensions_can_be_marked_not_applicable(): void
    {
        $payload = [
            'name' => 'Product Without Dimensions',
            'sku' => 'PRD-NA-DIM-01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'has_dimensions' => 1,
            'dimensions_not_applicable' => 1,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'PRD-NA-DIM-01')->first();
        $this->assertNotNull($product);
        $this->assertNull($product->height_cm);
        $this->assertNull($product->length_cm);
        $this->assertNull($product->width_cm);
        $this->assertNull($product->net_weight);
    }

    public function test_dimensions_and_net_weight_must_be_greater_than_zero(): void
    {
        $payload = [
            'name' => 'Product Non Positive Dimensions',
            'sku' => 'PRD-ERR-DIM-02',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'has_dimensions' => 1,
            'height_cm' => 0,
            'length_cm' => -10,
            'width_cm' => 0,
            'net_weight' => -2.5,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertSessionHasErrors(['height_cm', 'length_cm', 'width_cm', 'net_weight']);
    }

    public function test_can_update_product_dimensions_and_weight(): void
    {
        $product = $this->createProduct([
            'name' => 'Adjustable Stand',
            'sku' => 'ADJ-STND-01',
            'height_cm' => 30.00,
            'height_in' => 11.81,
            'length_cm' => 40.00,
            'length_in' => 15.75,
            'width_cm' => 20.00,
            'width_in' => 7.87,
            'net_weight' => 1.200,
            'weight_unit' => 'KG',
        ]);

        $updatePayload = [
            'name' => 'Adjustable Stand Pro',
            'sku' => 'ADJ-STND-01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'has_dimensions' => 1,
            'height_cm' => 50.00,
            'length_cm' => 60.00,
            'width_cm' => 25.00,
            'net_weight' => 2.100,
            'weight_unit' => 'KG',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), $updatePayload);
        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertEquals(50.00, (float) $product->height_cm);
        $this->assertEquals(19.69, (float) $product->height_in); // 50 / 2.54 = 19.685 -> 19.69
        $this->assertEquals(60.00, (float) $product->length_cm);
        $this->assertEquals(23.62, (float) $product->length_in); // 60 / 2.54 = 23.622 -> 23.62
        $this->assertEquals(25.00, (float) $product->width_cm);
        $this->assertEquals(9.84, (float) $product->width_in); // 25 / 2.54 = 9.842 -> 9.84
        $this->assertEquals(2.100, (float) $product->net_weight);
    }

    public function test_legacy_programmatic_product_creation_and_update_without_dimensions_remains_supported(): void
    {
        $payload = [
            'name' => 'Programmatic Service Product',
            'sku' => 'PROG-SVC-01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'PROG-SVC-01')->first();
        $this->assertNotNull($product);
        $this->assertNull($product->height_cm);
        $this->assertNull($product->net_weight);

        // Access edit form without dimensions
        $editResponse = $this->actingAs($this->user)->get(route('masters.products.edit', $product));
        $editResponse->assertOk();
    }
}

