<?php

namespace Tests\Feature\Reporting;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardDateRangeFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customer;
    protected Customer $supplier;
    protected FinancialYear $currentFy;
    protected FinancialYear $lastFy;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Apex Distribution Corp',
            'code' => 'ADC-01',
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);

        $now = now();
        $currStartYear = $now->month >= 4 ? $now->year : $now->year - 1;

        $this->currentFy = FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => $currStartYear.'-'.($currStartYear + 1),
            'starts_on' => Carbon::create($currStartYear, 4, 1)->toDateString(),
            'ends_on' => Carbon::create($currStartYear + 1, 3, 31)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $this->lastFy = FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => ($currStartYear - 1).'-'.$currStartYear,
            'starts_on' => Carbon::create($currStartYear - 1, 4, 1)->toDateString(),
            'ends_on' => Carbon::create($currStartYear, 3, 31)->toDateString(),
            'is_current' => false,
            'is_closed' => true,
        ]);

        $cType = CustomerType::create([
            'name' => 'Dealer',
            'code' => 'DLR',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Metro Retailers Pvt Ltd',
            'code' => 'MRPL-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $cType->id,
            'is_active' => true,
        ]);

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Bharat Heavy Supplies',
            'code' => 'BHS-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $cType->id,
            'is_active' => true,
        ]);
    }

    public function test_dashboard_page_loads_with_default_date_range_and_displays_showing_text(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('reporting.dashboard');
        $response->assertSee('Date range');
        $response->assertSee('Showing:');
        $response->assertSee("This Month's Sales");
        $response->assertSee("This Month's Purchases");
        $response->assertSee('Receivables');
        $response->assertSee('Payables');
    }

    public function test_today_filter_calculates_exact_today_sales_purchases_and_orders(): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // 1. Sales Invoices
        Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-TODAY-01',
            'invoice_date' => $today,
            'grand_total' => 15000.00,
            'status' => 'issued',
        ]);
        Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-YEST-01',
            'invoice_date' => $yesterday,
            'grand_total' => 20000.00,
            'status' => 'issued',
        ]);

        // 2. Purchase Invoices
        PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-TODAY-01',
            'invoice_date' => $today,
            'grand_total' => 9000.00,
            'status' => 'approved',
        ]);
        PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-YEST-01',
            'invoice_date' => $yesterday,
            'grand_total' => 12000.00,
            'status' => 'approved',
        ]);

        // 3. Orders
        Order::create([
            'customer_id' => $this->customer->id,
            'order_no' => 'ORD-TODAY-01',
            'order_date' => $today,
            'grand_total' => 15000.00,
            'status' => 'pending',
        ]);
        Order::create([
            'customer_id' => $this->customer->id,
            'order_no' => 'ORD-YEST-01',
            'order_date' => $yesterday,
            'grand_total' => 20000.00,
            'status' => 'pending',
        ]);

        // Request Today via JSON (AJAX)
        $response = $this->actingAs($this->user)->getJson(route('dashboard', ['date_range' => 'today'], false));

        $response->assertOk();
        $data = $response->json();

        $this->assertSame('today', $data['dateRange']);
        $this->assertSame("Showing: {$today} → {$today}", $data['showingText']);
        $this->assertEquals(15000.00, $data['stats']['range_sales']);
        $this->assertSame(1, $data['stats']['sales_invoice_count']);
        $this->assertEquals(9000.00, $data['stats']['purchase_amount']);
        $this->assertSame(1, $data['stats']['purchase_invoice_count']);
        $this->assertSame(1, $data['stats']['pending_orders']);
        $this->assertSame(1, $data['stats']['range_orders']);
    }

    public function test_yesterday_filter_calculates_exact_yesterday_transactions(): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-TODAY-02',
            'invoice_date' => $today,
            'grand_total' => 50000.00,
            'status' => 'issued',
        ]);
        Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-YEST-02',
            'invoice_date' => $yesterday,
            'grand_total' => 35000.00,
            'status' => 'issued',
        ]);

        PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-YEST-02',
            'invoice_date' => $yesterday,
            'grand_total' => 18000.00,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('dashboard', ['date_range' => 'yesterday'], false));

        $response->assertOk();
        $data = $response->json();

        $this->assertSame('yesterday', $data['dateRange']);
        $this->assertSame("Showing: {$yesterday} → {$yesterday}", $data['showingText']);
        $this->assertEquals(35000.00, $data['stats']['range_sales']);
        $this->assertSame(1, $data['stats']['sales_invoice_count']);
        $this->assertEquals(18000.00, $data['stats']['purchase_amount']);
        $this->assertSame(1, $data['stats']['purchase_invoice_count']);
    }

    public function test_weekly_and_monthly_filters_calculate_proper_boundaries(): void
    {
        $thisWeekStart = now()->startOfWeek()->toDateString();
        $thisWeekEnd = now()->endOfWeek()->toDateString();

        $lastWeekStart = now()->subWeek()->startOfWeek()->toDateString();
        $lastWeekEnd = now()->subWeek()->endOfWeek()->toDateString();

        $thisMonthStart = now()->startOfMonth()->toDateString();
        $thisMonthEnd = now()->endOfMonth()->toDateString();

        $lastMonthStart = now()->subMonth()->startOfMonth()->toDateString();
        $lastMonthEnd = now()->subMonth()->endOfMonth()->toDateString();

        // Check This Week
        $resThisWeek = $this->actingAs($this->user)->getJson(route('dashboard', ['date_range' => 'this_week'], false));
        $this->assertSame("Showing: {$thisWeekStart} → {$thisWeekEnd}", $resThisWeek->json('showingText'));

        // Check Last Week
        $resLastWeek = $this->actingAs($this->user)->getJson(route('dashboard', ['date_range' => 'last_week'], false));
        $this->assertSame("Showing: {$lastWeekStart} → {$lastWeekEnd}", $resLastWeek->json('showingText'));

        // Check This Month
        $resThisMonth = $this->actingAs($this->user)->getJson(route('dashboard', ['date_range' => 'this_month'], false));
        $this->assertSame("Showing: {$thisMonthStart} → {$thisMonthEnd}", $resThisMonth->json('showingText'));

        // Check Last Month
        $resLastMonth = $this->actingAs($this->user)->getJson(route('dashboard', ['date_range' => 'last_month'], false));
        $this->assertSame("Showing: {$lastMonthStart} → {$lastMonthEnd}", $resLastMonth->json('showingText'));
    }

    public function test_financial_year_filters_calculate_april_to_march_boundaries(): void
    {
        $currStarts = Carbon::parse($this->currentFy->starts_on)->toDateString();
        $currEnds = Carbon::parse($this->currentFy->ends_on)->toDateString();

        $lastStarts = Carbon::parse($this->lastFy->starts_on)->toDateString();
        $lastEnds = Carbon::parse($this->lastFy->ends_on)->toDateString();

        // This FY
        $resThisFy = $this->actingAs($this->user)->getJson(route('dashboard', ['date_range' => 'this_financial_year'], false));
        $this->assertSame("Showing: {$currStarts} → {$currEnds}", $resThisFy->json('showingText'));

        // Last FY
        $resLastFy = $this->actingAs($this->user)->getJson(route('dashboard', ['date_range' => 'last_financial_year'], false));
        $this->assertSame("Showing: {$lastStarts} → {$lastEnds}", $resLastFy->json('showingText'));
    }

    public function test_all_time_filter_includes_all_transactions_without_restriction(): void
    {
        Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-ALL-01',
            'invoice_date' => now()->subYears(2)->toDateString(),
            'grand_total' => 100000.00,
            'status' => 'issued',
        ]);
        Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-ALL-02',
            'invoice_date' => now()->toDateString(),
            'grand_total' => 50000.00,
            'status' => 'issued',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('dashboard', ['date_range' => 'all_time'], false));
        $response->assertOk();
        $data = $response->json();

        $this->assertSame('all_time', $data['dateRange']);
        $this->assertSame('Showing: All Time', $data['showingText']);
        $this->assertEquals(150000.00, $data['stats']['range_sales']);
        $this->assertSame(2, $data['stats']['sales_invoice_count']);
    }

    public function test_custom_date_range_filters_accurately_and_swaps_if_inverted(): void
    {
        $d1 = '2026-05-10';
        $d2 = '2026-05-20';

        Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-CUSTOM-IN',
            'invoice_date' => '2026-05-15',
            'grand_total' => 45000.00,
            'status' => 'issued',
        ]);
        Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-CUSTOM-OUT',
            'invoice_date' => '2026-05-25',
            'grand_total' => 30000.00,
            'status' => 'issued',
        ]);

        // 1. Regular custom range
        $response = $this->actingAs($this->user)->getJson(route('dashboard', [
            'date_range' => 'custom',
            'date_from' => $d1,
            'date_to' => $d2,
        ], false));
        $this->assertSame("Showing: {$d1} → {$d2}", $response->json('showingText'));
        $this->assertEquals(45000.00, $response->json('stats.range_sales'));
        $this->assertSame(1, $response->json('stats.sales_invoice_count'));

        // 2. Inverted custom range (date_from > date_to) should be safely auto-swapped
        $responseInverted = $this->actingAs($this->user)->getJson(route('dashboard', [
            'date_range' => 'custom',
            'date_from' => $d2,
            'date_to' => $d1,
        ], false));
        $this->assertSame("Showing: {$d1} → {$d2}", $responseInverted->json('showingText'));
        $this->assertEquals(45000.00, $responseInverted->json('stats.range_sales'));
    }

    public function test_empty_period_displays_zero_totals_and_zero_records(): void
    {
        $pastEmptyFrom = '2020-01-01';
        $pastEmptyTo = '2020-01-31';

        $response = $this->actingAs($this->user)->getJson(route('dashboard', [
            'date_range' => 'custom',
            'date_from' => $pastEmptyFrom,
            'date_to' => $pastEmptyTo,
        ], false));

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(0.0, $data['stats']['range_sales']);
        $this->assertSame(0, $data['stats']['sales_invoice_count']);
        $this->assertEquals(0.0, $data['stats']['purchase_amount']);
        $this->assertSame(0, $data['stats']['purchase_invoice_count']);
        $this->assertSame(0, $data['stats']['pending_orders']);
        $this->assertSame(0, $data['stats']['range_orders']);
        $this->assertEquals(0.0, $data['stats']['range_collections']);
        $this->assertCount(0, $data['recentOrders']);
        $this->assertCount(0, $data['recentInvoices']);
        $this->assertCount(0, $data['topCustomers']);
    }

    public function test_recent_orders_and_invoices_are_scoped_to_the_selected_date_range(): void
    {
        $today = now()->toDateString();
        $lastMonthDate = now()->subMonth()->startOfMonth()->toDateString();

        $orderToday = Order::create([
            'customer_id' => $this->customer->id,
            'order_no' => 'ORD-SCOPE-TODAY',
            'order_date' => $today,
            'grand_total' => 2500.00,
            'status' => 'pending',
        ]);
        $orderLastMonth = Order::create([
            'customer_id' => $this->customer->id,
            'order_no' => 'ORD-SCOPE-LASTMONTH',
            'order_date' => $lastMonthDate,
            'grand_total' => 3500.00,
            'status' => 'pending',
        ]);

        $resToday = $this->actingAs($this->user)->getJson(route('dashboard', ['date_range' => 'today'], false));
        $orderNumbers = collect($resToday->json('recentOrders'))->pluck('order_no')->all();
        $this->assertContains('ORD-SCOPE-TODAY', $orderNumbers);
        $this->assertNotContains('ORD-SCOPE-LASTMONTH', $orderNumbers);

        $resLastMonth = $this->actingAs($this->user)->getJson(route('dashboard', ['date_range' => 'last_month'], false));
        $lastMonthOrders = collect($resLastMonth->json('recentOrders'))->pluck('order_no')->all();
        $this->assertContains('ORD-SCOPE-LASTMONTH', $lastMonthOrders);
        $this->assertNotContains('ORD-SCOPE-TODAY', $lastMonthOrders);
    }
}
