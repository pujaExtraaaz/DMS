<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Banking\Models\BankAccountTransaction;
use App\Domains\Banking\Models\OdAccount;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\SupplierPayable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseInvoiceOdUtilizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Company $otherCompany;
    protected Customer $supplier;
    protected Warehouse $warehouse;
    protected Product $product;
    protected Uom $uom;
    protected OdAccount $odAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.manage', 'guard_name' => 'web']);

        $role->syncPermissions([
            'purchases.view',
            'purchases.create',
            'purchases.edit',
            'purchases.manage',
        ]);

        $this->company = Company::create([
            'name' => 'Avit Digital Systems Ltd',
            'code' => 'ADS-01',
            'is_active' => true,
        ]);

        $this->otherCompany = Company::create([
            'name' => 'Other Corporate Ltd',
            'code' => 'OCL-02',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($role);

        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'BR-01',
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Warehouse',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $customerType = CustomerType::create([
            'name' => 'Supplier Type',
            'code' => 'SUP-TYPE',
        ]);

        $this->supplier = Customer::create([
            'name' => 'Zenith Tech Supplier',
            'code' => 'ZEN-001',
            'customer_type_id' => $customerType->id,
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'is_active' => true,
            'credit_days' => 30,
        ]);

        $this->uom = Uom::create([
            'name' => 'Piece',
            'code' => 'PCS',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Router 5G',
            'sku' => 'RTR-5G-SKU',
            'serial_no' => 'SN-RTR-5G',
            'base_uom_id' => $this->uom->id,
            'uom_id' => $this->uom->id,
            'purchase_price' => 1000.00,
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);

        $this->odAccount = OdAccount::create([
            'company_id' => $this->company->id,
            'account_number' => '987654321001',
            'bank_name' => 'HDFC Bank',
            'ifsc_code' => 'HDFC0001234',
            'od_limit' => 500000.00,
            'interest_rate' => 10.50,
            'interest_calculation_method' => 'daily_simple',
            'effective_from' => '2026-04-01',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_create_purchase_invoice_screen_displays_od_accounts_for_company(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('purchasing.invoices.create'));

        $response->assertStatus(200);
        $response->assertSee('OD Bank Account');
        $response->assertSee('HDFC Bank');
        $response->assertSee('•••• 1001');
    }

    public function test_invoice_creation_with_od_account_records_single_debit_in_bank_account_transactions(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-14',
            'od_account_id' => $this->odAccount->id,
            'supplier_invoice_number' => 'INV-OD-001',
            'freight_charge' => 500.00,
            'other_charges' => 0.00,
            'credit_days' => 30,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_cost' => 1000.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        // Line subtotal = 10 * 1000 = 10,000. Tax (18%) = 1,800. Freight = 500.
        // Expected Grand Total = 12,300.00
        $response = $this->actingAs($this->adminUser)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-OD-001')->firstOrFail();

        $this->assertEquals(12300.00, (float) $invoice->grand_total);
        $this->assertEquals($this->odAccount->id, $invoice->od_account_id);
        $this->assertEquals($this->company->id, $invoice->company_id);

        // Verify exactly 1 BankAccountTransaction created
        $transactions = BankAccountTransaction::where('reference_type', $invoice->getMorphClass())
            ->where('reference_id', $invoice->id)
            ->get();

        $this->assertCount(1, $transactions);
        $tx = $transactions->first();

        $this->assertEquals('debit', $tx->transaction_type);
        $this->assertEquals(12300.00, (float) $tx->debit);
        $this->assertEquals(0.00, (float) $tx->credit);
        $this->assertEquals($this->odAccount->id, $tx->od_account_id);
        $this->assertEquals($this->odAccount->account_number, $tx->account_number);
        $this->assertEquals($this->company->id, $tx->company_id);
        $this->assertEquals('OD-' . $invoice->invoice_no, $tx->transaction_no);

        // Verify OD account utilization and available limit updated
        $this->odAccount->refresh();
        $this->assertEquals(12300.00, $this->odAccount->getCurrentUtilization());
        $this->assertEquals(500000.00 - 12300.00, $this->odAccount->getAvailableLimit());

        // Verify Supplier Payable is preserved (not marked as paid)
        $payable = SupplierPayable::where('reference_type', $invoice->getMorphClass())
            ->where('reference_id', $invoice->id)
            ->first();
        $this->assertNotNull($payable);
        $this->assertEquals(12300.00, (float) $payable->debit);
        $this->assertEquals(0.00, (float) $payable->credit);
    }

    public function test_invoice_creation_without_od_account_preserves_standard_workflow(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-14',
            'od_account_id' => null,
            'supplier_invoice_number' => 'INV-NO-OD-002',
            'freight_charge' => 0.00,
            'other_charges' => 0.00,
            'credit_days' => 30,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_cost' => 1000.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-NO-OD-002')->firstOrFail();

        $this->assertNull($invoice->od_account_id);
        $this->assertEquals(5900.00, (float) $invoice->grand_total);

        // Verify no BankAccountTransaction was created
        $txCount = BankAccountTransaction::where('reference_type', $invoice->getMorphClass())
            ->where('reference_id', $invoice->id)
            ->count();
        $this->assertEquals(0, $txCount);

        // Verify OD limit unaffected
        $this->odAccount->refresh();
        $this->assertEquals(0.00, $this->odAccount->getCurrentUtilization());
        $this->assertEquals(500000.00, $this->odAccount->getAvailableLimit());
    }

    public function test_invoice_creation_fails_when_amount_exceeds_available_od_limit(): void
    {
        // Reduce OD limit to 10,000
        $this->odAccount->update(['od_limit' => 10000.00]);

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-14',
            'od_account_id' => $this->odAccount->id,
            'supplier_invoice_number' => 'INV-OVER-LIMIT',
            'freight_charge' => 0.00,
            'other_charges' => 0.00,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10, // 10 * 1000 + 18% = 11,800 > 10,000
                    'unit_cost' => 1000.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasErrors('od_account_id');
        $this->assertDatabaseMissing('purchase_invoices', [
            'supplier_invoice_no' => 'INV-OVER-LIMIT',
        ]);
        $this->assertEquals(0, BankAccountTransaction::count());
    }

    public function test_invoice_creation_fails_for_inactive_or_foreign_company_od_account(): void
    {
        // 1. Inactive account
        $inactiveOd = OdAccount::create([
            'company_id' => $this->company->id,
            'account_number' => '987654321099',
            'bank_name' => 'Axis Bank',
            'od_limit' => 200000.00,
            'effective_from' => '2026-04-01',
            'status' => 'inactive',
        ]);

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-14',
            'od_account_id' => $inactiveOd->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 1000.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('purchasing.invoices.store'), $payload);
        $response->assertSessionHasErrors('od_account_id');

        // 2. Foreign company OD account
        $foreignOd = OdAccount::create([
            'company_id' => $this->otherCompany->id,
            'account_number' => '111122223333',
            'bank_name' => 'ICICI Bank',
            'od_limit' => 200000.00,
            'effective_from' => '2026-04-01',
            'status' => 'active',
        ]);

        $payload['od_account_id'] = $foreignOd->id;
        $response = $this->actingAs($this->adminUser)->post(route('purchasing.invoices.store'), $payload);
        $response->assertSessionHasErrors('od_account_id');
    }

    public function test_invoice_update_adjusts_od_utilization_when_grand_total_changes(): void
    {
        // 1. Create invoice with 10 units = 11,800
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-14',
            'od_account_id' => $this->odAccount->id,
            'supplier_invoice_number' => 'INV-EDIT-001',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_cost' => 1000.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $this->actingAs($this->adminUser)->post(route('purchasing.invoices.store'), $payload);
        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-EDIT-001')->firstOrFail();

        $this->assertEquals(11800.00, (float) $invoice->grand_total);
        $this->assertEquals(11800.00, $this->odAccount->fresh()->getCurrentUtilization());

        // 2. Update invoice: increase quantity to 20 units = 23,600
        $updatePayload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-14',
            'od_account_id' => $this->odAccount->id,
            'supplier_invoice_number' => 'INV-EDIT-001-MOD',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 20,
                    'unit_cost' => 1000.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->put(route('purchasing.invoices.update', $invoice), $updatePayload);
        $response->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertEquals(23600.00, (float) $invoice->grand_total);

        // Ensure still exactly 1 transaction with updated debit
        $txs = BankAccountTransaction::where('reference_type', $invoice->getMorphClass())
            ->where('reference_id', $invoice->id)
            ->get();
        $this->assertCount(1, $txs);
        $this->assertEquals(23600.00, (float) $txs->first()->debit);

        $this->assertEquals(23600.00, $this->odAccount->fresh()->getCurrentUtilization());
        $this->assertEquals(500000.00 - 23600.00, $this->odAccount->fresh()->getAvailableLimit());
    }

    public function test_invoice_update_removing_od_account_deletes_od_transaction(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-14',
            'od_account_id' => $this->odAccount->id,
            'supplier_invoice_number' => 'INV-REMOVE-OD',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_cost' => 1000.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $this->actingAs($this->adminUser)->post(route('purchasing.invoices.store'), $payload);
        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-REMOVE-OD')->firstOrFail();

        $this->assertEquals(1, BankAccountTransaction::where('reference_type', $invoice->getMorphClass())->where('reference_id', $invoice->id)->count());

        // Update with od_account_id = null
        $payload['od_account_id'] = null;
        $response = $this->actingAs($this->adminUser)->put(route('purchasing.invoices.update', $invoice), $payload);
        $response->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertNull($invoice->od_account_id);

        $this->assertEquals(0, BankAccountTransaction::where('reference_type', $invoice->getMorphClass())->where('reference_id', $invoice->id)->count());
        $this->assertEquals(0.00, $this->odAccount->fresh()->getCurrentUtilization());
        $this->assertEquals(500000.00, $this->odAccount->fresh()->getAvailableLimit());
    }

    public function test_invoice_deletion_voids_od_transaction_and_restores_limit(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-14',
            'od_account_id' => $this->odAccount->id,
            'supplier_invoice_number' => 'INV-DEL-001',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_cost' => 1000.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $this->actingAs($this->adminUser)->post(route('purchasing.invoices.store'), $payload);
        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-DEL-001')->firstOrFail();

        $this->assertEquals(11800.00, $this->odAccount->fresh()->getCurrentUtilization());

        // Delete invoice
        $response = $this->actingAs($this->adminUser)->delete(route('purchasing.invoices.destroy', $invoice));
        $response->assertRedirect(route('purchasing.invoices.index'));

        // Verify transaction deleted and OD limit restored
        $this->assertEquals(0, BankAccountTransaction::where('reference_type', $invoice->getMorphClass())->where('reference_id', $invoice->id)->count());
        $this->assertEquals(0.00, $this->odAccount->fresh()->getCurrentUtilization());
        $this->assertEquals(500000.00, $this->odAccount->fresh()->getAvailableLimit());
    }

    public function test_invoice_cancellation_voids_od_transaction_and_restores_limit(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-14',
            'od_account_id' => $this->odAccount->id,
            'supplier_invoice_number' => 'INV-CANCEL-001',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_cost' => 1000.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $this->actingAs($this->adminUser)->post(route('purchasing.invoices.store'), $payload);
        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-CANCEL-001')->firstOrFail();

        $this->assertEquals(11800.00, $this->odAccount->fresh()->getCurrentUtilization());

        // Cancel invoice
        $response = $this->actingAs($this->adminUser)->post(route('purchasing.invoices.cancel', $invoice));
        $response->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertEquals('cancelled', $invoice->status);

        // Verify transaction deleted and limit restored
        $this->assertEquals(0, BankAccountTransaction::where('reference_type', $invoice->getMorphClass())->where('reference_id', $invoice->id)->count());
        $this->assertEquals(0.00, $this->odAccount->fresh()->getCurrentUtilization());
        $this->assertEquals(500000.00, $this->odAccount->fresh()->getAvailableLimit());
    }

    public function test_show_page_displays_od_financing_banner_when_invoice_financed_via_od(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-10-14',
            'od_account_id' => $this->odAccount->id,
            'supplier_invoice_number' => 'INV-SHOW-OD',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_cost' => 1000.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                ],
            ],
        ];

        $this->actingAs($this->adminUser)->post(route('purchasing.invoices.store'), $payload);
        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-SHOW-OD')->firstOrFail();

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.invoices.show', $invoice));
        $response->assertStatus(200);
        $response->assertSee('OD Financed Invoice');
        $response->assertSee('OD Utilized');
        $response->assertSee('HDFC Bank');
        $response->assertSee('•••• 1001');
    }
}
