<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxInvoiceUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customerA;
    protected Customer $customerB;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product1;
    protected Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
            'orders.view', 'orders.create', 'orders.edit', 'orders.book', 'orders.approve',
            'purchasing.view', 'purchasing.create',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->company = Company::create([
            'name' => 'Avit Digital Systems',
            'code' => 'AVIT-01',
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $customerType = CustomerType::create([
            'name' => 'Wholesaler',
            'code' => 'WHL',
            'is_active' => true,
        ]);

        $this->customerA = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Apex Retailers',
            'code' => 'CUST-APEX',
            'phone' => '9876543210',
            'customer_type_id' => $customerType->id,
            'credit_days' => 30,
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $this->customerB = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Beacon Electronics',
            'code' => 'CUST-BEACON',
            'phone' => '9123456789',
            'customer_type_id' => $customerType->id,
            'credit_days' => 45,
            'state' => 'Gujarat',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name' => 'Pune Branch',
            'code' => 'PB-01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Depot',
            'code' => 'WH-PUNE',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product1 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Laser Meter LM-100',
            'sku' => 'LM-100',
            'serial_no' => 'PRD-LM-100',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 1200.00,
            'tax_rate' => 18.0,
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Digital Caliper DC-50',
            'sku' => 'DC-50',
            'serial_no' => 'PRD-DC-50',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 800.00,
            'tax_rate' => 12.0,
            'is_active' => true,
        ]);
    }

    protected function createSalesOrder(Customer $customer, string $status = 'approved', float $qty = 5, float $unitPrice = 1200.00): Order
    {
        $order = Order::create([
            'order_no' => 'SO-' . strtoupper(uniqid()),
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays($customer->credit_days)->toDateString(),
            'status' => $status,
            'subtotal' => $qty * $unitPrice,
            'discount_amount' => 0,
            'tax_amount' => ($qty * $unitPrice) * 0.18,
            'grand_total' => ($qty * $unitPrice) * 1.18,
            'reference_no' => 'REF-SO-TEST',
            'notes' => 'Deliver during business hours',
            'delivery_state' => $customer->state,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product1->id,
            'uom_id' => $this->uom->id,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'line_total' => $qty * $unitPrice,
        ]);

        return $order;
    }

    public function test_tax_invoice_create_renders_expected_header_fields_and_sales_order_dropdown(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));

        $response->assertStatus(200);
        // Header title
        $response->assertSee('Tax Invoice (Direct Billing)');
        // Customer searchable select (single dropdown, no duplicate search input)
        $response->assertSee('Customer / Debtor *');
        $response->assertSee('name="customer_id"', false);
        $response->assertSee('Select customer');
        $response->assertSee('Apex Retailers');
        $response->assertDontSee('Type to search customer by name or phone...');
        // Sales Order dropdown
        $response->assertSee('Sales Order (Optional)');
        $response->assertSee('name="order_id"', false);
        $response->assertSee('Select Sales Order (Direct Billing if empty)');
        $response->assertSee('Select a customer first to load sales orders');
        // Due Date & Credit Days
        $response->assertSee('Credit Days');
        $response->assertSee('30 Days');
        $response->assertSee('45 Days');
        $response->assertSee('50 Days');
        // Remarks Banner
        $response->assertSee('Remarks & Additional Delivery', false);
        // Line Items section & Add Item
        $response->assertSee('Line Items');
        $response->assertSee('+ Add Item');
        $response->assertSee('+ Quick Add Product');
        // Table columns
        $response->assertSee('Product / Description');
        $response->assertSee('Unit');
        $response->assertSee('Qty *');
        $response->assertSee('Unit Price');
        $response->assertSee('Discount');
        $response->assertSee('CGST %');
        $response->assertSee('SGST %');
        $response->assertSee('Tax Rate / IGST');
        $response->assertSee('Batch No');
        $response->assertSee('Line Total');
        // Save action
        $response->assertSee('Create Invoice');
    }

    public function test_customer_field_renders_single_searchable_dropdown_without_duplicates(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));

        $response->assertStatus(200);

        // Ensure exactly one customer select exists
        $content = $response->getContent();
        $this->assertEquals(1, substr_count($content, '<select name="customer_id"'));
        $this->assertEquals(1, substr_count($content, 'id="customer_id"'));

        // Options contain customer name, phone, and metadata for searchable select
        $this->assertStringContainsString('Apex Retailers', $content);
        $this->assertStringContainsString('Beacon Electronics', $content);
        $this->assertStringContainsString('data-code="9876543210"', $content);
        $this->assertStringContainsString('data-subtext="9876543210"', $content);

        // No redundant standalone Alpine search input or duplicate dropdown
        $this->assertStringNotContainsString('Type to search customer by name or phone...', $content);
        $this->assertStringNotContainsString('x-model="customerQuery"', $content);
        $this->assertStringNotContainsString('x-show="customerDropdownOpen"', $content);

        // Quick add customer modal trigger is present
        $this->assertStringContainsString('open-quick-add-customer', $content);
        $this->assertStringContainsString("quickAddCustomerModal('customer_id'", $content);
    }

    public function test_customer_sales_orders_api_returns_only_orders_for_selected_customer(): void
    {
        $orderA1 = $this->createSalesOrder($this->customerA, 'approved', 5);
        $orderA2 = $this->createSalesOrder($this->customerA, 'approved', 3);
        $orderB = $this->createSalesOrder($this->customerB, 'approved', 2);

        $responseA = $this->actingAs($this->user)->getJson(
            route('invoices.customer-sales-orders', $this->customerA)
        );

        $responseA->assertStatus(200);
        $orderIdsA = collect($responseA->json('orders'))->pluck('id')->all();
        $this->assertContains($orderA1->id, $orderIdsA);
        $this->assertContains($orderA2->id, $orderIdsA);
        $this->assertNotContains($orderB->id, $orderIdsA);

        $responseB = $this->actingAs($this->user)->getJson(
            route('invoices.customer-sales-orders', $this->customerB)
        );

        $responseB->assertStatus(200);
        $orderIdsB = collect($responseB->json('orders'))->pluck('id')->all();
        $this->assertContains($orderB->id, $orderIdsB);
        $this->assertNotContains($orderA1->id, $orderIdsB);
    }

    public function test_customer_sales_orders_api_excludes_non_approved_and_already_invoiced_orders(): void
    {
        $approvedOrder = $this->createSalesOrder($this->customerA, 'approved');
        $pendingOrder = $this->createSalesOrder($this->customerA, 'pending');
        $cancelledOrder = $this->createSalesOrder($this->customerA, 'cancelled');
        $convertedOrder = $this->createSalesOrder($this->customerA, 'converted');

        $response = $this->actingAs($this->user)->getJson(
            route('invoices.customer-sales-orders', $this->customerA)
        );

        $response->assertStatus(200);
        $orderIds = collect($response->json('orders'))->pluck('id')->all();

        $this->assertContains($approvedOrder->id, $orderIds);
        $this->assertNotContains($pendingOrder->id, $orderIds);
        $this->assertNotContains($cancelledOrder->id, $orderIds);
        $this->assertNotContains($convertedOrder->id, $orderIds);
    }

    public function test_sales_order_data_api_returns_order_details_and_items(): void
    {
        $order = $this->createSalesOrder($this->customerA, 'approved', 4, 1200.00);

        $response = $this->actingAs($this->user)->getJson(
            route('invoices.sales-order-data', $order)
        );

        $response->assertStatus(200);
        $data = $response->json('order');

        $this->assertEquals($order->id, $data['id']);
        $this->assertEquals($order->order_no, $data['order_no']);
        $this->assertEquals($this->customerA->id, $data['customer_id']);
        $this->assertEquals('REF-SO-TEST', $data['reference_no']);
        $this->assertCount(1, $data['items']);

        $item = $data['items'][0];
        $this->assertEquals($this->product1->id, $item['product_id']);
        $this->assertEquals(4.0, (float) $item['quantity']);
        $this->assertEquals(1200.00, (float) $item['unit_price']);
    }

    public function test_can_create_direct_tax_invoice_without_sales_order(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'invoice_date' => now()->toDateString(),
            'notes' => 'Direct invoice without SO',
            'delivery_state' => 'Maharashtra',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 1200.00,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertNull($invoice->order_id);
        $this->assertEquals($this->customerA->id, $invoice->customer_id);
        $this->assertEquals(2400.00, (float) $invoice->subtotal);
        $this->assertEquals(432.00, (float) $invoice->tax_amount); // 18% of 2400
        $this->assertEquals(2832.00, (float) $invoice->grand_total);
    }

    public function test_can_create_tax_invoice_linked_to_sales_order_and_order_is_marked_converted(): void
    {
        $order = $this->createSalesOrder($this->customerA, 'approved', 5, 1200.00);

        $payload = [
            'customer_id' => $this->customerA->id,
            'order_id' => $order->id,
            'invoice_date' => now()->toDateString(),
            'notes' => 'Invoiced from sales order',
            'delivery_state' => 'Maharashtra',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 5,
                    'unit_price' => 1200.00,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($order->id, $invoice->order_id);

        // Order status is now converted
        $order->refresh();
        $this->assertEquals('converted', $order->status);

        // Order item delivered_qty updated
        $orderItem = $order->items->first();
        $this->assertEquals(5.0, (float) $orderItem->delivered_qty);
    }

    public function test_manual_selling_price_can_be_edited_and_is_retained_in_invoice(): void
    {
        // Product 1 standard selling price is 1200.00, user manually enters 1050.00
        $payload = [
            'customer_id' => $this->customerA->id,
            'invoice_date' => now()->toDateString(),
            'delivery_state' => 'Maharashtra',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 3,
                    'unit_price' => 1050.00, // Manual price entry
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);

        $item = $invoice->items->first();
        $this->assertEquals(1050.00, (float) $item->unit_price);
        $this->assertEquals(3150.00, (float) $invoice->subtotal); // 3 * 1050
        $this->assertEquals(567.00, (float) $invoice->tax_amount); // 18% of 3150
        $this->assertEquals(3717.00, (float) $invoice->grand_total);
    }

    public function test_cannot_invoice_sales_order_belonging_to_another_customer(): void
    {
        $orderForB = $this->createSalesOrder($this->customerB, 'approved', 2);

        $payload = [
            'customer_id' => $this->customerA->id,
            'order_id' => $orderForB->id, // Mismatched customer!
            'invoice_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 1200.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasErrors('order_id');
    }

    public function test_cannot_invoice_quantity_exceeding_remaining_order_quantity(): void
    {
        $order = $this->createSalesOrder($this->customerA, 'approved', 3, 1200.00);

        $payload = [
            'customer_id' => $this->customerA->id,
            'order_id' => $order->id,
            'invoice_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 10, // Exceeds 3!
                    'unit_price' => 1200.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasErrors('items.0.quantity');
    }

    public function test_duplicate_invoicing_of_same_order_is_prevented(): void
    {
        $order = $this->createSalesOrder($this->customerA, 'approved', 2, 1200.00);

        // First invoice
        $payload = [
            'customer_id' => $this->customerA->id,
            'order_id' => $order->id,
            'invoice_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 1200.00,
                ],
            ],
        ];

        $response1 = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response1->assertSessionHasNoErrors();

        // Attempt second invoice for same order
        $response2 = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response2->assertSessionHasErrors('order_id');
    }

    public function test_customer_addresses_are_populated_on_selection(): void
    {
        $address = PartyAddress::create([
            'customer_id' => $this->customerA->id,
            'label' => 'Warehouse Hub',
            'address_line_1' => 'Plot 45, MIDC',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'is_default_billing' => true,
            'is_default_delivery' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson(
            route('masters.customers.addresses', $this->customerA)
        );

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals($address->id, $response->json('default_billing_id'));
    }

    public function test_purchase_invoice_module_remains_unaffected(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create'));

        $response->assertStatus(200);
        $response->assertSee('New Purchase Invoice');
        $response->assertSee('Supplier *');
        $response->assertSee('Purchase Order');
    }

    public function test_tax_invoice_form_renders_delivery_dispatch_and_order_details_sections(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));

        $response->assertStatus(200);

        // Section 1: Delivery Note Details
        $response->assertSee('Delivery Note Details');
        $response->assertSee('Delivery Note No(s)');
        $response->assertSee('Not Applicable');
        $response->assertSee('name="delivery_note_no"', false);
        $response->assertSee('name="delivery_note_na"', false);

        // Section 2: Dispatch Details
        $response->assertSee('Dispatch Details');
        $response->assertSee('Dispatch Doc No.');
        $response->assertSee('Dispatched Through');
        $response->assertSee('Destination');
        $response->assertSee('Carrier Name / Agent');
        $response->assertSee('Bill of Lading / LR-RR No.');
        $response->assertSee('Bill of Lading / LR-RR Date');
        $response->assertSee('Motor Vehicle No.');
        $response->assertSee('name="dispatch_doc_no"', false);
        $response->assertSee('name="dispatched_through"', false);
        $response->assertSee('name="destination"', false);
        $response->assertSee('name="carrier_name"', false);
        $response->assertSee('name="lr_rr_no"', false);
        $response->assertSee('name="lr_rr_date"', false);
        $response->assertSee('name="motor_vehicle_no"', false);

        // Section 3: Order Details
        $response->assertSee('Order Details');
        $response->assertSee('Order No(s)');
        $response->assertSee('Mode / Terms of Payment');
        $response->assertSee('Other References');
        $response->assertSee('Terms of Delivery');
        $response->assertSee('name="order_no"', false);
        $response->assertSee('name="payment_terms"', false);
        $response->assertSee('name="other_references"', false);
        $response->assertSee('name="terms_of_delivery"', false);
    }

    public function test_can_create_invoice_with_additional_delivery_dispatch_and_order_fields(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'delivery_note_no' => 'DN-2026-901, DN-2026-902',
            'delivery_note_na' => 0,
            'dispatch_doc_no' => 'DISP-8812',
            'dispatched_through' => 'Road Express Cargo',
            'destination' => 'Pune Warehouse Central',
            'carrier_name' => 'VRL Logistics Ltd',
            'lr_rr_no' => 'LR-776655',
            'lr_rr_date' => Carbon::now()->toDateString(),
            'motor_vehicle_no' => 'MH-12-DE-9988',
            'order_no' => 'CUST-PO-2026-001',
            'payment_terms' => '30 Days Net from Delivery',
            'other_references' => 'Tender-Ref-4421',
            'terms_of_delivery' => 'Ex-Warehouse / Freight Pre-paid by Seller',
            'delivery_state' => 'Maharashtra',
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 4,
                    'unit_price' => 1250.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($this->customerA->id, $invoice->customer_id);
        $this->assertEquals('DN-2026-901, DN-2026-902', $invoice->delivery_note_no);
        $this->assertFalse($invoice->delivery_note_na);
        $this->assertEquals('DISP-8812', $invoice->dispatch_doc_no);
        $this->assertEquals('Road Express Cargo', $invoice->dispatched_through);
        $this->assertEquals('Pune Warehouse Central', $invoice->destination);
        $this->assertStringContainsStringIgnoringCase('VRL Logistics Ltd', $invoice->carrier_name);
        $this->assertEquals('LR-776655', $invoice->lr_rr_no);
        $this->assertEquals(Carbon::now()->toDateString(), $invoice->lr_rr_date?->toDateString());
        $this->assertEquals('MH-12-DE-9988', $invoice->vehicle_no);
        $this->assertEquals('CUST-PO-2026-001', $invoice->order_no);
        $this->assertEquals('30 Days Net from Delivery', $invoice->payment_terms);
        $this->assertEquals('Tender-Ref-4421', $invoice->other_references);
        $this->assertEquals('Ex-Warehouse / Freight Pre-paid by Seller', $invoice->terms_of_delivery);
    }

    public function test_not_applicable_delivery_note_option_clears_delivery_note_no(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'delivery_note_no' => 'IGNORED-DN-123',
            'delivery_note_na' => 1,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 500.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertTrue($invoice->delivery_note_na);
        $this->assertNull($invoice->delivery_note_no);
    }

    public function test_selecting_sales_order_auto_populates_order_references_and_terms(): void
    {
        $order = $this->createSalesOrder($this->customerA, 'approved', 5);
        $order->update([
            'payment_terms' => '50% Advance, 50% on Delivery',
            'terms_of_delivery' => 'Door delivery by 15th',
            'reference_no' => 'QUOT-REF-998',
        ]);

        $response = $this->actingAs($this->user)->getJson(
            route('invoices.sales-order-data', $order)
        );

        $response->assertStatus(200);
        $orderData = $response->json('order');
        $this->assertEquals($order->order_no, $orderData['order_no']);
        $this->assertEquals('50% Advance, 50% on Delivery', $orderData['payment_terms']);
        $this->assertEquals('Door delivery by 15th', $orderData['terms_of_delivery']);
        $this->assertEquals('QUOT-REF-998', $orderData['other_references']);
    }

    public function test_can_edit_invoice_and_fields_remain_populated(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-TEST-EDIT-001',
            'customer_id' => $this->customerA->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'status' => 'issued',
            'subtotal' => 1000,
            'discount_amount' => 0,
            'tax_amount' => 180,
            'grand_total' => 1180,
            'delivery_note_no' => 'DN-EXISTING-01',
            'delivery_note_na' => false,
            'dispatch_doc_no' => 'DISP-ORIGINAL',
            'dispatched_through' => 'Air Cargo',
            'destination' => 'Delhi Terminal 3',
            'carrier_name' => 'Air India Cargo',
            'lr_rr_no' => 'AWB-112233',
            'lr_rr_date' => Carbon::now()->toDateString(),
            'vehicle_no' => 'DL-01-XX-0001',
            'order_no' => 'PO-ORIGINAL-99',
            'payment_terms' => 'Advance 100%',
            'other_references' => 'REF-OLD',
            'terms_of_delivery' => 'Airport Pickup',
        ]);

        // Test Edit view renders fields
        $response = $this->actingAs($this->user)->get(route('invoices.edit', $invoice));
        $response->assertStatus(200);
        $response->assertSee('Edit Tax Invoice - INV-TEST-EDIT-001');
        $response->assertSee('DN-EXISTING-01');
        $response->assertSee('DISP-ORIGINAL');
        $response->assertSee('Delhi Terminal 3');
        $response->assertSee('Air India Cargo');
        $response->assertSee('AWB-112233');
        $response->assertSee('DL-01-XX-0001');
        $response->assertSee('PO-ORIGINAL-99');
        $response->assertSee('Advance 100%');
        $response->assertSee('REF-OLD');
        $response->assertSee('Airport Pickup');

        // Test Update route
        $updatePayload = [
            'delivery_note_no' => 'DN-UPDATED-02',
            'delivery_note_na' => 0,
            'dispatch_doc_no' => 'DISP-UPDATED',
            'dispatched_through' => 'Surface Express',
            'destination' => 'Mumbai Docks',
            'carrier_name' => 'Blue Dart',
            'lr_rr_no' => 'LR-998877',
            'lr_rr_date' => Carbon::now()->addDays(1)->toDateString(),
            'motor_vehicle_no' => 'MH-01-NEW-7788',
            'order_no' => 'PO-UPDATED-100',
            'payment_terms' => 'Net 45 Days',
            'other_references' => 'REF-UPDATED-NEW',
            'terms_of_delivery' => 'Delivery to dock warehouse',
        ];

        $updateResponse = $this->actingAs($this->user)->put(route('invoices.update', $invoice), $updatePayload);
        $updateResponse->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals('DN-UPDATED-02', $invoice->delivery_note_no);
        $this->assertEquals('DISP-UPDATED', $invoice->dispatch_doc_no);
        $this->assertEquals('Surface Express', $invoice->dispatched_through);
        $this->assertEquals('Mumbai Docks', $invoice->destination);
        $this->assertEquals('Blue Dart', $invoice->carrier_name);
        $this->assertEquals('LR-998877', $invoice->lr_rr_no);
        $this->assertEquals('MH-01-NEW-7788', $invoice->vehicle_no);
        $this->assertEquals('PO-UPDATED-100', $invoice->order_no);
        $this->assertEquals('Net 45 Days', $invoice->payment_terms);
        $this->assertEquals('REF-UPDATED-NEW', $invoice->other_references);
        $this->assertEquals('Delivery to dock warehouse', $invoice->terms_of_delivery);
    }

    public function test_optional_fields_do_not_cause_validation_errors_when_left_blank(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'delivery_note_no' => '',
            'delivery_note_na' => 0,
            'dispatch_doc_no' => '',
            'dispatched_through' => '',
            'destination' => '',
            'carrier_name' => '',
            'lr_rr_no' => '',
            'lr_rr_date' => '',
            'motor_vehicle_no' => '',
            'order_no' => '',
            'payment_terms' => '',
            'other_references' => '',
            'terms_of_delivery' => '',
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 500.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertNull($invoice->delivery_note_no);
        $this->assertNull($invoice->dispatch_doc_no);
        $this->assertNull($invoice->destination);
    }

    public function test_invoice_preview_displays_additional_fields(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-PREV-001',
            'customer_id' => $this->customerA->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'status' => 'issued',
            'subtotal' => 1000,
            'discount_amount' => 0,
            'tax_amount' => 180,
            'grand_total' => 1180,
            'delivery_note_no' => 'DN-PREVIEW-44',
            'dispatch_doc_no' => 'DISP-PREVIEW-55',
            'dispatched_through' => 'Air Express',
            'destination' => 'Bangalore Hub',
            'carrier_name' => 'DTDC Express',
            'lr_rr_no' => 'LR-PREVIEW-99',
            'lr_rr_date' => Carbon::now()->toDateString(),
            'vehicle_no' => 'KA-01-AB-1234',
            'order_no' => 'SO-PREVIEW-77',
            'payment_terms' => 'Net 60 Days',
            'terms_of_delivery' => 'Doorstep Delivery',
        ]);

        $response = $this->actingAs($this->user)->get(route('invoices.preview', $invoice));

        $response->assertStatus(200);
        $response->assertSee('DN-PREVIEW-44');
        $response->assertSee('DISP-PREVIEW-55');
        $response->assertSee('Air Express');
        $response->assertSee('Bangalore Hub');
        $response->assertSee('DTDC Express');
        $response->assertSee('LR-PREVIEW-99');
        $response->assertSee('KA-01-AB-1234');
        $response->assertSee('SO-PREVIEW-77');
        $response->assertSee('Net 60 Days');
        $response->assertSee('Doorstep Delivery');
    }

    public function test_invoice_pdf_view_displays_additional_fields(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-PDF-001',
            'customer_id' => $this->customerA->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'status' => 'issued',
            'subtotal' => 1000,
            'discount_amount' => 0,
            'tax_amount' => 180,
            'grand_total' => 1180,
            'delivery_note_no' => 'DN-PDF-44',
            'dispatch_doc_no' => 'DISP-PDF-55',
            'dispatched_through' => 'Air Express',
            'destination' => 'Bangalore Hub',
            'carrier_name' => 'DTDC Express',
            'lr_rr_no' => 'LR-PDF-99',
            'lr_rr_date' => Carbon::now()->toDateString(),
            'vehicle_no' => 'KA-01-AB-1234',
            'order_no' => 'SO-PDF-77',
            'payment_terms' => 'Net 60 Days',
            'terms_of_delivery' => 'Doorstep Delivery',
        ]);

        $rendered = view('sales.invoices.pdf', [
            'invoice' => $invoice,
            'company' => $this->company,
            'invoiceQrDataUri' => '',
            'upiQrDataUri' => '',
        ])->render();

        $this->assertStringContainsString('DN-PDF-44', $rendered);
        $this->assertStringContainsString('DISP-PDF-55', $rendered);
        $this->assertStringContainsString('Air Express', $rendered);
        $this->assertStringContainsString('Bangalore Hub', $rendered);
        $this->assertStringContainsString('DTDC Express', $rendered);
        $this->assertStringContainsString('LR-PDF-99', $rendered);
        $this->assertStringContainsString('KA-01-AB-1234', $rendered);
        $this->assertStringContainsString('SO-PDF-77', $rendered);
        $this->assertStringContainsString('Net 60 Days', $rendered);
        $this->assertStringContainsString('Doorstep Delivery', $rendered);
    }

    public function test_multiline_terms_of_delivery_preserves_formatting_and_renders_in_views(): void
    {
        $multilineTerms = "KIN DLY DELIVER THE GOOD WITHIN 3 DAYS\nOR TIMING B/W\n10 AM TO 6 AM";

        $payload = [
            'customer_id' => $this->customerA->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'terms_of_delivery' => $multilineTerms,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 1000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($multilineTerms, $invoice->terms_of_delivery);

        // Verify Show View preserves multiline
        $showResponse = $this->actingAs($this->user)->get(route('invoices.show', $invoice));
        $showResponse->assertStatus(200);
        $showResponse->assertSee("KIN DLY DELIVER THE GOOD WITHIN 3 DAYS");
        $showResponse->assertSee("OR TIMING B/W");
        $showResponse->assertSee("10 AM TO 6 AM");

        // Verify Preview View preserves multiline
        $prevResponse = $this->actingAs($this->user)->get(route('invoices.preview', $invoice));
        $prevResponse->assertStatus(200);
        $prevResponse->assertSee("KIN DLY DELIVER THE GOOD WITHIN 3 DAYS");
        $prevResponse->assertSee("10 AM TO 6 AM");

        // Verify PDF View converts newlines with nl2br
        $pdfHtml = view('sales.invoices.pdf', [
            'invoice' => $invoice,
            'company' => $this->company,
            'invoiceQrDataUri' => '',
            'upiQrDataUri' => '',
        ])->render();
        $this->assertStringContainsString("KIN DLY DELIVER THE GOOD WITHIN 3 DAYS<br />\nOR TIMING B/W<br />\n10 AM TO 6 AM", $pdfHtml);
    }
}
