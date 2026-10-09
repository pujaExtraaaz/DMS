<?php

namespace Tests\Feature\Common;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DynamicSearchAndSortTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->company = Company::create([
            'name' => 'Test Apex Retailers',
            'code' => 'TAR-01',
            'is_active' => true,
        ]);

        $this->user->update(['company_id' => $this->company->id]);
    }

    public function test_brands_listing_defaults_to_alphabetical_a_to_z(): void
    {
        // Create brands out of order
        Brand::create(['name' => 'Zebra Technologies', 'code' => 'BR-001', 'is_active' => true, 'company_id' => $this->company->id]);
        Brand::create(['name' => 'Apple Inc', 'code' => 'BR-002', 'is_active' => true, 'company_id' => $this->company->id]);
        Brand::create(['name' => 'Microsoft Corp', 'code' => 'BR-003', 'is_active' => true, 'company_id' => $this->company->id]);

        $response = $this->actingAs($this->user)->get(route('masters.brands.index'));

        $response->assertOk();
        $items = $response->viewData('items');

        $this->assertEquals('Apple Inc', $items->first()->name);
        $this->assertEquals('Zebra Technologies', $items->last()->name);
    }

    public function test_brands_search_filters_results_case_insensitively(): void
    {
        Brand::create(['name' => 'Logitech Global', 'code' => 'BR-001', 'is_active' => true, 'company_id' => $this->company->id]);
        Brand::create(['name' => 'Samsung Electronics', 'code' => 'BR-002', 'is_active' => true, 'company_id' => $this->company->id]);

        $response = $this->actingAs($this->user)->get(route('masters.brands.index', ['search' => 'logi']));

        $response->assertOk();
        $items = $response->viewData('items');

        $this->assertCount(1, $items);
        $this->assertEquals('Logitech Global', $items->first()->name);
    }

    public function test_brands_sorting_toggles_order_to_descending(): void
    {
        Brand::create(['name' => 'Alpha Brand', 'code' => 'BR-001', 'is_active' => true, 'company_id' => $this->company->id]);
        Brand::create(['name' => 'Omega Brand', 'code' => 'BR-002', 'is_active' => true, 'company_id' => $this->company->id]);

        $response = $this->actingAs($this->user)->get(route('masters.brands.index', ['sort' => 'name', 'direction' => 'desc']));

        $response->assertOk();
        $items = $response->viewData('items');

        $this->assertEquals('Omega Brand', $items->first()->name);
        $this->assertEquals('Alpha Brand', $items->last()->name);
    }

    public function test_categories_listing_defaults_to_alphabetical_a_to_z(): void
    {
        Category::create(['name' => 'Stationery', 'code' => 'CAT-01', 'is_active' => true, 'company_id' => $this->company->id]);
        Category::create(['name' => 'Beverages', 'code' => 'CAT-02', 'is_active' => true, 'company_id' => $this->company->id]);
        Category::create(['name' => 'Apparel', 'code' => 'CAT-03', 'is_active' => true, 'company_id' => $this->company->id]);

        $response = $this->actingAs($this->user)->get(route('masters.categories.index'));

        $response->assertOk();
        $items = $response->viewData('items');

        $this->assertEquals('Apparel', $items->first()->name);
        $this->assertEquals('Stationery', $items->last()->name);
    }

    public function test_customers_listing_defaults_to_alphabetical_a_to_z(): void
    {
        $type = CustomerType::create(['name' => 'Retail', 'code' => 'RET-01']);

        Customer::create([
            'name' => 'Zeno Traders',
            'code' => 'CUST-001',
            'customer_type_id' => $type->id,
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
            'company_id' => $this->company->id,
        ]);
        Customer::create([
            'name' => 'Anand Enterprises',
            'code' => 'CUST-002',
            'customer_type_id' => $type->id,
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.customers.index'));

        $response->assertOk();
        $customers = $response->viewData('items');

        $this->assertEquals('Anand Enterprises', $customers->first()->name);
        $this->assertEquals('Zeno Traders', $customers->last()->name);
    }

    public function test_products_listing_search_and_sorting(): void
    {
        $uom = Uom::create(['name' => 'Pieces', 'code' => 'PCS', 'is_active' => true]);

        Product::create([
            'name' => 'Wireless Keyboard',
            'sku' => 'PRD-WK-01',
            'serial_no' => 'SN-001',
            'base_uom_id' => $uom->id,
            'purchase_price' => 500,
            'selling_price' => 750,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Bluetooth Mouse',
            'sku' => 'PRD-BM-01',
            'serial_no' => 'SN-002',
            'base_uom_id' => $uom->id,
            'purchase_price' => 250,
            'selling_price' => 400,
            'is_active' => true,
        ]);

        // Default sort is A-Z
        $response = $this->actingAs($this->user)->get(route('masters.products.index'));
        $response->assertOk();
        $products = $response->viewData('items');
        $this->assertEquals('Bluetooth Mouse', $products->first()->name);
        $this->assertEquals('Wireless Keyboard', $products->last()->name);

        // Search by sku
        $searchResponse = $this->actingAs($this->user)->get(route('masters.products.index', ['search' => 'PRD-WK']));
        $searchResponse->assertOk();
        $searchProducts = $searchResponse->viewData('items');
        $this->assertCount(1, $searchProducts);
        $this->assertEquals('Wireless Keyboard', $searchProducts->first()->name);
    }
}
