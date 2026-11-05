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
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Target\Models\SalesTarget;
use App\Domains\Target\Models\SalesTargetAchievement;
use App\Domains\Target\Services\SalesTargetCalculationService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyWiseSalesTargetTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;
    protected FinancialYear $financialYear;
    protected Customer $customer1;
    protected Customer $customer2;
    protected Brand $brand;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TC',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB01',
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
            'name' => 'Admin User',
            'email' => 'admin@test.com',
        ]);
        $this->adminUser->assignRole('super-admin');

        $customerType = CustomerType::create(['name' => 'Dealer', 'code' => 'DLR']);

        $this->customer1 = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_type_id' => $customerType->id,
            'name' => 'Om Electronics',
            'code' => 'CUST001',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        $this->customer2 = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_type_id' => $customerType->id,
            'name' => 'Apex Retailers',
            'code' => 'CUST002',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        $this->brand = Brand::create(['name' => 'Sony', 'code' => 'SNY']);
        $category = Category::create(['name' => 'Televisions', 'code' => 'TV']);
        $uom = Uom::create(['company_id' => $this->company->id, 'name' => 'Piece', 'code' => 'PCS']);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Bravia 55 OLED',
            'sku' => 'SNY-BRV-55',
            'serial_no' => 'SNY-SN-001',
            'brand_id' => $this->brand->id,
            'category_id' => $category->id,
            'base_uom_id' => $uom->id,
            'purchase_price' => 50000,
            'selling_price' => 75000,
            'mrp' => 80000,
            'is_active' => true,
        ]);
    }

    public function test_can_assign_sales_target_to_party(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('sales-targets.store'), [
            'name' => 'Om Electronics Q1 Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 500000,
            'status' => 'active',
            'notes' => 'Dealer target for Om Electronics',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('sales_targets', [
            'name' => 'Om Electronics Q1 Target',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'target_amount' => 500000,
            'status' => 'active',
        ]);

        $target = SalesTarget::where('customer_id', $this->customer1->id)->first();
        $this->assertEquals('Om Electronics', $target->owner_name);
        $this->assertEquals($this->customer1->id, $target->customer->id);
    }

    public function test_party_target_requires_valid_party_and_positive_amount(): void
    {
        // customer_id required if owner_type is party
        $response = $this->actingAs($this->adminUser)->post(route('sales-targets.store'), [
            'name' => 'Invalid Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => '',
            'period_type' => 'monthly',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 500000,
            'status' => 'active',
        ]);
        $response->assertSessionHasErrors('customer_id');

        // target_amount must be greater than zero
        $responseZero = $this->actingAs($this->adminUser)->post(route('sales-targets.store'), [
            'name' => 'Zero Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 0,
            'status' => 'active',
        ]);
        $responseZero->assertSessionHasErrors('target_amount');
    }

    public function test_duplicate_party_target_prevention(): void
    {
        SalesTarget::create([
            'name' => 'Existing Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 200000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('sales-targets.store'), [
            'name' => 'Duplicate Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 300000,
            'status' => 'active',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, SalesTarget::where('customer_id', $this->customer1->id)->count());
    }

    public function test_automatic_achievement_and_shortfall_calculation(): void
    {
        $target = SalesTarget::create([
            'name' => 'Om Electronics June Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-30',
            'target_amount' => 100000,
            'status' => 'active',
        ]);

        // 1. Eligible Invoice for customer1 inside period (₹ 60,000)
        $inv1 = Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer1->id,
            'invoice_no' => 'INV-001',
            'invoice_date' => '2026-06-10',
            'due_date' => '2026-06-25',
            'status' => 'posted',
            'subtotal' => 60000,
            'grand_total' => 60000,
        ]);

        // 2. Invoice for customer1 outside period (₹ 50,000 in July) -> should NOT count
        Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer1->id,
            'invoice_no' => 'INV-002',
            'invoice_date' => '2026-07-05',
            'due_date' => '2026-07-20',
            'status' => 'posted',
            'subtotal' => 50000,
            'grand_total' => 50000,
        ]);

        // 3. Invoice for different customer inside period (₹ 40,000) -> should NOT count
        Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer2->id,
            'invoice_no' => 'INV-003',
            'invoice_date' => '2026-06-12',
            'due_date' => '2026-06-26',
            'status' => 'posted',
            'subtotal' => 40000,
            'grand_total' => 40000,
        ]);

        // 4. Cancelled invoice for customer1 inside period -> should NOT count
        Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer1->id,
            'invoice_no' => 'INV-004',
            'invoice_date' => '2026-06-15',
            'due_date' => '2026-06-28',
            'status' => 'cancelled',
            'subtotal' => 30000,
            'grand_total' => 30000,
        ]);

        // Calculate target
        $service = app(SalesTargetCalculationService::class);
        $achievement = $service->calculateTarget($target);

        // Target: 100,000, Achieved: 60,000, Shortfall: 40,000, %: 60.0
        $this->assertEquals(60000.0, (float) $achievement->achieved_amount);
        $this->assertEquals(40000.0, (float) $achievement->pending_amount);
        $this->assertEquals(60.0, (float) $achievement->achievement_percent);
        $this->assertFalse($achievement->is_achieved);

        // 5. Posted Credit Note for customer1 inside period (₹ 10,000) -> deducts from achievement
        CreditNote::create([
            'customer_id' => $this->customer1->id,
            'invoice_id' => $inv1->id,
            'credit_note_no' => 'CN-001',
            'credit_note_date' => '2026-06-20',
            'status' => 'posted',
            'subtotal' => 10000,
            'grand_total' => 10000,
        ]);

        $achievementAfterCN = $service->calculateTarget($target);
        // Achieved: 60,000 - 10,000 = 50,000, Shortfall: 50,000, %: 50.0
        $this->assertEquals(50000.0, (float) $achievementAfterCN->achieved_amount);
        $this->assertEquals(50000.0, (float) $achievementAfterCN->pending_amount);
        $this->assertEquals(50.0, (float) $achievementAfterCN->achievement_percent);

        // 6. Additional Invoice to achieve target (₹ 60,000)
        Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer1->id,
            'invoice_no' => 'INV-005',
            'invoice_date' => '2026-06-22',
            'due_date' => '2026-06-30',
            'status' => 'posted',
            'subtotal' => 60000,
            'grand_total' => 60000,
        ]);

        $achievementFinal = $service->calculateTarget($target);
        // Net Achieved: 60,000 - 10,000 + 60,000 = 110,000 -> fully achieved!
        $this->assertEquals(110000.0, (float) $achievementFinal->achieved_amount);
        $this->assertEquals(0.0, (float) $achievementFinal->pending_amount);
        $this->assertEquals(110.0, (float) $achievementFinal->achievement_percent);
        $this->assertTrue($achievementFinal->is_achieved);
    }

    public function test_dashboard_renders_party_filter_and_party_wise_table(): void
    {
        $target = SalesTarget::create([
            'name' => 'Om Electronics July Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-07-31',
            'target_amount' => 250000,
            'status' => 'active',
        ]);

        app(SalesTargetCalculationService::class)->calculateTarget($target);

        $response = $this->actingAs($this->adminUser)->get(route('sales-targets.dashboard'));
        $response->assertOk();
        $response->assertSee('Party-Wise Sales Targets');
        $response->assertSee('Om Electronics');
        $response->assertSee('250,000.00');

        // Test filtering by customer_id
        $filterResponse = $this->actingAs($this->adminUser)->get(route('sales-targets.dashboard', [
            'customer_id' => $this->customer1->id,
        ]));
        $filterResponse->assertOk();
        $filterResponse->assertSee('Om Electronics');
    }

    public function test_index_and_reports_filter_by_party(): void
    {
        $target1 = SalesTarget::create([
            'name' => 'Target for Customer 1',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-08-31',
            'target_amount' => 150000,
            'status' => 'active',
        ]);

        $target2 = SalesTarget::create([
            'name' => 'Target for Customer 2',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer2->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-08-31',
            'target_amount' => 300000,
            'status' => 'active',
        ]);

        // Filter index by customer1
        $responseIndex = $this->actingAs($this->adminUser)->get(route('sales-targets.index', [
            'customer_id' => $this->customer1->id,
        ]));
        $responseIndex->assertOk();
        $responseIndex->assertSee($target1->name);
        $responseIndex->assertDontSee($target2->name);

        // Filter reports by customer2
        $responseReports = $this->actingAs($this->adminUser)->get(route('sales-targets.reports', [
            'customer_id' => $this->customer2->id,
        ]));
        $responseReports->assertOk();
        $responseReports->assertSee($target2->name);
        $responseReports->assertDontSee($target1->name);
    }

    public function test_invoice_creation_and_status_changes_trigger_observer_recalculation(): void
    {
        $target = SalesTarget::create([
            'name' => 'Auto Recalc Test Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'target_amount' => 200000,
            'status' => 'active',
        ]);

        // Creating invoice via Eloquent triggers SalesInvoiceTargetObserver
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer1->id,
            'invoice_no' => 'INV-AUTO-01',
            'invoice_date' => '2026-09-10',
            'due_date' => '2026-09-25',
            'status' => 'posted',
            'subtotal' => 80000,
            'grand_total' => 80000,
        ]);

        $achievement = $target->fresh()->achievement;
        $this->assertNotNull($achievement);
        $this->assertEquals(80000.0, (float) $achievement->achieved_amount);
        $this->assertEquals(120000.0, (float) $achievement->pending_amount);
        $this->assertEquals(40.0, (float) $achievement->achievement_percent);

        // Posting a credit note via Eloquent triggers observer recalculation
        CreditNote::create([
            'customer_id' => $this->customer1->id,
            'invoice_id' => $invoice->id,
            'credit_note_no' => 'CN-AUTO-01',
            'credit_note_date' => '2026-09-15',
            'status' => 'posted',
            'subtotal' => 20000,
            'grand_total' => 20000,
        ]);

        $achievementAfterCN = $target->fresh()->achievement;
        $this->assertEquals(60000.0, (float) $achievementAfterCN->achieved_amount);
        $this->assertEquals(140000.0, (float) $achievementAfterCN->pending_amount);
        $this->assertEquals(30.0, (float) $achievementAfterCN->achievement_percent);

        // Cancelling invoice triggers observer recalculation
        $invoice->update(['status' => 'cancelled']);

        $achievementCancelled = $target->fresh()->achievement;
        // Invoice is cancelled, so eligible invoice total is 0, net is 0
        $this->assertEquals(0.0, (float) $achievementCancelled->achieved_amount);
        $this->assertEquals(200000.0, (float) $achievementCancelled->pending_amount);
        $this->assertEquals(0.0, (float) $achievementCancelled->achievement_percent);
    }

    public function test_target_status_priorities_and_remaining_days_labels(): void
    {
        Carbon::setTestNow('2026-06-15');

        // 1. Future target -> Not Started, Starts in X days
        $futureTarget = SalesTarget::create([
            'name' => 'Future Party Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-07-31',
            'target_amount' => 100000,
            'status' => 'active',
        ]);
        $this->assertEquals('Not Started', $futureTarget->progress_status);
        $this->assertEquals(16, $futureTarget->remaining_days);
        $this->assertEquals('Starts In 16 Days', $futureTarget->remaining_days_label);

        // 2. In progress unachieved target -> In Progress, X days remaining
        $activeTarget = SalesTarget::create([
            'name' => 'Active Party Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-30',
            'target_amount' => 100000,
            'status' => 'active',
        ]);
        $this->assertEquals('In Progress', $activeTarget->progress_status);
        $this->assertEquals(15, $activeTarget->remaining_days);
        $this->assertEquals('15 Days Remaining', $activeTarget->remaining_days_label);

        // 3. Target ending today -> In Progress, Ends today
        $endingTodayTarget = SalesTarget::create([
            'name' => 'Ending Today Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-15',
            'target_amount' => 100000,
            'status' => 'active',
        ]);
        $this->assertEquals('In Progress', $endingTodayTarget->progress_status);
        $this->assertEquals(0, $endingTodayTarget->remaining_days);
        $this->assertEquals('Ends Today', $endingTodayTarget->remaining_days_label);

        // 4. Achieved target -> Achieved, 0 days needed (Priority 1)
        SalesTargetAchievement::create([
            'sales_target_id' => $activeTarget->id,
            'target_amount' => 100000,
            'achieved_amount' => 120000,
            'pending_amount' => 0,
            'achievement_percent' => 120.0,
            'is_achieved' => true,
            'status' => 'completed',
        ]);
        $activeTarget->unsetRelation('achievement');
        $this->assertEquals('Achieved', $activeTarget->progress_status);
        $this->assertEquals(0, $activeTarget->remaining_days);
        $this->assertEquals('0 Days Needed', $activeTarget->remaining_days_label);

        // 5. Expired unachieved target -> Period Expired, Period Expired (Priority 2)
        $expiredTarget = SalesTarget::create([
            'name' => 'Expired Party Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 150000,
            'status' => 'active',
        ]);
        $this->assertEquals('Period Expired', $expiredTarget->progress_status);
        $this->assertNull($expiredTarget->remaining_days);
        $this->assertEquals('Period Expired', $expiredTarget->remaining_days_label);
        $this->assertEquals(150000.0, $expiredTarget->shortfall_amount);

        Carbon::setTestNow();
    }

    public function test_changing_invoice_party_or_date_triggers_recalculation_for_both_parties(): void
    {
        $targetParty1 = SalesTarget::create([
            'name' => 'Party 1 Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-08-31',
            'target_amount' => 100000,
            'status' => 'active',
        ]);

        $targetParty2 = SalesTarget::create([
            'name' => 'Party 2 Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer2->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-08-31',
            'target_amount' => 100000,
            'status' => 'active',
        ]);

        // Invoice originally assigned to customer1
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer1->id,
            'invoice_no' => 'INV-CHANGE-01',
            'invoice_date' => '2026-08-10',
            'due_date' => '2026-08-25',
            'status' => 'posted',
            'subtotal' => 50000,
            'grand_total' => 50000,
        ]);

        $this->assertEquals(50000.0, (float) $targetParty1->fresh()->achievement?->achieved_amount);
        $this->assertNull($targetParty2->fresh()->achievement);

        // Edit invoice to customer2
        $invoice->update(['customer_id' => $this->customer2->id]);

        // Target for party 1 decreases to 0, target for party 2 increases to 50000
        $this->assertEquals(0.0, (float) $targetParty1->fresh()->achievement?->achieved_amount);
        $this->assertEquals(50000.0, (float) $targetParty2->fresh()->achievement?->achieved_amount);
    }

    public function test_dashboard_and_index_views_display_remaining_days_and_status(): void
    {
        Carbon::setTestNow('2026-06-15');

        $target = SalesTarget::create([
            'name' => 'View Display Test Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer1->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-30',
            'target_amount' => 100000,
            'status' => 'active',
        ]);

        $responseIndex = $this->actingAs($this->adminUser)->get(route('sales-targets.index'));
        $responseIndex->assertOk();
        $responseIndex->assertSee('15 Days Remaining');
        $responseIndex->assertSee('In Progress');

        $responseDashboard = $this->actingAs($this->adminUser)->get(route('sales-targets.dashboard'));
        $responseDashboard->assertOk();
        $responseDashboard->assertSee('15 Days Remaining');
        $responseDashboard->assertSee('In Progress');

        Carbon::setTestNow();
    }
}
