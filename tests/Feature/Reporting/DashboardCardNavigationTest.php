<?php

namespace Tests\Feature\Reporting;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Uom;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardCardNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Apex Distribution Corp',
            'code' => 'ADC-01',
            'state' => 'Maharashtra',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);

        $now = now();
        $currStartYear = $now->month >= 4 ? $now->year : $now->year - 1;

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => $currStartYear . '-' . ($currStartYear + 1),
            'starts_on' => Carbon::create($currStartYear, 4, 1)->toDateString(),
            'ends_on' => Carbon::create($currStartYear + 1, 3, 31)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);
    }

    public function test_all_13_dashboard_cards_have_valid_clickable_links(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));
        $response->assertOk();

        // 1. Frozen Parties: Masters -> Parties, filtered to Frozen
        $expectedFrozenUrl = route('masters.customers.index', ['credit_status' => 'frozen']);
        $response->assertSee($expectedFrozenUrl, false);

        // 2. Bounce Risk: Receivables -> Cheque Register / Bounce Risk
        $expectedBounceUrl = route('cheques.index', ['status' => 'bounced']);
        $response->assertSee($expectedBounceUrl, false);

        // 3. Dead Stock: Inventory -> Dead Stock / Stock Ageing
        $expectedDeadStockUrl = route('inventory.stock.index', ['stock_status' => 'dead_stock']);
        $response->assertSee($expectedDeadStockUrl, false);

        // 4. Margin Stub: Reports -> Profitability / Margin Report
        $expectedMarginUrl = route('reports.margin');
        $response->assertSee($expectedMarginUrl, false);

        // 5. Target Achievement: Sales Target Management -> Target Dashboard
        $expectedTargetUrl = route('sales-targets.dashboard');
        $response->assertSee($expectedTargetUrl, false);

        // 6. This Month's Sales: Sales & Orders -> Sales Invoices
        $expectedSalesUrl = route('invoices.index');
        $response->assertSee($expectedSalesUrl, false);

        // 7. This Month's Purchases: Purchases -> Purchase Invoices
        $expectedPurchasesUrl = route('purchasing.invoices.index');
        $response->assertSee($expectedPurchasesUrl, false);

        // 8. Month Revenue: Reports -> Sales / Revenue Report
        $expectedRevenueUrl = route('reports.sales');
        $response->assertSee($expectedRevenueUrl, false);

        // 9. Pending Orders: Sales & Orders -> Pending / Unconverted Orders
        $expectedOrdersUrl = route('orders.index', ['status' => 'pending']);
        $response->assertSee($expectedOrdersUrl, false);

        // 10. Period Collections: Receivables -> Receipt / Collection Register
        $expectedCollectionsUrl = route('payments.index');
        $response->assertSee($expectedCollectionsUrl, false);

        // 11. Receivables: Customer Outstanding
        $expectedReceivablesUrl = route('reports.outstanding');
        $response->assertSee($expectedReceivablesUrl, false);

        // 12. Payables: Supplier Outstanding
        $expectedPayablesUrl = route('reports.supplier-ageing');
        $response->assertSee($expectedPayablesUrl, false);

        // 13. Low Stock Part Numbers: Inventory -> Low Stock Items
        $expectedLowStockUrl = route('inventory.stock.index', ['stock_status' => 'low_stock']);
        $response->assertSee($expectedLowStockUrl, false);
    }

    public function test_all_13_destinations_load_successfully(): void
    {
        $this->withoutExceptionHandling();
        $destinations = [
            'Frozen Parties' => route('masters.customers.index', ['credit_status' => 'frozen']),
            'Bounce Risk' => route('cheques.index', ['status' => 'bounced']),
            'Dead Stock' => route('inventory.stock.index', ['stock_status' => 'dead_stock']),
            'Margin Stub' => route('reports.margin'),
            'Target Achievement' => route('sales-targets.dashboard'),
            "This Month's Sales" => route('invoices.index'),
            "This Month's Purchases" => route('purchasing.invoices.index'),
            'Month Revenue' => route('reports.sales'),
            'Pending Orders' => route('orders.index', ['status' => 'pending']),
            'Period Collections' => route('payments.index'),
            'Receivables' => route('reports.outstanding'),
            'Payables' => route('reports.supplier-ageing'),
            'Low Stock Part Numbers' => route('inventory.stock.index', ['stock_status' => 'low_stock']),
        ];

        foreach ($destinations as $cardName => $url) {
            $resp = $this->actingAs($this->user)->get($url);
            $this->assertEquals(200, $resp->getStatusCode(), "Failed loading destination for {$cardName}: {$url}");
        }
    }

    public function test_frozen_parties_filter_works_on_parties_page(): void
    {
        $type = CustomerType::create(['name' => 'Retail', 'code' => 'RET']);

        $frozenCustomer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $type->id,
            'name' => 'Frozen Party Ltd',
            'code' => 'FP-01',
            'credit_status' => 'frozen',
            'is_active' => true,
        ]);

        $activeCustomer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $type->id,
            'name' => 'Active Party Ltd',
            'code' => 'AP-01',
            'credit_status' => 'open',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.customers.index', ['credit_status' => 'frozen']));
        $response->assertOk();
        $response->assertSee('Frozen Party Ltd');
        $response->assertDontSee('Active Party Ltd');
    }

    public function test_dead_stock_filter_works_on_inventory_page(): void
    {
        $branch = \App\Domains\Organization\Models\Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'BR-01',
            'is_active' => true,
        ]);

        $uom = Uom::create(['name' => 'Piece', 'code' => 'PCS']);
        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $deadProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Dead Stock Widget',
            'sku' => 'DS-01',
            'serial_no' => 'SN-DS-01',
            'base_uom_id' => $uom->id,
            'is_active' => true,
        ]);
        StockLevel::create([
            'product_id' => $deadProduct->id,
            'warehouse_id' => $warehouse->id,
            'uom_id' => $uom->id,
            'quantity' => 1,
        ]);

        $healthyProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Healthy Fast Stock Widget',
            'sku' => 'HS-01',
            'serial_no' => 'SN-HS-01',
            'base_uom_id' => $uom->id,
            'is_active' => true,
        ]);
        StockLevel::create([
            'product_id' => $healthyProduct->id,
            'warehouse_id' => $warehouse->id,
            'uom_id' => $uom->id,
            'quantity' => 50,
        ]);

        $response = $this->actingAs($this->user)->get(route('inventory.stock.index', ['stock_status' => 'dead_stock']));
        $response->assertOk();
        $response->assertSee('Dead Stock Widget');
        
        $stockLevelProductIds = collect($response->viewData('stockLevels')->items())->pluck('product_id');
        $this->assertTrue($stockLevelProductIds->contains($deadProduct->id));
        $this->assertFalse($stockLevelProductIds->contains($healthyProduct->id));
    }
}
