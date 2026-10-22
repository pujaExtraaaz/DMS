<?php

namespace Tests\Feature\Target;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\CreditNoteItem;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Target\Models\SalesTarget;
use App\Domains\Target\Models\SalesTargetAchievement;
use App\Domains\Target\Models\SalesTargetAlert;
use App\Domains\Target\Models\SalesTeam;
use App\Domains\Target\Services\SalesTargetCalculationService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesTargetManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $salesperson;
    protected Company $company;
    protected Branch $branch;
    protected FinancialYear $financialYear;
    protected Customer $customer;
    protected Brand $brand;
    protected Product $product;
    protected SalesTeam $team;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Nexus Distribution Ltd',
            'code' => 'NDL',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Mumbai Central Branch',
            'code' => 'MUM01',
            'is_active' => true,
        ]);

        $this->financialYear = FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Super Administrator',
            'email' => 'admin@nexus.com',
        ]);
        $this->adminUser->assignRole('super-admin');

        $this->salesperson = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Sakshi Chavan',
            'email' => 'sakshi@nexus.com',
        ]);
        $this->salesperson->assignRole('salesperson');

        $this->team = SalesTeam::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'leader_id' => $this->adminUser->id,
            'name' => 'West Zone Enterprise Sales',
            'code' => 'WZ-ENT',
            'is_active' => true,
        ]);
        $this->team->members()->attach($this->salesperson->id, ['role' => 'member']);

        $customerType = CustomerType::create(['name' => 'Retailer', 'code' => 'RET']);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'salesperson_id' => $this->salesperson->id,
            'customer_type_id' => $customerType->id,
            'name' => 'Apex Retailers Pvt Ltd',
            'code' => 'CUST-001',
            'party_type' => 'customer',
            'status' => 'active',
        ]);

        $this->brand = Brand::create([
            'name' => 'Sony Professional',
            'is_active' => true,
        ]);

        $uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brand->id,
            'base_uom_id' => $uom->id,
            'name' => 'Sony Bravia 65 4K Display',
            'sku' => 'SNY-65-4K',
            'serial_no' => 'SNY-SN-001',
            'selling_price' => 100000.00,
        ]);
    }

    public function test_sales_targets_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('sales-targets.dashboard'));
        $response->assertOk();
        $response->assertSee('Sales Target Dashboard');
        $response->assertSee('Total Assigned Target');
        $response->assertSee('Actual Achievement');
    }

    public function test_can_create_value_target_for_individual_salesman(): void
    {
        $payload = [
            'name' => 'Q1 Individual Target for Sakshi',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salesperson->id,
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'period_type' => 'quarterly',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-06-30',
            'target_amount' => 500000.00,
            'status' => 'active',
            'notes' => 'Q1 growth quota',
        ];

        $response = $this->actingAs($this->adminUser)->post(route('sales-targets.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('sales_targets', [
            'name' => 'Q1 Individual Target For Sakshi',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salesperson->id,
            'target_amount' => 500000.00,
            'status' => 'active',
        ]);

        $target = SalesTarget::where('name', 'Q1 Individual Target For Sakshi')->first();
        $this->assertNotNull($target);
        $this->assertNotNull($target->achievement);
        $this->assertEquals(0, $target->achievement->achieved_amount);
        $this->assertEquals(500000.00, $target->achievement->pending_amount);
    }

    public function test_actual_achievement_calculates_automatically_from_real_sales_invoice(): void
    {
        // 1. Create Target
        $target = SalesTarget::create([
            'name' => 'Sony Monthly Target',
            'target_type' => 'brand',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salesperson->id,
            'brand_id' => $this->brand->id,
            'company_id' => $this->company->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 200000.00,
            'target_quantity' => 2,
            'status' => 'active',
        ]);

        // 2. Post a real invoice in that period for this salesperson
        $invoice = Invoice::create([
            'invoice_no' => 'INV-TEST-001',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson->id,
            'invoice_date' => '2026-05-15',
            'due_date' => '2026-06-15',
            'status' => 'posted',
            'subtotal' => 200000.00,
            'tax_amount' => 36000.00,
            'grand_total' => 236000.00,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->product->base_uom_id,
            'quantity' => 2,
            'unit_price' => 100000.00,
            'tax_amount' => 36000.00,
            'line_total' => 200000.00,
        ]);

        // 3. Trigger or let observer recalculate
        app(SalesTargetCalculationService::class)->calculateTarget($target);

        $target->refresh();
        $this->assertNotNull($target->achievement);
        $this->assertEquals(200000.00, $target->achievement->achieved_amount);
        $this->assertEquals(2, $target->achievement->achieved_quantity);
        $this->assertEquals(100.0, (float)$target->achievement->achievement_percent);
        $this->assertTrue((bool)$target->achievement->is_achieved);
    }

    public function test_credit_note_reduces_target_achievement(): void
    {
        $target = SalesTarget::create([
            'name' => 'Sakshi June Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salesperson->id,
            'company_id' => $this->company->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-30',
            'target_amount' => 100000.00,
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'invoice_no' => 'INV-TEST-002',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson->id,
            'invoice_date' => '2026-06-10',
            'due_date' => '2026-07-10',
            'status' => 'posted',
            'subtotal' => 100000.00,
            'tax_amount' => 18000.00,
            'grand_total' => 118000.00,
        ]);

        CreditNote::create([
            'credit_note_no' => 'CN-TEST-001',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'credit_note_date' => '2026-06-15',
            'status' => 'posted',
            'subtotal' => 20000.00,
            'tax_amount' => 3600.00,
            'grand_total' => 23600.00,
        ]);

        app(SalesTargetCalculationService::class)->calculateTarget($target);

        $target->refresh();
        // 118000 - 23600 = 94400
        $this->assertEquals(94400.00, $target->achievement->achieved_amount);
        $this->assertEquals(5600.00, $target->achievement->pending_amount);
        $this->assertEquals(94.4, (float)$target->achievement->achievement_percent);
    }

    public function test_cancelled_invoice_is_excluded_from_achievement(): void
    {
        $target = SalesTarget::create([
            'name' => 'Sakshi July Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salesperson->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-07-31',
            'target_amount' => 100000.00,
            'status' => 'active',
        ]);

        Invoice::create([
            'invoice_no' => 'INV-CANCELLED-001',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson->id,
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-08-10',
            'status' => 'cancelled', // Should be excluded!
            'subtotal' => 100000.00,
            'grand_total' => 118000.00,
        ]);

        app(SalesTargetCalculationService::class)->calculateTarget($target);

        $target->refresh();
        $this->assertEquals(0, $target->achievement->achieved_amount);
    }

    public function test_threshold_alert_is_generated_at_threshold_crossing(): void
    {
        $target = SalesTarget::create([
            'name' => 'Alert Test Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salesperson->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-08-31',
            'target_amount' => 100000.00,
            'status' => 'active',
        ]);

        // Invoice of 50,000 (50% threshold)
        Invoice::create([
            'invoice_no' => 'INV-ALERT-001',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson->id,
            'invoice_date' => '2026-08-10',
            'status' => 'posted',
            'grand_total' => 50000.00,
        ]);

        app(SalesTargetCalculationService::class)->calculateTarget($target);

        $this->assertDatabaseHas('sales_target_alerts', [
            'sales_target_id' => $target->id,
            'alert_type' => 'threshold_50',
            'threshold_percent' => 50.00,
        ]);
    }

    public function test_target_carry_forward_creates_new_active_target(): void
    {
        $target = SalesTarget::create([
            'name' => 'Carry Forward Source Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salesperson->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'target_amount' => 100000.00,
            'status' => 'active',
        ]);

        // Set achievement to 40,000 (pending = 60,000)
        SalesTargetAchievement::create([
            'sales_target_id' => $target->id,
            'achieved_amount' => 40000.00,
            'pending_amount' => 60000.00,
            'achievement_percent' => 40.0,
            'is_achieved' => false,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('sales-targets.carry-forward', $target), [
            'next_starts_on' => '2026-10-01',
            'next_ends_on' => '2026-10-31',
            'authorization_reason' => 'Approved by VP Sales due to supply chain backlog in September',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('sales_targets', [
            'carried_forward_from_id' => $target->id,
            'target_amount' => 60000.00,
            'status' => 'active',
        ]);

        $newTarget = SalesTarget::where('carried_forward_from_id', $target->id)->first();
        $this->assertNotNull($newTarget);
        $this->assertEquals('2026-10-01', $newTarget->starts_on->format('Y-m-d'));
        $this->assertEquals('2026-10-31', $newTarget->ends_on->format('Y-m-d'));
    }

    public function test_target_reports_and_excel_export(): void
    {
        SalesTarget::create([
            'name' => 'Report Sample Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salesperson->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-31',
            'target_amount' => 150000.00,
            'status' => 'active',
        ]);

        // View reports HTML
        $response = $this->actingAs($this->adminUser)->get(route('sales-targets.reports'));
        $response->assertOk();
        $response->assertSee('Report Sample Target');

        // Excel Export
        $exportResponse = $this->actingAs($this->adminUser)->get(route('sales-targets.reports', ['export' => 'xlsx']));
        $exportResponse->assertOk();
        $disposition = (string) $exportResponse->headers->get('content-disposition');
        $this->assertTrue(
            str_contains(strtolower($disposition), 'sales-targets-report') ||
            str_contains((string) $exportResponse->headers->get('content-type'), 'spreadsheetml.sheet')
        );
    }

    public function test_sidebar_navigation_places_sales_target_management_under_hr_crm(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertOk();

        $content = $response->getContent();

        // 1. Verify 8 · Commercials and 9 · HR · CRM exist in proper order
        $posCommercials = strpos($content, '8 · Commercials');
        $posHrCrm = strpos($content, '9 · HR · CRM');
        $posBooks = strpos($content, '10 · Accounting (Books)');
        $posReports = strpos($content, '11 · Reports');

        $this->assertNotFalse($posCommercials, '8 · Commercials missing in sidebar');
        $this->assertNotFalse($posHrCrm, '9 · HR · CRM missing in sidebar');
        $this->assertNotFalse($posBooks, '10 · Accounting (Books) missing in sidebar');
        $this->assertNotFalse($posReports, '11 · Reports missing in sidebar');

        $this->assertLessThan($posHrCrm, $posCommercials, '8 · Commercials must appear before 9 · HR · CRM');
        $this->assertLessThan($posBooks, $posHrCrm, '9 · HR · CRM must appear before 10 · Accounting (Books)');
        $this->assertLessThan($posReports, $posBooks, '10 · Accounting (Books) must appear before 11 · Reports');

        // 2. Sales Target Management must appear AFTER 9 · HR · CRM and BEFORE 10 · Accounting (Books)
        $posTargetSubmenu = strpos($content, 'Sales Target Management');
        $this->assertNotFalse($posTargetSubmenu, 'Sales Target Management missing from sidebar');
        $this->assertGreaterThan($posHrCrm, $posTargetSubmenu, 'Sales Target Management must be inside/after 9 · HR · CRM');
        $this->assertLessThan($posBooks, $posTargetSubmenu, 'Sales Target Management must appear before 10 · Accounting (Books)');

        // 3. Verify all 7 links are present in sidebar
        $response->assertSee(route('sales-targets.dashboard'));
        $response->assertSee(route('sales-targets.create'));
        $response->assertSee(route('sales-targets.index'));
        $response->assertSee(route('sales-targets.salesman-performance'));
        $response->assertSee(route('sales-targets.team-branch-performance'));
        $response->assertSee(route('sales-targets.alerts'));
        $response->assertSee(route('sales-targets.reports'));
    }

    public function test_all_seven_sales_target_management_routes_are_accessible_and_expand_hr_crm(): void
    {
        $routes = [
            route('sales-targets.dashboard'),
            route('sales-targets.create'),
            route('sales-targets.index'),
            route('sales-targets.salesman-performance'),
            route('sales-targets.team-branch-performance'),
            route('sales-targets.alerts'),
            route('sales-targets.reports'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($this->adminUser)->get($url);
            $response->assertOk();

            // HR · CRM parent container should be open
            $response->assertSee('9 · HR · CRM');
            $response->assertSee('Sales Target Management');
        }
    }

    public function test_unauthenticated_user_cannot_access_sales_target_management(): void
    {
        $response = $this->get(route('sales-targets.dashboard'));
        $response->assertRedirect(route('login'));
    }
}
