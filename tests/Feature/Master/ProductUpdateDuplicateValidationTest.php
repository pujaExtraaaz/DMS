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

class ProductUpdateDuplicateValidationTest extends TestCase
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
            'name' => 'Acme Trading Co',
            'code' => 'ATC-01',
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

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'name' => 'Sony Electronics',
            'code' => 'SONY',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Televisions',
            'code' => 'TV',
            'brand_id' => $this->brand->id,
            'is_active' => true,
        ]);
    }

    /**
     * 1. Edit a product without changing its name -> Update should succeed.
     */
    public function test_edit_product_without_changing_name_succeeds(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Sony Bravia 55 OLED',
            'sku' => 'SNY-55-OLED',
            'serial_no' => 'SN-55-001',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->uom->id,
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), [
            'name' => 'Sony Bravia 55 OLED',
            'sku' => 'SNY-55-OLED',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertEquals('Sony Bravia 55 OLED', $product->name);
    }

    /**
     * 2. Edit a product and change its name to a unique name -> Update should succeed.
     */
    public function test_edit_product_and_change_name_to_unique_name_succeeds(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Sony Bravia 55 OLED',
            'sku' => 'SNY-55-OLED',
            'serial_no' => 'SN-55-002',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->uom->id,
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), [
            'name' => 'Sony Bravia 55 OLED 2026 Edition',
            'sku' => 'SNY-55-OLED',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertEquals('Sony Bravia 55 OLED 2026 Edition', $product->name);
    }

    /**
     * 3. Change its name to another existing product's name -> Show duplicate validation.
     */
    public function test_change_name_to_another_existing_product_name_shows_duplicate_validation(): void
    {
        $product1 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Sony Bravia 55 OLED',
            'sku' => 'SNY-55-01',
            'serial_no' => 'SN-55-010',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->uom->id,
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        $product2 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Sony Bravia 65 OLED',
            'sku' => 'SNY-65-01',
            'serial_no' => 'SN-65-010',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->uom->id,
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        // Attempt to rename product2 to product1's name (case-insensitive)
        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product2), [
            'name' => 'sony bravia 55 oled',
            'sku' => 'SNY-65-01',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors(['name']);
        $product2->refresh();
        $this->assertEquals('Sony Bravia 65 OLED', $product2->name);
    }

    /**
     * 4. Create a new product with an existing name -> Prevent creation.
     */
    public function test_create_new_product_with_existing_name_prevents_creation(): void
    {
        Product::create([
            'company_id' => $this->company->id,
            'name' => 'Sony Bravia 55 OLED',
            'sku' => 'SNY-55-EXIST',
            'serial_no' => 'SN-55-EXIST',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->uom->id,
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => '  Sony Bravia 55 OLED  ',
            'sku' => 'SNY-55-NEW',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseMissing('products', ['sku' => 'SNY-55-NEW']);
    }

    /**
     * 5. Update other product fields without changing the name -> All changes should save correctly.
     */
    public function test_update_other_product_fields_without_changing_name_saves_correctly(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Sony Bravia 55 OLED',
            'sku' => 'SNY-55-OLED',
            'serial_no' => 'SN-55-003',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->uom->id,
            'tax_rate' => 18,
            'selling_price' => 50000.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), [
            'name' => 'Sony Bravia 55 OLED',
            'sku' => 'SNY-55-OLED-UPDATED',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'tax_rate' => 28,
            'selling_price' => 55000.00,
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertEquals('Sony Bravia 55 OLED', $product->name);
        $this->assertEquals('SNY-55-OLED-UPDATED', $product->sku);
        $this->assertEquals(28, (int) $product->tax_rate);
        $this->assertEquals(55000.00, (float) $product->selling_price);
    }
}
