<?php

namespace Tests\Feature\Organization;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\InterCompanyTransaction;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SisterConcernConsolidatedReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $unauthorizedUser;
    protected BusinessGroup $group;
    protected Company $ownerCompany;
    protected Company $sisterConcern1;
    protected Company $sisterConcern2;
    protected FinancialYear $fy2026;
    protected CustomerType $customerType;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $roleRegular = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->customerType = CustomerType::create([
            'name' => 'General Client',
            'code' => 'GEN',
            'is_active' => true,
        ]);

        // 1. Setup Owner & Sister Concerns
        $this->ownerCompany = Company::create([
            'name' => 'Avit Digital',
            'code' => 'AVIT-01',
            'legal_name' => 'Avit Digital Private Limited',
            'gstin' => '03ABGPG1954N1ZN',
            'state' => 'Punjab',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->group = BusinessGroup::create([
            'name' => 'Avit Corporate Group',
            'code' => 'AVIT-GRP',
            'parent_company_id' => $this->ownerCompany->id,
            'is_active' => true,
        ]);

        $this->ownerCompany->update(['business_group_id' => $this->group->id]);

        $this->sisterConcern1 = Company::create([
            'business_group_id' => $this->group->id,
            'name' => 'Adex Digital Solutions',
            'code' => 'ADEX-02',
            'gstin' => '03ABCDE1234F1Z5',
            'state' => 'Punjab',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->sisterConcern2 = Company::create([
            'business_group_id' => $this->group->id,
            'name' => 'VR Solution',
            'code' => 'VR-03',
            'gstin' => '27VWXYZ5678K1Z9',
            'state' => 'Maharashtra',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->group->companies()->sync([
            $this->ownerCompany->id,
            $this->sisterConcern1->id,
            $this->sisterConcern2->id,
        ]);

        // 2. Setup Financial Year 2026-27
        $this->fy2026 = FinancialYear::create([
            'company_id' => $this->ownerCompany->id,
            'name' => '2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);

        // 3. Setup Users
        $this->adminUser = User::factory()->create([
            'company_id' => $this->ownerCompany->id,
            'name' => 'Super Admin',
            'email' => 'admin@avitdigital.in',
        ]);
        $this->adminUser->assignRole($roleAdmin);

        $this->unauthorizedUser = User::factory()->create([
            'name' => 'Guest Clerk',
            'email' => 'clerk@example.com',
        ]);
        $this->unauthorizedUser->assignRole($roleRegular);
    }

    public function test_authorized_user_can_access_consolidated_reports_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('organization.sister-concerns.reports'));

        $response->assertOk();
        $response->assertViewIs('organization.sister-concerns.reports');
        $response->assertSee('Consolidated Reports');
        $response->assertSee('Avit Corporate Group');
        $response->assertSee($this->ownerCompany->name);
        $response->assertSee($this->sisterConcern1->name);
        $response->assertSee($this->sisterConcern2->name);
        $response->assertSee('FY 2026-27');
    }

    public function test_consolidated_sales_report_combines_invoices_across_sister_concerns(): void
    {
        $customer1 = Customer::create([
            'customer_type_id' => $this->customerType->id,
            'company_id' => $this->ownerCompany->id,
            'name' => 'Metro Supermarkets',
            'code' => 'CUST-001',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        $customer2 = Customer::create([
            'customer_type_id' => $this->customerType->id,
            'company_id' => $this->sisterConcern1->id,
            'name' => 'Apex Retailers',
            'code' => 'CUST-002',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        // Invoice 1 for Owner Company (₹10,000)
        Invoice::create([
            'company_id' => $this->ownerCompany->id,
            'customer_id' => $customer1->id,
            'invoice_no' => 'INV-AVIT-101',
            'invoice_date' => '2026-05-15',
            'subtotal' => 8474.58,
            'tax_amount' => 1525.42,
            'grand_total' => 10000.00,
            'paid_amount' => 10000.00,
            'status' => 'paid',
        ]);

        // Invoice 2 for Adex Digital Solutions (₹20,000)
        Invoice::create([
            'company_id' => $this->sisterConcern1->id,
            'customer_id' => $customer2->id,
            'invoice_no' => 'INV-ADEX-202',
            'invoice_date' => '2026-06-20',
            'subtotal' => 16949.15,
            'tax_amount' => 3050.85,
            'grand_total' => 20000.00,
            'paid_amount' => 5000.00,
            'status' => 'partial',
        ]);

        // Invoice 3 for VR Solution (₹15,000)
        Invoice::create([
            'company_id' => $this->sisterConcern2->id,
            'customer_id' => $customer2->id,
            'invoice_no' => 'INV-VR-303',
            'invoice_date' => '2026-07-10',
            'subtotal' => 12711.86,
            'tax_amount' => 2288.14,
            'grand_total' => 15000.00,
            'paid_amount' => 0.00,
            'status' => 'issued',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('organization.sister-concerns.reports', [
            'business_group_id' => $this->group->id,
            'report_type' => 'sales',
            'financial_year' => '2026-27',
        ]));

        $response->assertOk();
        $response->assertSee('INV-AVIT-101');
        $response->assertSee('INV-ADEX-202');
        $response->assertSee('INV-VR-303');
        $response->assertSee('Metro Supermarkets');
        $response->assertSee('Apex Retailers');

        // Total sales = 10,000 + 20,000 + 15,000 = 45,000.00
        $response->assertSee('45,000.00');
        // Total collected = 10,000 + 5,000 = 15,000.00
        $response->assertSee('15,000.00');
        // Total outstanding = 45,000 - 15,000 = 30,000.00
        $response->assertSee('30,000.00');
    }

    public function test_consolidated_sales_can_filter_by_individual_sister_concern(): void
    {
        $customer = Customer::create([
            'customer_type_id' => $this->customerType->id,
            'company_id' => $this->ownerCompany->id,
            'name' => 'Metro Supermarkets',
            'code' => 'CUST-001',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        Invoice::create([
            'company_id' => $this->ownerCompany->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-AVIT-101',
            'invoice_date' => '2026-05-15',
            'subtotal' => 8474.58,
            'tax_amount' => 1525.42,
            'grand_total' => 10000.00,
            'paid_amount' => 10000.00,
            'status' => 'paid',
        ]);

        Invoice::create([
            'company_id' => $this->sisterConcern1->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-ADEX-202',
            'invoice_date' => '2026-06-20',
            'subtotal' => 16949.15,
            'tax_amount' => 3050.85,
            'grand_total' => 20000.00,
            'paid_amount' => 5000.00,
            'status' => 'partial',
        ]);

        // Filter for Adex Digital Solutions only
        $response = $this->actingAs($this->adminUser)->get(route('organization.sister-concerns.reports', [
            'business_group_id' => $this->group->id,
            'company_id' => $this->sisterConcern1->id,
            'report_type' => 'sales',
            'financial_year' => '2026-27',
        ]));

        $response->assertOk();
        $response->assertSee('INV-ADEX-202');
        $response->assertDontSee('INV-AVIT-101');
        $response->assertSee('20,000.00');
    }

    public function test_consolidated_purchase_report_combines_purchases_across_sister_concerns(): void
    {
        $supplier = Customer::create([
            'customer_type_id' => $this->customerType->id,
            'company_id' => $this->ownerCompany->id,
            'name' => 'Global Tech Distributors',
            'code' => 'SUPP-001',
            'party_type' => 'vendor',
            'is_active' => true,
        ]);

        // Purchase Invoice for Avit Digital
        PurchaseInvoice::create([
            'company_id' => $this->ownerCompany->id,
            'supplier_id' => $supplier->id,
            'invoice_no' => 'PI-AVIT-501',
            'invoice_date' => '2026-05-10',
            'subtotal' => 8000.00,
            'tax_amount' => 1440.00,
            'grand_total' => 9440.00,
            'status' => 'posted',
        ]);

        // Purchase Invoice for VR Solution
        PurchaseInvoice::create([
            'company_id' => $this->sisterConcern2->id,
            'supplier_id' => $supplier->id,
            'invoice_no' => 'PI-VR-602',
            'invoice_date' => '2026-06-12',
            'subtotal' => 15000.00,
            'tax_amount' => 2700.00,
            'grand_total' => 17700.00,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('organization.sister-concerns.reports', [
            'business_group_id' => $this->group->id,
            'report_type' => 'purchases',
            'financial_year' => '2026-27',
        ]));

        $response->assertOk();
        $response->assertSee('PI-AVIT-501');
        $response->assertSee('PI-VR-602');
        $response->assertSee('Global Tech Distributors');

        // Total purchases = 9440 + 17700 = 27,140.00
        $response->assertSee('27,140.00');
    }

    public function test_single_view_consolidated_dashboard_displays_combined_kpis_and_breakdowns(): void
    {
        $party = Customer::create([
            'customer_type_id' => $this->customerType->id,
            'company_id' => $this->ownerCompany->id,
            'name' => 'General Enterprises',
            'code' => 'ENT-001',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        // Sales for Avit Digital (₹50,000)
        Invoice::create([
            'company_id' => $this->ownerCompany->id,
            'customer_id' => $party->id,
            'invoice_no' => 'INV-AVIT-50',
            'invoice_date' => '2026-05-20',
            'subtotal' => 42372.88,
            'tax_amount' => 7627.12,
            'grand_total' => 50000.00,
            'paid_amount' => 50000.00,
            'status' => 'paid',
        ]);

        // Purchases for Adex Digital Solutions (₹30,000)
        PurchaseInvoice::create([
            'company_id' => $this->sisterConcern1->id,
            'supplier_id' => $party->id,
            'invoice_no' => 'PI-ADEX-30',
            'invoice_date' => '2026-06-15',
            'subtotal' => 25423.73,
            'tax_amount' => 4576.27,
            'grand_total' => 30000.00,
            'status' => 'posted',
        ]);

        // Inter-company transaction between Avit Digital and Adex Digital (₹12,000)
        InterCompanyTransaction::create([
            'transaction_no' => 'ICT-TEST-001',
            'transaction_date' => '2026-07-01',
            'transaction_type' => 'Sale',
            'from_company_id' => $this->ownerCompany->id,
            'to_company_id' => $this->sisterConcern1->id,
            'amount' => 12000.00,
            'currency' => 'INR',
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('organization.sister-concerns.reports', [
            'business_group_id' => $this->group->id,
            'report_type' => 'all',
            'financial_year' => '2026-27',
        ]));

        $response->assertOk();
        $response->assertSee('Combined Sales');
        $response->assertSee('Combined Purchases');
        $response->assertSee('50,000.00');
        $response->assertSee('30,000.00');
        // Net position = 50,000 - 30,000 = +20,000.00
        $response->assertSee('20,000.00');
        // Breakdown sections
        $response->assertSee('Sales by Sister Concern');
        $response->assertSee('Purchases by Sister Concern');
        // Inter-company volume
        $response->assertSee('12,000.00');
    }

    public function test_financial_year_filtering_restricts_transactions_to_selected_period(): void
    {
        $party = Customer::create([
            'customer_type_id' => $this->customerType->id,
            'company_id' => $this->ownerCompany->id,
            'name' => 'General Enterprises',
            'code' => 'ENT-001',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        // Transaction in FY 2026-27
        Invoice::create([
            'company_id' => $this->ownerCompany->id,
            'customer_id' => $party->id,
            'invoice_no' => 'INV-IN-FY26',
            'invoice_date' => '2026-08-15',
            'subtotal' => 1000.00,
            'tax_amount' => 180.00,
            'grand_total' => 1180.00,
            'paid_amount' => 1180.00,
            'status' => 'paid',
        ]);

        // Older transaction outside FY 2026-27 (Feb 2026, which is FY 2025-26)
        Invoice::create([
            'company_id' => $this->ownerCompany->id,
            'customer_id' => $party->id,
            'invoice_no' => 'INV-OUT-FY25',
            'invoice_date' => '2026-02-10',
            'subtotal' => 5000.00,
            'tax_amount' => 900.00,
            'grand_total' => 5900.00,
            'paid_amount' => 5900.00,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('organization.sister-concerns.reports', [
            'business_group_id' => $this->group->id,
            'report_type' => 'sales',
            'financial_year' => '2026-27',
        ]));

        $response->assertOk();
        $response->assertSee('INV-IN-FY26');
        $response->assertDontSee('INV-OUT-FY25');
        $response->assertSee('1,180.00');
        $response->assertDontSee('5,900.00');
    }

    public function test_export_consolidated_sales_and_purchases_as_csv(): void
    {
        $party = Customer::create([
            'customer_type_id' => $this->customerType->id,
            'company_id' => $this->ownerCompany->id,
            'name' => 'Export Buyer',
            'code' => 'BUY-001',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        Invoice::create([
            'company_id' => $this->ownerCompany->id,
            'customer_id' => $party->id,
            'invoice_no' => 'INV-EXP-999',
            'invoice_date' => '2026-09-01',
            'subtotal' => 2000.00,
            'tax_amount' => 360.00,
            'grand_total' => 2360.00,
            'paid_amount' => 2360.00,
            'status' => 'paid',
        ]);

        // Export Sales
        $response = $this->actingAs($this->adminUser)->get(route('organization.sister-concerns.reports.export', [
            'business_group_id' => $this->group->id,
            'report_type' => 'sales',
            'financial_year' => '2026-27',
            'format' => 'csv',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // Export Purchases
        $responsePurchases = $this->actingAs($this->adminUser)->get(route('organization.sister-concerns.reports.export', [
            'business_group_id' => $this->group->id,
            'report_type' => 'purchases',
            'financial_year' => '2026-27',
            'format' => 'csv',
        ]));

        $responsePurchases->assertOk();
        $responsePurchases->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_unauthorized_user_cannot_access_consolidated_reports(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->get(route('organization.sister-concerns.reports'));
        $response->assertForbidden();

        $guestResponse = $this->get(route('organization.sister-concerns.reports'));
        $guestResponse->assertForbidden();
    }
}
