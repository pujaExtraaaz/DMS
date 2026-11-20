<?php

namespace Tests\Feature\Reporting;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\SupplierPayable;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyLedgerPurchaseInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Warehouse $warehouse;
    protected Customer $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Standard Industrial Dist',
            'code' => 'SID-01',
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Headquarters Branch',
            'code' => 'HQ-01',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Main Central Hub',
            'code' => 'WH-HUB',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);

        $cType = CustomerType::create([
            'name' => 'Supplier',
            'code' => 'SUPP',
            'is_active' => true,
        ]);

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Prime Steel Supplies Ltd',
            'code' => 'PSS-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $cType->id,
            'is_active' => true,
        ]);
    }

    public function test_posted_purchase_invoice_appears_in_supplier_party_ledger(): void
    {
        $invoice = PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-2026-001',
            'supplier_invoice_no' => 'SUP-INV-888',
            'invoice_date' => '2026-11-15',
            'status' => 'posted',
            'subtotal' => 10000.00,
            'grand_total' => 11800.00,
            'notes' => 'Raw material batch procurement',
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.party-statement', [
            'customer_id' => $this->supplier->id,
            'date_from' => '2026-11-01',
            'date_to' => '2026-11-30',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Prime Steel Supplies Ltd');
        $response->assertSee('PI-2026-001');
        $response->assertSee('Purchase Invoice');
        $response->assertSee('Raw material batch procurement');
        $response->assertSee('11,800.00');

        // Verify view data
        $entries = $response->viewData('entries');
        $this->assertCount(1, $entries);
        $entry = $entries->first();

        $this->assertEquals('PI-2026-001', $entry->voucher_number);
        $this->assertEquals('Purchase Invoice', $entry->voucher_type);
        $this->assertEquals(0.00, $entry->debit);
        $this->assertEquals(11800.00, $entry->credit);
        $this->assertEquals(11800.00, $entry->balance);

        $this->assertEquals(0.00, (float) $response->viewData('opening'));
        $this->assertEquals(0.00, (float) $response->viewData('totalDebit'));
        $this->assertEquals(11800.00, (float) $response->viewData('totalCredit'));
        $this->assertEquals(11800.00, (float) $response->viewData('closing'));
    }

    public function test_multiple_purchase_invoices_calculate_running_balances_accurately(): void
    {
        PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-2026-002',
            'invoice_date' => '2026-11-05',
            'status' => 'posted',
            'grand_total' => 5000.00,
            'notes' => 'First supply',
        ]);

        PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-2026-003',
            'invoice_date' => '2026-11-12',
            'status' => 'posted',
            'grand_total' => 7500.00,
            'notes' => 'Second supply',
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.party-statement', [
            'customer_id' => $this->supplier->id,
            'date_from' => '2026-11-01',
            'date_to' => '2026-11-30',
        ]));

        $response->assertStatus(200);

        $entries = $response->viewData('entries');
        $this->assertCount(2, $entries);

        $first = $entries->get(0);
        $this->assertEquals('PI-2026-002', $first->voucher_number);
        $this->assertEquals(0.00, $first->debit);
        $this->assertEquals(5000.00, $first->credit);
        $this->assertEquals(5000.00, $first->balance);

        $second = $entries->get(1);
        $this->assertEquals('PI-2026-003', $second->voucher_number);
        $this->assertEquals(0.00, $second->debit);
        $this->assertEquals(7500.00, $second->credit);
        $this->assertEquals(12500.00, $second->balance);

        $this->assertEquals(0.00, (float) $response->viewData('opening'));
        $this->assertEquals(0.00, (float) $response->viewData('totalDebit'));
        $this->assertEquals(12500.00, (float) $response->viewData('totalCredit'));
        $this->assertEquals(12500.00, (float) $response->viewData('closing'));
    }

    public function test_editing_purchase_invoice_updates_existing_ledger_entry_without_duplicates(): void
    {
        $invoice = PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-2026-EDIT',
            'invoice_date' => '2026-11-10',
            'status' => 'posted',
            'grand_total' => 8000.00,
            'notes' => 'Initial total',
        ]);

        $this->assertEquals(1, SupplierPayable::where('reference_type', $invoice->getMorphClass())->where('reference_id', $invoice->id)->count());

        // Update the invoice amount and notes
        $invoice->update([
            'grand_total' => 9500.00,
            'notes' => 'Adjusted total after weight check',
        ]);

        // Verify still exactly 1 ledger entry exists for this invoice (no duplicates)
        $this->assertEquals(1, SupplierPayable::where('reference_type', $invoice->getMorphClass())->where('reference_id', $invoice->id)->count());

        $response = $this->actingAs($this->user)->get(route('reports.party-statement', [
            'customer_id' => $this->supplier->id,
            'date_from' => '2026-11-01',
            'date_to' => '2026-11-30',
        ]));

        $entries = $response->viewData('entries');
        $this->assertCount(1, $entries);
        $this->assertEquals(9500.00, $entries->first()->credit);
        $this->assertEquals(9500.00, $entries->first()->balance);
        $this->assertEquals('Adjusted total after weight check', $entries->first()->particulars);
        $this->assertEquals(9500.00, (float) $response->viewData('closing'));
    }

    public function test_opening_and_closing_balances_across_different_date_ranges(): void
    {
        // October invoice (prior period)
        PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-OCT-001',
            'invoice_date' => '2026-10-20',
            'status' => 'posted',
            'grand_total' => 6000.00,
        ]);

        // November invoice (current period)
        PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-NOV-001',
            'invoice_date' => '2026-11-15',
            'status' => 'posted',
            'grand_total' => 4500.00,
        ]);

        // Filter for November only
        $response = $this->actingAs($this->user)->get(route('reports.party-statement', [
            'customer_id' => $this->supplier->id,
            'date_from' => '2026-11-01',
            'date_to' => '2026-11-30',
        ]));

        $response->assertStatus(200);

        // Opening balance must be 6000.00 from October
        $this->assertEquals(6000.00, (float) $response->viewData('opening'));
        $response->assertSee('6,000.00');

        // Only November invoice in period rows
        $entries = $response->viewData('entries');
        $this->assertCount(1, $entries);
        $this->assertEquals('PI-NOV-001', $entries->first()->voucher_number);
        $this->assertEquals(4500.00, $entries->first()->credit);
        $this->assertEquals(10500.00, $entries->first()->balance); // 6000 + 4500

        // Closing balance must be 10,500.00
        $this->assertEquals(10500.00, (float) $response->viewData('closing'));
        $this->assertEquals(4500.00, (float) $response->viewData('totalCredit'));
        $this->assertEquals(0.00, (float) $response->viewData('totalDebit'));
    }

    public function test_draft_and_cancelled_invoices_are_excluded_from_party_ledger(): void
    {
        // 1. Posted invoice (should appear)
        PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-ACTIVE-01',
            'invoice_date' => '2026-11-05',
            'status' => 'posted',
            'grand_total' => 3000.00,
        ]);

        // 2. Draft invoice (should be excluded)
        PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-DRAFT-01',
            'invoice_date' => '2026-11-10',
            'status' => 'draft',
            'grand_total' => 9000.00,
        ]);

        // 3. Cancelled invoice (should be excluded)
        PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-CANCEL-01',
            'invoice_date' => '2026-11-12',
            'status' => 'cancelled',
            'grand_total' => 5000.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.party-statement', [
            'customer_id' => $this->supplier->id,
            'date_from' => '2026-11-01',
            'date_to' => '2026-11-30',
        ]));

        $response->assertStatus(200);

        $entries = $response->viewData('entries');
        $this->assertCount(1, $entries);
        $this->assertEquals('PI-ACTIVE-01', $entries->first()->voucher_number);

        $response->assertDontSee('PI-DRAFT-01');
        $response->assertDontSee('PI-CANCEL-01');

        $this->assertEquals(3000.00, (float) $response->viewData('closing'));
    }

    public function test_posting_a_draft_invoice_automatically_synchronizes_to_party_ledger(): void
    {
        $service = app(PurchaseOrderService::class);

        $invoice = PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-DRAFT-TO-POST',
            'invoice_date' => '2026-11-18',
            'status' => 'draft',
            'grand_total' => 4200.00,
        ]);

        // Initial check: not in statement
        $response = $this->actingAs($this->user)->get(route('reports.party-statement', [
            'customer_id' => $this->supplier->id,
            'date_from' => '2026-11-01',
            'date_to' => '2026-11-30',
        ]));
        $this->assertCount(0, $response->viewData('entries'));

        // Post the invoice
        $service->postInvoice($invoice);

        // After posting: appears in statement
        $responseAfter = $this->actingAs($this->user)->get(route('reports.party-statement', [
            'customer_id' => $this->supplier->id,
            'date_from' => '2026-11-01',
            'date_to' => '2026-11-30',
        ]));
        $entries = $responseAfter->viewData('entries');
        $this->assertCount(1, $entries);
        $this->assertEquals('PI-DRAFT-TO-POST', $entries->first()->voucher_number);
        $this->assertEquals(4200.00, $entries->first()->credit);
        $this->assertEquals(4200.00, (float) $responseAfter->viewData('closing'));
    }

    public function test_party_statement_csv_export_includes_purchase_invoices(): void
    {
        PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-CSV-01',
            'invoice_date' => '2026-11-16',
            'status' => 'posted',
            'grand_total' => 15000.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.party-statement', [
            'customer_id' => $this->supplier->id,
            'date_from' => '2026-11-01',
            'date_to' => '2026-11-30',
            'export' => 'csv',
        ]));

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertStringContainsString('Date', $content);
        $this->assertStringContainsString('Voucher Number', $content);
        $this->assertStringContainsString('PI-CSV-01', $content);
        $this->assertStringContainsString('15,000.00', $content);
    }

    public function test_customer_sales_invoices_and_payments_work_correctly(): void
    {
        $cType = CustomerType::where('code', 'SUPP')->first();
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Metro Retail Customer',
            'code' => 'MRC-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $cType->id,
            'is_active' => true,
        ]);

        $outstandingService = app(\App\Domains\Payment\Services\OutstandingLedgerService::class);
        $outstandingService->recordAdjustment($customer->id, 5000.00, 0, 'Opening Sales Debit');
        $outstandingService->recordAdjustment($customer->id, 0, 2000.00, 'Customer Payment Credit');

        $response = $this->actingAs($this->user)->get(route('reports.party-statement', [
            'customer_id' => $customer->id,
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertSee('Metro Retail Customer');
        $entries = $response->viewData('entries');
        $this->assertCount(2, $entries);

        $this->assertEquals(5000.00, (float) $response->viewData('totalDebit'));
        $this->assertEquals(2000.00, (float) $response->viewData('totalCredit'));
        $this->assertEquals(3000.00, (float) $response->viewData('closing'));
    }
}
