<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Services\PriceMasterService;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PriceListTypeWiseTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Brand $sonyBrand;
    protected Brand $samsungBrand;
    protected CustomerType $dealerType;
    protected CustomerType $retailerType;
    protected Customer $customer;
    protected Uom $pcsUom;
    protected Product $sonyTvProduct;
    protected Product $sonyHeadphoneProduct;
    protected Product $samsungPhoneProduct;
    protected PriceMasterService $priceMasterService;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Apex Electronics Corp',
            'code' => 'AEC-01',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($role);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $this->pcsUom = Uom::firstOrCreate(['code' => 'PCS'], [
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'is_active' => true,
        ]);

        $this->sonyBrand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Sony',
            'is_active' => true,
        ]);

        $this->samsungBrand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Samsung',
            'is_active' => true,
        ]);

        $this->dealerType = CustomerType::create([
            'name' => 'Authorized Dealer',
            'code' => 'CT-DEALER',
            'is_active' => true,
        ]);

        $this->retailerType = CustomerType::create([
            'name' => 'Retail Partner',
            'code' => 'CT-RETAIL',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Metro Retailers',
            'code' => 'CUST-METRO-01',
            'customer_type_id' => $this->dealerType->id,
            'is_active' => true,
        ]);

        $this->sonyTvProduct = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->sonyBrand->id,
            'name' => 'Sony Bravia 55 OLED',
            'sku' => 'SONY-TV-55',
            'serial_no' => 'SN-STV-01',
            'base_uom_id' => $this->pcsUom->id,
            'tax_rate' => 18,
            'selling_price' => 75000.00,
            'is_active' => true,
        ]);

        $this->sonyHeadphoneProduct = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->sonyBrand->id,
            'name' => 'Sony WH1000XM5',
            'sku' => 'SONY-WH-XM5',
            'serial_no' => 'SN-SWH-02',
            'base_uom_id' => $this->pcsUom->id,
            'tax_rate' => 18,
            'selling_price' => 25000.00,
            'is_active' => true,
        ]);

        $this->samsungPhoneProduct = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->samsungBrand->id,
            'name' => 'Samsung Galaxy S24',
            'sku' => 'SAM-S24',
            'serial_no' => 'SN-S24-03',
            'base_uom_id' => $this->pcsUom->id,
            'tax_rate' => 18,
            'selling_price' => 60000.00,
            'is_active' => true,
        ]);

        $this->priceMasterService = app(PriceMasterService::class);
    }

    public function test_create_form_renders_price_list_type_selector(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('masters.price-masters.create'));

        $response->assertOk();
        $response->assertSee('Price List Type');
        $response->assertSee('Customer Type Wise');
        $response->assertSee('Brand Wise');
        $response->assertSee('name="type"', false);
        $response->assertSee('name="customer_type_id"', false);
        $response->assertSee('name="brand_id"', false);
    }

    public function test_can_create_customer_type_wise_price_list_with_multiple_products(): void
    {
        $payload = [
            'type' => 'customer_type',
            'customer_type_id' => $this->dealerType->id,
            'name' => 'Dealer Festive Pricing',
            'lines' => [
                [
                    'product_id' => $this->sonyTvProduct->id,
                    'uom_id' => $this->pcsUom->id,
                    'rate' => 70000.00,
                    'rate_includes_gst' => '0',
                    'min_qty' => 1,
                ],
                [
                    'product_id' => $this->samsungPhoneProduct->id,
                    'uom_id' => $this->pcsUom->id,
                    'rate' => 55000.00,
                    'rate_includes_gst' => '0',
                    'min_qty' => 5,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('masters.price-masters.store'), $payload);

        $response->assertRedirect(route('masters.price-masters.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('price_masters', [
            'type' => 'customer_type',
            'customer_type_id' => $this->dealerType->id,
            'brand_id' => null,
            'product_id' => $this->sonyTvProduct->id,
            'rate' => 70000.00,
            'min_qty' => 1,
        ]);

        $this->assertDatabaseHas('price_masters', [
            'type' => 'customer_type',
            'customer_type_id' => $this->dealerType->id,
            'brand_id' => null,
            'product_id' => $this->samsungPhoneProduct->id,
            'rate' => 55000.00,
            'min_qty' => 5,
        ]);
    }

    public function test_can_create_brand_wise_price_list_with_multiple_products(): void
    {
        $payload = [
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'name' => 'Sony Authorized Brand Rate',
            'lines' => [
                [
                    'product_id' => $this->sonyTvProduct->id,
                    'uom_id' => $this->pcsUom->id,
                    'rate' => 68000.00,
                    'rate_includes_gst' => '0',
                    'min_qty' => 2,
                ],
                [
                    'product_id' => $this->sonyHeadphoneProduct->id,
                    'uom_id' => $this->pcsUom->id,
                    'rate' => 22000.00,
                    'rate_includes_gst' => '1', // 18% GST inclusive
                    'min_qty' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('masters.price-masters.store'), $payload);

        $response->assertRedirect(route('masters.price-masters.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('price_masters', [
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'customer_type_id' => null,
            'product_id' => $this->sonyTvProduct->id,
            'rate' => 68000.00,
            'rate_includes_gst' => false,
            'min_qty' => 2,
        ]);

        // GST inclusive calculation: 22000 / 1.18 = 18644.07 exclusive
        $headphonePrice = PriceMaster::where('brand_id', $this->sonyBrand->id)
            ->where('product_id', $this->sonyHeadphoneProduct->id)
            ->first();

        $this->assertNotNull($headphonePrice);
        $this->assertTrue((bool) $headphonePrice->rate_includes_gst);
        $this->assertEquals(22000.00, (float) $headphonePrice->entered_rate);
        $this->assertEquals(18644.07, (float) $headphonePrice->rate);
    }

    public function test_brand_wise_creation_rejects_products_not_belonging_to_selected_brand(): void
    {
        $payload = [
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'lines' => [
                [
                    'product_id' => $this->samsungPhoneProduct->id, // Belongs to Samsung, NOT Sony!
                    'uom_id' => $this->pcsUom->id,
                    'rate' => 50000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('masters.price-masters.store'), $payload);

        $response->assertSessionHasErrors('lines.0.product_id');
        $this->assertDatabaseMissing('price_masters', [
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'product_id' => $this->samsungPhoneProduct->id,
        ]);
    }

    public function test_duplicate_entries_within_form_are_rejected(): void
    {
        $payload = [
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'lines' => [
                [
                    'product_id' => $this->sonyTvProduct->id,
                    'uom_id' => $this->pcsUom->id,
                    'rate' => 68000.00,
                    'min_qty' => 1,
                ],
                [
                    'product_id' => $this->sonyTvProduct->id, // Duplicate product, uom, and min_qty!
                    'uom_id' => $this->pcsUom->id,
                    'rate' => 67000.00,
                    'min_qty' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('masters.price-masters.store'), $payload);
        $response->assertSessionHasErrors('lines.1.product_id');
    }

    public function test_existing_prices_are_not_silently_overwritten_by_duplicate_submissions(): void
    {
        PriceMaster::create([
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'product_id' => $this->sonyTvProduct->id,
            'uom_id' => $this->pcsUom->id,
            'rate' => 68000.00,
            'min_qty' => null,
        ]);

        $payload = [
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'lines' => [
                [
                    'product_id' => $this->sonyTvProduct->id,
                    'uom_id' => $this->pcsUom->id,
                    'rate' => 65000.00,
                    'min_qty' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('masters.price-masters.store'), $payload);
        $response->assertSessionHasErrors('lines.0.product_id');

        // Existing price remains intact
        $this->assertEquals(68000.00, (float) PriceMaster::first()->rate);
    }

    public function test_can_edit_and_update_brand_wise_price_list(): void
    {
        $price = PriceMaster::create([
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'product_id' => $this->sonyTvProduct->id,
            'uom_id' => $this->pcsUom->id,
            'rate' => 68000.00,
            'rate_includes_gst' => false,
            'entered_rate' => 68000.00,
            'min_qty' => 1,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('masters.price-masters.edit', $price));
        $response->assertOk();
        $response->assertSee('Brand Wise');

        $updatePayload = [
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'product_id' => $this->sonyTvProduct->id,
            'uom_id' => $this->pcsUom->id,
            'rate' => 66000.00,
            'rate_includes_gst' => '0',
            'min_qty' => 2,
        ];

        $updateResponse = $this->actingAs($this->adminUser)->put(route('masters.price-masters.update', $price), $updatePayload);
        $updateResponse->assertRedirect(route('masters.price-masters.index'));

        $price->refresh();
        $this->assertEquals(66000.00, (float) $price->rate);
        $this->assertEquals(2, (int) $price->min_qty);
    }

    public function test_listing_identifies_price_list_types_and_filters_correctly(): void
    {
        PriceMaster::create([
            'type' => 'customer_type',
            'customer_type_id' => $this->dealerType->id,
            'product_id' => $this->sonyTvProduct->id,
            'uom_id' => $this->pcsUom->id,
            'rate' => 70000.00,
        ]);

        PriceMaster::create([
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'product_id' => $this->sonyTvProduct->id,
            'uom_id' => $this->pcsUom->id,
            'rate' => 68000.00,
        ]);

        // 1. Unfiltered shows both types
        $indexResponse = $this->actingAs($this->adminUser)->get(route('masters.price-masters.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Customer Type');
        $indexResponse->assertSee('Brand Wise');

        // 2. Filter by Type: brand
        $brandFilterResponse = $this->actingAs($this->adminUser)->get(route('masters.price-masters.index', ['type' => 'brand']));
        $brandFilterResponse->assertOk();
        $items = $brandFilterResponse->viewData('items');
        $this->assertCount(1, $items);
        $this->assertEquals('brand', $items->first()->type);

        // 3. Filter by Brand
        $brandResponse = $this->actingAs($this->adminUser)->get(route('masters.price-masters.index', ['brand_id' => $this->sonyBrand->id]));
        $brandResponse->assertOk();
        $items = $brandResponse->viewData('items');
        $this->assertCount(1, $items);
        $this->assertEquals($this->sonyBrand->id, $items->first()->brand_id);
    }

    public function test_price_resolution_prioritizes_brand_wise_over_customer_type_wise(): void
    {
        // 1. Configure Customer Type price at 72,000
        PriceMaster::create([
            'type' => 'customer_type',
            'customer_type_id' => $this->dealerType->id,
            'product_id' => $this->sonyTvProduct->id,
            'uom_id' => $this->pcsUom->id,
            'rate' => 72000.00,
        ]);

        // 2. Configure Brand Wise price at 69,000
        PriceMaster::create([
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'product_id' => $this->sonyTvProduct->id,
            'uom_id' => $this->pcsUom->id,
            'rate' => 69000.00,
        ]);

        // Resolve price via service
        $resolved = $this->priceMasterService->resolvePrice(
            $this->customer,
            $this->sonyTvProduct,
            $this->pcsUom,
            1.0
        );

        // Brand Wise price (69000) takes precedence over Customer Type price (72000)
        $this->assertEquals('69000.00', $resolved);

        // Also test endpoint in OrderController
        $endpointResponse = $this->actingAs($this->adminUser)->getJson(route('orders.resolve-price', [
            'customer_id' => $this->customer->id,
            'product_id' => $this->sonyTvProduct->id,
            'uom_id' => $this->pcsUom->id,
            'quantity' => 1,
        ]));

        $endpointResponse->assertOk();
        $endpointResponse->assertJson([
            'found' => true,
            'unit_price' => '69000.00',
        ]);
    }

    public function test_price_resolution_falls_back_to_customer_type_wise_when_no_brand_price_exists(): void
    {
        // Only customer type price configured
        PriceMaster::create([
            'type' => 'customer_type',
            'customer_type_id' => $this->dealerType->id,
            'product_id' => $this->sonyTvProduct->id,
            'uom_id' => $this->pcsUom->id,
            'rate' => 72000.00,
        ]);

        $resolved = $this->priceMasterService->resolvePrice(
            $this->customer,
            $this->sonyTvProduct,
            $this->pcsUom,
            1.0
        );

        $this->assertEquals('72000.00', $resolved);
    }

    public function test_price_resolution_falls_back_to_product_selling_price_when_no_price_master_exists(): void
    {
        // No PriceMaster configured for Samsung Phone
        $resolved = $this->priceMasterService->resolvePrice(
            $this->customer,
            $this->samsungPhoneProduct,
            $this->pcsUom,
            1.0
        );

        // Falls back to product's default selling_price (60000.00)
        $this->assertEquals('60000.00', $resolved);
    }

    public function test_can_delete_brand_wise_price_record(): void
    {
        $price = PriceMaster::create([
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'product_id' => $this->sonyTvProduct->id,
            'uom_id' => $this->pcsUom->id,
            'rate' => 68000.00,
        ]);

        $response = $this->actingAs($this->adminUser)->deleteJson(route('masters.price-masters.destroy', $price));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('price_masters', [
            'id' => $price->id,
        ]);
    }

    public function test_export_includes_brand_wise_information(): void
    {
        PriceMaster::create([
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'name' => 'Brand Q4 List',
            'product_id' => $this->sonyTvProduct->id,
            'uom_id' => $this->pcsUom->id,
            'rate' => 68000.00,
            'rate_includes_gst' => false,
            'entered_rate' => 68000.00,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('masters.price-masters.index', ['export' => 'csv']));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Price List Type', $content);
        $this->assertStringContainsString('Brand Wise', $content);
        $this->assertStringContainsString('Sony', $content);
        $this->assertStringContainsString('Brand Q4 List', $content);
    }

    public function test_brand_products_endpoint_returns_brand_products_with_details_and_company_isolation(): void
    {
        $this->sonyTvProduct->update(['model' => 'KD-55A8H']);

        $response = $this->actingAs($this->adminUser)->getJson(route('masters.price-masters.brand-products', $this->sonyBrand));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'brand' => [
                'id' => $this->sonyBrand->id,
                'name' => 'Sony',
            ],
            'count' => 2,
        ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        $tv = collect($data)->firstWhere('id', $this->sonyTvProduct->id);
        $this->assertNotNull($tv);
        $this->assertEquals('Sony Bravia 55 OLED', $tv['name']);
        $this->assertEquals('SONY-TV-55', $tv['sku']);
        $this->assertEquals('KD-55A8H', $tv['model']);
        $this->assertEquals('Sony', $tv['brand_name']);
        $this->assertEquals(18.0, $tv['tax_rate']);
        $this->assertEquals(75000.0, $tv['mrp']);
        $this->assertNotEmpty($tv['uoms']);
    }

    public function test_brand_products_endpoint_enforces_company_boundary_isolation(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Telecom Corp',
            'code' => 'OTC-99',
            'is_active' => true,
        ]);
        $otherBrand = Brand::create([
            'company_id' => $otherCompany->id,
            'name' => 'Panasonic',
            'is_active' => true,
        ]);

        $regularUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $role = Role::firstOrCreate(['name' => 'sales-manager', 'guard_name' => 'web']);
        $regularUser->assignRole($role);

        $response = $this->actingAs($regularUser)->getJson(route('masters.price-masters.brand-products', $otherBrand));
        $response->assertStatus(403);
    }

    public function test_form_renders_brand_products_catalog_table_and_bulk_controls(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('masters.price-masters.create'));

        $response->assertOk();
        $response->assertSee('Brand Products Catalog');
        $response->assertSee('Bulk Pricing Tool');
        $response->assertSee('Configure Pricing for Selected Products');
        $response->assertSee('Select All');
        $response->assertSee('Deselect All');
        $response->assertSee('Dealer Price');
        $response->assertSee('Retail Price');
    }

    public function test_can_store_brand_wise_price_list_with_multiple_selected_products_and_dual_pricing(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('masters.price-masters.store'), [
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'name' => 'Sony Festival 2026 Price List',
            'lines' => [
                [
                    'product_id' => $this->sonyTvProduct->id,
                    'uom_id' => $this->pcsUom->id,
                    'dealer_price' => '65000.00',
                    'dealer_price_gst_mode' => 'exclusive',
                    'retail_price' => '72000.00',
                    'retail_price_gst_mode' => 'inclusive',
                    'min_qty' => '2',
                ],
                [
                    'product_id' => $this->sonyHeadphoneProduct->id,
                    'uom_id' => $this->pcsUom->id,
                    'dealer_price' => '18000.00',
                    'dealer_price_gst_mode' => 'inclusive',
                    'retail_price' => '22000.00',
                    'retail_price_gst_mode' => 'exclusive',
                    'min_qty' => '5',
                ],
            ],
        ]);

        $response->assertRedirect(route('masters.price-masters.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('price_masters', [
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'name' => 'Sony Festival 2026 Price List',
            'product_id' => $this->sonyTvProduct->id,
            'uom_id' => $this->pcsUom->id,
            'dealer_price' => 65000.00,
            'dealer_price_gst_mode' => 'exclusive',
            'min_qty' => 2.0,
        ]);

        $this->assertDatabaseHas('price_masters', [
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'name' => 'Sony Festival 2026 Price List',
            'product_id' => $this->sonyHeadphoneProduct->id,
            'uom_id' => $this->pcsUom->id,
            'dealer_price_gst_mode' => 'inclusive',
            'retail_price' => 22000.00,
            'min_qty' => 5.0,
        ]);
    }

    public function test_brand_wise_price_list_rejects_products_not_belonging_to_brand(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('masters.price-masters.store'), [
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'name' => 'Invalid Brand Price List',
            'lines' => [
                [
                    'product_id' => $this->samsungPhoneProduct->id, // Belongs to Samsung, not Sony!
                    'uom_id' => $this->pcsUom->id,
                    'dealer_price' => '50000.00',
                    'dealer_price_gst_mode' => 'exclusive',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['lines.0.product_id']);
    }

    public function test_brand_wise_price_list_rejects_duplicate_products_within_same_brand_and_uom(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('masters.price-masters.store'), [
            'type' => 'brand',
            'brand_id' => $this->sonyBrand->id,
            'name' => 'Duplicate Products List',
            'lines' => [
                [
                    'product_id' => $this->sonyTvProduct->id,
                    'uom_id' => $this->pcsUom->id,
                    'dealer_price' => '65000.00',
                    'min_qty' => '1',
                ],
                [
                    'product_id' => $this->sonyTvProduct->id, // Duplicate product & min_qty!
                    'uom_id' => $this->pcsUom->id,
                    'dealer_price' => '64000.00',
                    'min_qty' => '1',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['lines.1.product_id']);
    }
}
