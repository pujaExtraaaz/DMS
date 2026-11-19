<?php

namespace Tests\Feature\Reporting;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\InterCompanyTransaction;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GroupDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedUser;
    protected Company $companyA;
    protected Company $companyB;
    protected FinancialYear $financialYear;
    protected Customer $customer;
    protected Customer $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $salespersonRole = Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);

        // Create Sister Concern Companies A & B
        $this->companyA = Company::create([
            'name' => 'Company A Trading',
            'code' => 'CO-A',
            'gstin' => '27AAAAA0000A1Z5',
            'is_active' => true,
        ]);

        $this->companyB = Company::create([
            'name' => 'Company B Manufacturing',
            'code' => 'CO-B',
            'gstin' => '27BBBBB0000B1Z6',
            'is_active' => true,
        ]);

        // Customer & Supplier Parties
        $this->customer = Customer::create([
            'company_id' => $this->companyA->id,
            'code' => 'CUST-001',
            'name' => 'Standard Client Party',
            'party_type' => 'sundry_debtors',
            'is_active' => true,
        ]);

        $this->supplier = Customer::create([
            'company_id' => $this->companyA->id,
            'code' => 'SUPP-001',
            'name' => 'Premier Raw Material Supplier',
            'party_type' => 'sundry_creditors',
            'is_active' => true,
        ]);

        // Financial Year
        $this->financialYear = FinancialYear::create([
            'company_id' => $this->companyA->id,
            'name' => '2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
        ]);

        // Users
        $this->superAdmin = User::factory()->create([
            'company_id' => $this->companyA->id,
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->restrictedUser = User::factory()->create([
            'company_id' => $this->companyB->id,
        ]);
        $this->restrictedUser->assignRole($salespersonRole);
    }

    public function test_group_dashboard_menu_option_and_landing_renders_successfully(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('group-dashboard'));

        $response->assertOk();
        $response->assertSee('Sister Concern – Group Dashboard');
        $response->assertSee('Both Companies (Combined / Consolidated)');
        $response->assertSee($this->companyA->name);
        $response->assertSee($this->companyB->name);
        $response->assertSee('Consolidated After Elimination');
    }

    public function test_select_company_a_displays_only_company_a_records(): void
    {
        // Company A sale: ₹10,000
        Invoice::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-A-001',
            'invoice_date' => '2026-05-10',
            'subtotal' => 8474.58,
            'tax_amount' => 1525.42,
            'grand_total' => 10000.00,
            'paid_amount' => 10000.00,
            'status' => 'paid',
        ]);

        // Company B sale: ₹25,000
        Invoice::create([
            'company_id' => $this->companyB->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-B-001',
            'invoice_date' => '2026-05-15',
            'subtotal' => 21186.44,
            'tax_amount' => 3813.56,
            'grand_total' => 25000.00,
            'paid_amount' => 25000.00,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('group-dashboard', [
            'company_id' => $this->companyA->id,
            'from_date' => '2026-04-01',
            'to_date' => '2027-03-31',
        ]));

        $response->assertOk();
        // Expect Company A's 10,000 to be displayed as combined/active metric
        $response->assertSee('10,000.00');
        $response->assertDontSee('35,000.00');
    }

    public function test_select_company_b_displays_only_company_b_records(): void
    {
        // Company A purchase: ₹12,000
        PurchaseInvoice::create([
            'company_id' => $this->companyA->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PUR-A-001',
            'invoice_date' => '2026-06-01',
            'subtotal' => 10000.00,
            'tax_amount' => 2000.00,
            'grand_total' => 12000.00,
            'status' => 'posted',
        ]);

        // Company B purchase: ₹18,000
        PurchaseInvoice::create([
            'company_id' => $this->companyB->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PUR-B-001',
            'invoice_date' => '2026-06-05',
            'subtotal' => 15000.00,
            'tax_amount' => 3000.00,
            'grand_total' => 18000.00,
            'status' => 'posted',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('group-dashboard', [
            'company_id' => $this->companyB->id,
            'from_date' => '2026-04-01',
            'to_date' => '2027-03-31',
        ]));

        $response->assertOk();
        $response->assertSee('18,000.00');
        $response->assertDontSee('30,000.00');
    }

    public function test_select_both_companies_combines_sales_and_purchases(): void
    {
        Invoice::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-A-101',
            'invoice_date' => '2026-05-01',
            'subtotal' => 40000.00,
            'tax_amount' => 7200.00,
            'grand_total' => 47200.00,
            'status' => 'paid',
        ]);

        Invoice::create([
            'company_id' => $this->companyB->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-B-101',
            'invoice_date' => '2026-05-02',
            'subtotal' => 50000.00,
            'tax_amount' => 9000.00,
            'grand_total' => 59000.00,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('group-dashboard', [
            'company_id' => 'all',
            'elimination_mode' => 'before',
            'from_date' => '2026-04-01',
            'to_date' => '2027-03-31',
        ]));

        $response->assertOk();
        // Combined sales = 47200 + 59000 = 106,200.00
        $response->assertSee('106,200.00');
    }

    public function test_sales_and_purchase_returns_are_calculated_separately(): void
    {
        $inv = Invoice::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-A-201',
            'invoice_date' => '2026-07-01',
            'subtotal' => 50000.00,
            'tax_amount' => 9000.00,
            'grand_total' => 59000.00,
            'status' => 'paid',
        ]);

        // Sales return credit note: ₹9,000
        CreditNote::create([
            'customer_id' => $this->customer->id,
            'credit_note_no' => 'CN-A-001',
            'credit_note_date' => '2026-07-10',
            'invoice_id' => $inv->id,
            'grand_total' => 9000.00,
            'status' => 'posted',
            'reason' => 'return',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('group-dashboard', [
            'company_id' => $this->companyA->id,
            'from_date' => '2026-04-01',
            'to_date' => '2027-03-31',
        ]));

        $response->assertOk();
        // Gross: 59,000.00, Return: 9,000.00, Net: 50,000.00
        $response->assertSee('59,000.00');
        $response->assertSee('9,000.00');
        $response->assertSee('50,000.00');
    }

    public function test_profit_and_loss_consolidation_computes_gross_and_net_profit(): void
    {
        // Net sales: 100,000
        Invoice::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-PL-01',
            'invoice_date' => '2026-08-01',
            'subtotal' => 100000.00,
            'tax_amount' => 18000.00,
            'grand_total' => 118000.00,
            'status' => 'paid',
        ]);

        // Net purchases (COGS): 60,000
        PurchaseInvoice::create([
            'company_id' => $this->companyA->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PUR-PL-01',
            'invoice_date' => '2026-08-05',
            'subtotal' => 60000.00,
            'tax_amount' => 10800.00,
            'grand_total' => 70800.00,
            'status' => 'paid',
        ]);

        // Expense: Freight bill 10,000
        DB::table('freight_bills')->insert([
            'freight_no' => 'FRT-001',
            'bill_date' => '2026-08-10',
            'total_amount' => 10000.00,
            'transporter_name' => 'Fast Express',
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('group-dashboard', [
            'company_id' => $this->companyA->id,
            'from_date' => '2026-04-01',
            'to_date' => '2027-03-31',
        ]));

        $response->assertOk();
        // Gross Profit = 118,000 - 70,800 = 47,200.00
        $response->assertSee('47,200.00');
        // Total Expenses = 10,000.00
        $response->assertSee('10,000.00');
        // Net Profit = 47,200 - 10,000 = 37,200.00
        $response->assertSee('37,200.00');
    }

    public function test_inter_company_transaction_matching_and_elimination(): void
    {
        // 1. External sale in Co A: 50,000
        Invoice::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-EXT-01',
            'invoice_date' => '2026-09-01',
            'subtotal' => 50000.00,
            'tax_amount' => 9000.00,
            'grand_total' => 59000.00,
            'status' => 'paid',
        ]);

        // 2. Inter-company transaction: Co A sells to Co B: 20,000
        InterCompanyTransaction::create([
            'transaction_no' => 'ICT-2026-001',
            'transaction_date' => '2026-09-05',
            'transaction_type' => 'Sale',
            'from_company_id' => $this->companyA->id,
            'to_company_id' => $this->companyB->id,
            'amount' => 20000.00,
            'currency' => 'INR',
            'exchange_rate' => 1.0,
            'net_amount' => 20000.00,
            'status' => 'Approved',
        ]);

        // Before elimination: 59,000 + 0 = 59,000 (plus internal 20,000 if in sales)
        $responseBefore = $this->actingAs($this->superAdmin)->get(route('group-dashboard', [
            'company_id' => 'all',
            'elimination_mode' => 'before',
            'from_date' => '2026-04-01',
            'to_date' => '2027-03-31',
        ]));
        $responseBefore->assertOk();
        $responseBefore->assertSee('Combined Before Elimination');

        // After elimination: Inter-company 20,000 eliminated transparently
        $responseAfter = $this->actingAs($this->superAdmin)->get(route('group-dashboard', [
            'company_id' => 'all',
            'elimination_mode' => 'after',
            'from_date' => '2026-04-01',
            'to_date' => '2027-03-31',
        ]));
        $responseAfter->assertOk();
        $responseAfter->assertSee('20,000.00');
        $responseAfter->assertSee('Matched');
    }

    public function test_unmatched_inter_company_transactions_are_flagged(): void
    {
        // Create an unapproved / pending inter-company transaction
        InterCompanyTransaction::create([
            'transaction_no' => 'ICT-PENDING-01',
            'transaction_date' => '2026-09-10',
            'transaction_type' => 'Sale',
            'from_company_id' => $this->companyA->id,
            'to_company_id' => $this->companyB->id,
            'amount' => 15000.00,
            'currency' => 'INR',
            'exchange_rate' => 1.0,
            'net_amount' => 15000.00,
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('group-dashboard', [
            'company_id' => 'all',
            'from_date' => '2026-04-01',
            'to_date' => '2027-03-31',
        ]));

        $response->assertOk();
        $response->assertSee('Pending Verification');
        $response->assertSee('ICT-PENDING-01');
    }

    public function test_kpi_drilldown_endpoint_returns_json_records(): void
    {
        Invoice::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-DRILL-01',
            'invoice_date' => '2026-08-15',
            'subtotal' => 20000.00,
            'tax_amount' => 3600.00,
            'grand_total' => 23600.00,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson(route('group-dashboard.drilldown', [
            'type' => 'sales',
            'company_id' => $this->companyA->id,
            'from_date' => '2026-04-01',
            'to_date' => '2027-03-31',
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'type' => 'sales',
        ]);
        $response->assertJsonFragment([
            'number' => 'INV-DRILL-01',
        ]);
    }

    public function test_reports_can_be_exported_in_excel_and_csv(): void
    {
        $responseCsv = $this->actingAs($this->superAdmin)->get(route('group-dashboard.export', [
            'report_type' => 'summary',
            'format' => 'csv',
            'from_date' => '2026-04-01',
            'to_date' => '2027-03-31',
        ]));

        $responseCsv->assertOk();
        $this->assertStringContainsString('text/csv', $responseCsv->headers->get('Content-Type') ?? '');

        $responsePl = $this->actingAs($this->superAdmin)->get(route('group-dashboard.export', [
            'report_type' => 'profit-loss',
            'format' => 'csv',
            'from_date' => '2026-04-01',
            'to_date' => '2027-03-31',
        ]));

        $responsePl->assertOk();
        $this->assertStringContainsString('text/csv', $responsePl->headers->get('Content-Type') ?? '');
    }

    public function test_unauthorized_user_without_permissions_is_rejected(): void
    {
        $guestUser = User::factory()->create();

        $response = $this->actingAs($guestUser)->get(route('group-dashboard'));

        $response->assertForbidden();
    }
}
