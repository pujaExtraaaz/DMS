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

class ProductWarrantyTest extends TestCase
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
            'name' => 'Laptops',
            'code' => 'LAP',
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
        static $counter = 100;
        $counter++;

        return Product::create(array_merge([
            'company_id' => $this->company->id,
            'name' => "Product {$counter}",
            'sku' => "SKU-{$counter}",
            'serial_no' => "SN-{$counter}",
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'is_active' => true,
        ], $overrides));
    }

    public function test_create_product_form_renders_warranty_field(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.create'));

        $response->assertOk();
        $response->assertSee('Warranty');
        $response->assertSee('name="warranty"', false);
        $response->assertSee('id="warranty"', false);
    }

    public function test_edit_product_form_renders_warranty_field_with_prefilled_value(): void
    {
        $product = $this->createProduct([
            'name' => 'Dell Latitude 7440',
            'sku' => 'LAT-7440',
            'warranty' => '3 Years ProSupport',
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.products.edit', $product));

        $response->assertOk();
        $response->assertSee('Warranty');
        $response->assertSee('name="warranty"', false);
        $response->assertSee('value="3 Years ProSupport"', false);
    }

    public function test_can_create_product_with_flexible_warranty_strings(): void
    {
        $warranties = ['6 Months', '1 Year', '2 Years', 'No Warranty', '3 Years On-site'];

        foreach ($warranties as $index => $warrantyText) {
            $sku = "PRD-WRN-{$index}";
            $payload = [
                'name' => "Product With Warranty {$index}",
                'sku' => $sku,
                'base_uom_id' => $this->baseUom->id,
                'alternate_uom_id' => $this->altUom->id,
                'conversion_factor' => 1,
                'warranty' => $warrantyText,
                'is_active' => true,
            ];

            $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
            $response->assertRedirect(route('masters.products.index'));

            $product = Product::where('sku', $sku)->first();
            $this->assertNotNull($product);
            $this->assertSame($warrantyText, $product->warranty);
        }
    }

    public function test_can_create_product_without_warranty_optional(): void
    {
        $payload = [
            'name' => 'Product Without Warranty',
            'sku' => 'PRD-NO-WRN',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'warranty' => null,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'PRD-NO-WRN')->first();
        $this->assertNotNull($product);
        $this->assertNull($product->warranty);
    }

    public function test_can_update_and_modify_warranty_on_existing_product(): void
    {
        $product = $this->createProduct([
            'name' => 'Dell XPS 15',
            'sku' => 'XPS-15-9530',
            'warranty' => '1 Year',
        ]);

        $updatePayload = [
            'name' => 'Dell XPS 15 Updated',
            'sku' => 'XPS-15-9530',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'warranty' => '3 Years Premium Support Plus',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), $updatePayload);
        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertSame('3 Years Premium Support Plus', $product->warranty);
    }

    public function test_can_clear_warranty_on_edit(): void
    {
        $product = $this->createProduct([
            'name' => 'Dell Monitor P2422H',
            'sku' => 'P2422H',
            'warranty' => '3 Years',
        ]);

        $clearPayload = [
            'name' => 'Dell Monitor P2422H',
            'sku' => 'P2422H',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'warranty' => '', // Cleared
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), $clearPayload);
        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertNull($product->warranty);
    }

    public function test_warranty_validates_maximum_length(): void
    {
        $longWarranty = str_repeat('A', 101);

        $payload = [
            'name' => 'Product Invalid Warranty',
            'sku' => 'PRD-INV-WRN',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'warranty' => $longWarranty,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertSessionHasErrors('warranty');
    }

    public function test_existing_products_without_warranty_continue_working_smoothly(): void
    {
        $product = $this->createProduct([
            'name' => 'Legacy Product Without Warranty',
            'sku' => 'LEG-NO-WRN',
        ]);

        // Access edit page
        $response = $this->actingAs($this->user)->get(route('masters.products.edit', $product));
        $response->assertOk();

        // Save without setting warranty
        $updatePayload = [
            'name' => 'Legacy Product Still Without Warranty',
            'sku' => 'LEG-NO-WRN',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'is_active' => true,
        ];

        $updateResponse = $this->actingAs($this->user)->put(route('masters.products.update', $product), $updatePayload);
        $updateResponse->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertNull($product->warranty);
        $this->assertSame('LEGACY PRODUCT STILL WITHOUT WARRANTY', $product->name);
    }
}
