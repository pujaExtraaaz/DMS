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
use App\Domains\Target\Models\SalesTeam;
use App\Domains\Target\Services\SalesTargetCalculationService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesInvoiceTargetIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $salespersonRahul;
    protected User $salespersonAmit;
    protected Company $company;
    protected Branch $branch;
    protected FinancialYear $financialYear;
    protected Customer $customer;
    protected Brand $brandSony;
    protected Brand $brandSamsung;
    protected Product $productSony;
    protected Product $productSamsung;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'DMS Corp',
            'code' => 'DMS',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Head Office',
            'code' => 'HO',
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
            'email' => 'admin@dms.test',
        ]);
        $this->adminUser->assignRole('super-admin');

        $this->salespersonRahul = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Rahul Sharma',
            'email' => 'rahul@dms.test',
        ]);
        $this->salespersonRahul->assignRole('salesperson');

        $this->salespersonAmit = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Amit Verma',
            'email' => 'amit@dms.test',
        ]);
        $this->salespersonAmit->assignRole('salesperson');

        $customerType = CustomerType::create(['name' => 'Retailer', 'code' => 'RTL']);
        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_type_id' => $customerType->id,
            'name' => 'Galaxy Enterprises',
            'code' => 'CUST-GLX',
            'party_type' => 'customer',
            'is_active' => true,
        ]);

        $this->brandSony = Brand::create(['name' => 'Sony', 'code' => 'SNY']);
        $this->brandSamsung = Brand::create(['name' => 'Samsung', 'code' => 'SAM']);
        $category = Category::create(['name' => 'Electronics', 'code' => 'ELEC']);
        $this->uom = Uom::create(['company_id' => $this->company->id, 'name' => 'Unit', 'code' => 'UNT']);

        $this->productSony = Product::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Sony Bravia 65',
            'sku' => 'SNY-65',
            'serial_no' => 'SER-SNY-01',
            'brand_id' => $this->brandSony->id,
            'category_id' => $category->id,
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 70000,
            'selling_price' => 100000,
            'mrp' => 110000,
            'is_active' => true,
        ]);

        $this->productSamsung = Product::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Samsung QLED 55',
            'sku' => 'SAM-55',
            'serial_no' => 'SER-SAM-01',
            'brand_id' => $this->brandSamsung->id,
            'category_id' => $category->id,
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 50000,
            'selling_price' => 75000,
            'mrp' => 85000,
            'is_active' => true,
        ]);
    }

    /**
     * Helper to create an invoice with items.
     */
    protected function createInvoice(array $attributes = [], array $items = []): Invoice
    {
        $defaultGrandTotal = 0;
        foreach ($items as $item) {
            $defaultGrandTotal += (float) ($item['line_total'] ?? (($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0)));
        }
        if (empty($items) && !isset($attributes['grand_total'])) {
            $defaultGrandTotal = 100000;
        }

        $invoice = Invoice::create(array_merge([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-' . uniqid(),
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-10',
            'due_date' => '2026-06-10',
            'due_date_basis' => 'custom',
            'status' => 'issued',
            'subtotal' => $attributes['grand_total'] ?? $defaultGrandTotal,
            'taxable_amount' => $attributes['grand_total'] ?? $defaultGrandTotal,
            'tax_amount' => 0,
            'grand_total' => $attributes['grand_total'] ?? $defaultGrandTotal,
            'paid_amount' => 0,
        ], $attributes));

        if (!empty($items)) {
            foreach ($items as $item) {
                InvoiceItem::create(array_merge([
                    'invoice_id' => $invoice->id,
                    'product_id' => $this->productSony->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 100000,
                    'taxable_amount' => 100000,
                    'line_total' => 100000,
                ], $item));
            }
        }

        // Trigger target recalculation
        app(SalesTargetCalculationService::class)->recalculateForInvoice($invoice->fresh());

        return $invoice->fresh(['items.product']);
    }

    /**
     * 1. Posting invoice automatically updates assigned salesperson's target achievement.
     */
    public function test_posting_invoice_automatically_updates_salesperson_target(): void
    {
        $target = SalesTarget::create([
            'name' => 'Rahul Monthly Target - May 2026',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonRahul->id,
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 500000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-15',
            'grand_total' => 100000,
        ]);

        $achievement = SalesTargetAchievement::where('sales_target_id', $target->id)->first();
        $this->assertNotNull($achievement);
        $this->assertEquals(100000, (float) $achievement->achieved_amount);
        $this->assertEquals(400000, (float) $achievement->pending_amount);
        $this->assertEquals(20.0, (float) $achievement->achievement_percent);
        $this->assertEquals(1, $achievement->invoices_count);
    }

    /**
     * 2. Multiple invoices for the same salesperson accumulate properly (example in prompt).
     */
    public function test_multiple_invoices_for_same_salesperson_accumulate_properly(): void
    {
        $target = SalesTarget::create([
            'name' => 'Rahul Target May',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonRahul->id,
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 500000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        // Invoice 1: 1,00,000
        $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-05',
            'grand_total' => 100000,
        ]);

        // Invoice 2: 75,000
        $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-12',
            'grand_total' => 75000,
        ]);

        // Invoice 3: 50,000
        $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-20',
            'grand_total' => 50000,
        ]);

        $achievement = SalesTargetAchievement::where('sales_target_id', $target->id)->first();
        $this->assertEquals(225000, (float) $achievement->achieved_amount);
        $this->assertEquals(275000, (float) $achievement->pending_amount);
        $this->assertEquals(45.0, (float) $achievement->achievement_percent);
        $this->assertEquals(3, $achievement->invoices_count);
    }

    /**
     * 3. Separate salespeople calculate independently without cross-attribution.
     */
    public function test_separate_salespeople_calculate_independently(): void
    {
        $targetRahul = SalesTarget::create([
            'name' => 'Rahul Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonRahul->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 500000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $targetAmit = SalesTarget::create([
            'name' => 'Amit Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonAmit->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 300000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-10',
            'grand_total' => 150000,
        ]);

        $this->createInvoice([
            'salesperson_id' => $this->salespersonAmit->id,
            'invoice_date' => '2026-05-15',
            'grand_total' => 90000,
        ]);

        $achRahul = SalesTargetAchievement::where('sales_target_id', $targetRahul->id)->first();
        $achAmit = SalesTargetAchievement::where('sales_target_id', $targetAmit->id)->first();

        $this->assertEquals(150000, (float) $achRahul->achieved_amount);
        $this->assertEquals(30.0, (float) $achRahul->achievement_percent);

        $this->assertEquals(90000, (float) $achAmit->achieved_amount);
        $this->assertEquals(30.0, (float) $achAmit->achievement_percent);
    }

    /**
     * 4. Customer / Party-wise targets update simultaneously with salesperson targets.
     */
    public function test_customer_party_target_updates_alongside_salesperson_target(): void
    {
        $partyTarget = SalesTarget::create([
            'name' => 'Galaxy Enterprises May Target',
            'target_type' => 'value',
            'owner_type' => 'party',
            'customer_id' => $this->customer->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 400000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $spTarget = SalesTarget::create([
            'name' => 'Rahul May Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonRahul->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 500000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->createInvoice([
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-14',
            'grand_total' => 200000,
        ]);

        $partyAch = SalesTargetAchievement::where('sales_target_id', $partyTarget->id)->first();
        $spAch = SalesTargetAchievement::where('sales_target_id', $spTarget->id)->first();

        $this->assertEquals(200000, (float) $partyAch->achieved_amount);
        $this->assertEquals(50.0, (float) $partyAch->achievement_percent);

        $this->assertEquals(200000, (float) $spAch->achieved_amount);
        $this->assertEquals(40.0, (float) $spAch->achievement_percent);
    }

    /**
     * 5. Sales Team target accumulates from team member invoices.
     */
    public function test_sales_team_target_accumulates_from_team_members(): void
    {
        $team = SalesTeam::create([
            'company_id' => $this->company->id,
            'name' => 'North Region Alpha Team',
            'code' => 'NRA',
            'leader_id' => $this->salespersonRahul->id,
            'is_active' => true,
        ]);
        $team->members()->sync([$this->salespersonRahul->id, $this->salespersonAmit->id]);

        $teamTarget = SalesTarget::create([
            'name' => 'Team Alpha May Quota',
            'target_type' => 'value',
            'owner_type' => 'team',
            'sales_team_id' => $team->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 800000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-10',
            'grand_total' => 300000,
        ]);

        $this->createInvoice([
            'salesperson_id' => $this->salespersonAmit->id,
            'invoice_date' => '2026-05-18',
            'grand_total' => 200000,
        ]);

        $teamAch = SalesTargetAchievement::where('sales_target_id', $teamTarget->id)->first();
        $this->assertEquals(500000, (float) $teamAch->achieved_amount);
        $this->assertEquals(62.5, (float) $teamAch->achievement_percent);
        $this->assertEquals(2, $teamAch->invoices_count);
    }

    /**
     * 6. Product and brand criteria targets calculate only eligible line items.
     */
    public function test_product_and_brand_criteria_targets_calculate_only_eligible_lines(): void
    {
        // Sony Brand Target
        $brandTarget = SalesTarget::create([
            'name' => 'Sony Exclusive Target',
            'target_type' => 'brand',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonRahul->id,
            'brand_id' => $this->brandSony->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 200000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        // Invoice with 2 lines: Line 1 Sony (₹100,000), Line 2 Samsung (₹75,000) = Total ₹175,000
        $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-10',
            'grand_total' => 175000,
        ], [
            [
                'product_id' => $this->productSony->id,
                'quantity' => 1,
                'unit_price' => 100000,
                'line_total' => 100000,
            ],
            [
                'product_id' => $this->productSamsung->id,
                'quantity' => 1,
                'unit_price' => 75000,
                'line_total' => 75000,
            ],
        ]);

        $brandAch = SalesTargetAchievement::where('sales_target_id', $brandTarget->id)->first();
        // Only the Sony line (₹100,000) should be counted, not the Samsung line or invoice total!
        $this->assertEquals(100000, (float) $brandAch->achieved_amount);
        $this->assertEquals(50.0, (float) $brandAch->achievement_percent);
    }

    /**
     * 7. Pure Quantity target calculates total units sold.
     */
    public function test_pure_quantity_target_calculates_total_units_sold(): void
    {
        $qtyTarget = SalesTarget::create([
            'name' => 'Rahul Quantity Target',
            'target_type' => 'quantity',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonRahul->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 500000,
            'target_quantity' => 10,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-10',
            'grand_total' => 300000,
        ], [
            [
                'product_id' => $this->productSony->id,
                'quantity' => 3,
                'unit_price' => 100000,
                'line_total' => 300000,
            ],
        ]);

        $qtyAch = SalesTargetAchievement::where('sales_target_id', $qtyTarget->id)->first();
        $this->assertEquals(3.0, (float) $qtyAch->achieved_quantity);
        $this->assertEquals(30.0, (float) $qtyAch->quantity_achievement_percent);
    }

    /**
     * 8. Draft, void, and cancelled invoices do not count. Cancelling an invoice immediately reverses achievement.
     */
    public function test_draft_cancelled_and_void_invoices_do_not_count(): void
    {
        $target = SalesTarget::create([
            'name' => 'Rahul Status Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonRahul->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 500000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        // Draft invoice: status = draft -> achievement must be 0
        $draftInvoice = $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-10',
            'grand_total' => 100000,
            'status' => 'draft',
        ]);

        $ach = SalesTargetAchievement::where('sales_target_id', $target->id)->first();
        $this->assertEquals(0, (float) ($ach?->achieved_amount ?? 0));

        // Issued invoice: status = issued -> achievement becomes 100,000
        $issuedInvoice = $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-12',
            'grand_total' => 100000,
            'status' => 'issued',
        ]);

        $ach = SalesTargetAchievement::where('sales_target_id', $target->id)->first();
        $this->assertEquals(100000, (float) $ach->achieved_amount);

        // Cancel the invoice -> achievement must immediately reverse back to 0
        $issuedInvoice->update(['status' => 'cancelled']);
        app(SalesTargetCalculationService::class)->recalculateForInvoice($issuedInvoice);

        $ach = SalesTargetAchievement::where('sales_target_id', $target->id)->first();
        $this->assertEquals(0, (float) $ach->achieved_amount);
        $this->assertEquals(500000, (float) $ach->pending_amount);
        $this->assertEquals(0, $ach->invoices_count);
    }

    /**
     * 9. Credit Note / Sales Return deducts from target achievement.
     */
    public function test_credit_note_or_sales_return_deducts_achievement(): void
    {
        $target = SalesTarget::create([
            'name' => 'Rahul Credit Note Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonRahul->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 500000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $invoice = $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-10',
            'grand_total' => 100000,
            'status' => 'issued',
        ]);

        $ach = SalesTargetAchievement::where('sales_target_id', $target->id)->first();
        $this->assertEquals(100000, (float) $ach->achieved_amount);

        // Create a posted credit note against this invoice for ₹20,000
        $creditNote = CreditNote::create([
            'credit_note_no' => 'CN-001',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'credit_note_date' => '2026-05-15',
            'reason' => 'damage',
            'status' => 'posted',
            'subtotal' => 20000,
            'tax_amount' => 0,
            'grand_total' => 20000,
            'affects_stock' => false,
            'created_by_name' => 'Admin',
        ]);

        app(SalesTargetCalculationService::class)->recalculateForCreditNote($creditNote);

        $ach = SalesTargetAchievement::where('sales_target_id', $target->id)->first();
        // 100,000 - 20,000 = 80,000
        $this->assertEquals(80000, (float) $ach->achieved_amount);
        $this->assertEquals(420000, (float) $ach->pending_amount);
        $this->assertEquals(16.0, (float) $ach->achievement_percent);
    }

    /**
     * 10. Changing salesperson transfers target attribution.
     */
    public function test_changing_salesperson_transfers_target_attribution(): void
    {
        $targetRahul = SalesTarget::create([
            'name' => 'Rahul Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonRahul->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 500000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $targetAmit = SalesTarget::create([
            'name' => 'Amit Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonAmit->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 500000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $invoice = $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-10',
            'grand_total' => 100000,
        ]);

        $achRahul = SalesTargetAchievement::where('sales_target_id', $targetRahul->id)->first();
        $this->assertEquals(100000, (float) $achRahul->achieved_amount);

        // Update salesperson to Amit
        $invoice->update(['salesperson_id' => $this->salespersonAmit->id]);
        app(SalesTargetCalculationService::class)->recalculateForSalespersonAndDate($this->salespersonRahul->id, '2026-05-10');
        app(SalesTargetCalculationService::class)->recalculateForInvoice($invoice->fresh());

        $achRahul = SalesTargetAchievement::where('sales_target_id', $targetRahul->id)->first();
        $achAmit = SalesTargetAchievement::where('sales_target_id', $targetAmit->id)->first();

        // Rahul drops to 0, Amit gets 100,000
        $this->assertEquals(0, (float) $achRahul->achieved_amount);
        $this->assertEquals(100000, (float) $achAmit->achieved_amount);
    }

    /**
     * 11. Changing invoice date recalculates both previous and new periods.
     */
    public function test_changing_invoice_date_recalculates_both_periods(): void
    {
        $targetMay = SalesTarget::create([
            'name' => 'Rahul May Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonRahul->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 500000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $targetJune = SalesTarget::create([
            'name' => 'Rahul June Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonRahul->id,
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-30',
            'target_amount' => 500000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $invoice = $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-15',
            'grand_total' => 150000,
        ]);

        $achMay = SalesTargetAchievement::where('sales_target_id', $targetMay->id)->first();
        $this->assertEquals(150000, (float) $achMay->achieved_amount);

        // Move invoice date to June
        $invoice->update(['invoice_date' => '2026-06-15']);
        app(SalesTargetCalculationService::class)->recalculateForSalespersonAndDate($this->salespersonRahul->id, '2026-05-15');
        app(SalesTargetCalculationService::class)->recalculateForInvoice($invoice->fresh());

        $achMay = SalesTargetAchievement::where('sales_target_id', $targetMay->id)->first();
        $achJune = SalesTargetAchievement::where('sales_target_id', $targetJune->id)->first();

        $this->assertEquals(0, (float) $achMay->achieved_amount);
        $this->assertEquals(150000, (float) $achJune->achieved_amount);
    }

    /**
     * Sakshi scenario: assigned target + posted invoices − sales return.
     * Assigned 3,00,000; invoices 50k+40k; return 10k → net 80k, pending 2,20,000, 26.67%.
     */
    public function test_sakshi_scenario_target_achievement_matches_invoice_and_return_math(): void
    {
        $sakshi = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Sakshi',
            'email' => 'sakshi@dms.test',
        ]);
        $sakshi->assignRole('salesperson');

        $target = SalesTarget::create([
            'name' => 'Sakshi Monthly Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $sakshi->id,
            'financial_year_id' => $this->financialYear->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 300000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->createInvoice([
            'salesperson_id' => $sakshi->id,
            'invoice_date' => '2026-05-05',
            'grand_total' => 50000,
        ]);
        $invoice2 = $this->createInvoice([
            'salesperson_id' => $sakshi->id,
            'invoice_date' => '2026-05-12',
            'grand_total' => 40000,
        ]);

        $creditNote = CreditNote::create([
            'credit_note_no' => 'CN-SAKSHI-001',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice2->id,
            'credit_note_date' => '2026-05-20',
            'reason' => 'return',
            'status' => 'posted',
            'subtotal' => 10000,
            'tax_amount' => 0,
            'grand_total' => 10000,
            'affects_stock' => false,
            'created_by_name' => 'Admin',
        ]);
        app(SalesTargetCalculationService::class)->recalculateForCreditNote($creditNote);

        $ach = SalesTargetAchievement::where('sales_target_id', $target->id)->first();
        $this->assertNotNull($ach);
        $this->assertEquals(80000.0, (float) $ach->achieved_amount);
        $this->assertEquals(220000.0, (float) $ach->pending_amount);
        $this->assertEquals(26.67, (float) $ach->achievement_percent);

        $perf = $this->actingAs($this->adminUser)->get(route('sales-targets.salesman-performance', [
            'salesperson_id' => $sakshi->id,
        ]));
        $perf->assertOk();
        $perf->assertSee('Sakshi');
        $perf->assertSee('300,000.00');
        $perf->assertSee('80,000.00');
        $perf->assertSee('220,000.00');
        $perf->assertSee('26.67');

        $report = $this->actingAs($this->adminUser)->get(route('sales-targets.reports', [
            'salesperson_id' => $sakshi->id,
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-15', // overlaps May period (must not require ends_on <= date_to)
        ]));
        $report->assertOk();
        $report->assertSee('Sakshi Monthly Target');
        $report->assertDontSee('No report records found');
    }

    /**
     * 12. Salesman performance view displays all required metrics.
     */
    public function test_salesman_performance_view_displays_all_required_metrics(): void
    {
        SalesTarget::create([
            'name' => 'Rahul May Target',
            'target_type' => 'value',
            'owner_type' => 'individual',
            'salesperson_id' => $this->salespersonRahul->id,
            'financial_year_id' => $this->financialYear->id,
            'period_type' => 'monthly',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'target_amount' => 500000,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-10',
            'grand_total' => 100000,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('sales-targets.salesman-performance'));

        $response->assertOk();
        $response->assertSee('Assigned Target (₹)');
        $response->assertSee('Achieved Sales (₹)');
        $response->assertSee('Pending Target (₹)');
        $response->assertSee('Achievement %');
        $response->assertSee('Posted Invoices');
        $response->assertSee('Sales Returns (₹)');
        $response->assertSee('Net Eligible Sales (₹)');
        $response->assertSee('Rahul Sharma');
    }

    /**
     * 13. Contributing Invoices drill-down view displays all required columns and responds to filters.
     */
    public function test_contributing_invoices_drilldown_view_displays_all_columns_and_filters(): void
    {
        $invoice = $this->createInvoice([
            'salesperson_id' => $this->salespersonRahul->id,
            'invoice_date' => '2026-05-10',
            'grand_total' => 100000,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('sales-targets.invoices', [
            'salesperson_id' => $this->salespersonRahul->id,
        ]));

        $response->assertOk();
        $response->assertSee('Contributing Sales Invoices');
        $response->assertSee('Gross Invoice Amount');
        $response->assertSee('Net Eligible Sales');
        $response->assertSee('DMS Corp');
        $response->assertSee($invoice->invoice_no);
        $response->assertSee('Galaxy Enterprises');
        $response->assertSee('Rahul Sharma');
        $response->assertSee('Issued');
    }
}
