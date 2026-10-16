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
        $response->assertSee('WHOLESALER TIER 1');
        $response->assertSee('SOLAR INVERTER 5KW');
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
}
