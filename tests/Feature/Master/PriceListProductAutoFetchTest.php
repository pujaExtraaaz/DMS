<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PriceListProductAutoFetchTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Brand $brand;
    protected Category $category;
    protected SubCategory $subCategory;
    protected ProductType $productType;
    protected Uom $baseUom;
    protected Product $product;
    protected CustomerType $customerType;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Acme Corporation',
            'code' => 'ACM-01',
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

        $this->customerType = CustomerType::create([
            'name' => 'Wholesale Tier 1',
            'code' => 'WT1',
            'is_active' => true,
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

        $this->productType = ProductType::create([
            'name' => 'Finished Goods',
            'is_active' => true,
        ]);

        $this->baseUom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'product_type_id' => $this->productType->id,
            'product_type' => 'Finished Goods',
            'base_uom_id' => $this->baseUom->id,
            'name' => 'Sony WH-1000XM5 Wireless Headphones',
            'sku' => 'SNY-WH-XM5',
            'model' => 'WH-1000XM5/B',
            'series' => '1000X Series',
            'serial_no' => 'SN-SNY-998877',
            'hsn_code' => '85183000',
            'tax_rate' => 18.00,
            'calculation_mrp' => 29990.00,
            'selling_price' => 26990.00,
            'purchase_price' => 21000.00,
            'tracking_type' => 'serial',
            'is_active' => true,
        ]);
    }

    public function test_product_details_endpoint_returns_json_with_attributes_and_relations(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('masters.price-masters.product-details', $this->product));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->product->id,
                    'name' => 'Sony WH-1000XM5 Wireless Headphones',
                    'sku' => 'SNY-WH-XM5',
                    'part_number' => 'SNY-WH-XM5',
                    'model' => 'WH-1000XM5/B',
                    'series' => '1000X Series',
                    'brand_id' => $this->brand->id,
                    'brand_name' => 'Sony Electronics',
                    'category_id' => $this->category->id,
                    'category_name' => 'Audio Video',
                    'sub_category_id' => $this->subCategory->id,
                    'sub_category_name' => 'Headphones',
                    'product_type_id' => $this->productType->id,
                    'product_type_name' => 'Finished Goods',
                    'hsn_code' => '85183000',
                    'tax_rate' => 18.0,
                    'gst_rate' => 18.0,
                    'mrp' => 29990.0,
                    'selling_price' => 26990.0,
                    'base_uom_id' => $this->baseUom->id,
                    'base_uom_code' => 'PCS',
                    'base_uom_name' => 'Pieces',
                ],
            ]);
    }

    public function test_product_details_endpoint_handles_missing_optional_attributes(): void
    {
        $bareProduct = Product::create([
            'company_id' => $this->company->id,
            'base_uom_id' => $this->baseUom->id,
            'name' => 'Generic Cable 1m',
            'serial_no' => 'SN-CABLE-001',
            'selling_price' => 150.00,
            'calculation_mrp' => 0,
            'tax_rate' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('masters.price-masters.product-details', $bareProduct));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $bareProduct->id,
                    'name' => 'Generic Cable 1m',
                    'sku' => null,
                    'model' => null,
                    'brand_id' => null,
                    'brand_name' => null,
                    'category_id' => null,
                    'category_name' => null,
                    'sub_category_id' => null,
                    'sub_category_name' => null,
                    'product_type_id' => null,
                    'product_type_name' => null,
                    'hsn_code' => null,
                    'mrp' => 150.0, // fallback to selling_price when calculation_mrp is 0
                    'base_uom_id' => $this->baseUom->id,
                    'base_uom_code' => 'PCS',
                ],
            ]);
    }

    public function test_product_controller_show_returns_json_when_requested(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('masters.products.show', $this->product));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->product->id,
                    'name' => 'Sony WH-1000XM5 Wireless Headphones',
                    'sku' => 'SNY-WH-XM5',
                    'brand_name' => 'Sony Electronics',
                    'category_name' => 'Audio Video',
                    'sub_category_name' => 'Headphones',
                    'product_type_name' => 'Finished Goods',
                    'base_uom_code' => 'PCS',
                ],
            ]);
    }

    public function test_create_price_master_form_view_has_complete_product_attributes(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.create'));

        $response->assertOk();
        $response->assertSee('Sony Electronics');
        $response->assertSee('Audio Video');
        $response->assertSee('Headphones');
        $response->assertSee('SNY-WH-XM5');
        $response->assertSee('WH-1000XM5/B');
        $response->assertSee('Product Master Details');
    }

    public function test_edit_price_master_form_view_has_complete_product_attributes(): void
    {
        $priceMaster = PriceMaster::create([
            'type' => PriceMaster::TYPE_CUSTOMER_TYPE,
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->baseUom->id,
            'rate' => 24000.00,
            'entered_rate' => 24000.00,
            'rate_includes_gst' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.edit', $priceMaster));

        $response->assertOk();
        $response->assertSee('Sony Electronics');
        $response->assertSee('Audio Video');
        $response->assertSee('Headphones');
        $response->assertSee('Product Master Details');
    }

    public function test_saving_and_updating_price_master_does_not_modify_product_master_data(): void
    {
        $initialMrp = (float) $this->product->calculation_mrp;
        $initialSellingPrice = (float) $this->product->selling_price;
        $initialTaxRate = (float) $this->product->tax_rate;
        $initialName = $this->product->name;
        $initialSku = $this->product->sku;

        // 1. Create a price list entry with a special discounted rate
        $createResponse = $this->actingAs($this->adminUser)
            ->post(route('masters.price-masters.store'), [
                'type' => 'customer_type',
                'customer_type_id' => $this->customerType->id,
                'name' => 'Festive Special 2026',
                'lines' => [
                    [
                        'product_id' => $this->product->id,
                        'uom_id' => $this->baseUom->id,
                        'rate' => 19999.00,
                        'rate_includes_gst' => '0',
                    ],
                ],
            ]);

        $createResponse->assertRedirect(route('masters.price-masters.index'));

        // Refresh product from database and verify attributes were NOT changed
        $this->product->refresh();
        $this->assertSame($initialMrp, (float) $this->product->calculation_mrp);
        $this->assertSame($initialSellingPrice, (float) $this->product->selling_price);
        $this->assertSame($initialTaxRate, (float) $this->product->tax_rate);
        $this->assertSame($initialName, $this->product->name);
        $this->assertSame($initialSku, $this->product->sku);

        // 2. Update the created price master record
        $priceMaster = PriceMaster::where('product_id', $this->product->id)->firstOrFail();
        $updateResponse = $this->actingAs($this->adminUser)
            ->put(route('masters.price-masters.update', $priceMaster), [
                'type' => 'customer_type',
                'customer_type_id' => $this->customerType->id,
                'name' => 'Updated Festive Special',
                'product_id' => $this->product->id,
                'uom_id' => $this->baseUom->id,
                'rate' => 18500.00,
                'rate_includes_gst' => '0',
            ]);

        $updateResponse->assertRedirect(route('masters.price-masters.index'));

        // Re-verify product was untouched
        $this->product->refresh();
        $this->assertSame($initialMrp, (float) $this->product->calculation_mrp);
        $this->assertSame($initialSellingPrice, (float) $this->product->selling_price);
        $this->assertSame($initialTaxRate, (float) $this->product->tax_rate);
        $this->assertSame($initialName, $this->product->name);
        $this->assertSame($initialSku, $this->product->sku);
    }

    public function test_rendered_price_master_form_does_not_contain_raw_code_snippet(): void
    {
        // 1. Create form
        $createResponse = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.create'));

        $createResponse->assertOk();
        $createContent = $createResponse->getContent();
        // Ensure there is no orphaned class attribute text outside of an opening tag (e.g. directly after a closing tag)
        $this->assertDoesNotMatchRegularExpression('/<\/div>\s*class="block w-full rounded-lg/', $createContent);

        // 2. Edit form
        $priceMaster = PriceMaster::create([
            'type' => PriceMaster::TYPE_CUSTOMER_TYPE,
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->baseUom->id,
            'dealer_price' => 20000.00,
            'dealer_price_gst_mode' => 'exclusive',
            'retail_price' => 25000.00,
            'retail_price_gst_mode' => 'inclusive',
            'rate' => 20000.00,
        ]);

        $editResponse = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.edit', $priceMaster));

        $editResponse->assertOk();
        $editContent = $editResponse->getContent();
        $this->assertDoesNotMatchRegularExpression('/<\/div>\s*class="block w-full rounded-lg/', $editContent);
    }

    public function test_uom_dropdown_options_are_rendered_natively_in_html(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.create'));

        $response->assertOk();
        // Verify valid UOM option tag exists in DOM
        $response->assertSee('<option value="' . $this->baseUom->id . '">', false);
        $response->assertSee('Select UOM');
        $response->assertSee('Product Units:');
    }

    public function test_product_details_endpoint_returns_valid_uoms_list_including_alternate_and_product_uoms(): void
    {
        $altUom = Uom::create([
            'name' => 'Box',
            'code' => 'BOX',
            'is_active' => true,
        ]);

        $caseUom = Uom::create([
            'name' => 'Case',
            'code' => 'CASE',
            'is_active' => true,
        ]);

        $this->product->update([
            'alternate_uom_id' => $altUom->id,
        ]);

        \App\Domains\Master\Models\ProductUom::create([
            'product_id' => $this->product->id,
            'uom_id' => $caseUom->id,
            'conversion_factor' => 12.0,
            'is_base' => false,
            'label' => 'Shipper Case',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('masters.price-masters.product-details', $this->product));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->product->id,
                    'base_uom_id' => $this->baseUom->id,
                    'base_uom_code' => 'PCS',
                ],
            ]);

        $data = $response->json('data');
        $this->assertIsArray($data['uoms']);
        $uomIds = array_column($data['uoms'], 'id');
        $this->assertContains((string) $this->baseUom->id, $uomIds);
        $this->assertContains((string) $altUom->id, $uomIds);
        $this->assertContains((string) $caseUom->id, $uomIds);
    }
}
