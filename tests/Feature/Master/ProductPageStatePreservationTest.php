<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductPageStatePreservationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Brand $brand;
    protected Category $category;
    protected SubCategory $subCategory;
    protected ProductType $productType;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'State Preservation Corp',
            'code' => 'SPC-01',
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

        $branch = Branch::create([
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Warehouse',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'name' => 'Krystal',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Hardware',
            'brand_id' => $this->brand->id,
            'is_active' => true,
        ]);

        $this->subCategory = SubCategory::create([
            'name' => 'Fittings',
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $this->productType = ProductType::create([
            'name' => 'Finished Goods',
            'is_active' => true,
        ]);
    }

    public function test_product_listing_actions_include_return_url_and_preserve_state_attribute(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Krystal Heavy Hinge',
            'sku' => 'KHH-001',
            'serial_no' => 'SN-TEST-001',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
            'purchase_price' => 50,
            'selling_price' => 80,
            'tax_rate' => 18,
        ]);

        $url = route('masters.products.index', [
            'search' => 'Hinge',
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'page' => 1,
        ]);

        $response = $this->actingAs($this->user)->get($url);
        $response->assertOk();

        $content = $response->getContent();

        // 1. + Add Product button must have data-preserve-state and include return_url
        $this->assertStringContainsString('data-preserve-state', $content);
        $this->assertStringContainsString(route('masters.products.create'), $content);
        $this->assertStringContainsString('return_url=', $content);

        // 2. Edit button on product row must have return_url
        $this->assertStringContainsString(route('masters.products.edit', $product), $content);

        // 3. View button on product row must have return_url
        $this->assertStringContainsString(route('masters.products.show', $product), $content);
    }

    public function test_create_product_form_renders_return_url_and_back_label(): void
    {
        $returnUrl = '/masters/products?search=Krystal&brand_id=' . $this->brand->id . '&page=2';

        $response = $this->actingAs($this->user)->get(route('masters.products.create', [
            'return_url' => $returnUrl,
        ]));

        $response->assertOk();
        $content = $response->getContent();

        // Hidden input for return_url
        $this->assertStringContainsString('name="return_url" value="' . e($returnUrl) . '"', $content);

        // Back button pointing to return_url
        $this->assertStringContainsString('href="' . e($returnUrl) . '"', $content);
        $this->assertTrue(
            str_contains($content, 'Back To Products') || str_contains($content, 'Back to Products'),
            'Failed asserting that product form displays Back to Products.'
        );
    }

    public function test_saving_product_redirects_back_to_exact_filtered_listing_state(): void
    {
        $returnUrl = '/masters/products?search=Valve&brand_id=' . $this->brand->id . '&status=active&page=2';

        $payload = [
            'name' => 'Brass Gate Valve 1-inch',
            'sku' => 'BGV-001',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'is_active' => 1,
            'purchase_price' => 200,
            'selling_price' => 320,
            'tax_rate' => 18,
            'return_url' => $returnUrl,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);

        $product = Product::where('sku', 'BGV-001')->first();
        $this->assertNotNull($product);

        // Must redirect back to the exact returnUrl, preserving search, brand_id, status, and page
        $expectedRedirect = $returnUrl . '&new_product_id=' . $product->id;
        $response->assertRedirect($expectedRedirect);
        $response->assertSessionHas('status', "Product '{$product->name}' created successfully.");

        // Follow redirect to listing page
        $followResponse = $this->actingAs($this->user)->get($expectedRedirect);
        $followResponse->assertOk();
        $followResponse->assertSee($product->name);
    }

    public function test_edit_product_form_renders_return_url_and_back_button(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Existing Pipe Joint',
            'sku' => 'EPJ-001',
            'serial_no' => 'SN-TEST-002',
            'brand_id' => $this->brand->id,
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
            'purchase_price' => 40,
            'selling_price' => 60,
            'tax_rate' => 18,
        ]);

        $returnUrl = '/masters/products?brand_id=' . $this->brand->id . '&status=active&sort=name&direction=asc&page=3';

        $response = $this->actingAs($this->user)->get(route('masters.products.edit', [
            'product' => $product,
            'return_url' => $returnUrl,
        ]));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('name="return_url" value="' . e($returnUrl) . '"', $content);
        $this->assertStringContainsString('href="' . e($returnUrl) . '"', $content);
        $this->assertTrue(
            str_contains($content, 'Back To Products') || str_contains($content, 'Back to Products'),
            'Failed asserting that edit form displays Back to Products.'
        );
    }

    public function test_updating_product_redirects_back_to_exact_filtered_listing_state(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Steel Coupling 50mm',
            'sku' => 'SC-50',
            'serial_no' => 'SN-TEST-003',
            'brand_id' => $this->brand->id,
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
            'purchase_price' => 100,
            'selling_price' => 150,
            'tax_rate' => 18,
        ]);

        $returnUrl = '/masters/products?search=Steel&brand_id=' . $this->brand->id . '&status=active&page=2&per_page=15';

        $updatePayload = [
            'name' => 'Steel Coupling 50mm Heavy Duty',
            'sku' => 'SC-50',
            'brand_id' => $this->brand->id,
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'is_active' => 1,
            'purchase_price' => 110,
            'selling_price' => 165,
            'tax_rate' => 18,
            'return_url' => $returnUrl,
        ];

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), $updatePayload);

        $expectedRedirect = $returnUrl . '&updated_product_id=' . $product->id . '&returned_from=product_edit';
        $response->assertRedirect($expectedRedirect);
        $response->assertSessionHas('status', "Product 'Steel Coupling 50mm Heavy Duty' updated successfully.");

        $this->assertEquals('Steel Coupling 50mm Heavy Duty', $product->fresh()->name);

        // Follow redirect to listing page
        $followResponse = $this->actingAs($this->user)->get($expectedRedirect);
        $followResponse->assertOk();
        $followResponse->assertSee($product->fresh()->name);
        $followResponse->assertSee('Updated');
    }

    public function test_view_product_preserves_return_url_on_back_and_forwards_to_edit(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Copper Elbow 90 Deg',
            'sku' => 'CE-90',
            'serial_no' => 'SN-TEST-004',
            'brand_id' => $this->brand->id,
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
            'purchase_price' => 30,
            'selling_price' => 50,
            'tax_rate' => 18,
        ]);

        $returnUrl = '/masters/products?brand_id=' . $this->brand->id . '&page=2';

        $response = $this->actingAs($this->user)->get(route('masters.products.show', [
            'product' => $product,
            'return_url' => $returnUrl,
        ]));

        $response->assertOk();
        $content = $response->getContent();

        // Back button should lead to returnUrl
        $this->assertStringContainsString('href="' . e($returnUrl) . '"', $content);

        // Edit button should forward the return_url
        $expectedEditUrl = route('masters.products.edit', [
            'product' => $product,
            'return_url' => $returnUrl,
        ]);
        $this->assertStringContainsString(e($expectedEditUrl), $content);
    }

    public function test_open_redirect_is_prevented_and_malicious_urls_are_rejected(): void
    {
        $payload = [
            'name' => 'Safe Redirect Test Product',
            'sku' => 'SRTP-001',
            'brand_id' => $this->brand->id,
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'is_active' => 1,
            'purchase_price' => 10,
            'selling_price' => 20,
            'tax_rate' => 18,
            'return_url' => 'https://malicious-external-site.com/steal-creds',
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);

        // Must NOT redirect to malicious site. Must fall back to safe internal products index.
        $this->assertFalse(str_contains((string) $response->headers->get('Location'), 'malicious-external-site.com'));
        $response->assertRedirect(route('masters.products.index'));
    }

    public function test_validation_failure_retains_entered_inputs_and_return_url(): void
    {
        $returnUrl = '/masters/products?search=Testing&page=3';

        $invalidPayload = [
            'name' => '', // Required field missing
            'sku' => 'VALID-SKU',
            'return_url' => $returnUrl,
        ];

        $response = $this->actingAs($this->user)->from(route('masters.products.create', ['return_url' => $returnUrl]))
            ->post(route('masters.products.store'), $invalidPayload);

        $response->assertRedirect(route('masters.products.create', ['return_url' => $returnUrl]));
        $response->assertSessionHasErrors(['name']);
        $this->assertEquals($returnUrl, session('_old_input')['return_url'] ?? null);
        $this->assertEquals('VALID-SKU', session('_old_input')['sku'] ?? null);
    }
}
