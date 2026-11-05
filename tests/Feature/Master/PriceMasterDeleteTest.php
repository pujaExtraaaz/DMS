<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Organization\Models\Company;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PriceMasterDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected CustomerType $customerType;
    protected Uom $uom;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);

        $this->company = Company::create([
            'name' => 'Solar Energy Ltd',
            'code' => 'SOLAR-01',
            'is_active' => true,
        ]);

        $this->customerType = CustomerType::create([
            'name' => 'Wholesaler Tier 1',
            'code' => 'CT-WHOLESALE-01',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Inverter 5kW',
            'sku' => 'INV-5KW-001',
            'serial_no' => 'SN-INV-001',
            'base_uom_id' => $this->uom->id,
            'tax_rate' => 18,
            'is_active' => true,
        ]);
    }

    public function test_price_masters_listing_renders_edit_and_delete_buttons_and_modal(): void
    {
        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'rate' => 45000.00,
            'min_qty' => 5,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('masters.price-masters.index'));

        $response->assertStatus(200);
        $response->assertSee($this->customerType->name);
        $response->assertSee($this->product->name);
        $response->assertSee('PCS');
        $response->assertSee('45,000.00');
        $response->assertSee('Edit');
        $response->assertSee('Delete');
        $response->assertSee('data-delete-price="' . $priceMaster->id . '"', false);
        $response->assertSee('Delete Price Record');
        $response->assertSee('Customer Type:');
        $response->assertSee('Product:');
        $response->assertSee('Unit:');
        $response->assertSee('Rate:');
        $response->assertSee('Min Qty:');
        $response->assertSee('Confirm Delete');
        $response->assertSee('Cancel');
    }

    public function test_can_delete_price_master_permanently_via_json(): void
    {
        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'rate' => 12500.00,
            'min_qty' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.price-masters.destroy', $priceMaster));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('price_masters', [
            'id' => $priceMaster->id,
        ]);

        // Assert associated product, customer type, and UOM remain intact
        $this->assertDatabaseHas('products', ['id' => $this->product->id]);
        $this->assertDatabaseHas('customer_types', ['id' => $this->customerType->id]);
        $this->assertDatabaseHas('uoms', ['id' => $this->uom->id]);
    }

    public function test_can_delete_price_master_via_form_redirect(): void
    {
        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'rate' => 18000.00,
            'min_qty' => 2,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('masters.price-masters.destroy', $priceMaster));

        $response->assertRedirect(route('masters.price-masters.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('price_masters', [
            'id' => $priceMaster->id,
        ]);
    }

    public function test_can_delete_price_master_via_alias_route(): void
    {
        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'rate' => 999.00,
            'min_qty' => 1,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.price-lists.destroy', $priceMaster));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('price_masters', [
            'id' => $priceMaster->id,
        ]);
    }

    public function test_deleting_price_master_preserves_historical_sales_documents(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Acme Distributing',
            'code' => 'CUST-ACME-001',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'rate' => 3000.00,
            'min_qty' => 1,
        ]);

        // Historical order with snapshot price
        $order = Order::create([
            'order_no' => 'ORD-TEST-999',
            'customer_id' => $customer->id,
            'salesperson_id' => $this->adminUser->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'grand_total' => 6000.00,
            'subtotal' => 6000.00,
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_price' => 3000.00,
            'line_total' => 6000.00,
        ]);

        // Delete price master
        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.price-masters.destroy', $priceMaster));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('price_masters', [
            'id' => $priceMaster->id,
        ]);

        // Verify order and item remain completely intact with original stored price
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'order_no' => 'ORD-TEST-999']);
        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'unit_price' => 3000.00,
            'quantity' => 2,
        ]);
    }

    public function test_unauthorized_user_cannot_delete_price_master(): void
    {
        $regularUser = User::factory()->create();
        $staffRole = Role::firstOrCreate(['name' => 'warehouse-staff', 'guard_name' => 'web']);
        $regularUser->assignRole($staffRole);

        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'rate' => 25000.00,
            'min_qty' => 1,
        ]);

        $response = $this->actingAs($regularUser)
            ->deleteJson(route('masters.price-masters.destroy', $priceMaster));

        $response->assertStatus(403);

        $this->assertDatabaseHas('price_masters', [
            'id' => $priceMaster->id,
        ]);
    }

    public function test_user_cannot_delete_price_master_belonging_to_another_company(): void
    {
        $otherCompany = Company::create([
            'name' => 'Competitor Corp',
            'code' => 'COMP-01',
            'is_active' => true,
        ]);

        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'masters.manage', 'guard_name' => 'web']);
        $managerRole->givePermissionTo('masters.manage');

        $restrictedUser = User::factory()->create([
            'company_id' => $otherCompany->id,
        ]);
        $restrictedUser->assignRole($managerRole);

        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id, // belongs to $this->company
            'uom_id' => $this->uom->id,
            'rate' => 7500.00,
            'min_qty' => 1,
        ]);

        $response = $this->actingAs($restrictedUser)
            ->deleteJson(route('masters.price-masters.destroy', $priceMaster));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized: This price record belongs to another company.',
        ]);

        $this->assertDatabaseHas('price_masters', [
            'id' => $priceMaster->id,
        ]);
    }

    public function test_search_and_filters_work_on_price_masters_listing(): void
    {
        $secondCustomerType = CustomerType::create([
            'name' => 'Retail Walk-In',
            'code' => 'CT-RETAIL-01',
            'is_active' => true,
        ]);

        $secondProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Battery Pack 48V',
            'sku' => 'BAT-48V-001',
            'serial_no' => 'SN-BAT-001',
            'base_uom_id' => $this->uom->id,
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        $price1 = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'rate' => 45000.00,
            'min_qty' => 1,
        ]);

        $price2 = PriceMaster::create([
            'customer_type_id' => $secondCustomerType->id,
            'product_id' => $secondProduct->id,
            'uom_id' => $this->uom->id,
            'rate' => 85000.00,
            'min_qty' => 1,
        ]);

        // Filter by product_id
        $responseProductFilter = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.index', ['product_id' => $this->product->id]));
        $responseProductFilter->assertStatus(200);
        $responseProductFilter->assertSee('45,000.00');
        $responseProductFilter->assertDontSee('85,000.00');

        // Filter by customer_type_id
        $responseTypeFilter = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.index', ['customer_type_id' => $secondCustomerType->id]));
        $responseTypeFilter->assertStatus(200);
        $responseTypeFilter->assertSee('85,000.00');
        $responseTypeFilter->assertDontSee('45,000.00');

        // Filter by search term
        $responseSearch = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.index', ['search' => 'Battery']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('85,000.00');
        $responseSearch->assertDontSee('45,000.00');
    }

    public function test_price_masters_listing_renders_view_edit_and_delete_actions(): void
    {
        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'dealer_price' => 40000.00,
            'dealer_price_gst_mode' => 'exclusive',
            'retail_price' => 45000.00,
            'retail_price_gst_mode' => 'inclusive',
            'min_qty' => 5,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('masters.price-masters.index'));

        $response->assertStatus(200);
        $response->assertSee('View');
        $response->assertSee('Edit');
        $response->assertSee('Delete');
        $response->assertSee('data-view-price="' . $priceMaster->id . '"', false);
        $response->assertSee('data-edit-price="' . $priceMaster->id . '"', false);
        $response->assertSee('data-delete-price="' . $priceMaster->id . '"', false);
        $response->assertSee('Price List Product Details');
        $response->assertSee('Edit Product Pricing');
        $response->assertSee('Delete Price Record');
    }

    public function test_show_price_master_json_returns_complete_product_master_details_and_pricing_breakdown(): void
    {
        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'dealer_price' => 50000.00,
            'dealer_price_gst_mode' => 'exclusive',
            'retail_price' => 59000.00,
            'retail_price_gst_mode' => 'inclusive',
            'min_qty' => 2,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('masters.price-masters.show', $priceMaster));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $priceMaster->id,
                'product_id' => $this->product->id,
                'dealer_price_entered' => 50000.00,
                'dealer_price_gst_mode' => 'exclusive',
                'retail_price_entered' => 59000.00,
                'retail_price_gst_mode' => 'inclusive',
                'min_qty' => 2,
                'product' => [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'sku' => $this->product->sku,
                    'tax_rate' => 18.0,
                ],
            ],
        ]);
    }

    public function test_can_update_individual_product_pricing_via_json_modal(): void
    {
        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'dealer_price' => 30000.00,
            'dealer_price_gst_mode' => 'exclusive',
            'retail_price' => 35000.00,
            'retail_price_gst_mode' => 'exclusive',
            'min_qty' => 1,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('masters.price-masters.update', $priceMaster), [
                'dealer_price' => 32000.00,
                'dealer_price_gst_mode' => 'inclusive',
                'retail_price' => 38000.00,
                'retail_price_gst_mode' => 'exclusive',
                'min_qty' => 3,
                'uom_id' => $this->uom->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Price updated successfully.',
        ]);

        $priceMaster->refresh();
        $this->assertEquals(32000.00, (float) $priceMaster->dealer_price_entered);
        $this->assertEquals('inclusive', $priceMaster->dealer_price_gst_mode);
        $this->assertEquals(38000.00, (float) $priceMaster->retail_price_entered);
        $this->assertEquals('exclusive', $priceMaster->retail_price_gst_mode);
        $this->assertEquals(3, $priceMaster->min_qty);
    }

    public function test_updating_or_deleting_price_master_never_modifies_product_master_data(): void
    {
        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'dealer_price' => 20000.00,
            'min_qty' => 1,
        ]);

        $originalProductAttributes = $this->product->fresh()->getAttributes();

        // 1. Update price master
        $this->actingAs($this->adminUser)
            ->putJson(route('masters.price-masters.update', $priceMaster), [
                'dealer_price' => 25000.00,
                'uom_id' => $this->uom->id,
            ])
            ->assertStatus(200);

        $this->assertEquals($originalProductAttributes, $this->product->fresh()->getAttributes());

        // 2. Delete price master
        $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.price-masters.destroy', $priceMaster))
            ->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'sku' => $this->product->sku,
        ]);
        $this->assertEquals($originalProductAttributes, $this->product->fresh()->getAttributes());
    }

    public function test_brand_wise_individual_product_pricing_can_be_edited_independently(): void
    {
        $brand = \App\Domains\Catalog\Models\Brand::create([
            'name' => 'SolarTech',
            'code' => 'ST-001',
            'is_active' => true,
        ]);

        $this->product->update(['brand_id' => $brand->id]);

        $brandPrice = PriceMaster::create([
            'type' => PriceMaster::TYPE_BRAND,
            'brand_id' => $brand->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'dealer_price' => 42000.00,
            'dealer_price_gst_mode' => 'exclusive',
            'retail_price' => 48000.00,
            'retail_price_gst_mode' => 'exclusive',
            'min_qty' => 1,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('masters.price-masters.update', $brandPrice), [
                'dealer_price' => 41000.00,
                'dealer_price_gst_mode' => 'exclusive',
                'retail_price' => 49500.00,
                'retail_price_gst_mode' => 'inclusive',
                'min_qty' => 5,
                'uom_id' => $this->uom->id,
            ]);

        $response->assertStatus(200);
        $brandPrice->refresh();
        $this->assertEquals(41000.00, (float) $brandPrice->dealer_price_entered);
        $this->assertEquals(49500.00, (float) $brandPrice->retail_price_entered);
        $this->assertEquals('inclusive', $brandPrice->retail_price_gst_mode);
        $this->assertEquals(5, $brandPrice->min_qty);
        $this->assertEquals(PriceMaster::TYPE_BRAND, $brandPrice->type);
        $this->assertEquals($brand->id, $brandPrice->brand_id);
    }

    public function test_view_action_button_links_to_dedicated_show_route(): void
    {
        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'dealer_price' => 28000.00,
            'min_qty' => 1,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('masters.price-masters.index'));

        $response->assertStatus(200);
        $response->assertSee(route('masters.price-masters.show', $priceMaster), false);
        $response->assertSee('data-view-price="' . $priceMaster->id . '"', false);
    }

    public function test_show_page_displays_customer_type_wise_price_list_and_associated_products(): void
    {
        $secondProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Battery 100Ah',
            'sku' => 'BAT-100AH',
            'serial_no' => 'SN-BAT-100',
            'base_uom_id' => $this->uom->id,
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        $price1 = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'dealer_price' => 50000.00,
            'dealer_price_gst_mode' => 'exclusive',
            'retail_price' => 59000.00,
            'retail_price_gst_mode' => 'inclusive',
            'min_qty' => 2,
        ]);

        $price2 = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $secondProduct->id,
            'uom_id' => $this->uom->id,
            'dealer_price' => 20000.00,
            'dealer_price_gst_mode' => 'exclusive',
            'retail_price' => 25000.00,
            'retail_price_gst_mode' => 'exclusive',
            'min_qty' => 5,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.show', $price1));

        $response->assertStatus(200);
        $response->assertViewIs('masters.price-masters.show');

        // Price List Information
        $response->assertSee('#' . $price1->id);
        $response->assertSee('Customer Type Wise');
        $response->assertSee($this->customerType->name);
        $response->assertSee($this->company->name);

        // Product Information (Product Master)
        $response->assertSee($this->product->name);
        $response->assertSee($this->product->sku);
        $response->assertSee('18%');
        $response->assertSee('PCS');

        // Pricing Information
        $response->assertSee('50,000.00');
        $response->assertSee('GST Exclusive');
        $response->assertSee('59,000.00');
        $response->assertSee('GST Inclusive');

        // Associated products in this customer type price list
        $response->assertSee($secondProduct->name);
        $response->assertSee($secondProduct->sku);
        $response->assertSee('20,000.00');

        // Read-only navigation
        $response->assertSee('Back To Price List');
        $response->assertSee(route('masters.price-masters.index'), false);
        $response->assertSee('Edit Price');
        $response->assertSee(route('masters.price-masters.edit', $price1), false);
    }

    public function test_show_page_displays_brand_wise_price_list_and_all_associated_products(): void
    {
        $brand = \App\Domains\Catalog\Models\Brand::create([
            'name' => 'Luminous Power',
            'code' => 'LUM-001',
            'is_active' => true,
        ]);

        $this->product->update(['brand_id' => $brand->id]);

        $secondProduct = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'name' => 'Luminous Solar Panel 330W',
            'sku' => 'PANEL-330W',
            'serial_no' => 'SN-PANEL-330',
            'base_uom_id' => $this->uom->id,
            'tax_rate' => 12,
            'is_active' => true,
        ]);

        $brandPrice1 = PriceMaster::create([
            'type' => PriceMaster::TYPE_BRAND,
            'brand_id' => $brand->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'dealer_price' => 45000.00,
            'dealer_price_gst_mode' => 'exclusive',
            'retail_price' => 52000.00,
            'retail_price_gst_mode' => 'exclusive',
            'min_qty' => 1,
        ]);

        $brandPrice2 = PriceMaster::create([
            'type' => PriceMaster::TYPE_BRAND,
            'brand_id' => $brand->id,
            'product_id' => $secondProduct->id,
            'uom_id' => $this->uom->id,
            'dealer_price' => 12000.00,
            'dealer_price_gst_mode' => 'inclusive',
            'retail_price' => 15000.00,
            'retail_price_gst_mode' => 'inclusive',
            'min_qty' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.price-masters.show', $brandPrice1));

        $response->assertStatus(200);
        $response->assertViewIs('masters.price-masters.show');

        // Brand Wise details
        $response->assertSee('Brand Wise');
        $response->assertSee($brand->name);
        $response->assertSee('#' . $brandPrice1->id);

        // Current product details
        $response->assertSee($this->product->name);
        $response->assertSee('45,000.00');

        // Associated products table includes second product
        $response->assertSee($secondProduct->name);
        $response->assertSee('12,000.00');
    }

    public function test_unauthorized_user_cannot_view_price_master_details_page(): void
    {
        $unauthorizedUser = User::factory()->create();

        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'dealer_price' => 15000.00,
            'min_qty' => 1,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->get(route('masters.price-masters.show', $priceMaster));

        $response->assertStatus(403);
    }

    public function test_user_cannot_view_price_master_belonging_to_another_company(): void
    {
        $otherCompany = Company::create([
            'name' => 'Foreign Power Corp',
            'code' => 'FOREIGN-01',
            'is_active' => true,
        ]);

        $otherCompanyUser = User::factory()->create(['company_id' => $otherCompany->id]);
        $role = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'price-master.view', 'guard_name' => 'web']);
        $role->givePermissionTo('price-master.view');
        $otherCompanyUser->assignRole($role);

        $priceMaster = PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id, // belongs to $this->company
            'uom_id' => $this->uom->id,
            'dealer_price' => 15000.00,
            'min_qty' => 1,
        ]);

        $response = $this->actingAs($otherCompanyUser)
            ->get(route('masters.price-masters.show', $priceMaster));

        $response->assertStatus(403);
    }

    public function test_viewing_nonexistent_price_master_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(url('masters/price-masters/999999'));

        $response->assertStatus(404);
    }
}
