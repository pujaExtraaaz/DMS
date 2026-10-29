<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PriceListCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $staffUser;
    protected Company $company;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'client-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'masters.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'masters.manage', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Test Company Pvt Ltd',
            'code' => 'TC01',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole('super-admin');

        $this->staffUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->staffUser->assignRole('staff');
        $this->staffUser->givePermissionTo(['masters.view', 'masters.manage']);

        $this->uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);
    }

    public function test_customize_view_button_is_removed_from_price_master_index(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.index'));

        $response->assertOk();
        $response->assertDontSee('Customize View');
        $response->assertDontSee('openCustomizeModal');
        $response->assertDontSee('showCustomizeModal');
        $response->assertDontSee('Customize Price List');
    }

    public function test_customization_modal_and_alpine_state_are_completely_removed(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.index'));

        $response->assertOk();
        $response->assertDontSee('customizationErrors');
        $response->assertDontSee('isSavingCustomization');
        $response->assertDontSee('saveCustomization()');
        $response->assertDontSee('resetCustomization()');
        $response->assertDontSee('Save as Company Default');
        $response->assertDontSee('Reset to Default Layout');
    }

    public function test_customization_endpoints_are_removed(): void
    {
        $this->assertFalse(Route::has('masters.price-masters.customization.get'));
        $this->assertFalse(Route::has('masters.price-masters.customization.save'));
        $this->assertFalse(Route::has('masters.price-masters.customization.reset'));

        $getRes = $this->actingAs($this->adminUser)
            ->getJson('/masters/price-masters/customization');
        $this->assertEquals(404, $getRes->status());

        $postRes = $this->actingAs($this->adminUser)
            ->postJson('/masters/price-masters/customization', []);
        $this->assertTrue(in_array($postRes->status(), [404, 405], true));

        $resetRes = $this->actingAs($this->adminUser)
            ->postJson('/masters/price-masters/customization/reset', []);
        $this->assertEquals(404, $resetRes->status());
    }

    public function test_index_page_renders_clean_fixed_11_column_table(): void
    {
        $customerType = CustomerType::create([
            'name' => 'Authorized Dealer',
            'code' => 'CT-DEALER-01',
            'is_active' => true,
        ]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Inverter 5kW',
            'sku' => 'INV-5KW-001',
            'serial_no' => 'SN-INV-001',
            'tax_rate' => 18,
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        PriceMaster::create([
            'type' => PriceMaster::TYPE_CUSTOMER_TYPE,
            'customer_type_id' => $customerType->id,
            'product_id' => $product->id,
            'uom_id' => $this->uom->id,
            'rate' => 45000.00,
            'dealer_price' => 40000.00,
            'dealer_price_entered' => 40000.00,
            'dealer_price_gst_mode' => 'exclusive',
            'retail_price' => 50000.00,
            'retail_price_entered' => 50000.00,
            'retail_price_gst_mode' => 'exclusive',
            'min_qty' => 5,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.index'));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('items'));
        $response->assertSee($product->name);
        $response->assertSee($product->sku);
        $response->assertSee('40,000.00');
        $response->assertSee('50,000.00');
        $response->assertSee('45,000.00');
        $response->assertSee('Customer Type / Brand');
        $response->assertSee('Dealer Price');
        $response->assertSee('Retail Price');
        $response->assertSee('General Rate');
        $response->assertSee('Min Qty');
        $response->assertSee('Actions');
    }

    public function test_copy_options_endpoint_is_preserved_and_works(): void
    {
        $ct = CustomerType::create(['name' => 'Wholesaler', 'code' => 'WHL', 'is_active' => true]);
        $brand = Brand::create(['name' => 'Sony Electronics', 'code' => 'BR-SONY-01', 'company_id' => $this->company->id, 'is_active' => true]);

        $product1 = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'name' => 'Bravia 55 OLED',
            'sku' => 'SN-BR55',
            'serial_no' => 'SN-BR55-001',
            'tax_rate' => 18,
            'calculation_mrp' => 120000,
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        // Customer Type price
        PriceMaster::create([
            'type' => PriceMaster::TYPE_CUSTOMER_TYPE,
            'customer_type_id' => $ct->id,
            'product_id' => $product1->id,
            'uom_id' => $this->uom->id,
            'rate' => 90000,
            'dealer_price' => 85000,
            'retail_price' => 95000,
        ]);

        // Brand Wise price
        PriceMaster::create([
            'type' => PriceMaster::TYPE_BRAND,
            'brand_id' => $brand->id,
            'product_id' => $product1->id,
            'uom_id' => $this->uom->id,
            'rate' => 92000,
            'dealer_price' => 87000,
            'retail_price' => 98000,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('masters.price-masters.copy-options'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $cts = collect($response->json('customer_types'));
        $this->assertTrue($cts->contains('id', $ct->id));

        $brands = collect($response->json('brands'));
        $this->assertTrue($brands->contains('id', $brand->id));
    }

    public function test_copy_source_endpoint_is_preserved_and_works(): void
    {
        $brand = Brand::create(['name' => 'Samsung', 'code' => 'BR-SAM-01', 'company_id' => $this->company->id, 'is_active' => true]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'name' => 'Galaxy Tab S9',
            'sku' => 'SM-X710',
            'serial_no' => 'SN-TAB-001',
            'tax_rate' => 18,
            'calculation_mrp' => 75000,
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        PriceMaster::create([
            'type' => PriceMaster::TYPE_BRAND,
            'brand_id' => $brand->id,
            'product_id' => $product->id,
            'uom_id' => $this->uom->id,
            'rate' => 60000,
            'dealer_price' => 58000,
            'dealer_price_entered' => 58000,
            'dealer_price_gst_mode' => 'exclusive',
            'retail_price' => 64000,
            'retail_price_entered' => 64000,
            'retail_price_gst_mode' => 'inclusive',
            'min_qty' => 5,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('masters.price-masters.copy-source', [
                'type' => 'brand',
                'source_id' => $brand->id,
            ]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'source_type' => 'brand',
                'source_id' => (string) $brand->id,
                'count' => 1,
            ]);

        $item = $response->json('items.0');
        $this->assertEquals($product->id, $item['product_id']);
        $this->assertEquals('Galaxy Tab S9', $item['product_name']);
        $this->assertEquals(58000.0, $item['dealer_price']);
        $this->assertEquals('exclusive', $item['dealer_price_gst_mode']);
        $this->assertEquals(64000.0, $item['retail_price']);
        $this->assertEquals('inclusive', $item['retail_price_gst_mode']);
        $this->assertEquals(5, $item['min_qty']);
    }

    public function test_index_blade_renders_safe_defaults_for_alpine_components(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.index'));

        $response->assertOk();
        $response->assertDontSee('editForm: null', false);
        $response->assertSee('defaultEditForm()', false);
        $response->assertSee('dealer_price_gst_mode', false);
    }
}
