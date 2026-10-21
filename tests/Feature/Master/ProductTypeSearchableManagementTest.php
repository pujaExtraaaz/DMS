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

class ProductTypeSearchableManagementTest extends TestCase
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
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'company_id' => $this->company->id,
            'name' => 'Display Systems',
            'code' => 'CAT-DISP',
            'is_active' => true,
        ]);
    }

    public function test_can_fetch_product_types_list(): void
    {
        ProductType::firstOrCreate(['name' => 'FINISHED GOODS'], ['code' => 'FG', 'is_active' => true]);
        ProductType::firstOrCreate(['name' => 'HARDWARE'], ['code' => 'HW', 'is_active' => true]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.product-types.list'));

        $response->assertOk()
            ->assertJsonStructure([
                'items' => [
                    '*' => ['id', 'name', 'code'],
                ],
            ]);

        $names = collect($response->json('items'))->pluck('name')->all();
        $this->assertTrue(in_array('Finished Goods', $names) || in_array('FINISHED GOODS', $names));
        $this->assertTrue(in_array('Hardware', $names) || in_array('HARDWARE', $names));
    }

    public function test_can_quick_store_new_product_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('masters.product-types.quick-store'), [
                'name' => 'Raw Materials',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Product Type created successfully.',
            ])
            ->assertJsonStructure([
                'item' => ['id', 'name', 'code'],
            ]);

        $this->assertDatabaseHas('product_types', [
            'name' => 'Raw Materials',
        ]);
    }

    public function test_quick_store_validates_required_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('masters.product-types.quick-store'), [
                'name' => '   ',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Product Type Name is required.',
            ])
            ->assertJsonValidationErrors(['name']);
    }

    public function test_quick_store_prevents_duplicate_product_type_case_insensitive_and_trimmed(): void
    {
        ProductType::firstOrCreate(['name' => 'FINISHED GOODS'], ['code' => 'FG', 'is_active' => true]);

        // Same name with mixed case and extra surrounding whitespace
        $response = $this->actingAs($this->user)
            ->postJson(route('masters.product-types.quick-store'), [
                'name' => '   finished goods   ',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'A product type with this name already exists.',
            ])
            ->assertJsonValidationErrors(['name']);
    }

    public function test_product_create_form_does_not_render_product_type_dropdown_or_add_button(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('masters.products.create'));

        $response->assertOk();
        $response->assertDontSee('+ Add Product Type');
        $response->assertDontSee('productTypeManager');
        $response->assertDontSee('product_type_search_input');
    }

    public function test_can_create_product_with_selected_product_type(): void
    {
        $type = ProductType::firstOrCreate(['name' => 'FINISHED GOODS'], ['code' => 'FG', 'is_active' => true]);

        $payload = [
            'name' => 'Bravia 65 4K Screen',
            'sku' => 'PART-BRV-65',
            'model' => 'KD-65X75L',
            'product_type_id' => $type->id,
            'product_type' => $type->name,
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'purchase_price' => 45000,
            'selling_price' => 60000,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('masters.products.store'), $payload);

        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'PART-BRV-65')->first();
        $this->assertNotNull($product);
        $this->assertEquals($type->id, $product->product_type_id);
        $this->assertEquals('Finished Goods', $product->product_type);
    }

    public function test_can_update_product_type(): void
    {
        $type1 = ProductType::firstOrCreate(['name' => 'Finished Goods'], ['code' => 'FG', 'is_active' => true]);
        $type2 = ProductType::firstOrCreate(['name' => 'Spare Part'], ['code' => 'SP', 'is_active' => true]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Camera Sensor Module',
            'sku' => 'PART-SNSR-01',
            'serial_no' => 'PRD-SN-001',
            'product_type_id' => $type1->id,
            'product_type' => $type1->name,
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('masters.products.update', $product), [
                'name' => 'Camera Sensor Module',
                'sku' => 'PART-SNSR-01',
                'product_type_id' => $type2->id,
                'product_type' => $type2->name,
                'base_uom_id' => $this->uom->id,
                'alternate_uom_id' => $this->boxUom->id,
                'conversion_factor' => 10,
                'brand_id' => $this->brand->id,
                'category_id' => $this->category->id,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertSame($type2->id, (int) $product->product_type_id);
        $this->assertEquals('Spare Part', $product->product_type);
    }

    public function test_product_edit_form_prefills_selected_product_type(): void
    {
        $type = ProductType::firstOrCreate(['name' => 'Hardware'], ['code' => 'HW', 'is_active' => true]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Mounting Bracket',
            'sku' => 'PART-BRKT-01',
            'serial_no' => 'PRD-SN-002',
            'product_type_id' => $type->id,
            'product_type' => $type->name,
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('masters.products.edit', $product));

        $response->assertOk();
        $response->assertSee((string) $type->id);
        $response->assertSee('Hardware');
    }

    public function test_product_type_is_optional(): void
    {
        $payload = [
            'name' => 'Product Without Type',
            'sku' => 'PART-NO-TYPE',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('masters.products.store'), $payload);

        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'PART-NO-TYPE')->first();
        $this->assertNotNull($product);
        $this->assertNull($product->product_type_id);
        $this->assertNull($product->product_type);
    }
}
