<?php

namespace Tests\Feature\Sales;

use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnableProductEditingFromExistingTaxInvoiceEditPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customer;
    protected Product $productA;
    protected Product $productB;
    protected Uom $uomPcs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Apex Solar Technologies Pvt Ltd',
            'email' => 'sales@apexsolar.test',
            'state' => 'Maharashtra',
            'gstin' => '27AAACA1234A1Z5',
            'address' => 'Plot 42, Hadapsar Industrial Area',
            'city' => 'Pune',
            'pincode' => '411028',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'name' => 'Nikhil Patil',
            'email' => 'nikhil@apexsolar.test',
            'company_id' => $this->company->id,
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $this->user->assignRole($adminRole);

        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.manage',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
            'products.view', 'products.edit', 'products.manage', 'masters.manage'
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
            $this->user->givePermissionTo($perm);
        }

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => Carbon::now()->subMonths(2)->startOfMonth()->toDateString(),
            'ends_on' => Carbon::now()->addMonths(10)->endOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        $this->uomPcs = Uom::create([
            'code' => 'PCS',
            'name' => 'Pieces',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->productA = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Hybrid Inverter 5kVA',
            'code' => 'INV-5KVA',
            'sku' => 'INV-5KVA-01',
            'serial_no' => 'PRD-INV-5KVA',
            'base_uom_id' => $this->uomPcs->id,
            'selling_price' => 15000.00,
            'tax_rate' => 18.00,
            'hsn_code' => '85044000',
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Lithium Battery 48V 100Ah',
            'code' => 'BAT-48V-100',
            'sku' => 'BAT-48V-100-01',
            'serial_no' => 'PRD-BAT-48V',
            'base_uom_id' => $this->uomPcs->id,
            'selling_price' => 25000.00,
            'tax_rate' => 18.00,
            'hsn_code' => '85072000',
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Green Energy Enterprises',
            'code' => 'CUST-GREEN-01',
            'state' => 'Maharashtra',
            'shipping_state' => 'Maharashtra',
            'gstin' => '27AAACG9876M1ZR',
            'address' => 'Shop 12, Market Yard',
            'city' => 'Pune',
            'pincode' => '411037',
            'credit_days' => 30,
            'is_active' => true,
        ]);
    }

    protected function createSampleInvoice(array $overrides = []): Invoice
    {
        $invoice = Invoice::create(array_merge([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-2026-EDIT-001',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->user->id,
            'invoice_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(30)->toDateString(),
            'due_date_basis' => 'invoice_date',
            'due_date_source_date' => Carbon::today()->toDateString(),
            'price_type' => 'exclusive',
            'tax_type' => 'intra',
            'delivery_state' => 'Maharashtra',
            'place_of_supply_state' => 'Maharashtra',
            'status' => 'issued',
            'subtotal' => 15000.00,
            'taxable_amount' => 15000.00,
            'discount_amount' => 0.00,
            'universal_discount_type' => 'flat',
            'universal_discount_value' => 0.00,
            'item_discount_total' => 0.00,
            'tax_amount' => 2700.00,
            'cgst_amount' => 1350.00,
            'sgst_amount' => 1350.00,
            'igst_amount' => 0.00,
            'round_off' => 0.00,
            'grand_total' => 17700.00,
            'paid_amount' => 0.00,
            'einvoice_status' => 'pending',
            'eway_bill_status' => 'pending',
            'eway_bill_required' => 'auto',
            'notes' => 'Handle with care. Deliver to main warehouse.',
        ], $overrides));

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->productA->id,
            'uom_id' => $this->uomPcs->id,
            'quantity' => 1.0,
            'price_type' => 'exclusive',
            'entered_unit_price' => 15000.00,
            'unit_price' => 15000.00,
            'discount_amount' => 0.00,
            'discount_type' => 'flat',
            'discount_value' => 0.00,
            'taxable_amount' => 15000.00,
            'tax_rate' => 18.00,
            'cgst_rate' => 9.00,
            'sgst_rate' => 9.00,
            'igst_rate' => 0.00,
            'hsn_code' => $this->productA->hsn_code,
            'tax_amount' => 2700.00,
            'cgst_amount' => 1350.00,
            'sgst_amount' => 1350.00,
            'igst_amount' => 0.00,
            'line_total' => 17700.00,
        ]);

        return $invoice;
    }

    /**
     * Test 1: Edit Tax Invoice page renders Edit Product action in the editable line items table.
     */
    public function test_edit_tax_invoice_page_renders_edit_product_action_for_authorized_users(): void
    {
        $invoice = $this->createSampleInvoice();

        $response = $this->actingAs($this->user)->get(route('invoices.edit', $invoice));

        $response->assertStatus(200);
        $response->assertSee('Edit Product', false);
        $response->assertSee('productEditUrl', false);
        $response->assertSee('saveDraftAndRedirectToEditProduct', false);
        $response->assertSee('return_to=invoices.edit', false);
    }

    /**
     * Test 2: Edit Tax Invoice page renders Edit Product action in read-only items table when statutory locked.
     */
    public function test_edit_tax_invoice_page_renders_edit_product_action_when_statutory_locked(): void
    {
        $invoice = $this->createSampleInvoice([
            'einvoice_status' => 'generated',
            'eway_bill_status' => 'pending',
        ]);

        $this->assertTrue($invoice->isStatutoryLocked());

        $response = $this->actingAs($this->user)->get(route('invoices.edit', $invoice));

        $response->assertStatus(200);
        $response->assertSee('Legally Locked (Statutory Generated)');
        // The read-only table must render the Edit Product link beside the product name
        $response->assertSee('Edit Product', false);
        $response->assertSee(route('masters.products.edit', [
            'product' => $this->productA->id,
            'return_to' => 'invoices.edit',
            'return_url' => route('invoices.edit', $invoice),
            'invoice_id' => $invoice->id,
            'target_row' => 0,
        ]));
    }

    /**
     * Test 3: Users without product editing permissions do not see the Edit Product action.
     */
    public function test_user_without_product_edit_permissions_cannot_see_edit_product_action(): void
    {
        $invoice = $this->createSampleInvoice();

        $limitedUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        // Give only invoice viewing/editing permissions, but NO product master permissions
        foreach (['invoices.view', 'invoices.edit', 'sales.view', 'sales.edit'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
            $limitedUser->givePermissionTo($perm);
        }

        $response = $this->actingAs($limitedUser)->get(route('invoices.edit', $invoice));

        $response->assertStatus(200);
        $response->assertDontSee(':href="productEditUrl(row.product_id, i)"', false);
    }

    /**
     * Test 4: Saving edited product with return_to=invoices.edit returns to the same invoice edit page.
     */
    public function test_product_update_redirects_back_to_same_invoice_edit_page(): void
    {
        $invoice = $this->createSampleInvoice();

        $updateResponse = $this->actingAs($this->user)->put(route('masters.products.update', $this->productA), [
            'name' => 'Solar Hybrid Inverter 5kVA Premium Plus',
            'sku' => 'INV-5KVA-01',
            'serial_no' => 'PRD-INV-5KVA',
            'base_uom_id' => $this->uomPcs->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'purchase_price' => 12000.00,
            'selling_price' => 15500.00,
            'tax_rate' => 18.00,
            'hsn_code' => '85044000',
            'is_active' => true,
            'return_to' => 'invoices.edit',
            'return_url' => route('invoices.edit', $invoice),
            'invoice_id' => $invoice->id,
            'target_row' => 0,
        ]);

        $updateResponse->assertRedirect(route('invoices.edit', [
            'invoice' => $invoice->id,
            'updated_product_id' => $this->productA->id,
            'returned_from' => 'product_edit',
            'target_row' => 0,
        ]));
        $updateResponse->assertSessionHas('success');

        // Product in DB was actually updated (with title case applied by middleware)
        $this->productA->refresh();
        $this->assertEquals('Solar Hybrid Inverter 5kva Premium Plus', $this->productA->name);
    }

    /**
     * Test 5: Returning to invoice edit page loads updated product data and preserves invoice context.
     */
    public function test_returning_to_invoice_edit_page_loads_updated_product_and_preserves_invoice(): void
    {
        $invoice = $this->createSampleInvoice();

        // Simulate returning to edit page after updating product
        $response = $this->actingAs($this->user)->get(route('invoices.edit', [
            'invoice' => $invoice->id,
            'updated_product_id' => $this->productA->id,
            'returned_from' => 'product_edit',
            'target_row' => 0,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Solar Hybrid Inverter', false);
        // Invoice number and customer are preserved
        $response->assertSee($invoice->invoice_no);
        $response->assertSee($this->customer->name);
    }

    /**
     * Test 6: In-progress draft is saved and restored cleanly without losing entered data or creating duplicate rows.
     */
    public function test_in_progress_draft_is_saved_and_restored_cleanly_on_invoice_edit(): void
    {
        $invoice = $this->createSampleInvoice();

        $draftData = [
            'customerId' => $this->customer->id,
            'salespersonId' => $this->user->id,
            'invoiceDate' => Carbon::today()->toDateString(),
            'deliveryState' => 'Maharashtra',
            'notes' => 'Updated dispatch gate instructions: Gate 4 Delivery.',
            'motorVehicleNo' => 'MH-12-AB-9999',
            'targetRow' => 0,
            'lines' => [
                [
                    'id' => $invoice->items->first()->id,
                    'product_id' => $this->productA->id,
                    'uom_id' => $this->uomPcs->id,
                    'unit' => 'PCS',
                    'quantity' => 2,
                    'unit_price' => 16000.00,
                    'discount_type' => 'flat',
                    'discount_value' => 500.00,
                    'tax_rate' => 18.00,
                    'batch_no' => 'BATCH-INV-99',
                    'serial_numbers' => '',
                ],
                [
                    'id' => null,
                    'product_id' => $this->productB->id,
                    'uom_id' => $this->uomPcs->id,
                    'unit' => 'PCS',
                    'quantity' => 1,
                    'unit_price' => 25000.00,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                    'tax_rate' => 18.00,
                    'batch_no' => 'BATCH-BAT-01',
                    'serial_numbers' => '',
                ]
            ]
        ];

        // Save draft via endpoint with invoice_id
        $draftResponse = $this->actingAs($this->user)->postJson(route('invoices.draft.save'), [
            'draft' => $draftData,
            'invoice_id' => $invoice->id,
        ]);

        $draftResponse->assertStatus(200);
        $savedSessionDraft = session('tax_invoice_edit_draft_' . $invoice->id);
        $this->assertNotNull($savedSessionDraft);
        $this->assertEquals($draftData['customerId'], $savedSessionDraft['customerId']);
        $this->assertEquals($draftData['notes'], $savedSessionDraft['notes']);
        $this->assertEquals($draftData['motorVehicleNo'], $savedSessionDraft['motorVehicleNo']);
        $this->assertCount(2, $savedSessionDraft['lines']);

        // Visit edit page: should pass savedDraft in view and clear draft from session
        session(['tax_invoice_edit_draft_' . $invoice->id => $draftData]);
        $editResponse = $this->actingAs($this->user)->get(route('invoices.edit', [
            'invoice' => $invoice->id,
            'updated_product_id' => $this->productA->id,
            'returned_from' => 'product_edit',
        ]));

        $editResponse->assertStatus(200);
        $editResponse->assertSee('Gate 4 Delivery');

        // Now save the updated invoice
        $updateResponse = $this->actingAs($this->user)->put(route('invoices.update', $invoice), [
            'delivery_state' => 'Maharashtra',
            'price_type' => 'exclusive',
            'tax_type' => 'intra',
            'vehicle_no' => 'MH-12-AB-9999',
            'notes' => 'Updated dispatch gate instructions: Gate 4 Delivery.',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'uom_id' => $this->uomPcs->id,
                    'quantity' => 2,
                    'unit_price' => 16000.00,
                    'discount_type' => 'flat',
                    'discount_value' => 500.00,
                ],
                [
                    'product_id' => $this->productB->id,
                    'uom_id' => $this->uomPcs->id,
                    'quantity' => 1,
                    'unit_price' => 25000.00,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                ]
            ]
        ]);

        $updateResponse->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals('MH-12-AB-9999', $invoice->vehicle_no);
        $this->assertEquals('Updated dispatch gate instructions: Gate 4 Delivery.', $invoice->notes);
        // Verify exactly 2 items (no duplicates, no new invoice created)
        $this->assertCount(2, $invoice->items);
        $this->assertEquals('INV-2026-EDIT-001', $invoice->invoice_no);
        // Session draft cleared
        $this->assertNull(session('tax_invoice_edit_draft_' . $invoice->id));
    }

    /**
     * Test 7: Verify edit tax invoice page renders a clean form without raw JavaScript leaking into DOM.
     */
    public function test_edit_tax_invoice_page_renders_clean_form_without_raw_javascript_leaking_into_dom(): void
    {
        $invoice = $this->createSampleInvoice([
            'einvoice_status' => 'pending',
            'eway_bill_status' => 'generated',
            'eway_bill_no' => '191040878105',
        ]);

        $response = $this->actingAs($this->user)->get(route('invoices.edit', $invoice));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Verify clean form attribute
        $this->assertStringContainsString('x-data="editSalesInvoiceForm()"', $content);

        // 2. Verify script tag defines the function properly
        $this->assertStringContainsString('function editSalesInvoiceForm()', $content);

        // 3. Verify no raw JavaScript text is leaking into visible HTML text nodes
        $this->assertStringNotContainsString('class="space-y-6">', substr($content, strpos($content, '<form') + 60, 200));

        // 4. Verify statutory banner and fields render normally
        $this->assertStringContainsString('Statutory Lock Active', $content);
        $this->assertStringContainsString('191040878105', $content);
    }
}
