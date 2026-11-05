<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
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

class PriceListDealerRetailPricingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Brand $brand;
    protected Category $category;
    protected SubCategory $subCategory;
    protected CustomerType $dealerCustomerType;
    protected CustomerType $retailCustomerType;
    protected Customer $dealerCustomer;
    protected Customer $retailCustomer;
    protected Uom $pcsUom;
    protected Product $standardGstProduct;
    protected Product $zeroGstProduct;
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

        $this->brand = Brand::create([
            'name' => 'Sony Electronics',
            'code' => 'SONY',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Audio Video',
            'code' => 'AV',
            'brand_id' => $this->brand->id,
            'is_active' => true,
        ]);

        $this->subCategory = SubCategory::create([
            'name' => 'Headphones',
            'code' => 'HP',
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $this->dealerCustomerType = CustomerType::create([
            'name' => 'Authorized Dealer',
            'code' => 'AD',
            'is_active' => true,
        ]);

        $this->retailCustomerType = CustomerType::create([
            'name' => 'Retail Consumer',
            'code' => 'RC',
            'is_active' => true,
        ]);

        $this->dealerCustomer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->dealerCustomerType->id,
            'name' => 'Mega Dealer Ltd',
            'code' => 'CUST-DL-001',
            'is_active' => true,
        ]);

        $this->retailCustomer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->retailCustomerType->id,
            'name' => 'Walk-in Retail Buyer',
            'code' => 'CUST-RT-001',
            'is_active' => true,
        ]);

        $this->pcsUom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        // Standard 18% GST product
        $this->standardGstProduct = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'base_uom_id' => $this->pcsUom->id,
            'name' => 'Sony WH-1000XM5 Wireless Headphones',
            'sku' => 'SNY-WH-XM5',
            'model' => 'WH-1000XM5',
            'serial_no' => 'SN-1000XM5-01',
            'hsn_code' => '85183000',
            'tax_rate' => 18.00,
            'calculation_mrp' => 29990.00,
            'selling_price' => 26990.00,
            'tracking_type' => 'serial',
            'is_active' => true,
        ]);

        // 0% GST (exempt) product
        $this->zeroGstProduct = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'base_uom_id' => $this->pcsUom->id,
            'name' => 'Exempt Audio Manual Book',
            'sku' => 'MAN-AUDIO-01',
            'serial_no' => 'SN-MAN-001',
            'tax_rate' => 0.00,
            'calculation_mrp' => 500.00,
            'selling_price' => 450.00,
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $this->priceMasterService = app(PriceMasterService::class);
    }

    public function test_dealer_price_entered_as_gst_exclusive(): void
    {
        // Dealer price = 10000 (Excl. GST). Product tax = 18%.
        // Tax-Exclusive Base Price = 10000
        // Tax Amount = 1800
        // Tax-Inclusive Price = 11800
        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.price-masters.store'), [
                'type' => 'customer_type',
                'customer_type_id' => $this->dealerCustomerType->id,
                'name' => 'Dealer Price List Excl',
                'lines' => [
                    [
                        'product_id' => $this->standardGstProduct->id,
                        'uom_id' => $this->pcsUom->id,
                        'dealer_price' => 10000.00,
                        'dealer_price_gst_mode' => 'exclusive',
                    ],
                ],
            ]);

        $response->assertRedirect(route('masters.price-masters.index'));

        $record = PriceMaster::where('product_id', $this->standardGstProduct->id)->firstOrFail();
        $this->assertEquals(10000.00, (float) $record->dealer_price);
        $this->assertEquals(10000.00, (float) $record->dealer_price_entered);
        $this->assertEquals('exclusive', $record->dealer_price_gst_mode);

        $summary = $record->dealerPricingSummary();
        $this->assertEquals(10000.00, $summary['exclusive']);
        $this->assertEquals(1800.00, $summary['gst']);
        $this->assertEquals(11800.00, $summary['final']);
    }

    public function test_dealer_price_entered_as_gst_inclusive(): void
    {
        // Dealer price = 11800 (Incl. GST). Product tax = 18%.
        // Base Price = 11800 * 100 / 118 = 10000
        // Tax Amount = 1800
        // Tax-Inclusive Price = 11800
        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.price-masters.store'), [
                'type' => 'customer_type',
                'customer_type_id' => $this->dealerCustomerType->id,
                'name' => 'Dealer Price List Incl',
                'lines' => [
                    [
                        'product_id' => $this->standardGstProduct->id,
                        'uom_id' => $this->pcsUom->id,
                        'dealer_price' => 11800.00,
                        'dealer_price_gst_mode' => 'inclusive',
                    ],
                ],
            ]);

        $response->assertRedirect(route('masters.price-masters.index'));

        $record = PriceMaster::where('product_id', $this->standardGstProduct->id)->firstOrFail();
        $this->assertEquals(10000.00, (float) $record->dealer_price);
        $this->assertEquals(11800.00, (float) $record->dealer_price_entered);
        $this->assertEquals('inclusive', $record->dealer_price_gst_mode);

        $summary = $record->dealerPricingSummary();
        $this->assertEquals(10000.00, $summary['exclusive']);
        $this->assertEquals(1800.00, $summary['gst']);
        $this->assertEquals(11800.00, $summary['final']);
    }

    public function test_retail_price_entered_as_gst_exclusive(): void
    {
        // Retail price = 20000 (Excl. GST). Product tax = 18%.
        // Base = 20000
        // Tax Amount = 3600
        // Inclusive = 23600
        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.price-masters.store'), [
                'type' => 'customer_type',
                'customer_type_id' => $this->retailCustomerType->id,
                'name' => 'Retail Price List Excl',
                'lines' => [
                    [
                        'product_id' => $this->standardGstProduct->id,
                        'uom_id' => $this->pcsUom->id,
                        'retail_price' => 20000.00,
                        'retail_price_gst_mode' => 'exclusive',
                    ],
                ],
            ]);

        $response->assertRedirect(route('masters.price-masters.index'));

        $record = PriceMaster::where('product_id', $this->standardGstProduct->id)->firstOrFail();
        $this->assertEquals(20000.00, (float) $record->retail_price);
        $this->assertEquals(20000.00, (float) $record->retail_price_entered);
        $this->assertEquals('exclusive', $record->retail_price_gst_mode);

        $summary = $record->retailPricingSummary();
        $this->assertEquals(20000.00, $summary['exclusive']);
        $this->assertEquals(3600.00, $summary['gst']);
        $this->assertEquals(23600.00, $summary['final']);
    }

    public function test_retail_price_entered_as_gst_inclusive(): void
    {
        // Retail price = 23600 (Incl. GST). Product tax = 18%.
        // Base = 20000
        // Tax Amount = 3600
        // Inclusive = 23600
        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.price-masters.store'), [
                'type' => 'customer_type',
                'customer_type_id' => $this->retailCustomerType->id,
                'name' => 'Retail Price List Incl',
                'lines' => [
                    [
                        'product_id' => $this->standardGstProduct->id,
                        'uom_id' => $this->pcsUom->id,
                        'retail_price' => 23600.00,
                        'retail_price_gst_mode' => 'inclusive',
                    ],
                ],
            ]);

        $response->assertRedirect(route('masters.price-masters.index'));

        $record = PriceMaster::where('product_id', $this->standardGstProduct->id)->firstOrFail();
        $this->assertEquals(20000.00, (float) $record->retail_price);
        $this->assertEquals(23600.00, (float) $record->retail_price_entered);
        $this->assertEquals('inclusive', $record->retail_price_gst_mode);

        $summary = $record->retailPricingSummary();
        $this->assertEquals(20000.00, $summary['exclusive']);
        $this->assertEquals(3600.00, $summary['gst']);
        $this->assertEquals(23600.00, $summary['final']);
    }

    public function test_dealer_and_retail_prices_with_different_values_and_gst_modes(): void
    {
        // In the same price list line:
        // Dealer Price: 10000 (Excl. GST) -> Base 10000, GST 1800, Inc 11800
        // Retail Price: 23600 (Incl. GST) -> Base 20000, GST 3600, Inc 23600
        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.price-masters.store'), [
                'type' => 'customer_type',
                'customer_type_id' => $this->dealerCustomerType->id,
                'name' => 'Dual Pricing FY26',
                'lines' => [
                    [
                        'product_id' => $this->standardGstProduct->id,
                        'uom_id' => $this->pcsUom->id,
                        'dealer_price' => 10000.00,
                        'dealer_price_gst_mode' => 'exclusive',
                        'retail_price' => 23600.00,
                        'retail_price_gst_mode' => 'inclusive',
                    ],
                ],
            ]);

        $response->assertRedirect(route('masters.price-masters.index'));

        $record = PriceMaster::where('product_id', $this->standardGstProduct->id)->firstOrFail();

        // Dealer calculations
        $dealerSummary = $record->dealerPricingSummary();
        $this->assertEquals(10000.00, $dealerSummary['exclusive']);
        $this->assertEquals(1800.00, $dealerSummary['gst']);
        $this->assertEquals(11800.00, $dealerSummary['final']);

        // Retail calculations
        $retailSummary = $record->retailPricingSummary();
        $this->assertEquals(20000.00, $retailSummary['exclusive']);
        $this->assertEquals(3600.00, $retailSummary['gst']);
        $this->assertEquals(23600.00, $retailSummary['final']);

        // Check independent settings
        $this->assertEquals('exclusive', $record->dealer_price_gst_mode);
        $this->assertEquals('inclusive', $record->retail_price_gst_mode);
    }

    public function test_product_with_zero_gst_or_exemption(): void
    {
        // Zero GST product (tax_rate = 0)
        // Dealer Price: 400 (exclusive) -> Base 400, GST 0, Inc 400
        // Retail Price: 480 (inclusive) -> Base 480, GST 0, Inc 480
        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.price-masters.store'), [
                'type' => 'customer_type',
                'customer_type_id' => $this->dealerCustomerType->id,
                'name' => 'Exempt Product Pricing',
                'lines' => [
                    [
                        'product_id' => $this->zeroGstProduct->id,
                        'uom_id' => $this->pcsUom->id,
                        'dealer_price' => 400.00,
                        'dealer_price_gst_mode' => 'exclusive',
                        'retail_price' => 480.00,
                        'retail_price_gst_mode' => 'inclusive',
                    ],
                ],
            ]);

        $response->assertRedirect(route('masters.price-masters.index'));

        $record = PriceMaster::where('product_id', $this->zeroGstProduct->id)->firstOrFail();

        $dealer = $record->dealerPricingSummary();
        $this->assertEquals(400.00, $dealer['exclusive']);
        $this->assertEquals(0.00, $dealer['gst']);
        $this->assertEquals(400.00, $dealer['final']);

        $retail = $record->retailPricingSummary();
        $this->assertEquals(480.00, $retail['exclusive']);
        $this->assertEquals(0.00, $retail['gst']);
        $this->assertEquals(480.00, $retail['final']);
    }

    public function test_editing_and_updating_dealer_and_retail_prices(): void
    {
        $priceMaster = PriceMaster::create([
            'type' => PriceMaster::TYPE_CUSTOMER_TYPE,
            'customer_type_id' => $this->dealerCustomerType->id,
            'product_id' => $this->standardGstProduct->id,
            'uom_id' => $this->pcsUom->id,
            'dealer_price' => 10000.00,
            'dealer_price_entered' => 10000.00,
            'dealer_price_gst_mode' => 'exclusive',
            'retail_price' => 20000.00,
            'retail_price_entered' => 20000.00,
            'retail_price_gst_mode' => 'exclusive',
            'rate' => 10000.00,
        ]);

        // Load Edit Form
        $formResponse = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.edit', $priceMaster));

        $formResponse->assertOk();
        $formResponse->assertSee('Dealer Price');
        $formResponse->assertSee('Retail Price');

        // Update via PUT request: change dealer price to 11800 (incl) and retail price to 22000 (excl)
        $updateResponse = $this->actingAs($this->adminUser)
            ->put(route('masters.price-masters.update', $priceMaster), [
                'type' => 'customer_type',
                'customer_type_id' => $this->dealerCustomerType->id,
                'product_id' => $this->standardGstProduct->id,
                'uom_id' => $this->pcsUom->id,
                'dealer_price' => 11800.00,
                'dealer_price_gst_mode' => 'inclusive',
                'retail_price' => 22000.00,
                'retail_price_gst_mode' => 'exclusive',
            ]);

        $updateResponse->assertRedirect(route('masters.price-masters.index'));

        $priceMaster->refresh();
        $this->assertEquals(10000.00, (float) $priceMaster->dealer_price);
        $this->assertEquals(11800.00, (float) $priceMaster->dealer_price_entered);
        $this->assertEquals('inclusive', $priceMaster->dealer_price_gst_mode);

        $this->assertEquals(22000.00, (float) $priceMaster->retail_price);
        $this->assertEquals(22000.00, (float) $priceMaster->retail_price_entered);
        $this->assertEquals('exclusive', $priceMaster->retail_price_gst_mode);
    }

    public function test_brand_wise_price_list_supports_dealer_and_retail_pricing(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.price-masters.store'), [
                'type' => 'brand',
                'brand_id' => $this->brand->id,
                'name' => 'Sony Official Brand Price List',
                'lines' => [
                    [
                        'product_id' => $this->standardGstProduct->id,
                        'uom_id' => $this->pcsUom->id,
                        'dealer_price' => 9500.00,
                        'dealer_price_gst_mode' => 'exclusive',
                        'retail_price' => 25000.00,
                        'retail_price_gst_mode' => 'inclusive',
                    ],
                ],
            ]);

        $response->assertRedirect(route('masters.price-masters.index'));

        $record = PriceMaster::where('type', PriceMaster::TYPE_BRAND)
            ->where('brand_id', $this->brand->id)
            ->where('product_id', $this->standardGstProduct->id)
            ->firstOrFail();

        $this->assertEquals(9500.00, (float) $record->dealer_price);
        $this->assertEquals('exclusive', $record->dealer_price_gst_mode);

        $retailSummary = $record->retailPricingSummary();
        $this->assertEquals(25000.00, $retailSummary['final']);
        $this->assertEquals('inclusive', $record->retail_price_gst_mode);
    }

    public function test_price_resolution_service_resolves_dealer_and_retail_prices_correctly(): void
    {
        // Create price master entry with both dealer and retail rates
        PriceMaster::create([
            'type' => PriceMaster::TYPE_CUSTOMER_TYPE,
            'customer_type_id' => $this->dealerCustomerType->id,
            'product_id' => $this->standardGstProduct->id,
            'uom_id' => $this->pcsUom->id,
            'dealer_price' => 12000.00,
            'dealer_price_entered' => 12000.00,
            'dealer_price_gst_mode' => 'exclusive',
            'retail_price' => 22000.00,
            'retail_price_entered' => 22000.00,
            'retail_price_gst_mode' => 'exclusive',
            'rate' => 12000.00,
        ]);

        PriceMaster::create([
            'type' => PriceMaster::TYPE_CUSTOMER_TYPE,
            'customer_type_id' => $this->retailCustomerType->id,
            'product_id' => $this->standardGstProduct->id,
            'uom_id' => $this->pcsUom->id,
            'dealer_price' => 12000.00,
            'dealer_price_entered' => 12000.00,
            'dealer_price_gst_mode' => 'exclusive',
            'retail_price' => 22000.00,
            'retail_price_entered' => 22000.00,
            'retail_price_gst_mode' => 'exclusive',
            'rate' => 12000.00,
        ]);

        // Resolving for dealer customer should return dealer price (12000.00)
        $dealerResolved = $this->priceMasterService->resolvePrice(
            $this->dealerCustomer,
            $this->standardGstProduct,
            $this->pcsUom,
            1.0
        );
        $this->assertEquals('12000.00', $dealerResolved);

        // Resolving for retail customer should return retail price (22000.00)
        $retailResolved = $this->priceMasterService->resolvePrice(
            $this->retailCustomer,
            $this->standardGstProduct,
            $this->pcsUom,
            1.0
        );
        $this->assertEquals('22000.00', $retailResolved);
    }
}

