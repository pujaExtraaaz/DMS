<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Organization\Models\Branch;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductNameCapitalizationTest extends TestCase
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
            'name' => 'OmniCore',
            'code' => 'OMNI',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Electronics',
            'code' => 'ELEC',
            'is_active' => true,
        ]);
    }

    public function test_product_model_accessor_and_mutator_standardize_capitalization(): void
    {
        $cases = [
            'apple iphone 18' => 'Apple iphone 18',
            'apple iphone 18 pro' => 'Apple iphone 18 pro',
            'assorted Biscuits' => 'Assorted Biscuits',
            'chips' => 'Chips',
            'premium Basmati Rice 25kg' => 'Premium Basmati Rice 25kg',
            'SONY FW-43EZ20L' => 'SONY FW-43EZ20L',
            'Widget X' => 'Widget X',
            '  chips  ' => 'Chips',
        ];

        foreach ($cases as $input => $expected) {
            $product = new Product(['name' => $input]);
            $this->assertSame($expected, $product->name, "Failed for input: {$input}");
            $this->assertSame($expected, $product->toArray()['name'], "Failed toArray for input: {$input}");
        }
    }

    public function test_product_store_normalizes_name_to_start_with_capital_letter(): void
    {
        $payload = [
            'name' => 'apple iphone 18 pro',
            'sku' => 'IPHONE-18-PRO',
            'base_uom_id' => $this->uom->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'purchase_price' => 100000,
            'selling_price' => 120000,
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertRedirect(route('masters.products.index'));

        // Check raw database storage
        $stored = DB::table('products')->where('sku', 'IPHONE-18-PRO')->first();
        $this->assertNotNull($stored);
        $this->assertSame('Apple iphone 18 pro', $stored->name);

        // Check Eloquent model
        $product = Product::where('sku', 'IPHONE-18-PRO')->first();
        $this->assertSame('Apple iphone 18 pro', $product->name);
    }

    public function test_product_update_normalizes_name_to_start_with_capital_letter(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Old Product Name',
            'sku' => 'PROD-UPD-01',
            'serial_no' => 'SN-UPD-01',
            'base_uom_id' => $this->uom->id,
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'premium Basmati Rice 25kg',
            'sku' => 'PROD-UPD-01',
            'base_uom_id' => $this->uom->id,
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), $payload);
        $response->assertRedirect(route('masters.products.index'));

        $stored = DB::table('products')->where('id', $product->id)->first();
        $this->assertSame('Premium Basmati Rice 25kg', $stored->name);
    }

    public function test_quick_add_product_normalizes_name_to_start_with_capital_letter(): void
    {
        $payload = [
            'name' => 'chips',
            'sku' => 'CHIPS-01',
            'base_uom_id' => $this->uom->id,
        ];

        $response = $this->actingAs($this->user)->postJson(route('masters.products.quick-add'), $payload);
        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('product.name', 'Chips');

        $stored = DB::table('products')->where('sku', 'CHIPS-01')->first();
        $this->assertSame('Chips', $stored->name);
    }

    public function test_product_list_displays_standardized_capitalized_name(): void
    {
        // Force raw lowercase insert into database to test display normalization
        $id = DB::table('products')->insertGetId([
            'company_id' => $this->company->id,
            'name' => 'apple iphone 18',
            'sku' => 'IPHONE-18-RAW',
            'serial_no' => 'SN-RAW-18',
            'base_uom_id' => $this->uom->id,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.products.index'));
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Apple iphone 18', $content);
        $this->assertStringNotContainsString('>apple iphone 18<', $content);
    }

    public function test_case_insensitive_product_search(): void
    {
        Product::create([
            'company_id' => $this->company->id,
            'name' => 'apple iphone 18',
            'sku' => 'IPHONE-SEARCH',
            'serial_no' => 'SN-SEARCH-18',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        foreach (['apple', 'Apple', 'APPLE'] as $searchTerm) {
            $response = $this->actingAs($this->user)->get(route('masters.products.index', ['search' => $searchTerm]));
            $response->assertStatus(200);
            $response->assertSee('Apple iphone 18');
        }
    }

    public function test_purchase_order_dropdown_displays_standardized_capitalized_name(): void
    {
        Product::create([
            'company_id' => $this->company->id,
            'name' => 'apple iphone 18',
            'sku' => 'IPHONE-PO-TEST',
            'serial_no' => 'SN-PO-18',
            'brand_id' => $this->brand->id,
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.orders.create'));
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Apple iphone 18', $content);
        $this->assertStringNotContainsString('"name":"apple iphone 18"', $content);
        $this->assertStringContainsString('"name":"Apple iphone 18"', $content);
    }
}

