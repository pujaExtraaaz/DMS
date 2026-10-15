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
use App\Domains\Purchasing\Models\LandedCost;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Domains\Purchasing\Models\SupplierPayable;
use App\Domains\Purchasing\Models\VendorPriceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseInvoiceEditAndDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $purchaserUser;
    protected User $unauthorizedUser;
    protected Company $company;
    protected Customer $supplier;
    protected Customer $supplier2;
    protected Warehouse $warehouse;
    protected Product $product;
    protected Product $product2;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $purchaserRole = Role::firstOrCreate(['name' => 'purchaser', 'guard_name' => 'web']);
        $guestRole = Role::firstOrCreate(['name' => 'guest', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'purchases.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.manage', 'guard_name' => 'web']);

        $purchaserRole->syncPermissions([
            'purchases.view',
            'purchases.create',
            'purchases.edit',
            'purchases.manage',
        ]);

        $this->company = Company::create([
            'name' => 'Avit Digital Systems',
            'code' => 'ADS-01',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($superAdminRole);

        $this->purchaserUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->purchaserUser->assignRole($purchaserRole);

        $this->unauthorizedUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->unauthorizedUser->assignRole($guestRole);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $branch = Branch::create([
            'name' => 'Central Hub Branch',
            'code' => 'CHB01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Main Distribution WH',
            'code' => 'WH-DIST',
            'is_active' => true,
        ]);

        $customerType = CustomerType::create([
            'name' => 'Vendor',
            'code' => 'VND',
            'is_active' => true,
        ]);

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'TechSupply Corp',
            'code' => 'SUP-TECH',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $customerType->id,
            'credit_days' => 30,
            'phone' => '9876543210',
            'state' => 'Punjab',
            'is_active' => true,
        ]);

        $this->supplier2 = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Global Source Logistics',
            'code' => 'SUP-GLOB',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $customerType->id,
            'credit_days' => 45,
            'phone' => '9811223344',
            'state' => 'Delhi',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Control Motherboard X1',
            'sku' => 'CMB-X1',
            'serial_no' => 'SN-CMB-X1',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 5000.00,
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Power Regulator Module',
            'sku' => 'PRM-200',
            'serial_no' => 'SN-PRM-200',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 1500.00,
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);
    }

    protected function createDraftPurchaseInvoice(array $attributes = []): PurchaseInvoice
    {
        $invoice = PurchaseInvoice::create(array_merge([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-TEST-' . uniqid(),
            'supplier_invoice_no' => 'SUP-INV-001',
            'invoice_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 10000.00,
            'tax_amount' => 1800.00,
            'freight_charge' => 200.00,
            'other_charges' => 50.00,
            'grand_total' => 12050.00,
            'credit_days' => 30,
            'due_date' => now()->addDays(30)->toDateString(),
            'notes' => 'Draft invoice test notes',
            'created_by' => $this->purchaserUser->id,
        ], $attributes));

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_cost' => 5000.00,
            'tax_percent' => 18.00,
            'cgst_percent' => 9.00,
            'sgst_percent' => 9.00,
            'cgst_amount' => 900.00,
            'sgst_amount' => 900.00,
            'line_total' => 11800.00,
            'batch_no' => 'BATCH-A1',
            'batch_mrp' => 7500.00,
        ]);

        SupplierPayable::create([
            'supplier_id' => $invoice->supplier_id,
            'type' => 'invoice',
            'reference_type' => $invoice->getMorphClass(),
            'reference_id' => $invoice->id,
            'debit' => $invoice->grand_total,
            'credit' => 0,
            'balance' => $invoice->grand_total,
            'notes' => "Purchase invoice {$invoice->invoice_no}",
        ]);

        VendorPriceHistory::create([
            'supplier_id' => $invoice->supplier_id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'unit_cost' => 5000.00,
            'effective_from' => $invoice->invoice_date,
            'reference_type' => $invoice->getMorphClass(),
            'reference_id' => $invoice->id,
            'created_by' => $this->purchaserUser->id,
        ]);

        return $invoice;
    }

    public function test_actions_column_displays_preview_view_edit_and_delete_buttons(): void
    {
        $invoice = $this->createDraftPurchaseInvoice();

        $response = $this->actingAs($this->purchaserUser)->get(route('purchasing.invoices.index'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Existing Preview and View buttons preserved
        $this->assertStringContainsString('Preview', $content);
        $this->assertStringContainsString(route('purchasing.invoices.preview', $invoice), $content);
        $this->assertStringContainsString('View', $content);
        $this->assertStringContainsString(route('purchasing.invoices.show', $invoice), $content);

        // 2. New Edit button with edit link
        $this->assertStringContainsString('Edit', $content);
        $this->assertStringContainsString(route('purchasing.invoices.edit', $invoice), $content);

        // 3. New Delete button with trash icon & confirmation trigger
        $this->assertStringContainsString('Delete', $content);
        $this->assertStringContainsString('promptDelete(', $content);
        $this->assertStringContainsString('Delete Purchase Invoice', $content);
        $this->assertStringContainsString('Warning:', $content);
    }

    public function test_edit_page_loads_correct_purchase_invoice_data(): void
    {
        $invoice = $this->createDraftPurchaseInvoice();

        $response = $this->actingAs($this->purchaserUser)->get(route('purchasing.invoices.edit', $invoice));
        $response->assertStatus(200);

        $response->assertSee($invoice->invoice_no);
        $response->assertSee('TechSupply Corp');
        $response->assertSee('SUP-INV-001');
        $response->assertSee('Control Motherboard X1');
        $response->assertSee('Draft invoice test notes');
    }

    public function test_saving_edits_updates_existing_record_without_duplicating_it(): void
    {
        $invoice = $this->createDraftPurchaseInvoice();
        $originalId = $invoice->id;
        $originalNo = $invoice->invoice_no;
        $initialCount = PurchaseInvoice::count();

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'SUP-INV-UPDATED',
            'credit_days' => 45,
            'freight_charge' => 350.00,
            'other_charges' => 75.00,
            'notes' => 'Updated invoice remarks',
            'items' => [
                [
                    'product_id' => $this->product2->id,
                    'quantity' => 4,
                    'unit_cost' => 1500.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                    'batch_number' => 'BATCH-UPDATED-02',
                    'batch_mrp' => 2200.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->purchaserUser)->put(route('purchasing.invoices.update', $invoice), $payload);
        $response->assertStatus(302);
        $response->assertRedirect(route('purchasing.invoices.show', $invoice));

        // Assert record count remains the same (no duplicate created)
        $this->assertSame($initialCount, PurchaseInvoice::count());

        $refreshed = $invoice->fresh(['items']);
        $this->assertSame($originalId, $refreshed->id);
        $this->assertSame($originalNo, $refreshed->invoice_no);
        $this->assertSame('SUP-INV-UPDATED', $refreshed->supplier_invoice_number);
        $this->assertSame('Updated invoice remarks', $refreshed->notes);
        $this->assertSame(45, $refreshed->credit_days);

        // Subtotal = 4 * 1500 = 6000.00, Tax = 6000 * 0.18 = 1080.00
        // Grand Total = 6000 + 1080 + 350 + 75 = 7505.00
        $this->assertEquals(6000.00, (float) $refreshed->subtotal);
        $this->assertEquals(1080.00, (float) $refreshed->tax_amount);
        $this->assertEquals(350.00, (float) $refreshed->freight_charge);
        $this->assertEquals(75.00, (float) $refreshed->other_charges);
        $this->assertEquals(7505.00, (float) $refreshed->grand_total);

        // Check line item updated
        $this->assertCount(1, $refreshed->items);
        $item = $refreshed->items->first();
        $this->assertSame($this->product2->id, $item->product_id);
        $this->assertEquals(4.0, (float) $item->quantity);
        $this->assertSame('BATCH-UPDATED-02', $item->batch_no);

        // Check supplier payable was updated to new grand total
        $payable = SupplierPayable::where('reference_type', $invoice->getMorphClass())
            ->where('reference_id', $invoice->id)
            ->first();
        $this->assertNotNull($payable);
        $this->assertEquals(7505.00, (float) $payable->debit);
    }

    public function test_paid_invoice_cannot_be_edited_or_deleted(): void
    {
        $invoice = $this->createDraftPurchaseInvoice([
            'status' => 'paid',
        ]);

        $this->assertFalse($invoice->isEditable());
        $this->assertFalse($invoice->canBeDeleted());
        $this->assertStringContainsString('Paid purchase invoices cannot be deleted', $invoice->getDeleteBlockReason());

        // Attempt edit
        $response = $this->actingAs($this->purchaserUser)->get(route('purchasing.invoices.edit', $invoice));
        $response->assertStatus(302);
        $response->assertSessionHas('error');

        // Attempt delete
        $delResponse = $this->actingAs($this->purchaserUser)->delete(route('purchasing.invoices.destroy', $invoice));
        $delResponse->assertStatus(302);
        $delResponse->assertSessionHas('error');

        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoice->id]);
    }

    public function test_eligible_purchase_invoice_can_be_deleted(): void
    {
        $invoice = $this->createDraftPurchaseInvoice();
        $invoiceId = $invoice->id;

        $response = $this->actingAs($this->purchaserUser)->delete(route('purchasing.invoices.destroy', $invoice));
        $response->assertStatus(302);
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('purchase_invoices', ['id' => $invoiceId]);
        $this->assertDatabaseMissing('purchase_invoice_items', ['purchase_invoice_id' => $invoiceId]);
        $this->assertDatabaseMissing('supplier_payables', ['reference_type' => $invoice->getMorphClass(), 'reference_id' => $invoiceId]);
    }

    public function test_eligible_purchase_invoice_can_be_deleted_via_ajax(): void
    {
        $invoice = $this->createDraftPurchaseInvoice();
        $invoiceId = $invoice->id;

        $response = $this->actingAs($this->purchaserUser)->deleteJson(route('purchasing.invoices.destroy', $invoice));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('purchase_invoices', ['id' => $invoiceId]);
        $this->assertDatabaseMissing('purchase_invoice_items', ['purchase_invoice_id' => $invoiceId]);
    }

    public function test_invoice_linked_to_posted_landed_costs_cannot_be_deleted(): void
    {
        $invoice = $this->createDraftPurchaseInvoice();

        LandedCost::create([
            'landed_no' => 'LC-TEST-001',
            'landed_date' => now()->toDateString(),
            'purchase_invoice_id' => $invoice->id,
            'allocation_method' => 'value',
            'total_additional_cost' => 500.00,
            'status' => 'posted',
            'created_by' => $this->purchaserUser->id,
        ]);

        $this->assertFalse($invoice->canBeDeleted());
        $this->assertStringContainsString('posted landed costs', $invoice->getDeleteBlockReason());

        $response = $this->actingAs($this->purchaserUser)->deleteJson(route('purchasing.invoices.destroy', $invoice));
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);

        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoice->id]);
    }

    public function test_unauthorized_user_cannot_edit_or_delete_purchase_invoice(): void
    {
        $invoice = $this->createDraftPurchaseInvoice();

        $editResponse = $this->actingAs($this->unauthorizedUser)->get(route('purchasing.invoices.edit', $invoice));
        $editResponse->assertStatus(403);

        $updateResponse = $this->actingAs($this->unauthorizedUser)->put(route('purchasing.invoices.update', $invoice), [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 5000.00,
                ],
            ],
        ]);
        $updateResponse->assertStatus(403);

        $deleteResponse = $this->actingAs($this->unauthorizedUser)->delete(route('purchasing.invoices.destroy', $invoice));
        $deleteResponse->assertStatus(403);
    }

    public function test_supplier_purchase_orders_filtering_endpoint(): void
    {
        $po1 = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'po_no' => 'PO-SUP1-01',
            'po_date' => now()->toDateString(),
            'status' => 'approved',
            'subtotal' => 5000.00,
            'tax_amount' => 900.00,
            'grand_total' => 5900.00,
            'created_by' => $this->purchaserUser->id,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po1->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 1,
            'unit_cost' => 5000.00,
            'tax_percent' => 18.00,
            'line_total' => 5900.00,
        ]);

        $response = $this->actingAs($this->purchaserUser)->getJson(route('purchasing.invoices.supplier-purchase-orders', $this->supplier));
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $po1->id,
            'po_no' => 'PO-SUP1-01',
        ]);

        // When checking supplier 2, PO 1 should not be returned
        $response2 = $this->actingAs($this->purchaserUser)->getJson(route('purchasing.invoices.supplier-purchase-orders', $this->supplier2));
        $response2->assertStatus(200);
        $response2->assertJsonMissing([
            'po_no' => 'PO-SUP1-01',
        ]);
    }
}
