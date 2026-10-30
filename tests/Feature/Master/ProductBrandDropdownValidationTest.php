<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductBrandDropdownValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Uom $uom;
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

        $this->brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Sony Electronics',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Televisions',
            'is_active' => true,
        ]);
    }

    public function test_create_product_page_does_not_have_inaccessible_required_brand_select(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.create'));

        $response->assertOk();
        $content = $response->getContent();

        // 1. catalog_category_brand should NOT have static unconditional 'required' attribute
        // that would cause "An invalid form control with name='' is not focusable"
        $this->assertStringNotContainsString(
            'id="catalog_category_brand" required',
            $content,
            'catalog_category_brand must not have static required attribute when modal is closed'
        );

        // 2. catalog_category_brand uses dynamic Alpine binding :required="categoryCreateOpen"
        $this->assertStringContainsString(':required="categoryCreateOpen"', $content);

        // 3. Brand searchable dropdown on Product form is present
        $this->assertStringContainsString('id="brand_id_search_input"', $content);
        $this->assertStringContainsString('name="brand_id"', $content);
    }

    public function test_product_creation_succeeds_with_brand_selected(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Bravia 65 OLED',
            'sku' => 'SKU-BRV-65',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'SKU-BRV-65')->first();
        $this->assertNotNull($product);
        $this->assertEquals($this->brand->id, $product->brand_id);
    }

    public function test_product_creation_succeeds_with_brand_missing(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Generic Unbranded Screen',
            'sku' => 'SKU-GEN-01',
            'brand_id' => null,
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'SKU-GEN-01')->first();
        $this->assertNotNull($product);
        $this->assertNull($product->brand_id);
    }

    public function test_quick_category_modal_validates_mandatory_brand(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('masters.categories.quick-store'), [
            'name' => 'Monitors Without Brand',
            'brand_id' => '',
            'is_active' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Please select a brand before creating a category.',
        ]);
    }
}

