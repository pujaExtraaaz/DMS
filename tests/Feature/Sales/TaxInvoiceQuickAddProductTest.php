<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxInvoiceQuickAddProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customer;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
            'orders.view', 'orders.create', 'orders.edit', 'orders.book', 'orders.approve',
            'products.view', 'products.create', 'products.edit', 'products.manage',
            'masters.view', 'masters.manage',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->company = Company::create([
            'name' => 'Avit Digital Systems',
            'code' => 'AVIT-01',
            'state' => 'Maharashtra',
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

        $customerType = CustomerType::create([
            'name' => 'Wholesaler',
            'code' => 'WHL',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Apex Retailers',
            'code' => 'CUST-APEX',
            'phone' => '9876543210',
            'customer_type_id' => $customerType->id,
            'credit_days' => 30,
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name' => 'Pune Branch',
            'code' => 'PB-01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Depot',
            'code' => 'WH-PUNE',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Existing Laser Meter LM-100',
            'sku' => 'LM-100',
            'serial_no' => 'PRD-LM-100',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 1200.00,
            'tax_rate' => 18.0,
            'is_active' => true,
        ]);
    }

    public function test_tax_invoice_page_renders_quick_add_product_buttons_with_return_url(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));

        $response->assertStatus(200);

        // Header Quick Add Product button exists and links to product creation with return_to
        $expectedUrl = route('masters.products.create', [
            'return_to' => 'invoices.create',
            'return_url' => route('invoices.create'),
        ]);
        $response->assertSee(e($expectedUrl), false);
        $response->assertSee('+ Quick Add Product');
        $response->assertSee('saveDraftAndRedirectToProduct($event)');

        // Row-level Quick Add link exists
        $response->assertSee('+ Quick Add');
        $response->assertSee('saveDraftAndRedirectToProduct($event, i)');
    }

    public function test_product_create_page_shows_back_to_tax_invoice_when_return_to_is_invoices_create(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.create', [
            'return_to' => 'invoices.create',
            'return_url' => route('invoices.create'),
        ]));

        $response->assertStatus(200);
        $response->assertSee('Back To Tax Invoice');
        $response->assertSee('name="return_to"', false);
        $response->assertSee('value="invoices.create"', false);
    }

    public function test_product_validation_error_keeps_user_on_product_create_with_inputs_and_return_to(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'return_to' => 'invoices.create',
            'return_url' => route('invoices.create'),
            'name' => '', // Missing required name
            'sku' => 'TEST-INVALID-SKU',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertEquals(0, Product::where('sku', 'TEST-INVALID-SKU')->count());
    }

    public function test_saving_product_with_return_to_invoices_create_redirects_back_to_tax_invoice_with_message(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'return_to' => 'invoices.create',
            'return_url' => route('invoices.create'),
            'target_row' => 1,
            'name' => 'Newly Created Smart Sensor SS-500',
            'sku' => 'SS-500',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'selling_price' => 3500.00,
            'tax_rate' => 18.0,
            'is_active' => 1,
        ]);

        $created = Product::where('sku', 'SS-500')->first();
        $this->assertNotNull($created);
        $this->assertEquals('Newly Created Smart Sensor SS-500', $created->name);

        $response->assertRedirect(route('invoices.create', [
            'new_product_id' => $created->id,
            'target_row' => 1,
        ]));

        $response->assertSessionHas('status', "Product '{$created->name}' created successfully. You can continue preparing your tax invoice.");
    }

    public function test_tax_invoice_draft_save_endpoint_persists_draft_in_session(): void
    {
        $draftData = [
            'customerId' => $this->customer->id,
            'deliveryNoteNo' => 'DN-7788',
            'orderNo' => 'PO-CUST-9900',
            'termsOfDelivery' => "Line 1 Terms\nLine 2 Timing\nLine 3 Note",
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 4,
                    'unit_price' => 1250.00,
                    'discount_value' => 50.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->postJson(route('invoices.draft.save'), [
            'draft' => $draftData,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals($draftData, session('tax_invoice_draft'));
    }

    public function test_tax_invoice_create_restores_draft_and_supplies_new_product_data(): void
    {
        $newProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Brand New Widget BW-10',
            'sku' => 'BW-10',
            'serial_no' => 'PRD-BW-10',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 2400.00,
            'tax_rate' => 18.0,
            'is_active' => true,
        ]);

        $draftData = [
            'customerId' => $this->customer->id,
            'deliveryNoteNo' => 'DN-9999',
            'destination' => 'Warehouse Bay 4',
            'termsOfDelivery' => 'Urgent delivery within 48 hours',
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 1200.00,
                ],
            ],
        ];

        // Put draft in session
        session(['tax_invoice_draft' => $draftData]);

        // Load invoice create with newly created product
        $response = $this->actingAs($this->user)->get(route('invoices.create', [
            'new_product_id' => $newProduct->id,
        ]));

        $response->assertStatus(200);

        // Product list contains the new product
        $response->assertSee('Brand New Widget BW-10');
        $response->assertSee((string) $newProduct->id);

        // Saved draft is passed to view and cleared from session
        $this->assertNull(session('tax_invoice_draft'));
    }

    public function test_normal_product_create_without_return_to_redirects_to_products_index(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Standard Catalog Product',
            'sku' => 'STD-CAT-01',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'selling_price' => 500.00,
            'tax_rate' => 18.0,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));
    }

    public function test_open_redirect_with_external_return_url_is_prevented(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'return_url' => 'https://evil-phishing-site.com/steal-creds',
            'name' => 'Security Test Product',
            'sku' => 'SEC-01',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'selling_price' => 100.00,
            'tax_rate' => 18.0,
            'is_active' => 1,
        ]);

        // External return_url must be rejected; should fall back to masters.products.index
        $response->assertRedirect(route('masters.products.index'));
        $this->assertFalse(str_contains((string) $response->headers->get('Location'), 'evil-phishing-site.com'));
    }
}
