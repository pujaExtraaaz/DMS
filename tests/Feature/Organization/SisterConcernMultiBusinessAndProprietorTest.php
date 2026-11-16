<?php

namespace Tests\Feature\Organization;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SisterConcernMultiBusinessAndProprietorTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected BusinessGroup $group;
    protected Company $business1;
    protected Company $business2;
    protected FinancialYear $financialYear;
    protected CustomerType $customerType;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles & Permissions
        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->customerType = CustomerType::create([
            'name' => 'Standard Client',
            'code' => 'STD',
            'is_active' => true,
        ]);

        // 2. Business 1: AVIT Digital Central (Existing Business)
        $this->business1 = Company::create([
            'name' => 'AVIT Digital Central Ltd',
            'code' => 'ADC-01',
            'legal_name' => 'AVIT Digital Central Private Limited',
            'business_type' => 'Existing Business',
            'main_work' => 'AV / IT equipment, displays, projectors and audio solutions',
            'nature_of_business' => 'AV & IT Solutions',
            'gstin' => '27AAACA1234A1Z5',
            'pan' => 'ABCDE1234F',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'bank_name' => 'HDFC Bank',
            'bank_account_no' => '50200012345678',
            'bank_ifsc' => 'HDFC0001234',
            'bank_branch' => 'Nariman Point, Mumbai',
            'is_active' => true,
            'is_default' => true,
        ]);

        // 3. Business Group with Common Proprietor
        $this->group = BusinessGroup::create([
            'name' => 'Vivek Gupta Enterprises',
            'code' => 'VGE-01',
            'description' => 'Multi-business enterprise under common proprietor oversight',
            'parent_company_id' => $this->business1->id,
            'proprietor_name' => 'Mr. Vivek Gupta',
            'proprietor_relationship' => 'Common ownership and business oversight',
            'proprietor_email' => 'vivek.gupta@avitdigital.com',
            'proprietor_phone' => '+91 98765 43210',
            'proprietor_pan' => 'ABCDE1234F',
            'is_active' => true,
        ]);

        $this->business1->update(['business_group_id' => $this->group->id]);

        // 4. Business 2: AVIT Tech Solutions (Proposed New Business)
        $this->business2 = Company::create([
            'business_group_id' => $this->group->id,
            'name' => 'AVIT Tech Solutions Ltd',
            'code' => 'ATS-02',
            'legal_name' => 'AVIT Tech Solutions Private Limited',
            'business_type' => 'Proposed New Business',
            'main_work' => 'Smart boardroom automation, acoustic treatment, and UC software integration',
            'nature_of_business' => 'Smart Automation & Systems Integration',
            'gstin' => '27AAACA9876B1Z2',
            'pan' => 'ABCDE1234F',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'bank_name' => 'ICICI Bank',
            'bank_account_no' => '001105009999',
            'bank_ifsc' => 'ICIC0000011',
            'bank_branch' => 'Shivajinagar, Pune',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->group->companies()->sync([
            $this->business1->id,
            $this->business2->id,
        ]);

        // 5. Financial Year 2026-27
        $this->financialYear = FinancialYear::create([
            'company_id' => $this->business1->id,
            'name' => '2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);

        // 6. Admin User
        $this->adminUser = User::factory()->create([
            'company_id' => $this->business1->id,
            'name' => 'Vivek Gupta Admin',
            'email' => 'admin.vg@example.com',
        ]);
        $this->adminUser->assignRole($adminRole);
    }

    /**
     * Test Common Proprietor header details and associated business cards render accurately.
     */
    public function test_common_proprietor_and_business_cards_render_on_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.dashboard', ['business_group_id' => $this->group->id]));

        $response->assertStatus(200);

        // Common Proprietor Header Assertions
        $response->assertSee('Mr. Vivek Gupta');
        $response->assertSee('ABCDE1234F');
        $response->assertSee('Edit Proprietor');
        $response->assertSee('+ Add Business');

        // Business 1 Details
        $response->assertSee('Avit Digital Central Ltd');
        $response->assertSee('Existing Business');
        $response->assertSee('27AAACA1234A1Z5');
        $response->assertSee('Hdfc Bank');
        $response->assertSee('50200012345678');
        $response->assertSee('HDFC0001234');

        // Business 2 Details
        $response->assertSee('Avit Tech Solutions Ltd');
        $response->assertSee('Proposed New Business');
        $response->assertSee('27AAACA9876B1Z2');
        $response->assertSee('Icici Bank');
        $response->assertSee('001105009999');

        // 6 Consolidated Summary KPI cards headers
        $response->assertSee('Combined Sales');
        $response->assertSee('Combined Purchases');
        $response->assertSee('Combined Expenses');
        $response->assertSee('Combined Profit');
        $response->assertSee('Total Receivables');
        $response->assertSee('Total Payables');
    }

    /**
     * Test updating Common Proprietor details updates database record.
     */
    public function test_can_update_common_proprietor_details(): void
    {
        $payload = [
            'business_group_id' => $this->group->id,
            'proprietor_name' => 'Mr. Vivek K. Gupta',
            'proprietor_relationship' => 'Sole Proprietor & Managing Director',
            'proprietor_email' => 'vivek.k.gupta@avitdigital.com',
            'proprietor_phone' => '+91 99999 88888',
            'proprietor_pan' => 'VKGUP1234K',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.sister-concerns.proprietor.update'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('business_groups', [
            'id' => $this->group->id,
            'proprietor_name' => 'Mr. Vivek K. Gupta',
            'proprietor_relationship' => 'Sole Proprietor & Managing Director',
            'proprietor_pan' => 'VKGUP1234K',
        ]);
    }

    /**
     * Test associating a business with nature of business & business type.
     */
    public function test_can_associate_business_to_proprietor_group(): void
    {
        $thirdBusiness = Company::create([
            'name' => 'AVIT Cloud Services Ltd',
            'code' => 'ACS-03',
            'is_active' => true,
        ]);

        $payload = [
            'business_group_id' => $this->group->id,
            'company_id' => $thirdBusiness->id,
            'business_type' => 'Subsidiary',
            'main_work' => 'Cloud infrastructure and unified communications hosting',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.sister-concerns.associate-business'), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'id' => $thirdBusiness->id,
            'business_group_id' => $this->group->id,
            'business_type' => 'Subsidiary',
        ]);
    }

    /**
     * Test consolidated dashboard calculates financial metrics from independent sales and purchases.
     */
    public function test_consolidated_dashboard_calculates_combined_metrics_accurately(): void
    {
        $today = Carbon::parse('2026-06-15')->toDateString();

        // Customer for Business 1
        $customer1 = Customer::create([
            'company_id' => $this->business1->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Client One Corp',
            'code' => 'CUST-001',
            'is_active' => true,
        ]);

        // Invoice 1 for Business 1: ₹5,00,000
        Invoice::create([
            'company_id' => $this->business1->id,
            'customer_id' => $customer1->id,
            'invoice_no' => 'INV-ADC-101',
            'invoice_date' => $today,
            'subtotal' => 450000,
            'tax_amount' => 50000,
            'grand_total' => 500000,
            'paid_amount' => 300000,
            'balance_due' => 200000,
            'status' => 'issued',
        ]);

        // Customer for Business 2
        $customer2 = Customer::create([
            'company_id' => $this->business2->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Client Two Corp',
            'code' => 'CUST-002',
            'is_active' => true,
        ]);

        // Invoice 2 for Business 2: ₹3,00,000
        Invoice::create([
            'company_id' => $this->business2->id,
            'customer_id' => $customer2->id,
            'invoice_no' => 'INV-ATS-201',
            'invoice_date' => $today,
            'subtotal' => 270000,
            'tax_amount' => 30000,
            'grand_total' => 300000,
            'paid_amount' => 100000,
            'balance_due' => 200000,
            'status' => 'issued',
        ]);

        // Supplier for Business 1
        $supplier1 = Customer::create([
            'company_id' => $this->business1->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Hardware Distributor India',
            'code' => 'SUP-001',
            'party_type' => 'supplier',
            'is_active' => true,
        ]);

        // Purchase Invoice for Business 1: ₹2,50,000
        PurchaseInvoice::create([
            'company_id' => $this->business1->id,
            'supplier_id' => $supplier1->id,
            'invoice_no' => 'PINV-ADC-01',
            'invoice_date' => $today,
            'subtotal' => 220000,
            'tax_amount' => 30000,
            'grand_total' => 250000,
            'paid_amount' => 100000,
            'balance_due' => 150000,
            'status' => 'posted',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.dashboard', [
                'business_group_id' => $this->group->id,
                'company_id' => 'all',
                'from_date' => '2026-04-01',
                'to_date' => '2027-03-31',
            ]));

        $response->assertStatus(200);

        // Combined Sales: ₹5,00,000 + ₹3,00,000 = ₹8,00,000
        $response->assertViewHas('combined', function ($combined) {
            return (float) $combined['sales'] === 800000.0
                && (float) $combined['purchases'] === 250000.0
                && (float) $combined['gross_profit'] === 550000.0
                && (float) $combined['receivables'] === 400000.0
                && (float) $combined['payables'] === 250000.0;
        });

        // Verification of Business Comparison Matrix
        $response->assertViewHas('comparisonCompanies', function ($companies) {
            $c1 = collect($companies)->firstWhere('company_id', $this->business1->id);
            $c2 = collect($companies)->firstWhere('company_id', $this->business2->id);

            return $c1 && $c2
                && (float) $c1['sales'] === 500000.0
                && (float) $c1['purchases'] === 250000.0
                && (float) $c1['receivables'] === 200000.0
                && (float) $c2['sales'] === 300000.0
                && (float) $c2['purchases'] === 0.0
                && (float) $c2['receivables'] === 200000.0;
        });
    }

    /**
     * Test selecting an individual business filters metrics to that business alone.
     */
    public function test_business_selector_filters_metrics_to_individual_business(): void
    {
        $today = Carbon::parse('2026-06-15')->toDateString();

        $cust = Customer::create([
            'company_id' => $this->business1->id,
            'customer_type_id' => $this->customerType->id,
            'name' => 'Single Client',
            'code' => 'SC-01',
            'is_active' => true,
        ]);

        Invoice::create([
            'company_id' => $this->business1->id,
            'customer_id' => $cust->id,
            'invoice_no' => 'INV-ADC-999',
            'invoice_date' => $today,
            'subtotal' => 125000,
            'grand_total' => 125000,
            'balance_due' => 125000,
            'status' => 'issued',
        ]);

        // Filter specifically by business 1
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.dashboard', [
                'business_group_id' => $this->group->id,
                'company_id' => $this->business1->id,
                'from_date' => '2026-04-01',
                'to_date' => '2027-03-31',
            ]));

        $response->assertStatus(200);
        $response->assertViewHas('combined', function ($combined) {
            return (float) $combined['sales'] === 125000.0;
        });
    }

    /**
     * Test multi-format exports for Consolidated Reporting (CSV, Excel, PDF).
     */
    public function test_can_export_comparison_report_in_csv_excel_and_pdf(): void
    {
        // CSV Export
        $responseCsv = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.reports.export', [
                'report_type' => 'all',
                'business_group_id' => $this->group->id,
                'format' => 'csv',
            ]));

        $responseCsv->assertStatus(200);
        $this->assertStringContainsString('text/csv', $responseCsv->headers->get('content-type'));

        // Excel Export
        $responseExcel = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.reports.export', [
                'report_type' => 'all',
                'business_group_id' => $this->group->id,
                'format' => 'excel',
            ]));

        $responseExcel->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $responseExcel->headers->get('content-type'));

        // PDF Export
        $responsePdf = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.reports.export', [
                'report_type' => 'all',
                'business_group_id' => $this->group->id,
                'format' => 'pdf',
            ]));

        $responsePdf->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $responsePdf->headers->get('content-type'));
    }
}
