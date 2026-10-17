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

class ProductRemoveLifespanAndDescriptionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Uom $baseUom;
    protected Uom $altUom;
    protected Brand $brand;
    protected Category $category;
    protected SubCategory $subCategory;
    protected ProductType $productType;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'AVIT DIGITAL',
            'code' => 'AD-01',
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
            'name' => 'Sony',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Displays',
            'code' => 'DISP',
            'is_active' => true,
        ]);

        $this->subCategory = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Professional Displays',
            'code' => 'PRO-DISP',
            'is_active' => true,
        ]);

        $this->productType = ProductType::firstOrCreate(['name' => 'FINISHED GOODS'], [
            'company_id' => $this->company->id,
            'code' => 'FG',
            'is_active' => true,
        ]);
    }

    public function test_create_product_form_does_not_render_lifespan_or_description_input_fields(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.create'));
        $response->assertOk();

        // Must not contain visible input or label for Lifespan
        $response->assertDontSee('Lifespan (days)', false);
        $response->assertDontSee('Lifespan (Days)', false);
        $response->assertDontSee('name="lifespan_days" type="number"', false);

        // Must not contain textarea for Description
        $response->assertDontSee('<textarea name="description"', false);
        $response->assertDontSee('name="description" rows', false);

        // Still contains Specification
        $response->assertSee('Specification');
        $response->assertSee('name="specification"', false);

        // Still contains Min Stock, Reorder Level, and Catalog Link
        $response->assertSee('Min Stock');
        $response->assertSee('Reorder Level');
        $response->assertSee('Catalog Link');
    }

    public function test_edit_product_form_does_not_render_lifespan_or_description_input_fields(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Sony Bravia Pro 55',
            'sku' => 'FW-55BZ40L',
            'serial_no' => 'PRD-SN-FW55',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'lifespan_days' => 1825,
            'description' => 'High brightness commercial display with 4K HDR',
            'specification' => '55 inch, 700 nits, Android TV OS',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.products.edit', $product));
        $response->assertOk();

        // No visible input or label for Lifespan
        $response->assertDontSee('Lifespan (days)', false);
        $response->assertDontSee('Lifespan (Days)', false);

        // No visible textarea for Description
        $response->assertDontSee('<textarea name="description"', false);

        // Specification remains visible with its prefilled content
        $response->assertSee('Specification');
        $response->assertSee('55 inch, 700 nits, Android TV OS');
    }

    public function test_can_create_product_without_lifespan_and_description(): void
    {
        $payload = [
            'name' => 'Sony Crystal LED Video Wall',
            'sku' => 'ZRD-CH12D',
            'model' => 'CH-Series',
            'product_type_id' => $this->productType->id,
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'specification' => '1.2mm pixel pitch, Deep black coating',
            'min_stock' => 5,
            'reorder_level' => 2,
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'ZRD-CH12D')->first();
        $this->assertNotNull($product);
        $this->assertSame('SONY CRYSTAL LED VIDEO WALL', $product->name);
        $this->assertSame('1.2mm pixel pitch, Deep black coating', $product->specification);
        $this->assertNull($product->lifespan_days);
    }

    public function test_editing_product_preserves_historical_lifespan_and_description(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Legacy Display Unit',
            'sku' => 'LDU-001',
            'serial_no' => 'PRD-SN-LDU01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'lifespan_days' => 1095,
            'description' => 'Original factory product description',
            'specification' => 'Original factory specs',
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Legacy Display Unit Updated',
            'sku' => 'LDU-001',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'specification' => 'Updated factory specs',
            'is_active' => 1,
            // Hidden fields submitted automatically by form
            'lifespan_days' => 1095,
            'description' => 'Original factory product description',
        ];

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), $payload);
        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertSame('LEGACY DISPLAY UNIT UPDATED', $product->name);
        $this->assertEquals(1095, (int) $product->lifespan_days);
        $this->assertSame('Original factory product description', $product->description);
        $this->assertSame('Updated factory specs', $product->specification);
    }
}

