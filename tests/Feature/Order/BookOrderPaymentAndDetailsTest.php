<?php

namespace Tests\Feature\Order;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
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

class BookOrderPaymentAndDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $kapilDhir;
    protected User $abdulMajid;
    protected Company $company;
    protected Customer $customer;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $salespersonRole = Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sales-manager', 'guard_name' => 'web']);

        $perms = [
            'orders.create',
            'orders.edit',
            'orders.book',
            'orders.view',
            'customers.view',
        ];

        foreach ($perms as $perm) {
            $p = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $superAdminRole->givePermissionTo($p);
        }

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TC-PAY-01',
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
        $this->adminUser->assignRole($superAdminRole);

        $this->kapilDhir = User::create([
            'company_id' => $this->company->id,
            'name' => 'Kapil Dhir',
            'email' => 'kapil.dhir@dms.test',
            'password' => bcrypt('password'),
        ]);
        $this->kapilDhir->assignRole($salespersonRole);

        $this->abdulMajid = User::create([
            'company_id' => $this->company->id,
            'name' => 'Abdul Majid',
            'email' => 'abdul.majid@dms.test',
            'password' => bcrypt('password'),
        ]);
        $this->abdulMajid->assignRole($salespersonRole);

        $customerType = CustomerType::create([
            'name' => 'Retailer',
            'code' => 'RET',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'name' => 'ABC Enterprises',
            'code' => 'CUST-ABC-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'credit_days' => 30,
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Smart Inverter 5kVA',
            'sku' => 'INV-5KVA',
            'serial_no' => 'PRD-PAY-0001',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 1000,
            'tax_rate' => 0,
            'is_active' => true,
        ]);

        ProductUom::create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'conversion_factor' => 1,
            'is_base' => true,
            'selling_price' => 1000,
        ]);
    }

    public function test_book_order_create_view_renders_all_required_sections_and_fields(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('orders.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Sections
        $this->assertStringContainsString('Order Details', $content);
        $this->assertStringContainsString('Payment Terms &amp; Advance Details', $content);
        $this->assertStringContainsString('Sales &amp; Reference Details', $content);
        $this->assertStringContainsString('Delivery Details', $content);
        $this->assertStringContainsString('Supervisor / Caretaker Details', $content);
        $this->assertStringContainsString('Architect Details', $content);

        // 2. Specific fields
        $this->assertStringContainsString('name="payment_terms"', $content);
        $this->assertStringContainsString('name="advance_amount"', $content);
        $this->assertStringContainsString('name="advance_percentage"', $content);
        $this->assertStringContainsString('name="balance_amount"', $content);
        $this->assertStringContainsString('name="salesperson_id"', $content);
        $this->assertStringContainsString('name="reference_no"', $content);
        $this->assertStringContainsString('name="terms_of_delivery"', $content);
        $this->assertStringContainsString('name="caretaker_phone"', $content);
        $this->assertStringContainsString('name="architect_name"', $content);
        $this->assertStringContainsString('name="architect_phone"', $content);

        // 3. Salesperson options
        $this->assertStringContainsString('Kapil Dhir', $content);
        $this->assertStringContainsString('Abdul Majid', $content);

        // 4. Delivery terms options
        $this->assertStringContainsString('Freight Inclusive', $content);
        $this->assertStringContainsString('Freight Exclusive', $content);
    }

    public function test_create_order_with_all_new_fields_populated_and_balance_calculated(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-02',
            'due_date' => '2026-12-02',
            'fulfilment_mode' => 'van',
            'salesperson_id' => $this->kapilDhir->id,
            'payment_terms' => '50% advance against Proforma Invoice, 30% after delivery, and 20% on completion',
            'advance_amount' => 400.00,
            'advance_percentage' => 40.00,
            'reference_no' => 'ENQ-2026-9921',
            'terms_of_delivery' => 'freight_inclusive',
            'caretaker_phone' => '+91 98765 43210',
            'architect_name' => 'Studio Design Arc / Rajesh Verma',
            'architect_phone' => '+91 98111 22334',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), $payload);

        $response->assertSessionHasNoErrors();
        $order = Order::latest('id')->firstOrFail();

        $this->assertEquals($this->customer->id, $order->customer_id);
        $this->assertEquals($this->kapilDhir->id, $order->salesperson_id);
        $this->assertEquals('Kapil Dhir', $order->salesperson_name);
        $this->assertEquals('50% advance against Proforma Invoice, 30% after delivery, and 20% on completion', $order->payment_terms);
        $this->assertEquals(400.00, (float) $order->advance_amount);
        $this->assertEquals(40.00, (float) $order->advance_percentage);
        $this->assertEquals(600.00, (float) $order->balance_amount); // 1000 - 400 = 600
        $this->assertEquals('ENQ-2026-9921', $order->reference_no);
        $this->assertEquals('freight_inclusive', $order->terms_of_delivery);
        $this->assertEquals('+91 98765 43210', $order->caretaker_phone);
        $this->assertEquals('Studio Design Arc / Rajesh Verma', $order->architect_name);
        $this->assertEquals('+91 98111 22334', $order->architect_phone);
    }

    public function test_create_order_with_optional_fields_blank(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-02',
            'salesperson_id' => $this->abdulMajid->id,
            'payment_terms' => null,
            'advance_amount' => 0,
            'advance_percentage' => null,
            'reference_no' => null,
            'terms_of_delivery' => null,
            'caretaker_phone' => null,
            'architect_name' => null,
            'architect_phone' => null,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 1000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), $payload);

        $response->assertSessionHasNoErrors();
        $order = Order::latest('id')->firstOrFail();

        $this->assertEquals($this->abdulMajid->id, $order->salesperson_id);
        $this->assertEquals('Abdul Majid', $order->salesperson_name);
        $this->assertNull($order->payment_terms);
        $this->assertEquals(0, (float) $order->advance_amount);
        $this->assertEquals(2000.00, (float) $order->balance_amount); // 2000 - 0 = 2000
        $this->assertNull($order->reference_no);
        $this->assertNull($order->terms_of_delivery);
        $this->assertNull($order->caretaker_phone);
        $this->assertNull($order->architect_name);
        $this->assertNull($order->architect_phone);
    }

    public function test_advance_amount_cannot_exceed_grand_total_or_be_negative(): void
    {
        // 1. Advance greater than grand total
        $payloadExcess = [
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-02',
            'salesperson_id' => $this->kapilDhir->id,
            'advance_amount' => 1500, // Total is 1000
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('orders.store'), $payloadExcess);
        $response->assertSessionHasErrors('advance_amount');

        // 2. Advance is negative
        $payloadNegative = [
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-02',
            'salesperson_id' => $this->kapilDhir->id,
            'advance_amount' => -100,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
        ];

        $responseNeg = $this->actingAs($this->adminUser)->post(route('orders.store'), $payloadNegative);
        $responseNeg->assertSessionHasErrors('advance_amount');
    }

    public function test_edit_order_displays_previously_saved_fields_and_updates_successfully(): void
    {
        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-2026-TEST-01',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->kapilDhir->id,
            'salesperson_name' => 'Kapil Dhir',
            'order_date' => '2026-11-02',
            'status' => 'pending',
            'payment_terms' => 'Initial 30% advance',
            'advance_amount' => 300,
            'advance_percentage' => 30,
            'balance_amount' => 700,
            'reference_no' => 'REF-OLD-11',
            'terms_of_delivery' => 'freight_inclusive',
            'caretaker_phone' => '9876543210',
            'architect_name' => 'Old Architect',
            'architect_phone' => '9812345678',
            'subtotal' => 1000,
            'grand_total' => 1000,
        ]);

        // Verify edit view renders previously saved values
        $response = $this->actingAs($this->adminUser)->get(route('orders.edit', $order));
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Initial 30% advance', $content);
        $this->assertStringContainsString('REF-OLD-11', $content);
        $this->assertStringContainsString('freight_inclusive', $content);
        $this->assertStringContainsString('9876543210', $content);
        $this->assertStringContainsString('Old Architect', $content);
        $this->assertStringContainsString('9812345678', $content);

        // Now update the order with Abdul Majid, Freight Exclusive, and new advance
        $updatePayload = [
            'customer_id' => $this->customer->id,
            'order_date' => '2026-11-02',
            'salesperson_id' => $this->abdulMajid->id,
            'payment_terms' => 'Revised 60% advance agreement',
            'advance_amount' => 600,
            'advance_percentage' => 60,
            'reference_no' => 'REF-NEW-22',
            'terms_of_delivery' => 'freight_exclusive',
            'caretaker_phone' => '+91 99999 88888',
            'architect_name' => 'New Architect & Co',
            'architect_phone' => '+91 88888 77777',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
        ];

        $updateResponse = $this->actingAs($this->adminUser)->put(route('orders.update', $order), $updatePayload);
        $updateResponse->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertEquals($this->abdulMajid->id, $order->salesperson_id);
        $this->assertEquals('Abdul Majid', $order->salesperson_name);
        $this->assertEquals('Revised 60% advance agreement', $order->payment_terms);
        $this->assertEquals(600.00, (float) $order->advance_amount);
        $this->assertEquals(400.00, (float) $order->balance_amount);
        $this->assertEquals('REF-NEW-22', $order->reference_no);
        $this->assertEquals('freight_exclusive', $order->terms_of_delivery);
        $this->assertEquals('+91 99999 88888', $order->caretaker_phone);
        $this->assertEquals('New Architect & Co', $order->architect_name);
        $this->assertEquals('+91 88888 77777', $order->architect_phone);
    }

    public function test_show_and_pdf_views_render_payment_terms_and_delivery_details(): void
    {
        $order = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-2026-VIEW-01',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->kapilDhir->id,
            'salesperson_name' => 'Kapil Dhir',
            'order_date' => '2026-11-02',
            'status' => 'pending',
            'payment_terms' => '50% advance against Proforma Invoice',
            'advance_amount' => 500,
            'advance_percentage' => 50,
            'balance_amount' => 500,
            'reference_no' => 'PO-CUST-8877',
            'terms_of_delivery' => 'freight_inclusive',
            'caretaker_phone' => '+91 98765 00000',
            'architect_name' => 'Apex Architecture',
            'architect_phone' => '+91 98111 00000',
            'subtotal' => 1000,
            'grand_total' => 1000,
        ]);

        // 1. Show view
        $showResponse = $this->actingAs($this->adminUser)->get(route('orders.show', $order));
        $showResponse->assertStatus(200);
        $showContent = $showResponse->getContent();

        $this->assertStringContainsString('50% advance against Proforma Invoice', $showContent);
        $this->assertStringContainsString('PO-CUST-8877', $showContent);
        $this->assertStringContainsString('Freight Inclusive', $showContent);
        $this->assertStringContainsString('Kapil Dhir', $showContent);
        $this->assertStringContainsString('Apex Architecture', $showContent);
        $this->assertStringContainsString('Advance Received', $showContent);
        $this->assertStringContainsString('Balance Pending', $showContent);

        // 2. PDF view
        $pdfResponse = $this->actingAs($this->adminUser)->get(route('orders.pdf', ['q' => $order->order_no]));
        $pdfResponse->assertStatus(200);
    }

    public function test_historical_orders_without_new_values_render_cleanly_without_errors(): void
    {
        $historicalOrder = Order::create([
            'company_id' => $this->company->id,
            'order_no' => 'ORD-HISTORICAL-01',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->adminUser->id,
            'order_date' => '2026-08-01',
            'status' => 'pending',
            'subtotal' => 500,
            'grand_total' => 500,
            'payment_terms' => null,
            'advance_amount' => 0,
            'balance_amount' => null,
            'advance_percentage' => null,
            'reference_no' => null,
            'terms_of_delivery' => null,
            'caretaker_phone' => null,
            'architect_name' => null,
            'architect_phone' => null,
        ]);

        // Show page
        $showResponse = $this->actingAs($this->adminUser)->get(route('orders.show', $historicalOrder));
        $showResponse->assertStatus(200);

        // Edit page
        $editResponse = $this->actingAs($this->adminUser)->get(route('orders.edit', $historicalOrder));
        $editResponse->assertStatus(200);
    }
}

