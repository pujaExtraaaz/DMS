<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateNewPurchaseInvoiceButtonTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $supplier;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Apex Enterprises',
            'code' => 'APEX-01',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $branch = Branch::create([
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Central Warehouse',
            'code' => 'CW-01',
            'branch_id' => $branch->id,
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $creditorType = CustomerType::firstOrCreate(
            ['name' => Customer::PARTY_TYPE_SUNDRY_CREDITORS],
            ['code' => 'SC-01', 'is_active' => true]
        );

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $creditorType->id,
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'name' => 'Supreme Steel Suppliers',
            'code' => 'SUP-001',
            'credit_days' => 30,
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Stainless Steel Rod',
            'sku' => 'SSR-001',
            'serial_no' => 'SSR-SN-001',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 150.00,
            'tax_rate' => 18,
            'tracking_type' => 'none',
            'is_active' => true,
        ]);
    }

    public function test_saved_purchase_invoice_details_page_displays_create_new_purchase_invoice_button_above_preview(): void
    {
        $service = app(PurchaseOrderService::class);
        $invoice = $service->createInvoice([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'SUPP-INV-101',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_cost' => 150,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
            ],
        ], $this->user);

        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $invoice));

        $response->assertOk();
        $response->assertSee('+ Create New Purchase Invoice');
        $response->assertSee('Preview Invoice');
        $response->assertSee(route('purchasing.invoices.create'));
        $response->assertSee(route('purchasing.invoices.preview', $invoice));

        // Verify order in HTML: "+ Create New Purchase Invoice" appears before "Preview Invoice"
        $content = $response->getContent();
        $posCreate = strpos($content, '+ Create New Purchase Invoice');
        $posPreview = strpos($content, 'Preview Invoice');

        $this->assertNotFalse($posCreate, 'Create New Purchase Invoice button not found');
        $this->assertNotFalse($posPreview, 'Preview Invoice button not found');
        $this->assertLessThan($posPreview, $posCreate, 'Create New Purchase Invoice button should appear above Preview Invoice button');
    }

    public function test_create_form_does_not_display_create_new_purchase_invoice_button_before_saving(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create'));

        $response->assertOk();
        $response->assertDontSee('+ Create New Purchase Invoice');
        $response->assertDontSee('Preview Invoice');
    }

    public function test_clicking_create_new_purchase_invoice_opens_fresh_form_without_carrying_previous_invoice_data(): void
    {
        // 1. Create and save an initial purchase invoice
        $saveResponse = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-03-15',
            'supplier_invoice_number' => 'INV-PREV-999',
            'notes' => 'Old special invoice instructions',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 25,
                    'unit_cost' => 500,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
            ],
        ]);

        $firstInvoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-PREV-999')->firstOrFail();
        $saveResponse->assertRedirect(route('purchasing.invoices.show', $firstInvoice));

        // 2. Open details page and follow the link to create a new purchase invoice
        $showResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $firstInvoice));
        $showResponse->assertSee(route('purchasing.invoices.create'));

        $createResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.create'));
        $createResponse->assertOk();

        // 3. Verify previous invoice specific values are not preset in the fresh create form
        $createResponse->assertDontSee('INV-PREV-999');
        $createResponse->assertDontSee('Old special invoice instructions');

        // 4. Create second invoice with fresh details
        $secondProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Aluminum Bar',
            'sku' => 'ALB-002',
            'serial_no' => 'ALB-SN-002',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 300.00,
            'tax_rate' => 18,
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $saveSecondResponse = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-03-20',
            'supplier_invoice_number' => 'INV-NEW-1000',
            'items' => [
                [
                    'product_id' => $secondProduct->id,
                    'quantity' => 5,
                    'unit_cost' => 300,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
            ],
        ]);

        $secondInvoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-NEW-1000')->firstOrFail();
        $saveSecondResponse->assertRedirect(route('purchasing.invoices.show', $secondInvoice));

        // 5. Verify different voucher numbers were generated
        $this->assertNotEmpty($firstInvoice->invoice_no);
        $this->assertNotEmpty($secondInvoice->invoice_no);
        $this->assertNotEquals($firstInvoice->invoice_no, $secondInvoice->invoice_no);

        // 6. Verify first invoice is completely unchanged
        $firstInvoice->refresh();
        $this->assertEquals('INV-PREV-999', $firstInvoice->supplier_invoice_no);
        $this->assertEquals(25, (float) $firstInvoice->items()->first()->quantity);

        // 7. Verify second invoice details page also displays the button above preview
        $showSecond = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $secondInvoice));
        $showSecond->assertOk();
        $showSecond->assertSee('+ Create New Purchase Invoice');
        $showSecond->assertSee('Preview Invoice');
    }

    public function test_preview_invoice_button_and_endpoint_continue_working_normally(): void
    {
        $service = app(PurchaseOrderService::class);
        $invoice = $service->createInvoice([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'SUPP-INV-202',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_cost' => 100,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
            ],
        ], $this->user);

        // Verify preview page renders successfully
        $previewResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.preview', $invoice));
        $previewResponse->assertOk();
        $previewResponse->assertSee($invoice->invoice_no);
        $previewResponse->assertSee('SUPP-INV-202');
    }
}
