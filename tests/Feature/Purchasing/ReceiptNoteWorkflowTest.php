<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
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
use App\Domains\Purchasing\Models\ReceiptNote;
use App\Domains\Purchasing\Models\ReceiptNoteItem;
use App\Domains\Purchasing\Models\SupplierPayable;
use App\Domains\Purchasing\Services\ReceiptNoteService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReceiptNoteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $supplier;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Test Trading Co',
            'code' => 'TTC-01',
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

        $branch = Branch::create([
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Central Depot',
            'code' => 'CD-01',
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $ctype = CustomerType::firstOrCreate(
            ['code' => 'VENDOR'],
            ['name' => 'Vendor', 'is_active' => true]
        );

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $ctype->id,
            'name' => 'Apex Raw Materials Ltd',
            'code' => 'APEX-01',
            'email' => 'apex@vendor.com',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'is_active' => true,
        ]);

        $this->uom = Uom::firstOrCreate(
            ['code' => 'NOS'],
            ['name' => 'Numbers', 'is_active' => true]
        );

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Steel Pipe 2-inch',
            'sku' => 'STEEL-PIPE-02',
            'serial_no' => 'MST-STEEL-PIPE-02',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 500.00,
            'selling_price' => 650.00,
            'mrp' => 700.00,
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);
    }

    /**
     * Scenario A: Material received physically without Purchase Invoice.
     * Confirming Receipt Note increases stock, but creates NO invoices and NO supplier payables.
     */
    public function test_scenario_a_material_received_without_purchase_invoice(): void
    {
        $response = $this->actingAs($this->user)->post(route('purchasing.receipt-notes.store'), [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'receipt_date' => now()->toDateString(),
            'supplier_challan_no' => 'CH-8921',
            'supplier_challan_date' => now()->toDateString(),
            'received_by' => 'John Storekeeper',
            'confirm_immediately' => '1',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'challan_qty' => 50,
                    'received_qty' => 50,
                    'unit_cost' => 500,
                    'batch_no' => 'BATCH-A1',
                ],
            ],
        ]);

        $response->assertRedirect();

        $receiptNote = ReceiptNote::where('supplier_challan_no', 'CH-8921')->first();
        $this->assertNotNull($receiptNote);
        $this->assertEquals(ReceiptNote::STATUS_RECEIVED, $receiptNote->status);
        $this->assertEquals(50, (float) $receiptNote->total_received_qty);

        // Verify stock in warehouse is increased
        $stockLevel = StockLevel::query()
            ->where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertNotNull($stockLevel);
        $this->assertEquals(50.0, (float) $stockLevel->quantity);

        // Verify stock movement logged as receipt_note
        $movement = StockMovement::query()
            ->where('reference_type', $receiptNote->getMorphClass())
            ->where('reference_id', $receiptNote->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals('receipt_note', $movement->type);
        $this->assertEquals(50.0, (float) $movement->quantity);

        // CRITICAL: 0 Purchase Invoices and 0 Supplier Payables exist
        $this->assertEquals(0, PurchaseInvoice::count());
        $this->assertEquals(0, SupplierPayable::count());
    }

    /**
     * Scenario B: Purchase Invoice received later against previously confirmed Receipt Note.
     * Linking invoice to Receipt Note lines sets invoice as received without double-counting inventory!
     */
    public function test_scenario_b_purchase_invoice_received_later_no_duplicate_stock(): void
    {
        // 1. Create and confirm Receipt Note for 30 units
        $service = app(ReceiptNoteService::class);
        $note = $service->createReceiptNote([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'receipt_date' => now()->subDays(2)->toDateString(),
            'supplier_challan_no' => 'CHALLAN-B',
            'status' => ReceiptNote::STATUS_RECEIVED,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'challan_qty' => 30,
                    'received_qty' => 30,
                    'unit_cost' => 500,
                ],
            ],
        ], $this->user);

        $rnItem = $note->items()->first();

        // Stock is 30
        $stockBefore = (float) StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals(30.0, $stockBefore);

        // 2. Later, supplier issues Purchase Invoice for the 30 units
        $invResponse = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), [
            'save_mode' => 'draft',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'receipt_note_id' => $note->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'INV-SUP-9001',
            'items' => [
                [
                    'receipt_note_item_id' => $rnItem->id,
                    'product_id' => $this->product->id,
                    'quantity' => 30,
                    'unit_cost' => 500,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
            ],
        ]);

        $invResponse->assertRedirect();

        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-SUP-9001')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($note->id, $invoice->receipt_note_id);

        // Check invoice item
        $invItem = $invoice->items()->first();
        $this->assertEquals($rnItem->id, $invItem->receipt_note_item_id);
        $this->assertEquals(30.0, (float) $invItem->received_qty);

        // CRITICAL: Stock is STILL 30, NOT 60!
        $stockAfter = (float) StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals(30.0, $stockAfter);

        // RN is now fully invoiced
        $note->refresh();
        $this->assertEquals(ReceiptNote::STATUS_FULLY_INVOICED, $note->status);
        $this->assertEquals(30.0, (float) $rnItem->fresh()->invoiced_qty);
        $this->assertEquals(0.0, $note->pending_invoice_qty);

        // Supplier payable was created for the invoice
        $this->assertGreaterThan(0, SupplierPayable::where('reference_id', $invoice->id)->count());
    }

    /**
     * Scenario C: Partial receipt against Purchase Order.
     * PO for 100 units -> RN 1 for 40 units -> RN 2 for 30 units -> 30 units pending.
     */
    public function test_scenario_c_partial_receipt_against_po(): void
    {
        // 1. Create approved PO for 100 units
        $po = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'po_no' => 'PO-2026-001',
            'po_date' => now()->toDateString(),
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'approved',
            'subtotal' => 50000,
            'grand_total' => 59000,
            'created_by' => $this->user->id,
        ]);

        $poItem = $po->items()->create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 100,
            'received_qty' => 0,
            'unit_cost' => 500,
            'cgst_percent' => 9,
            'sgst_percent' => 9,
            'cgst_amount' => 4500,
            'sgst_amount' => 4500,
            'line_total' => 59000,
        ]);

        $service = app(ReceiptNoteService::class);

        // 2. First Receipt Note: 40 units
        $rn1 = $service->createReceiptNote([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'purchase_order_id' => $po->id,
            'receipt_date' => now()->toDateString(),
            'supplier_challan_no' => 'CH-PARTIAL-1',
            'status' => ReceiptNote::STATUS_RECEIVED,
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'product_id' => $this->product->id,
                    'ordered_qty' => 100,
                    'challan_qty' => 40,
                    'received_qty' => 40,
                    'unit_cost' => 500,
                ],
            ],
        ], $this->user);

        $po->refresh();
        $this->assertEquals('partially_received', $po->status);
        $this->assertEquals(40.0, (float) $poItem->fresh()->received_qty);
        $this->assertEquals(60.0, $poItem->fresh()->remainingQty());

        // 3. Second Receipt Note: 30 units
        $rn2 = $service->createReceiptNote([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'purchase_order_id' => $po->id,
            'receipt_date' => now()->toDateString(),
            'supplier_challan_no' => 'CH-PARTIAL-2',
            'status' => ReceiptNote::STATUS_RECEIVED,
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'product_id' => $this->product->id,
                    'ordered_qty' => 100,
                    'challan_qty' => 30,
                    'received_qty' => 30,
                    'unit_cost' => 500,
                ],
            ],
        ], $this->user);

        $po->refresh();
        $this->assertEquals('partially_received', $po->status);
        $this->assertEquals(70.0, (float) $poItem->fresh()->received_qty);
        $this->assertEquals(30.0, $poItem->fresh()->remainingQty());

        // Warehouse total physical stock = 40 + 30 = 70
        $currentStock = (float) StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals(70.0, $currentStock);
    }

    /**
     * Scenario D: Return material via Delivery Challan.
     * Receipt Note receives 50 units -> Return 10 units -> stock drops by 10 to 40.
     */
    public function test_scenario_d_return_material_via_delivery_challan(): void
    {
        $service = app(ReceiptNoteService::class);

        // 1. Receive 50 units
        $note = $service->createReceiptNote([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'receipt_date' => now()->toDateString(),
            'supplier_challan_no' => 'CH-RETURN-TEST',
            'status' => ReceiptNote::STATUS_RECEIVED,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'challan_qty' => 50,
                    'received_qty' => 50,
                    'unit_cost' => 500,
                ],
            ],
        ], $this->user);

        $rnItem = $note->items()->first();

        // Initial stock = 50
        $stock1 = (float) StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals(50.0, $stock1);

        // 2. Return 10 units via Delivery Challan
        $returnResponse = $this->actingAs($this->user)->post(route('purchasing.receipt-notes.return.store', $note), [
            'return_date' => now()->toDateString(),
            'return_no' => 'DC-RET-TEST-01',
            'reason' => 'Damaged / Defective Goods',
            'items' => [
                $rnItem->id => [
                    'quantity' => 10,
                    'remarks' => '10 pipes damaged in transit',
                ],
            ],
        ]);

        $returnResponse->assertRedirect();

        // Check stock dropped to 40
        $stock2 = (float) StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals(40.0, $stock2);

        // Check RN item quantities
        $rnItem->refresh();
        $this->assertEquals(10.0, (float) $rnItem->returned_qty);
        $this->assertEquals(40.0, $rnItem->pending_return_qty);
        $this->assertEquals(40.0, $rnItem->pending_invoice_qty);

        // Check RN status
        $note->refresh();
        $this->assertEquals(ReceiptNote::STATUS_PARTIALLY_RETURNED, $note->status);

        // Stock movement OUT logged
        $outMovement = StockMovement::query()
            ->where('type', 'receipt_note_return')
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertNotNull($outMovement);
        $this->assertEquals(-10.0, (float) $outMovement->quantity);
    }

    /**
     * Scenario E: Partial invoicing.
     * RN for 100 units -> Invoice 60 units (40 pending) -> Invoice remaining 40 units -> 0 stock duplication throughout.
     */
    public function test_scenario_e_partial_invoicing_no_stock_duplication(): void
    {
        $service = app(ReceiptNoteService::class);

        // 1. Receive 100 units into stock
        $note = $service->createReceiptNote([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'receipt_date' => now()->toDateString(),
            'supplier_challan_no' => 'CH-PARTIAL-INV',
            'status' => ReceiptNote::STATUS_RECEIVED,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'challan_qty' => 100,
                    'received_qty' => 100,
                    'unit_cost' => 500,
                ],
            ],
        ], $this->user);

        $rnItem = $note->items()->first();

        $this->assertEquals(100.0, (float) StockLevel::where('warehouse_id', $this->warehouse->id)->where('product_id', $this->product->id)->value('quantity'));

        // 2. Invoice 60 units
        $this->actingAs($this->user)->post(route('purchasing.invoices.store'), [
            'save_mode' => 'draft',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'receipt_note_id' => $note->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'INV-PART-1',
            'items' => [
                [
                    'receipt_note_item_id' => $rnItem->id,
                    'product_id' => $this->product->id,
                    'quantity' => 60,
                    'unit_cost' => 500,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
            ],
        ]);

        $note->refresh();
        $this->assertEquals(ReceiptNote::STATUS_PARTIALLY_INVOICED, $note->status);
        $this->assertEquals(60.0, (float) $rnItem->fresh()->invoiced_qty);
        $this->assertEquals(40.0, $note->pending_invoice_qty);

        // Stock must still be 100!
        $this->assertEquals(100.0, (float) StockLevel::where('warehouse_id', $this->warehouse->id)->where('product_id', $this->product->id)->value('quantity'));

        // 3. Invoice remaining 40 units
        $this->actingAs($this->user)->post(route('purchasing.invoices.store'), [
            'save_mode' => 'draft',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'receipt_note_id' => $note->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'INV-PART-2',
            'items' => [
                [
                    'receipt_note_item_id' => $rnItem->id,
                    'product_id' => $this->product->id,
                    'quantity' => 40,
                    'unit_cost' => 500,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
            ],
        ]);

        $note->refresh();
        $this->assertEquals(ReceiptNote::STATUS_FULLY_INVOICED, $note->status);
        $this->assertEquals(100.0, (float) $rnItem->fresh()->invoiced_qty);
        $this->assertEquals(0.0, $note->pending_invoice_qty);

        // Stock remains exactly 100!
        $this->assertEquals(100.0, (float) StockLevel::where('warehouse_id', $this->warehouse->id)->where('product_id', $this->product->id)->value('quantity'));
    }

    public function test_receipt_note_create_and_edit_views_render_successfully(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchasing.receipt-notes.create'));
        $response->assertOk();
        $response->assertSee('New Receipt Note');

        $receiptNote = ReceiptNote::create([
            'company_id' => $this->company->id,
            'receipt_note_no' => 'GRN-TEST-VIEW-01',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'receipt_date' => now()->toDateString(),
            'supplier_challan_no' => 'DC-001',
            'status' => ReceiptNote::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        $receiptNote->items()->create([
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'challan_qty' => 10,
            'received_qty' => 10,
            'unit_cost' => 100,
        ]);

        $editResponse = $this->actingAs($this->user)->get(route('purchasing.receipt-notes.edit', $receiptNote));
        $editResponse->assertOk();
        $editResponse->assertSee('Edit Receipt Note');
    }
}
