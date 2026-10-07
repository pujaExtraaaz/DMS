<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use App\Support\IndianCities;
use App\Support\IndianStates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyMultipleAddressTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CustomerType $customerType;
    protected Company $company;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->customerType = CustomerType::create([
            'name' => 'General',
            'code' => 'GEN',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TC01',
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $branch = \App\Domains\Organization\Models\Branch::create([
            'name' => 'Main Branch',
            'code' => 'MB01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'MW01',
            'branch_id' => $branch->id,
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TP001',
            'serial_no' => 'TP001-SN',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 100,
            'selling_price' => 150,
            'tax_rate' => 18,
            'is_active' => true,
        ]);
    }

    public function test_party_create_form_renders_multiple_address_repeater(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.customers.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('+ Add Address', $content);
        $this->assertStringContainsString('Address Label', $content);
        $this->assertStringContainsString('Default Billing', $content);
        $this->assertStringContainsString('Default Delivery', $content);
        $this->assertStringContainsString('addresses[', $content);
    }

    public function test_can_create_party_with_multiple_addresses(): void
    {
        $payload = [
            'name' => 'Multi Address Corp',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543210',
            'is_active' => true,
            'addresses' => [
                [
                    'label' => 'Corporate HQ',
                    'contact_person' => 'Rajesh Sharma',
                    'contact_phone' => '9820098200',
                    'address_line_1' => 'Nariman Point, Marine Drive',
                    'address_line_2' => 'Tower A, 12th Floor',
                    'state' => 'Maharashtra',
                    'city' => 'Mumbai',
                    'pincode' => '400021',
                    'type' => 'billing',
                    'is_default_billing' => 1,
                    'is_default_delivery' => 0,
                ],
                [
                    'label' => 'Bhiwandi Depot',
                    'contact_person' => 'Amit Patil',
                    'contact_phone' => '9830098300',
                    'address_line_1' => 'Gala No 5, Mankoli Naka',
                    'address_line_2' => 'Bhiwandi Bypass',
                    'state' => 'Maharashtra',
                    'city' => 'Bhiwandi',
                    'pincode' => '421302',
                    'type' => 'delivery',
                    'is_default_billing' => 0,
                    'is_default_delivery' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('masters.customers.store'), $payload);

        $response->assertSessionHasNoErrors();
        $customer = Customer::where('name', 'Multi Address Corp')->first();
        $this->assertNotNull($customer);

        $addresses = $customer->addresses()->get();
        $this->assertCount(2, $addresses);

        $billing = $customer->defaultBillingAddress();
        $this->assertNotNull($billing);
        $this->assertEquals('Corporate HQ', $billing->label);
        $this->assertEquals('Mumbai', $billing->city);

        $delivery = $customer->defaultDeliveryAddress();
        $this->assertNotNull($delivery);
        $this->assertEquals('Bhiwandi Depot', $delivery->label);
        $this->assertEquals('Bhiwandi', $delivery->city);

        // Verify customer legacy fields synced with default billing
        $this->assertEquals('Maharashtra', $customer->state);
        $this->assertEquals('400021', $customer->pincode);
        $this->assertStringContainsString('Nariman Point', $customer->address);
    }

    public function test_city_state_validation_rejects_mismatched_city_and_state(): void
    {
        $payload = [
            'name' => 'Mismatch Testing Pvt Ltd',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543210',
            'is_active' => true,
            'addresses' => [
                [
                    'label' => 'Office',
                    'address_line_1' => 'Main Road',
                    'state' => 'Maharashtra',
                    'city' => 'Ahmedabad', // Ahmedabad is in Gujarat, not Maharashtra!
                    'pincode' => '400001',
                    'type' => 'billing',
                    'is_default_billing' => 1,
                    'is_default_delivery' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('masters.customers.store'), $payload);

        $response->assertSessionHasErrors('addresses.0.city');
        $this->assertDatabaseMissing('customers', ['name' => 'Mismatch Testing Pvt Ltd']);
    }

    public function test_multiple_default_billing_addresses_are_rejected(): void
    {
        $payload = [
            'name' => 'Duplicate Default Corp',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543210',
            'is_active' => true,
            'addresses' => [
                [
                    'label' => 'Office 1',
                    'address_line_1' => 'Road 1',
                    'state' => 'Maharashtra',
                    'city' => 'Mumbai',
                    'pincode' => '400001',
                    'type' => 'billing',
                    'is_default_billing' => 1,
                    'is_default_delivery' => 0,
                ],
                [
                    'label' => 'Office 2',
                    'address_line_1' => 'Road 2',
                    'state' => 'Maharashtra',
                    'city' => 'Pune',
                    'pincode' => '411001',
                    'type' => 'billing',
                    'is_default_billing' => 1, // Invalid: two default billing addresses
                    'is_default_delivery' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('masters.customers.store'), $payload);

        $response->assertSessionHasErrors('addresses');
        $this->assertDatabaseMissing('customers', ['name' => 'Duplicate Default Corp']);
    }

    public function test_addresses_json_endpoint_returns_active_addresses_and_defaults(): void
    {
        $customer = Customer::create([
            'name' => 'Endpoint Test Client',
            'code' => 'CUST-EP-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        $addr1 = PartyAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Billing Office',
            'address_line_1' => 'Line 1',
            'city' => 'Nagpur',
            'state' => 'Maharashtra',
            'pincode' => '440001',
            'type' => 'billing',
            'is_default_billing' => true,
            'is_default_delivery' => false,
            'is_active' => true,
        ]);

        $addr2 = PartyAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Delivery Hub',
            'address_line_1' => 'Line 2',
            'city' => 'Nashik',
            'state' => 'Maharashtra',
            'pincode' => '422001',
            'type' => 'delivery',
            'is_default_billing' => false,
            'is_default_delivery' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('masters.customers.addresses', $customer));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'default_billing_id' => $addr1->id,
            'default_delivery_id' => $addr2->id,
        ]);

        $data = $response->json();
        $this->assertCount(2, $data['addresses']);
    }

    public function test_unreferenced_address_is_deleted_on_party_update(): void
    {
        $customer = Customer::create([
            'name' => 'Deletable Address Party',
            'code' => 'CUST-DEL-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        $addr1 = PartyAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Keep Address',
            'address_line_1' => 'Keep Line',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'type' => 'both',
            'is_default_billing' => true,
            'is_default_delivery' => true,
            'is_active' => true,
        ]);

        $addr2 = PartyAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Remove Address',
            'address_line_1' => 'Remove Line',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'type' => 'delivery',
            'is_default_billing' => false,
            'is_default_delivery' => false,
            'is_active' => true,
        ]);

        // Update party with only addr1
        $updatePayload = [
            'name' => 'Deletable Address Party',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543210',
            'is_active' => true,
            'addresses' => [
                [
                    'id' => $addr1->id,
                    'label' => 'Keep Address Updated',
                    'address_line_1' => 'Keep Line',
                    'city' => 'Mumbai',
                    'state' => 'Maharashtra',
                    'pincode' => '400001',
                    'type' => 'both',
                    'is_default_billing' => 1,
                    'is_default_delivery' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->put(route('masters.customers.update', $customer), $updatePayload);
        $response->assertSessionHasNoErrors();

        // addr2 was never referenced in any transaction, so it should be deleted
        $this->assertDatabaseMissing('party_addresses', ['id' => $addr2->id]);
        $this->assertDatabaseHas('party_addresses', ['id' => $addr1->id, 'label' => 'Keep Address Updated']);
    }

    public function test_referenced_address_is_soft_deactivated_on_party_removal(): void
    {
        $customer = Customer::create([
            'name' => 'Referenced Address Party',
            'code' => 'CUST-REF-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        $addr1 = PartyAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Old Invoiced Address',
            'address_line_1' => 'Station Road',
            'city' => 'Thane',
            'state' => 'Maharashtra',
            'pincode' => '400601',
            'type' => 'billing',
            'is_default_billing' => true,
            'is_default_delivery' => false,
            'is_active' => true,
        ]);

        // Reference addr1 in an invoice
        $invoice = Invoice::create([
            'invoice_no' => 'INV-TEST-001',
            'customer_id' => $customer->id,
            'billing_address_id' => $addr1->id,
            'billing_address' => $addr1->formatSnapshot(),
            'shipping_address' => $addr1->formatSnapshot(),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 1000,
            'tax_amount' => 180,
            'grand_total' => 1180,
            'company_id' => $this->company->id,
        ]);

        $this->assertTrue($addr1->isReferencedInTransactions());

        // Now update customer with a new address instead of addr1
        $updatePayload = [
            'name' => 'Referenced Address Party',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543210',
            'is_active' => true,
            'addresses' => [
                [
                    'label' => 'Brand New Address',
                    'address_line_1' => 'Ghodbunder Road',
                    'city' => 'Thane',
                    'state' => 'Maharashtra',
                    'pincode' => '400607',
                    'type' => 'both',
                    'is_default_billing' => 1,
                    'is_default_delivery' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->put(route('masters.customers.update', $customer), $updatePayload);
        $response->assertSessionHasNoErrors();

        // addr1 must NOT be deleted because it is referenced in an invoice; it must be deactivated!
        $this->assertDatabaseHas('party_addresses', [
            'id' => $addr1->id,
            'is_active' => false,
        ]);
    }

    public function test_purchase_order_stores_address_ids_and_immutable_snapshots(): void
    {
        $supplier = Customer::create([
            'name' => 'Supplier Address Test',
            'code' => 'SUP-ADDR-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        $bAddr = PartyAddress::create([
            'customer_id' => $supplier->id,
            'label' => 'Vendor Head Office',
            'address_line_1' => 'Plot 42, MIDC',
            'city' => 'Aurangabad',
            'state' => 'Maharashtra',
            'pincode' => '431001',
            'type' => 'billing',
            'is_default_billing' => true,
            'is_default_delivery' => false,
            'is_active' => true,
        ]);

        $dAddr = PartyAddress::create([
            'customer_id' => $supplier->id,
            'label' => 'Vendor Dispatch Yard',
            'address_line_1' => 'Yard 12, Waluj',
            'city' => 'Aurangabad',
            'state' => 'Maharashtra',
            'pincode' => '431136',
            'type' => 'delivery',
            'is_default_billing' => false,
            'is_default_delivery' => true,
            'is_active' => true,
        ]);

        $payload = [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'billing_address_id' => $bAddr->id,
            'shipping_address_id' => $dAddr->id,
            'billing_address' => 'Custom Frozen Billing Snapshot',
            'shipping_address' => 'Custom Frozen Delivery Snapshot',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 10,
                    'unit_cost' => 100,
                    'tax_percent' => 18,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $po = PurchaseOrder::where('supplier_id', $supplier->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals($bAddr->id, $po->billing_address_id);
        $this->assertEquals($dAddr->id, $po->shipping_address_id);
        $this->assertEquals('Custom Frozen Billing Snapshot', $po->billing_address);
        $this->assertEquals('Custom Frozen Delivery Snapshot', $po->shipping_address);
    }

    public function test_purchase_order_rejects_address_belonging_to_another_supplier(): void
    {
        $supplier1 = Customer::create([
            'name' => 'Supplier Alpha',
            'code' => 'SUP-ALP-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        $supplier2 = Customer::create([
            'name' => 'Supplier Beta',
            'code' => 'SUP-BET-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543211',
            'is_active' => true,
        ]);

        $betaAddr = PartyAddress::create([
            'customer_id' => $supplier2->id,
            'label' => 'Beta Office',
            'address_line_1' => 'Plot 99',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'type' => 'both',
            'is_active' => true,
        ]);

        $payload = [
            'supplier_id' => $supplier1->id,
            'warehouse_id' => $this->warehouse->id,
            'po_date' => now()->toDateString(),
            'billing_address_id' => $betaAddr->id, // Belongs to supplier2, not supplier1!
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 10,
                    'unit_cost' => 100,
                    'tax_percent' => 18,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.orders.store'), $payload);
        $response->assertSessionHasErrors('billing_address_id');
    }

    public function test_sales_order_stores_address_ids_and_immutable_snapshots(): void
    {
        $customer = Customer::create([
            'name' => 'Order Client Address Test',
            'code' => 'ORD-CL-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        $bAddr = PartyAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Client HQ',
            'address_line_1' => 'Plot 10, MIDC',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'type' => 'billing',
            'is_default_billing' => true,
            'is_default_delivery' => false,
            'is_active' => true,
        ]);

        $dAddr = PartyAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Client Delivery Yard',
            'address_line_1' => 'Yard 8, Chakan',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '410501',
            'type' => 'delivery',
            'is_default_billing' => false,
            'is_default_delivery' => true,
            'is_active' => true,
        ]);

        \App\Domains\Master\Models\PriceMaster::create([
            'customer_type_id' => $this->customerType->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'rate' => 150,
        ]);

        foreach (['orders.view', 'orders.create', 'orders.book'] as $perm) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $this->user->givePermissionTo($perm);
        }

        $payload = [
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'billing_address_id' => $bAddr->id,
            'shipping_address_id' => $dAddr->id,
            'billing_address' => 'Frozen Order Billing Snapshot',
            'shipping_address' => 'Frozen Order Delivery Snapshot',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 5,
                    'unit_price' => 150,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('orders.store'), $payload);
        $response->assertSessionHasNoErrors();

        $order = \App\Domains\Order\Models\Order::where('customer_id', $customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals($bAddr->id, $order->billing_address_id);
        $this->assertEquals($dAddr->id, $order->shipping_address_id);
        $this->assertEquals('Frozen Order Billing Snapshot', $order->billing_address);
        $this->assertEquals('Frozen Order Delivery Snapshot', $order->shipping_address);
    }

    public function test_sales_invoice_rejects_address_belonging_to_another_customer(): void
    {
        $customer1 = Customer::create([
            'name' => 'Invoice Customer Alpha',
            'code' => 'INV-ALP-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        $customer2 = Customer::create([
            'name' => 'Invoice Customer Beta',
            'code' => 'INV-BET-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543211',
            'is_active' => true,
        ]);

        $betaAddr = PartyAddress::create([
            'customer_id' => $customer2->id,
            'label' => 'Beta Office',
            'address_line_1' => 'Plot 99',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'type' => 'both',
            'is_active' => true,
        ]);

        foreach (['invoices.view', 'invoices.create'] as $perm) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $this->user->givePermissionTo($perm);
        }

        $payload = [
            'customer_id' => $customer1->id,
            'invoice_date' => now()->toDateString(),
            'billing_address_id' => $betaAddr->id, // Belongs to customer2!
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 150,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertSessionHasErrors('billing_address_id');
    }

    public function test_indian_states_all_alphabetical_is_sorted_a_to_z(): void
    {
        $states = IndianStates::allAlphabetical();
        $stateNames = array_values($states);

        $sortedNames = $stateNames;
        natcasesort($sortedNames);
        $sortedNames = array_values($sortedNames);

        $this->assertSame($sortedNames, $stateNames);
        $this->assertSame('Maharashtra', $states['27']);
        $this->assertSame('Rajasthan', $states['08']);

        $options = IndianStates::options();
        $this->assertNotEmpty($options);
        $this->assertSame('35', $options[0]['code']);
        $this->assertSame('Andaman and Nicobar Islands', $options[0]['name']);
    }

    public function test_cities_api_returns_alphabetical_cities_for_state(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('masters.cities', ['state' => 'Maharashtra']));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'state' => 'Maharashtra',
        ]);

        $cities = $response->json('cities');
        $this->assertIsArray($cities);
        $this->assertContains('Mumbai', $cities);
        $this->assertContains('Pune', $cities);

        $sortedCities = $cities;
        natcasesort($sortedCities);
        $this->assertSame(array_values($sortedCities), $cities);

        // Rajasthan check
        $rajResponse = $this->actingAs($this->user)->getJson(route('masters.cities', ['state' => 'Rajasthan']));
        $rajResponse->assertStatus(200);
        $rajCities = $rajResponse->json('cities');
        $this->assertContains('Jaipur', $rajCities);
        $this->assertNotContains('Mumbai', $rajCities);

        $sortedRaj = $rajCities;
        natcasesort($sortedRaj);
        $this->assertSame(array_values($sortedRaj), $rajCities);
    }

    public function test_party_create_form_provides_alphabetical_states_and_cities(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.customers.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('stateDropdown(addr', $content);
        $this->assertStringContainsString('cityDropdown(addr', $content);
        $this->assertStringContainsString('Select State / UT', $content);
        $this->assertStringContainsString('Select State First', $content);
        $this->assertStringContainsString('No cities found', $content);
    }
}

