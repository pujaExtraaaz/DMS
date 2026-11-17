<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseInvoiceCreateFormFixTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $supplier;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product1;
    protected Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Acme Test Corp',
            'code' => 'ATC-01',
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
            'name' => 'Sundry Creditor',
            'code' => 'SC-TEST',
            'is_active' => true,
        ]);

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'name' => 'Prime Supplier Logistics',
            'code' => 'SUP-PRIME',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'credit_days' => 30,
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Central Branch',
            'code' => 'CB-01',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Hub Warehouse',
            'code' => 'CHW-01',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product1 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Valve 100mm',
            'sku' => 'VALVE-100-IND',
            'serial_no' => 'VALVE-100-IND',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 1500.00,
            'mrp' => 2000.00,
            'tax_rate' => 18.00,
            'tracking_type' => 'none',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $this->product2 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Smart Pump Controller',
            'sku' => 'PUMP-CTRL-SMART',
            'serial_no' => 'PUMP-CTRL-SMART',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 4500.00,
            'mrp' => 6000.00,
            'tax_rate' => 18.00,
            'tracking_type' => 'serial',
            'is_serial_tracked' => true,
            'is_active' => true,
            'is_hidden' => false,
        ]);
    }

    public function test_create_page_renders_successfully_with_dropdowns_and_alpine_bindings(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create'));

        $response->assertOk();
        $response->assertSee('New Purchase Invoice');
        $response->assertSee('Prime Supplier Logistics');
        $response->assertSee('Central Hub Warehouse');
        $response->assertSee('purchaseInvoiceForm');
        $response->assertSee('syncSupplierLabel');
        $response->assertSee('getSelectedProductSku');
        $response->assertSee('submitForm');
        $response->assertSee('VALVE-100-IND');
        $response->assertSee('PUMP-CTRL-SMART');
    }

    public function test_submitting_without_items_fails_validation_with_items_required_message(): void
    {
        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'save_mode' => 'draft',
        ]);

        $response->assertSessionHasErrors(['items']);
    }

    public function test_supplier_endpoints_return_correct_json(): void
    {
        $po = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-TEST-001',
            'po_date' => now()->toDateString(),
            'status' => 'approved',
            'credit_days' => 45,
            'subtotal' => 3000,
            'tax_total' => 540,
            'grand_total' => 3540,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product1->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_cost' => 1500,
            'cgst_percent' => 9,
            'sgst_percent' => 9,
            'line_total' => 3540,
        ]);

        // 1. Supplier POs endpoint
        $poResponse = $this->actingAs($this->user)
            ->getJson("/purchasing/invoices/suppliers/{$this->supplier->id}/purchase-orders");
        $poResponse->assertOk();
        $poResponse->assertJsonFragment(['po_no' => 'PO-TEST-001']);

        // 2. PO data endpoint
        $poDataResponse = $this->actingAs($this->user)
            ->getJson("/purchasing/invoices/purchase-order/{$po->id}/data");
        $poDataResponse->assertOk();
        $poDataResponse->assertJsonPath('order.id', $po->id);
        $poDataResponse->assertJsonPath('order.po_no', 'PO-TEST-001');

        // 3. Product details endpoint
        $prodResponse = $this->actingAs($this->user)
            ->getJson("/purchasing/invoices/products/{$this->product1->id}/details");
        $prodResponse->assertOk();
        $prodResponse->assertJsonPath('data.id', $this->product1->id);
    }

    public function test_save_as_draft_creates_invoice_and_receives_stock(): void
    {
        $payload = [
            'save_mode' => 'draft',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'purchase_receive_date' => now()->toDateString(),
            'credit_days' => 30,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 5,
                    'unit_cost' => 1500.00,
                    'batch_mrp' => 2000.00,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                    'batch_number' => 'BATCH-TEST-01',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $invoice = PurchaseInvoice::where('supplier_id', $this->supplier->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $response->assertRedirect(route('purchasing.invoices.show', $invoice));

        // In draft mode, receiveMaterial runs: stock is posted and invoice is marked ready for payment
        $this->assertEquals($this->warehouse->id, $invoice->warehouse_id);
        $this->assertCount(1, $invoice->items);
        $this->assertEquals(5, (float) $invoice->items->first()->quantity);

        // Check stock level was updated
        $stock = StockLevel::where('product_id', $this->product1->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertNotNull($stock);
        $this->assertGreaterThanOrEqual(5, (float) $stock->quantity);
    }

    public function test_save_as_open_creates_invoice_in_transit_without_immediate_stock(): void
    {
        $initialStock = (float) (StockLevel::where('product_id', $this->product1->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity') ?? 0);

        $payload = [
            'save_mode' => 'open',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'material_dispatch_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addDays(15)->toDateString(),
            'credit_days' => 30,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 10,
                    'unit_cost' => 1500.00,
                    'batch_mrp' => 2000.00,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                    'batch_number' => 'BATCH-TRANSIT-01',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $invoice = PurchaseInvoice::where('supplier_id', $this->supplier->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertContains($invoice->status, ['open', 'posted']);
        $response->assertRedirect(route('purchasing.invoices.show', $invoice));

        // When saved as open (in transit), stock is not received yet
        $currentStock = (float) (StockLevel::where('product_id', $this->product1->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity') ?? 0);
        $this->assertEquals($initialStock, $currentStock);
    }

    public function test_reopen_and_edit_draft_invoice(): void
    {
        $invoice = PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_number' => 'INV-TEST-EDIT-01',
            'invoice_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 1500,
            'tax_total' => 270,
            'grand_total' => 1770,
            'created_by' => $this->user->id,
        ]);

        $editResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.edit', $invoice));
        $editResponse->assertOk();
        $editResponse->assertSee('Prime Supplier Logistics');
        $editResponse->assertSee('syncSupplierLabel');
    }
}
