<?php

namespace Tests\Feature\Target;

use App\Domains\Master\Models\Customer;
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
use App\Domains\Target\Services\SalesTargetCalculationService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalespersonInvoicePerformanceScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $salespersonSakshi;
    protected Company $company;
    protected Branch $branch;
    protected FinancialYear $financialYear;
    protected Customer $customer;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'DMS ERP Technologies',
            'code' => 'DMS',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'is_active' => true,
        ]);

        $this->financialYear = FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-2027',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@dms.test',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->adminUser->assignRole('super-admin');

        $this->salespersonSakshi = User::factory()->create([
            'name' => 'Sakshi',
            'email' => 'sakshi@salesperson.test',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->salespersonSakshi->assignRole('salesperson');

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'salesperson_id' => $this->salespersonSakshi->id,
            'name' => 'Apex Retailers',
            'code' => 'CUST-APEX',
            'trade_name' => 'Apex Retail',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Piece',
            'code' => 'PCS',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Standard Item A',
            'sku' => 'SKU-ITEM-A',
            'serial_no' => 'SN-ITEM-A-01',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 1000.00,
            'is_active' => true,
        ]);
    }

    public function test_sakshi_sales_target_scenario_and_performance_matrix(): void
    {
        // 1. Assign Monthly Target to Sakshi: ₹3,00,000 for November 2026
        $target = SalesTarget::create([
            'name' => 'Sakshi November Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonSakshi->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'financial_year_id' => $this->financialYear->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-11-30',
            'target_amount' => 300000.00,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
            'approved_by' => $this->adminUser->id,
            'approved_at' => now(),
        ]);

        // 2. Posted Invoice 1: ₹50,000
        $invoice1 = Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salespersonSakshi->id,
            'invoice_no' => 'INV-202611-0001',
            'invoice_date' => '2026-11-10',
            'status' => 'issued',
            'subtotal' => 50000.00,
            'tax_total' => 0.00,
            'grand_total' => 50000.00,
            'created_by' => $this->adminUser->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice1->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 50,
            'unit_price' => 1000.00,
            'taxable_amount' => 50000.00,
            'line_total' => 50000.00,
        ]);

        // 3. Posted Invoice 2: ₹40,000
        $invoice2 = Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salespersonSakshi->id,
            'invoice_no' => 'INV-202611-0002',
            'invoice_date' => '2026-11-15',
            'status' => 'issued',
            'subtotal' => 40000.00,
            'tax_total' => 0.00,
            'grand_total' => 40000.00,
            'created_by' => $this->adminUser->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice2->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 40,
            'unit_price' => 1000.00,
            'taxable_amount' => 40000.00,
            'line_total' => 40000.00,
        ]);

        // 4. Sales Return (Credit Note): ₹10,000 on Invoice 1
        $creditNote = CreditNote::create([
            'credit_note_no' => 'CN-202611-0001',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice1->id,
            'credit_note_date' => '2026-11-20',
            'status' => 'posted',
            'subtotal' => 10000.00,
            'tax_amount' => 0.00,
            'grand_total' => 10000.00,
        ]);
        CreditNoteItem::create([
            'credit_note_id' => $creditNote->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 10,
            'unit_price' => 1000.00,
            'tax_amount' => 0.00,
            'line_total' => 10000.00,
        ]);

        // Trigger calculation service
        $calculationService = app(SalesTargetCalculationService::class);
        $achievement = $calculationService->calculateTarget($target);

        // Verify Calculation Service results
        $this->assertEquals(300000.00, (float) $target->target_amount);
        $this->assertEquals(80000.00, (float) $achievement->achieved_amount); // 50,000 + 40,000 - 10,000 = 80,000
        $this->assertEquals(220000.00, (float) $achievement->pending_amount);  // 300,000 - 80,000 = 220,000
        $this->assertEquals(26.67, (float) $achievement->achievement_percent); // (80,000 / 300,000) * 100 = 26.67%
        $this->assertEquals(2, $achievement->invoices_count);

        // 5. Test Salesman Performance Controller endpoint
        $response = $this->actingAs($this->adminUser)
            ->get(route('sales-targets.salesman-performance', [
                'salesperson_id' => $this->salespersonSakshi->id,
                'financial_year_id' => $this->financialYear->id,
                'period_type' => 'monthly',
                'date_from' => '2026-11-01',
                'date_to' => '2026-11-30',
            ]));

        $response->assertOk();
        $response->assertSee('Sakshi');
        $response->assertSee('300,000.00'); // Assigned Target
        $response->assertSee('80,000.00');  // Achieved Sales & Net Eligible Sales
        $response->assertSee('220,000.00'); // Pending Target
        $response->assertSee('26.67%');     // Achievement %
        $response->assertSee('10,000.00');  // Sales Returns

        // 6. Test Sales Target Reports endpoint with Salesperson filter
        $reportsResponse = $this->actingAs($this->adminUser)
            ->get(route('sales-targets.reports', [
                'salesperson_id' => $this->salespersonSakshi->id,
            ]));

        $reportsResponse->assertOk();
        $reportsResponse->assertSee('Sakshi November Target');
        $reportsResponse->assertSee('Sakshi');
        $reportsResponse->assertSee('300,000.00');
        $reportsResponse->assertSee('80,000.00');
        $reportsResponse->assertSee('220,000.00');
        $reportsResponse->assertSee('26.67%');

        // 7. Test Export CSV endpoint with Salesperson filter
        $csvResponse = $this->actingAs($this->adminUser)
            ->get(route('sales-targets.reports', [
                'salesperson_id' => $this->salespersonSakshi->id,
                'export' => 'csv',
            ]));

        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', $csvResponse->headers->get('content-type'));

        // 8. Test unassigned invoice does not leak into Sakshi's performance
        $unassignedInvoice = Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'salesperson_id' => null,
            'invoice_no' => 'INV-UNASSIGNED',
            'invoice_date' => '2026-11-25',
            'status' => 'issued',
            'subtotal' => 25000.00,
            'tax_total' => 0.00,
            'grand_total' => 25000.00,
            'created_by' => $this->adminUser->id,
        ]);

        $calculationService->recalculateForInvoice($unassignedInvoice);
        $freshAchievement = $target->fresh()->achievement;
        $this->assertEquals(80000.00, (float) $freshAchievement->achieved_amount);
        $this->assertEquals(220000.00, (float) $freshAchievement->pending_amount);
        $this->assertEquals(26.67, (float) $freshAchievement->achievement_percent);
    }
}
