<?php

namespace Tests\Feature\Sales;

use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\ActivityLog;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Sales\Models\EInvoice;
use App\Domains\Sales\Models\EWayBill;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EditSavedTaxInvoiceBeforeStatutoryGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $unauthorizedUser;
    protected Company $company;
    protected Customer $customer;
    protected Product $productA;
    protected Product $productB;
    protected Uom $uomPcs;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.manage',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->company = Company::create([
            'name' => 'Apex Technologies Pvt Ltd',
            'code' => 'APEX-01',
            'state' => 'Maharashtra',
            'gstin' => '27AAACA1234F1Z8',
            'address' => 'Plot 10, Andheri East',
            'city' => 'Mumbai',
            'pincode' => '400069',
            'is_active' => true,
        ]);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => Carbon::now()->subMonths(2)->startOfMonth()->toDateString(),
            'ends_on' => Carbon::now()->addMonths(10)->endOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'name' => 'Senior Accountant',
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);

        $this->unauthorizedUser = User::factory()->create([
            'name' => 'Guest User',
            'company_id' => $this->company->id,
        ]);

        $this->uomPcs = Uom::create([
            'code' => 'PCS',
            'name' => 'Pieces',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->productA = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Inverter 5kW',
            'code' => 'PRD-INV-5K',
            'sku' => 'INV-5K-01',
            'serial_no' => 'PRD-INV-5K',
            'base_uom_id' => $this->uomPcs->id,
            'selling_price' => 10000.00,
            'tax_rate' => 18.00,
            'hsn_code' => '85044000',
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Battery 200Ah',
            'code' => 'PRD-BAT-200',
            'sku' => 'BAT-200-01',
            'serial_no' => 'PRD-BAT-200',
            'base_uom_id' => $this->uomPcs->id,
            'selling_price' => 15000.00,
            'tax_rate' => 18.00,
            'hsn_code' => '85072000',
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Sunlight Power Solutions',
            'code' => 'CUST-SUN-01',
            'state' => 'Maharashtra',
            'shipping_state' => 'Maharashtra',
            'gstin' => '27AABCS1234M1ZR',
            'address' => 'Plot 55, Hinjewadi Phase 1',
            'city' => 'Pune',
            'pincode' => '411057',
            'credit_days' => 15,
            'is_active' => true,
        ]);
    }

    protected function createSampleInvoice(array $overrides = []): Invoice
    {
        $invoice = Invoice::create(array_merge([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-2026-TEST-001',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->user->id,
            'invoice_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(15)->toDateString(),
            'due_date_basis' => 'invoice_date',
            'due_date_source_date' => Carbon::today()->toDateString(),
            'price_type' => 'exclusive',
            'tax_type' => 'intra',
            'delivery_state' => 'Maharashtra',
            'place_of_supply_state' => 'Maharashtra',
            'status' => 'issued',
            'subtotal' => 10000.00,
            'taxable_amount' => 10000.00,
            'discount_amount' => 0.00,
            'universal_discount_type' => 'flat',
            'universal_discount_value' => 0.00,
            'item_discount_total' => 0.00,
            'tax_amount' => 1800.00,
            'cgst_amount' => 900.00,
            'sgst_amount' => 900.00,
            'igst_amount' => 0.00,
            'round_off' => 0.00,
            'grand_total' => 11800.00,
            'paid_amount' => 0.00,
            'einvoice_status' => 'pending',
            'eway_bill_status' => 'pending',
            'eway_bill_required' => 'auto',
        ], $overrides));

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->productA->id,
            'uom_id' => $this->uomPcs->id,
            'quantity' => 1.0,
            'price_type' => 'exclusive',
            'entered_unit_price' => 10000.00,
            'unit_price' => 10000.00,
            'discount_amount' => 0.00,
            'discount_type' => 'flat',
            'discount_value' => 0.00,
            'taxable_amount' => 10000.00,
            'tax_rate' => 18.00,
            'cgst_rate' => 9.00,
            'sgst_rate' => 9.00,
            'igst_rate' => 0.00,
            'hsn_code' => $this->productA->hsn_code,
            'tax_amount' => 1800.00,
            'cgst_amount' => 900.00,
            'sgst_amount' => 900.00,
            'igst_amount' => 0.00,
            'line_total' => 11800.00,
        ]);

        return $invoice;
    }

    /**
     * Test 1: Edit product price & quantity before statutory generation recalculates totals and saves correctly.
     */
    public function test_authorized_user_can_edit_product_price_and_quantity_before_statutory_generation(): void
    {
        $invoice = $this->createSampleInvoice();

        $this->assertFalse($invoice->isStatutoryLocked());

        // Update: Change quantity to 2 and unit_price to 12000 (Taxable = 24,000, 18% GST = 4,320, Total = 28,320)
        $response = $this->actingAs($this->user)->put(route('invoices.update', $invoice), [
            'delivery_state' => 'Maharashtra',
            'price_type' => 'exclusive',
            'tax_type' => 'intra',
            'round_off' => 0,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'uom_id' => $this->uomPcs->id,
                    'quantity' => 2,
                    'unit_price' => 12000.00,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('success');

        $invoice->refresh();

        $this->assertEquals(24000.00, (float) $invoice->taxable_amount);
        $this->assertEquals(4320.00, (float) $invoice->tax_amount);
        $this->assertEquals(2160.00, (float) $invoice->cgst_amount);
        $this->assertEquals(2160.00, (float) $invoice->sgst_amount);
        $this->assertEquals(28320.00, (float) $invoice->grand_total);

        // Verify updated items table
        $this->assertCount(1, $invoice->items);
        $item = $invoice->items->first();
        $this->assertEquals(2.0, (float) $item->quantity);
        $this->assertEquals(12000.00, (float) $item->unit_price);
        $this->assertEquals(28320.00, (float) $item->line_total);
    }

    /**
     * Test 2: Modify products list (add second product) and verify updated totals in view and preview.
     */
    public function test_user_can_add_products_and_verify_updated_values_in_invoice_preview(): void
    {
        $invoice = $this->createSampleInvoice();

        // Update with 2 line items: Product A (1 @ 10,000) and Product B (1 @ 15,000)
        // Subtotal = 25,000, 18% GST = 4,500, Grand Total = 29,500
        $response = $this->actingAs($this->user)->put(route('invoices.update', $invoice), [
            'delivery_state' => 'Maharashtra',
            'price_type' => 'exclusive',
            'tax_type' => 'intra',
            'round_off' => 0,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'uom_id' => $this->uomPcs->id,
                    'quantity' => 1,
                    'unit_price' => 10000.00,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                ],
                [
                    'product_id' => $this->productB->id,
                    'uom_id' => $this->uomPcs->id,
                    'quantity' => 1,
                    'unit_price' => 15000.00,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $invoice->refresh();

        $this->assertEquals(29500.00, (float) $invoice->grand_total);
        $this->assertCount(2, $invoice->items);

        // Check preview page reflects updated items and grand total
        $previewResponse = $this->actingAs($this->user)->get(route('invoices.preview', $invoice));
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('Solar Inverter', false);
        $previewResponse->assertSee('Solar Battery', false);
        $previewResponse->assertSee('29,500.00');

        // Check show page reflects updated details
        $showResponse = $this->actingAs($this->user)->get(route('invoices.show', $invoice));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Solar Battery', false);
        $showResponse->assertSee('29,500.00');
    }

    /**
     * Test 3: Verify audit trail logs the changes in ActivityLog.
     */
    public function test_modifying_invoice_records_audit_trail_in_activity_log(): void
    {
        $invoice = $this->createSampleInvoice();

        $this->actingAs($this->user)->put(route('invoices.update', $invoice), [
            'delivery_state' => 'Maharashtra',
            'price_type' => 'exclusive',
            'tax_type' => 'intra',
            'round_off' => 0,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'uom_id' => $this->uomPcs->id,
                    'quantity' => 3,
                    'unit_price' => 10000.00,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                ],
            ],
        ]);

        $invoice->refresh();

        $latestLog = ActivityLog::where('subject_type', Invoice::class)
            ->where('subject_id', $invoice->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($latestLog);
        $this->assertEquals('Senior Accountant', $latestLog->actor_name);
        $this->assertEquals('updated', $latestLog->action);
        $this->assertIsArray($latestLog->properties);
        $this->assertEquals(11800.00, (float) $latestLog->properties['old']['grand_total']);
        $this->assertEquals(35400.00, (float) $latestLog->properties['new']['grand_total']);
        $this->assertEquals(23600.00, (float) $latestLog->properties['diff_grand_total']);
    }

    /**
     * Test 4: Attempting to edit product & pricing details after E-Invoice is generated is restricted.
     */
    public function test_modifying_product_details_is_restricted_after_einvoice_generation(): void
    {
        $invoice = $this->createSampleInvoice([
            'einvoice_status' => 'generated',
        ]);

        EInvoice::create([
            'invoice_id' => $invoice->id,
            'status' => 'generated',
            'irn' => '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
            'ack_no' => '1122334455',
            'ack_date' => now(),
            'requested_at' => now(),
        ]);

        $this->assertTrue($invoice->isEInvoiceGenerated());
        $this->assertTrue($invoice->isStatutoryLocked());

        // Attempt to change product and price
        $response = $this->actingAs($this->user)->put(route('invoices.update', $invoice), [
            'delivery_state' => 'Maharashtra',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'uom_id' => $this->uomPcs->id,
                    'quantity' => 5,
                    'unit_price' => 20000.00,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('E-Invoice', session('error'));
        $this->assertStringContainsString('legally restricted', session('error'));

        // Verify values were NOT changed in the database
        $invoice->refresh();
        $this->assertEquals(11800.00, (float) $invoice->grand_total);
        $this->assertEquals(1.0, (float) $invoice->items->first()->quantity);
    }

    /**
     * Test 5: Attempting to edit product & pricing details after E-Way Bill is generated is restricted.
     */
    public function test_modifying_product_details_is_restricted_after_eway_bill_generation(): void
    {
        $invoice = $this->createSampleInvoice([
            'eway_bill_status' => 'generated',
            'eway_bill_no' => '231000987654',
        ]);

        EWayBill::create([
            'invoice_id' => $invoice->id,
            'status' => 'generated',
            'eway_bill_no' => '231000987654',
            'ewb_date' => now(),
            'valid_upto' => now()->addDays(2),
        ]);

        $this->assertTrue($invoice->isEWayBillGenerated());
        $this->assertTrue($invoice->isStatutoryLocked());

        // Attempt to change items
        $response = $this->actingAs($this->user)->put(route('invoices.update', $invoice), [
            'delivery_state' => 'Maharashtra',
            'items' => [
                [
                    'product_id' => $this->productB->id,
                    'uom_id' => $this->uomPcs->id,
                    'quantity' => 10,
                    'unit_price' => 15000.00,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('E-Way Bill', session('error'));
        $this->assertStringContainsString('legally restricted', session('error'));

        // Verify values were NOT changed in the database
        $invoice->refresh();
        $this->assertEquals(11800.00, (float) $invoice->grand_total);
    }

    /**
     * Test 6: Non-statutory details (e.g. internal notes) can still be updated when statutory locked.
     */
    public function test_non_statutory_metadata_can_be_updated_when_statutory_locked(): void
    {
        $invoice = $this->createSampleInvoice([
            'einvoice_status' => 'generated',
        ]);

        EInvoice::create([
            'invoice_id' => $invoice->id,
            'status' => 'generated',
            'irn' => 'dummy-irn-hash-98765',
        ]);

        $response = $this->actingAs($this->user)->put(route('invoices.update', $invoice), [
            'notes' => 'Updated dispatch gate instructions: Delivery at Rear Gate.',
            'terms_and_conditions' => 'Standard warranty applies.',
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals('Updated dispatch gate instructions: Delivery at Rear Gate.', $invoice->notes);
        $this->assertEquals(11800.00, (float) $invoice->grand_total);
    }

    /**
     * Test 7: Unauthorized user cannot edit saved invoices.
     */
    public function test_unauthorized_user_cannot_edit_saved_invoice(): void
    {
        $invoice = $this->createSampleInvoice();

        $response = $this->actingAs($this->unauthorizedUser)->get(route('invoices.edit', $invoice));
        $response->assertStatus(403);

        $putResponse = $this->actingAs($this->unauthorizedUser)->put(route('invoices.update', $invoice), [
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'uom_id' => $this->uomPcs->id,
                    'quantity' => 5,
                ],
            ],
        ]);
        $putResponse->assertStatus(403);
    }

    /**
     * Test 8: Edit page displays the statutory lock warning when locked.
     */
    public function test_edit_page_displays_statutory_lock_banner_when_locked(): void
    {
        $invoice = $this->createSampleInvoice([
            'einvoice_status' => 'generated',
        ]);

        EInvoice::create([
            'invoice_id' => $invoice->id,
            'status' => 'generated',
            'irn' => 'IRN-ALPHA-998877',
        ]);

        $response = $this->actingAs($this->user)->get(route('invoices.edit', $invoice));
        $response->assertStatus(200);
        $response->assertSee('Statutory Lock Active');
        $response->assertSee('IRN-ALPHA-998877');
        $response->assertSee('Legally Locked (Statutory Generated)');
    }
}

