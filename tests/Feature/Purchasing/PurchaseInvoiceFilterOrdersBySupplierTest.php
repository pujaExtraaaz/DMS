<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseInvoiceFilterOrdersBySupplierTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $supplierA;
    protected Customer $supplierB;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Global Logistics Inc',
            'code' => 'GLI-01',
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
            'name' => 'Vendor',
            'code' => 'VND',
            'is_active' => true,
        ]);

        $this->supplierA = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Alpha Suppliers Corp',
            'code' => 'SUP-ALPHA',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $customerType->id,
            'credit_days' => 30,
            'phone' => '9876543211',
            'is_active' => true,
        ]);

        $this->supplierB = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Beta Trade Partners',
            'code' => 'SUP-BETA',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $customerType->id,
            'credit_days' => 45,
            'phone' => '9876543222',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name' => 'Main Branch',
            'code' => 'MB01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Hub',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Hydraulic Valve H1',
            'sku' => 'VALVE-H1',
            'serial_no' => 'SN-VALVE-H1',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 150.00,
            'selling_price' => 250.00,
            'is_active' => true,
        ]);
    }

    protected function createPurchaseOrder(Customer $supplier, string $status = 'approved', float $qty = 10, float $unitCost = 150.00, ?Company $company = null): PurchaseOrder
    {
        $comp = $company ?? $this->company;
        $order = PurchaseOrder::create([
            'company_id' => $comp->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $supplier->id,
            'po_no' => 'PO-' . strtoupper(uniqid()),
            'po_date' => '2026-10-10',
            'credit_days' => $supplier->credit_days,
            'due_date' => '2026-11-09',
            'status' => $status,
            'subtotal' => $qty * $unitCost,
            'tax_amount' => 0,
            'grand_total' => $qty * $unitCost,
            'freight_total' => 250.00,
            'created_by' => $this->user->id,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $order->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => $qty,
            'received_qty' => $status === 'closed' ? $qty : 0,
            'unit_cost' => $unitCost,
            'tax_percent' => 0,
            'line_total' => $qty * $unitCost,
        ]);

        return $order;
    }

    public function test_create_invoice_page_renders_purchase_order_dropdown_near_supplier(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create'));

        $response->assertStatus(200);
        $response->assertSee('Purchase Order');
        $response->assertSee('name="purchase_order_id"', false);
        $response->assertSee('Select Purchase Order');
        $response->assertSee('No eligible purchase orders found for this supplier');
        $response->assertSee('Select a supplier first to load purchase orders');
    }

    public function test_supplier_purchase_orders_api_returns_only_orders_belonging_to_selected_supplier(): void
    {
        $poA1 = $this->createPurchaseOrder($this->supplierA, 'approved', 10);
        $poA2 = $this->createPurchaseOrder($this->supplierA, 'approved', 5);
        $poB = $this->createPurchaseOrder($this->supplierB, 'approved', 8);

        $responseA = $this->actingAs($this->user)->getJson(
            route('purchasing.invoices.supplier-purchase-orders', $this->supplierA)
        );

        $responseA->assertStatus(200);
        $orderIdsA = collect($responseA->json('orders'))->pluck('id')->all();
        $this->assertContains($poA1->id, $orderIdsA);
        $this->assertContains($poA2->id, $orderIdsA);
        $this->assertNotContains($poB->id, $orderIdsA);

        $responseB = $this->actingAs($this->user)->getJson(
            route('purchasing.invoices.supplier-purchase-orders', $this->supplierB)
        );

        $responseB->assertStatus(200);
        $orderIdsB = collect($responseB->json('orders'))->pluck('id')->all();
        $this->assertContains($poB->id, $orderIdsB);
        $this->assertNotContains($poA1->id, $orderIdsB);
        $this->assertNotContains($poA2->id, $orderIdsB);
    }

    public function test_supplier_purchase_orders_api_filters_out_draft_pending_and_cancelled_orders(): void
    {
        $approvedPO = $this->createPurchaseOrder($this->supplierA, 'approved');
        $partiallyReceivedPO = $this->createPurchaseOrder($this->supplierA, 'partially_received');
        $closedPO = $this->createPurchaseOrder($this->supplierA, 'closed');
        $draftPO = $this->createPurchaseOrder($this->supplierA, 'draft');
        $pendingPO = $this->createPurchaseOrder($this->supplierA, 'pending_approval');
        $cancelledPO = $this->createPurchaseOrder($this->supplierA, 'cancelled');

        $response = $this->actingAs($this->user)->getJson(
            route('purchasing.invoices.supplier-purchase-orders', $this->supplierA)
        );

        $response->assertStatus(200);
        $orderIds = collect($response->json('orders'))->pluck('id')->all();

        $this->assertContains($approvedPO->id, $orderIds);
        $this->assertContains($partiallyReceivedPO->id, $orderIds);
        $this->assertContains($closedPO->id, $orderIds);

        $this->assertNotContains($draftPO->id, $orderIds);
        $this->assertNotContains($pendingPO->id, $orderIds);
        $this->assertNotContains($cancelledPO->id, $orderIds);
    }

    public function test_supplier_purchase_orders_api_filters_out_fully_invoiced_orders_but_keeps_partially_invoiced(): void
    {
        // PO 1: 10 ordered, fully invoiced
        $po1 = $this->createPurchaseOrder($this->supplierA, 'approved', 10);
        $inv1 = PurchaseInvoice::create([
            'purchase_order_id' => $po1->id,
            'supplier_id' => $this->supplierA->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-FULL-01',
            'invoice_date' => '2026-10-11',
            'status' => 'posted',
            'subtotal' => 1500.00,
            'tax_amount' => 0.00,
            'grand_total' => 1500.00,
        ]);
        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $inv1->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 10,
            'unit_cost' => 150.00,
            'line_total' => 1500.00,
        ]);

        // PO 2: 10 ordered, 4 invoiced -> 6 remaining
        $po2 = $this->createPurchaseOrder($this->supplierA, 'approved', 10);
        $inv2 = PurchaseInvoice::create([
            'purchase_order_id' => $po2->id,
            'supplier_id' => $this->supplierA->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-PART-01',
            'invoice_date' => '2026-10-11',
            'status' => 'posted',
            'subtotal' => 600.00,
            'tax_amount' => 0.00,
            'grand_total' => 600.00,
        ]);
        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $inv2->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 4,
            'unit_cost' => 150.00,
            'line_total' => 600.00,
        ]);

        // PO 3: 10 ordered, 0 invoiced
        $po3 = $this->createPurchaseOrder($this->supplierA, 'approved', 10);

        $response = $this->actingAs($this->user)->getJson(
            route('purchasing.invoices.supplier-purchase-orders', $this->supplierA)
        );

        $response->assertStatus(200);
        $orderIds = collect($response->json('orders'))->pluck('id')->all();

        // PO1 is fully invoiced -> must NOT appear
        $this->assertNotContains($po1->id, $orderIds);

        // PO2 is partially invoiced -> must appear
        $this->assertContains($po2->id, $orderIds);
        $po2Data = collect($response->json('orders'))->firstWhere('id', $po2->id);
        $this->assertEquals(6, $po2Data['remaining_qty']);

        // PO3 has no invoices -> must appear
        $this->assertContains($po3->id, $orderIds);
        $po3Data = collect($response->json('orders'))->firstWhere('id', $po3->id);
        $this->assertEquals(10, $po3Data['remaining_qty']);
    }

    public function test_supplier_purchase_orders_api_returns_empty_when_no_eligible_orders_exist(): void
    {
        $response = $this->actingAs($this->user)->getJson(
            route('purchasing.invoices.supplier-purchase-orders', $this->supplierA)
        );

        $response->assertStatus(200);
        $this->assertEmpty($response->json('orders'));
    }

    public function test_purchase_order_data_endpoint_returns_order_details_for_form_population(): void
    {
        $po = $this->createPurchaseOrder($this->supplierA, 'approved', 5, 200.00);

        $response = $this->actingAs($this->user)->getJson(
            route('purchasing.invoices.purchase-order-data', $po)
        );

        $response->assertStatus(200);
        $data = $response->json('order');

        $this->assertEquals($po->id, $data['id']);
        $this->assertEquals($this->supplierA->id, $data['supplier_id']);
        $this->assertEquals($this->warehouse->id, $data['warehouse_id']);
        $this->assertEquals(30, $data['credit_days']);
        $this->assertEquals(250.00, $data['freight_total']);
        $this->assertCount(1, $data['items']);
        $this->assertEquals($this->product->id, $data['items'][0]['product_id']);
        $this->assertEquals(5, $data['items'][0]['quantity']);
        $this->assertEquals(200.00, $data['items'][0]['unit_cost']);
    }

    public function test_store_invoice_with_valid_matching_purchase_order_succeeds_and_persists_link(): void
    {
        $po = $this->createPurchaseOrder($this->supplierA, 'approved', 5, 150.00);

        $payload = [
            'purchase_order_id' => $po->id,
            'supplier_id' => $this->supplierA->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-12',
            'purchase_receive_date' => '2026-10-10',
            'payment_order_date' => '2026-10-11',
            'supplier_invoice_number' => 'INV-MATCH-001',
            'credit_days' => 30,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_cost' => 150.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-MATCH-001')->firstOrFail();
        $this->assertEquals($po->id, $invoice->purchase_order_id);

        $showResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $invoice));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($po->po_no);
    }

    public function test_store_invoice_rejects_purchase_order_belonging_to_another_supplier(): void
    {
        $poB = $this->createPurchaseOrder($this->supplierB, 'approved', 5);

        // Try to associate Supplier B's PO while Supplier A is selected
        $payload = [
            'purchase_order_id' => $poB->id,
            'supplier_id' => $this->supplierA->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-12',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_cost' => 150.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasErrors(['purchase_order_id']);
        $this->assertDatabaseMissing('purchase_invoices', [
            'supplier_id' => $this->supplierA->id,
            'purchase_order_id' => $poB->id,
        ]);
    }

    public function test_store_invoice_rejects_ineligible_purchase_order_status(): void
    {
        $draftPO = $this->createPurchaseOrder($this->supplierA, 'draft', 5);

        $payload = [
            'purchase_order_id' => $draftPO->id,
            'supplier_id' => $this->supplierA->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-12',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_cost' => 150.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasErrors(['purchase_order_id']);
    }

    public function test_store_invoice_rejects_already_fully_invoiced_purchase_order(): void
    {
        $po = $this->createPurchaseOrder($this->supplierA, 'approved', 10);

        // Create an existing full invoice
        $existingInvoice = PurchaseInvoice::create([
            'purchase_order_id' => $po->id,
            'supplier_id' => $this->supplierA->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-EXISTING-FULL',
            'invoice_date' => '2026-10-10',
            'status' => 'posted',
            'subtotal' => 1500.00,
            'tax_amount' => 0.00,
            'grand_total' => 1500.00,
        ]);
        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $existingInvoice->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 10,
            'unit_cost' => 150.00,
            'line_total' => 1500.00,
        ]);

        // Attempt to create another invoice for the same order
        $payload = [
            'purchase_order_id' => $po->id,
            'supplier_id' => $this->supplierA->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-12',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_cost' => 150.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasErrors(['purchase_order_id']);
    }

    public function test_store_invoice_rejects_unauthorized_cross_company_purchase_order(): void
    {
        $otherCompany = Company::create([
            'name' => 'Foreign Corp',
            'code' => 'FC-99',
            'is_active' => true,
        ]);
        $crossCompanyPO = $this->createPurchaseOrder($this->supplierA, 'approved', 5, 150.00, $otherCompany);

        $payload = [
            'purchase_order_id' => $crossCompanyPO->id,
            'supplier_id' => $this->supplierA->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-12',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_cost' => 150.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasErrors(['purchase_order_id']);
    }

    public function test_direct_invoice_without_purchase_order_succeeds(): void
    {
        $payload = [
            'purchase_order_id' => null,
            'supplier_id' => $this->supplierA->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-12',
            'purchase_receive_date' => '2026-10-10',
            'payment_order_date' => '2026-10-11',
            'supplier_invoice_number' => 'INV-DIRECT-001',
            'credit_days' => 30,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                    'unit_cost' => 150.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-DIRECT-001')->firstOrFail();
        $this->assertNull($invoice->purchase_order_id);

        $showResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $invoice));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Purchase Order');
        $showResponse->assertSee('—');
    }
}
