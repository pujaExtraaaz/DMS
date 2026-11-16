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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseInvoiceProductCreateRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Warehouse $warehouse;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Acme Test Corp',
            'code' => 'ATC-01',
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
            'code' => 'MB01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);
    }

    public function test_purchase_invoice_create_renders_create_product_action_and_draft_support(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('+ Create product', $content);
        $this->assertStringContainsString('createProductUrl', $content);
        $this->assertStringContainsString('restoreCreateDraft', $content);
        $this->assertStringContainsString('saveDraftAndRedirectToProduct', $content);
        $this->assertStringContainsString('pi_create_draft', $content);
    }

    public function test_product_create_form_displays_back_to_purchase_invoice_when_return_to_is_provided(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.create', [
            'return_to' => 'purchasing.invoices.create',
            'target_row' => 2,
        ]));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('name="return_to" value="purchasing.invoices.create"', $content);
        $this->assertStringContainsString('name="target_row" value="2"', $content);
        $this->assertTrue(
            str_contains($content, 'Back To Purchase Invoice') || str_contains($content, 'Back to Purchase Invoice'),
            'Failed asserting that product form displays Back to Purchase Invoice.'
        );

        $expectedReturnUrl = route('purchasing.invoices.create', ['returned_from' => 'product_create']);
        $this->assertStringContainsString($expectedReturnUrl, $content);
    }

    public function test_saving_product_from_purchase_invoice_redirects_to_purchase_invoice_create(): void
    {
        $payload = [
            'name' => 'Widget Pro Ultra',
            'sku' => 'WPU-001',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'is_active' => 1,
            'purchase_price' => 125.50,
            'selling_price' => 200.00,
            'tax_rate' => 18,
            'return_to' => 'purchasing.invoices.create',
            'target_row' => '1',
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);

        $product = Product::where('sku', 'WPU-001')->first();
        $this->assertNotNull($product);

        $expectedRedirect = route('purchasing.invoices.create', [
            'new_product_id' => $product->id,
            'target_row' => '1',
        ]);

        $response->assertRedirect($expectedRedirect);
        $response->assertSessionHas('status', "Product '{$product->name}' created successfully. You can now add it to your purchase invoice.");
    }

    public function test_product_validation_failure_stays_on_product_create_and_preserves_return_to(): void
    {
        $payload = [
            'name' => '', // Required field missing
            'return_to' => 'purchasing.invoices.create',
            'target_row' => '3',
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['name']);
        $this->assertEquals('purchasing.invoices.create', session()->getOldInput('return_to'));
        $this->assertEquals('3', session()->getOldInput('target_row'));
    }

    public function test_purchase_invoice_create_loads_newly_created_active_product(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Hydraulic Valve',
            'sku' => 'HV-100',
            'serial_no' => 'PRD-TEST-0001',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
            'purchase_price' => 500,
            'tax_rate' => 18,
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create', [
            'new_product_id' => $product->id,
            'target_row' => 0,
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('newProduct', function ($p) use ($product) {
            return $p && $p->id === $product->id;
        });

        $products = $response->viewData('products');
        $this->assertTrue($products->contains('id', $product->id));
    }

    public function test_purchase_invoice_create_does_not_select_inactive_product(): void
    {
        $inactiveProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Deprecated Valve',
            'sku' => 'DV-000',
            'serial_no' => 'PRD-TEST-0002',
            'base_uom_id' => $this->uom->id,
            'is_active' => false,
            'purchase_price' => 100,
            'tax_rate' => 18,
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create', [
            'new_product_id' => $inactiveProduct->id,
            'target_row' => 0,
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('newProduct', null);

        $products = $response->viewData('products');
        $this->assertFalse($products->contains('id', $inactiveProduct->id));
    }

    public function test_normal_product_creation_still_redirects_to_products_index(): void
    {
        $payload = [
            'name' => 'Standard Master Item',
            'sku' => 'SMI-001',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'is_active' => 1,
            'purchase_price' => 50.00,
            'selling_price' => 80.00,
            'tax_rate' => 18,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);

        $response->assertRedirect(route('masters.products.index'));
        $response->assertSessionHas('status', function ($msg) {
            return strcasecmp($msg, 'Product created successfully.') === 0;
        });
    }

    public function test_orders_create_return_to_still_redirects_to_orders_create(): void
    {
        $payload = [
            'name' => 'Order Item Redirect',
            'sku' => 'OIR-001',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'is_active' => 1,
            'return_to' => 'orders.create',
            'target_row' => '2',
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);

        $product = Product::where('sku', 'OIR-001')->first();
        $this->assertNotNull($product);

        $response->assertRedirect(route('orders.create', [
            'new_product_id' => $product->id,
            'target_row' => '2',
        ]));
    }

    public function test_server_draft_session_can_be_saved_and_cleared(): void
    {
        $draftPayload = [
            'supplierId' => 10,
            'supplierInvoiceNumber' => 'PI-001',
            'items' => [
                ['product_id' => 1, 'quantity' => 10, 'unit_cost' => 500],
            ],
        ];

        $response = $this->actingAs($this->user)->postJson(route('purchasing.invoices.draft.save'), [
            'draft' => $draftPayload,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals($draftPayload, session('purchase_invoice_draft'));

        $clearResponse = $this->actingAs($this->user)->deleteJson(route('purchasing.invoices.draft.clear'));
        $clearResponse->assertStatus(200);
        $clearResponse->assertJson(['success' => true]);
        $this->assertNull(session('purchase_invoice_draft'));
    }

    public function test_full_workflow_preserves_multiple_items_and_saves_invoice(): void
    {
        $customerType = CustomerType::create([
            'name' => 'Vendor',
            'code' => 'VND',
            'is_active' => true,
        ]);

        $supplier = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'ABC Traders',
            'code' => 'SUP-ABC',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $customerType->id,
            'is_active' => true,
        ]);

        $productA = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Product A',
            'sku' => 'PRD-A-001',
            'serial_no' => 'SN-PRD-A',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
            'purchase_price' => 500,
            'tax_rate' => 18,
        ]);

        $productB = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Product B',
            'sku' => 'PRD-B-001',
            'serial_no' => 'SN-PRD-B',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
            'purchase_price' => 1000,
            'tax_rate' => 18,
        ]);

        // 1. Simulate saving draft before navigating to create Product C
        $draft = [
            'supplierId' => $supplier->id,
            'warehouseId' => $this->warehouse->id,
            'invoiceDate' => '2026-11-16',
            'supplierInvoiceNumber' => 'PI-001',
            'creditDays' => 30,
            'priceType' => 'exclusive',
            'targetRow' => 1,
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 10,
                    'unit_cost' => 500,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                    'line_total' => 5900,
                ],
                [
                    'product_id' => $productB->id,
                    'quantity' => 5,
                    'unit_cost' => 1000,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                    'line_total' => 5900,
                ],
            ],
        ];

        $this->actingAs($this->user)->postJson(route('purchasing.invoices.draft.save'), ['draft' => $draft])
            ->assertStatus(200);

        // 2. User creates Product C
        $createProductResponse = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Product C',
            'sku' => 'PRD-C-001',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'is_active' => 1,
            'purchase_price' => 750.00,
            'selling_price' => 1200.00,
            'tax_rate' => 18,
            'return_to' => 'purchasing.invoices.create',
            'target_row' => '1',
        ]);

        $productC = Product::where('sku', 'PRD-C-001')->first();
        $this->assertNotNull($productC);

        $createProductResponse->assertRedirect(route('purchasing.invoices.create', [
            'new_product_id' => $productC->id,
            'target_row' => '1',
        ]));

        // 3. Page loads upon returning
        $returnResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.create', [
            'new_product_id' => $productC->id,
            'target_row' => '1',
        ]));

        $returnResponse->assertStatus(200);
        $returnResponse->assertViewHas('savedDraft', function ($saved) use ($supplier) {
            return $saved && (int) $saved['supplierId'] === $supplier->id && $saved['supplierInvoiceNumber'] === 'PI-001';
        });

        $products = $returnResponse->viewData('products');
        $this->assertTrue($products->contains('id', $productA->id));
        $this->assertTrue($products->contains('id', $productB->id));
        $this->assertTrue($products->contains('id', $productC->id));

        // 4. Save the Purchase Invoice with Product A, Product B, and newly added Product C
        $invoicePayload = [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-11-16',
            'supplier_invoice_number' => 'PI-001',
            'credit_days' => 30,
            'price_type' => 'exclusive',
            'save_mode' => 'open',
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 10,
                    'unit_cost' => 500,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
                [
                    'product_id' => $productB->id,
                    'quantity' => 5,
                    'unit_cost' => 1000,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
                [
                    'product_id' => $productC->id,
                    'quantity' => 2,
                    'unit_cost' => 750,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
            ],
        ];

        $storeInvoiceResponse = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $invoicePayload);
        $storeInvoiceResponse->assertSessionHasNoErrors();

        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'PI-001')->first();
        $this->assertNotNull($invoice);
        $storeInvoiceResponse->assertRedirect(route('purchasing.invoices.show', $invoice));

        // Verify session draft was forgotten on store
        $this->assertNull(session('purchase_invoice_draft'));

        // Verify all 3 items exist in DB
        $this->assertCount(3, $invoice->items);
        $this->assertTrue($invoice->items->contains('product_id', $productA->id));
        $this->assertTrue($invoice->items->contains('product_id', $productB->id));
        $this->assertTrue($invoice->items->contains('product_id', $productC->id));
    }
}
