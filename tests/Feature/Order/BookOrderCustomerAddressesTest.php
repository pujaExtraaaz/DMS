<?php

namespace Tests\Feature\Order;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookOrderCustomerAddressesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Customer $customerA;
    protected Customer $customerB;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        foreach ([
            'orders.create',
            'orders.edit',
            'orders.view',
            'orders.book',
            'customers.view',
            'customers.edit',
        ] as $p) {
            $perm = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $role->givePermissionTo($perm);
        }

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TC-ADDR-01',
            'is_active' => true,
        ]);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_closed' => false,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Admin User',
        ]);
        $this->adminUser->assignRole($role);

        $customerType = CustomerType::create([
            'name' => 'Retailer',
            'code' => 'RET-ADDR',
            'discount_percentage' => 0,
        ]);

        $this->customerA = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'name' => 'Acme Corporation A',
            'code' => 'CUST-ADDR-001',
            'credit_days' => 30,
            'phone' => '+91 9876543210',
            'gstin' => '29ABCDE1234F1Z5',
            'state' => 'Karnataka',
            'pincode' => '560001',
        ]);

        $this->customerB = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'name' => 'Beta Industries B',
            'code' => 'CUST-ADDR-002',
            'credit_days' => 45,
            'phone' => '+91 9123456780',
            'gstin' => '27XYZAB9876C1Z3',
            'state' => 'Maharashtra',
            'pincode' => '400001',
        ]);

        $this->uom = Uom::create([
            'name' => 'Box',
            'code' => 'BOX',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Widget X',
            'sku' => 'WID-X-01',
            'serial_no' => 'PRD-TEST-ADDR-01',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 500.00,
            'is_active' => true,
        ]);

        ProductUom::create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'conversion_factor' => 1,
            'is_base' => true,
            'selling_price' => 500.00,
        ]);
    }

    /** Scenario 1: Select a customer with one billing and one delivery address */
    public function test_customer_with_one_billing_and_one_delivery_address(): void
    {
        $billingAddr = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'type' => 'billing',
            'label' => 'Head Office Billing',
            'contact_person' => 'Rajesh Sharma',
            'contact_phone' => '+91 9876543210',
            'address_line_1' => '101 Corporate Park',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'gstin' => '29ABCDE1234F1Z5',
            'is_default_billing' => true,
            'is_active' => true,
        ]);

        $deliveryAddr = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'type' => 'delivery',
            'label' => 'Peenya Warehouse Delivery',
            'contact_person' => 'Suresh Kumar',
            'contact_phone' => '+91 9876543211',
            'address_line_1' => 'Plot 45, Peenya Industrial Area',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560058',
            'gstin' => '29ABCDE1234F1Z5',
            'is_default_delivery' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('masters.customers.addresses', $this->customerA));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'default_billing_id' => $billingAddr->id,
            'default_delivery_id' => $deliveryAddr->id,
        ]);

        $addresses = $response->json('addresses');
        $this->assertCount(2, $addresses);

        $bItem = collect($addresses)->firstWhere('id', $billingAddr->id);
        $this->assertNotNull($bItem);
        $this->assertEquals('Head Office Billing', $bItem['label']);
        $this->assertEquals('Rajesh Sharma', $bItem['contact_person']);
        $this->assertEquals('101 Corporate Park, Bengaluru, Karnataka - 560001', $bItem['full_address']);

        $dItem = collect($addresses)->firstWhere('id', $deliveryAddr->id);
        $this->assertNotNull($dItem);
        $this->assertEquals('Peenya Warehouse Delivery', $dItem['label']);
        $this->assertEquals('Suresh Kumar', $dItem['contact_person']);
    }

    /** Scenario 2: Select a customer with multiple addresses allowing independent selection */
    public function test_customer_with_multiple_addresses_allows_independent_selection(): void
    {
        $hq = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'type' => 'billing',
            'label' => 'HQ Finance',
            'address_line_1' => 'Tower A, Floor 5',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'is_default_billing' => true,
            'is_active' => true,
        ]);

        $wh1 = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'type' => 'delivery',
            'label' => 'North Depot',
            'address_line_1' => 'Shed 12, Airport Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560300',
            'is_active' => true,
        ]);

        $wh2 = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'type' => 'delivery',
            'label' => 'South Depot',
            'address_line_1' => 'Electronic City Phase 2',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560100',
            'is_active' => true,
        ]);

        $postData = [
            'customer_id' => $this->customerA->id,
            'order_date' => now()->toDateString(),
            'billing_address_id' => $hq->id,
            'shipping_address_id' => $wh2->id,
            'billing_address' => $hq->formatSnapshot(),
            'shipping_address' => $wh2->formatSnapshot(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 500,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), $postData);
        $response->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customerA->id,
            'billing_address_id' => $hq->id,
            'shipping_address_id' => $wh2->id,
        ]);

        $order = Order::latest('id')->first();
        $this->assertStringContainsString('HQ Finance', $order->billing_address);
        $this->assertStringContainsString('South Depot', $order->shipping_address);
    }

    /** Scenario 3: Change the customer and verify old addresses disappear and UI logic resets */
    public function test_customer_change_clears_old_addresses_and_fetches_new_customer(): void
    {
        $addrA = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'type' => 'both',
            'label' => 'Customer A Site',
            'address_line_1' => 'Street A',
            'is_active' => true,
        ]);

        $addrB = PartyAddress::create([
            'customer_id' => $this->customerB->id,
            'type' => 'both',
            'label' => 'Customer B Site',
            'address_line_1' => 'Street B',
            'is_active' => true,
        ]);

        // Customer A addresses
        $resA = $this->actingAs($this->adminUser)
            ->getJson(route('masters.customers.addresses', $this->customerA));
        $resA->assertStatus(200);
        $idsA = collect($resA->json('addresses'))->pluck('id')->all();
        $this->assertContains($addrA->id, $idsA);
        $this->assertNotContains($addrB->id, $idsA);

        // Customer B addresses
        $resB = $this->actingAs($this->adminUser)
            ->getJson(route('masters.customers.addresses', $this->customerB));
        $resB->assertStatus(200);
        $idsB = collect($resB->json('addresses'))->pluck('id')->all();
        $this->assertContains($addrB->id, $idsB);
        $this->assertNotContains($addrA->id, $idsB);

        // Verify create view contains the address reset and separation logic
        $createView = $this->actingAs($this->adminUser)->get(route('orders.create'));
        $content = $createView->getContent();
        $this->assertStringContainsString('this.partyAddresses = [];', $content);
        $this->assertStringContainsString('this.selectedBillingId = \'\';', $content);
        $this->assertStringContainsString('this.selectedShippingId = \'\';', $content);
        $this->assertStringContainsString('billingAddresses', $content);
        $this->assertStringContainsString('deliveryAddresses', $content);
    }

    /** Scenario 4: Check Same as Billing Address and verify synchronization */
    public function test_same_as_billing_checkbox_synchronizes_delivery_address(): void
    {
        $billingAddr = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'type' => 'both',
            'label' => 'Primary Combined Facility',
            'contact_person' => 'Amit Verma',
            'contact_phone' => '+91 9988776655',
            'address_line_1' => 'Industrial Area 4',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560002',
            'is_default' => true,
            'is_active' => true,
        ]);

        $snapshot = $billingAddr->formatSnapshot();

        $postData = [
            'customer_id' => $this->customerA->id,
            'order_date' => now()->toDateString(),
            'billing_address_id' => $billingAddr->id,
            'shipping_address_id' => $billingAddr->id, // Synchronized
            'billing_address' => $snapshot,
            'shipping_address' => $snapshot,           // Synchronized
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), $postData);
        $response->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customerA->id,
            'billing_address_id' => $billingAddr->id,
            'shipping_address_id' => $billingAddr->id,
            'billing_address' => $snapshot,
            'shipping_address' => $snapshot,
        ]);
    }

    /** Scenario 5: Uncheck it and select a different delivery address */
    public function test_unchecking_same_as_billing_allows_different_delivery_address(): void
    {
        $billing = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'type' => 'billing',
            'label' => 'Billing Office',
            'address_line_1' => 'MG Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'is_active' => true,
        ]);

        $delivery = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'type' => 'delivery',
            'label' => 'Receiving Dock #2',
            'address_line_1' => 'Ring Road Warehouse',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560045',
            'is_active' => true,
        ]);

        $postData = [
            'customer_id' => $this->customerA->id,
            'order_date' => now()->toDateString(),
            'billing_address_id' => $billing->id,
            'shipping_address_id' => $delivery->id,
            'billing_address' => $billing->formatSnapshot(),
            'shipping_address' => $delivery->formatSnapshot(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 3,
                    'unit_price' => 500,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), $postData);
        $response->assertRedirect();

        $order = Order::latest('id')->first();
        $this->assertEquals($billing->id, $order->billing_address_id);
        $this->assertEquals($delivery->id, $order->shipping_address_id);
        $this->assertNotEquals($order->billing_address_id, $order->shipping_address_id);
    }

    /** Scenario 6: Save the order and confirm the selected addresses persist in database */
    public function test_save_order_persists_addresses_and_snapshots_in_database(): void
    {
        $address = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'type' => 'both',
            'label' => 'Main Office',
            'contact_person' => 'Neha Gupta',
            'contact_phone' => '+91 9777788888',
            'address_line_1' => 'Sector 5, HSR Layout',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560102',
            'gstin' => '29ABCDE1234F1Z5',
            'is_default' => true,
            'is_active' => true,
        ]);

        $snapshot = $address->formatSnapshot();

        $this->actingAs($this->adminUser)->post(route('orders.store'), [
            'customer_id' => $this->customerA->id,
            'order_date' => now()->toDateString(),
            'billing_address_id' => $address->id,
            'shipping_address_id' => $address->id,
            'billing_address' => $snapshot,
            'shipping_address' => $snapshot,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ]);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customerA->id,
            'billing_address_id' => $address->id,
            'shipping_address_id' => $address->id,
            'billing_address' => $snapshot,
            'shipping_address' => $snapshot,
        ]);
    }

    /** Scenario 7: Edit the order and confirm correct addresses reload */
    public function test_edit_order_reloads_saved_addresses_and_updates_successfully(): void
    {
        $billing = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'type' => 'billing',
            'label' => 'Edit Test Billing',
            'address_line_1' => 'Brigade Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560025',
            'is_active' => true,
        ]);

        $delivery1 = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'type' => 'delivery',
            'label' => 'Edit Test Delivery Initial',
            'address_line_1' => 'Koramangala 4th Block',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560034',
            'is_active' => true,
        ]);

        $delivery2 = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'type' => 'delivery',
            'label' => 'Edit Test Delivery Changed',
            'address_line_1' => 'Whitefield EPIP Zone',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560066',
            'is_active' => true,
        ]);

        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-EDIT-ADDR-01',
            'customer_id' => $this->customerA->id,
            'billing_address_id' => $billing->id,
            'shipping_address_id' => $delivery1->id,
            'billing_address' => $billing->formatSnapshot(),
            'shipping_address' => $delivery1->formatSnapshot(),
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'subtotal' => 500,
            'grand_total' => 500,
            'advance_amount' => 0,
            'balance_amount' => 500,
        ]);

        // Verify edit view renders preselected IDs and snapshots
        $editResponse = $this->actingAs($this->adminUser)->get(route('orders.edit', $order));
        $editResponse->assertStatus(200);
        $content = $editResponse->getContent();
        $this->assertStringContainsString((string) $billing->id, $content);
        $this->assertStringContainsString((string) $delivery1->id, $content);
        $this->assertStringContainsString('Brigade Road', $content);
        $this->assertStringContainsString('Koramangala 4th Block', $content);

        // Update with changed delivery address
        $updateResponse = $this->actingAs($this->adminUser)->put(route('orders.update', $order), [
            'customer_id' => $this->customerA->id,
            'order_date' => now()->toDateString(),
            'billing_address_id' => $billing->id,
            'shipping_address_id' => $delivery2->id,
            'billing_address' => $billing->formatSnapshot(),
            'shipping_address' => $delivery2->formatSnapshot(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ]);

        $updateResponse->assertRedirect();

        $order->refresh();
        $this->assertEquals($delivery2->id, $order->shipping_address_id);
        $this->assertStringContainsStringIgnoringCase('Whitefield EPIP Zone', $order->shipping_address);
    }

    /** Scenario 8: Test a customer with no saved addresses */
    public function test_customer_with_no_saved_addresses_returns_empty_and_shows_prompt(): void
    {
        $customerNoAddress = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerA->customer_type_id,
            'name' => 'Empty Address Customer',
            'code' => 'CUST-NO-ADDR',
            'credit_days' => 15,
            'address' => null, // Explicitly no address
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('masters.customers.addresses', $customerNoAddress));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'addresses' => [],
            'default_billing_id' => null,
            'default_delivery_id' => null,
        ]);

        // Assert create view renders the empty address warning and add-address link
        $viewResponse = $this->actingAs($this->adminUser)->get(route('orders.create'));
        $content = $viewResponse->getContent();
        $this->assertStringContainsString('No saved addresses found for this customer', $content);
        $this->assertStringContainsString('+ Add Address', $content);
    }

    /** Scenario 9: Verify that an address belonging to another customer cannot be selected or submitted */
    public function test_address_belonging_to_another_customer_is_rejected(): void
    {
        $addrBelongingToCustomerB = PartyAddress::create([
            'customer_id' => $this->customerB->id,
            'type' => 'both',
            'label' => 'Customer B Exclusive Address',
            'address_line_1' => 'Nariman Point',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400021',
            'is_active' => true,
        ]);

        // Attempt to create order for Customer A using Customer B's billing address
        $resStoreBilling = $this->actingAs($this->adminUser)->post(route('orders.store'), [
            'customer_id' => $this->customerA->id,
            'order_date' => now()->toDateString(),
            'billing_address_id' => $addrBelongingToCustomerB->id,
            'shipping_address_id' => null,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ]);

        $resStoreBilling->assertSessionHasErrors(['billing_address_id']);

        // Attempt to create order for Customer A using Customer B's shipping address
        $resStoreShipping = $this->actingAs($this->adminUser)->post(route('orders.store'), [
            'customer_id' => $this->customerA->id,
            'order_date' => now()->toDateString(),
            'billing_address_id' => null,
            'shipping_address_id' => $addrBelongingToCustomerB->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ]);

        $resStoreShipping->assertSessionHasErrors(['shipping_address_id']);

        // Attempt update with foreign address
        $existingOrder = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-FOREIGN-ADDR-01',
            'customer_id' => $this->customerA->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'subtotal' => 500,
            'grand_total' => 500,
            'advance_amount' => 0,
            'balance_amount' => 500,
        ]);

        $resUpdate = $this->actingAs($this->adminUser)->put(route('orders.update', $existingOrder), [
            'customer_id' => $this->customerA->id,
            'order_date' => now()->toDateString(),
            'billing_address_id' => $addrBelongingToCustomerB->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ]);

        $resUpdate->assertSessionHasErrors(['billing_address_id']);
    }

    /** Scenario 10: Confirm existing orders and historical views show saved snapshots even if customer addresses change */
    public function test_historical_order_view_shows_order_snapshot_even_if_customer_address_changes(): void
    {
        $historicalSnapshot = "Label: Old Headquarters\nContact: Old Manager (Ph: 9000000000)\nOld Street 100, Bengaluru - 560001\nGSTIN: 29ABCDE1234F1Z5";

        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-HIST-01',
            'customer_id' => $this->customerA->id,
            'billing_address' => $historicalSnapshot,
            'shipping_address' => $historicalSnapshot,
            'order_date' => '2026-01-15',
            'status' => 'approved',
            'subtotal' => 1000,
            'grand_total' => 1000,
            'advance_amount' => 0,
            'balance_amount' => 1000,
        ]);

        // Customer's current profile address is updated later
        $this->customerA->update([
            'address' => 'New Relocated Facility, Sector 99, Outer Ring Road',
        ]);

        $showResponse = $this->actingAs($this->adminUser)->get(route('orders.show', $order));
        $showResponse->assertStatus(200);

        // Order view must show the historical snapshot recorded with the order
        $showResponse->assertSee('Old Headquarters');
        $showResponse->assertSee('Old Manager');
        $showResponse->assertSee('Old Street 100');
    }
}
