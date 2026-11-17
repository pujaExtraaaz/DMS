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

class CreatePartyFromDirectBillingInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected CustomerType $customerType;
    protected Customer $existingCustomer;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product;
    protected Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
            'orders.view', 'orders.create', 'orders.edit', 'orders.book', 'orders.approve',
            'customers.view', 'customers.create', 'customers.edit', 'customers.manage',
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

        $this->customerType = CustomerType::create([
            'name' => 'Dealer',
            'code' => 'DLR',
            'is_active' => true,
        ]);

        $this->existingCustomer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Existing Alpha Traders',
            'code' => 'CUST-ALPHA',
            'phone' => '9876500001',
            'customer_type_id' => $this->customerType->id,
            'credit_days' => 15,
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name' => 'Head Office',
            'code' => 'HO-01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Sensor X10',
            'sku' => 'SN-X10',
            'serial_no' => 'PRD-SN-X10',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 2500.00,
            'tax_rate' => 18.0,
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Digital Controller C20',
            'sku' => 'CTRL-C20',
            'serial_no' => 'PRD-CTRL-C20',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 4500.00,
            'tax_rate' => 18.0,
            'is_active' => true,
        ]);
    }

    public function test_tax_invoice_page_renders_add_new_party_link(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));

        $response->assertStatus(200);
        $response->assertSee('Customer / Debtor *', false);
        $response->assertSee('+ Add New', false);
        $response->assertSee('saveDraftAndRedirectToParty($event)', false);
        $response->assertSee('partyCreateUrl', false);
    }

    public function test_customer_create_page_displays_return_banner_when_return_url_passed(): void
    {
        $returnUrl = route('invoices.create', ['returned_from' => 'party_create']);

        $response = $this->actingAs($this->user)->get(route('masters.customers.create', [
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'return_to' => 'invoices.create',
            'return_url' => $returnUrl,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Adding Customer for Tax Invoice', false);
        $response->assertSee('Cancel &amp; Go Back', false);
        $response->assertSee(e($returnUrl), false);
        $response->assertSee('name="return_to" value="invoices.create"', false);
    }

    public function test_customer_store_redirects_to_invoices_create_with_new_customer_id(): void
    {
        $returnUrl = route('invoices.create', ['returned_from' => 'party_create']);

        $payload = [
            'name' => 'New Horizon Enterprises',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9822099999',
            'email' => 'horizon@example.com',
            'state' => 'Maharashtra',
            'credit_days' => 45,
            'is_active' => '1',
            'return_to' => 'invoices.create',
            'return_url' => $returnUrl,
            'addresses' => [
                [
                    'label' => 'Head Office / Billing',
                    'contact_person' => 'Rajesh Sharma',
                    'contact_phone' => '9822099999',
                    'address_line_1' => 'Plot 44, MIDC Industrial Area',
                    'city' => 'Pune',
                    'state' => 'Maharashtra',
                    'pincode' => '411018',
                    'type' => 'both',
                    'is_default_billing' => '1',
                    'is_default_delivery' => '1',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('masters.customers.store'), $payload);

        $newCustomer = Customer::where('name', 'New Horizon Enterprises')->first();
        $this->assertNotNull($newCustomer);
        $this->assertEquals(45, $newCustomer->credit_days);

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        $this->assertStringContainsString('invoices/create', $target);
        $this->assertStringContainsString('customer_id='.$newCustomer->id, $target);
        $this->assertStringContainsString('new_customer_id='.$newCustomer->id, $target);
        $this->assertStringContainsString('returned_from=party_create', $target);
    }

    public function test_scenario_a_party_creation_and_automatic_return_with_all_invoice_data_preserved(): void
    {
        // 1. User is entering an invoice with multiple products, remarks, credit days, universal discount
        $draft = [
            'customerId' => '',
            'notes' => 'Deliver by Saturday morning at Gate 3',
            'deliveryState' => 'Maharashtra',
            'invoiceDate' => '2026-11-20',
            'creditDays' => 30,
            'universalType' => 'flat',
            'universalValue' => 250,
            'lines' => [
                [
                    'product_id' => (string) $this->product->id,
                    'unit' => 'PCS',
                    'quantity' => 10,
                    'unit_price' => 2500,
                    'discount_type' => 'flat',
                    'discount_value' => 100,
                    'tax_rate' => 18,
                ],
                [
                    'product_id' => (string) $this->product2->id,
                    'unit' => 'PCS',
                    'quantity' => 4,
                    'unit_price' => 4500,
                    'discount_type' => 'percent',
                    'discount_value' => 5,
                    'tax_rate' => 18,
                ],
            ],
        ];

        // Save draft via JSON endpoint
        $draftResponse = $this->actingAs($this->user)->postJson(route('invoices.draft.save'), [
            'draft' => $draft,
        ]);
        $draftResponse->assertStatus(200)->assertJson(['success' => true]);

        // 2. User creates a new party via form submission
        $payload = [
            'name' => 'Metro Infra Projects',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9822188888',
            'email' => 'metro@example.com',
            'state' => 'Maharashtra',
            'credit_days' => 30,
            'is_active' => '1',
            'return_to' => 'invoices.create',
            'return_url' => route('invoices.create', ['returned_from' => 'party_create']),
        ];

        $storeResponse = $this->actingAs($this->user)->post(route('masters.customers.store'), $payload);

        $newCustomer = Customer::where('name', 'Metro Infra Projects')->first();
        $this->assertNotNull($newCustomer);

        $storeResponse->assertRedirect();
        $target = $storeResponse->headers->get('Location');
        $this->assertStringContainsString('invoices/create', $target);
        $this->assertStringContainsString('customer_id='.$newCustomer->id, $target);

        // 3. Application redirects user back to tax invoice creation page
        $response = $this->actingAs($this->user)
            ->withSession(['tax_invoice_draft' => $draft])
            ->get($target);

        $response->assertStatus(200);

        // Verify view data contains the new customer and it is selected
        $customers = $response->viewData('customers');
        $this->assertTrue($customers->contains('id', $newCustomer->id));
        $this->assertEquals($newCustomer->id, $response->viewData('newCustomer')?->id);

        // Verify savedDraft contains all original line items and metadata
        $savedDraft = $response->viewData('savedDraft');
        $this->assertNotNull($savedDraft);
        $this->assertEquals('Deliver by Saturday morning at Gate 3', $savedDraft['notes']);
        $this->assertCount(2, $savedDraft['lines']);
        $this->assertEquals((string) $this->product->id, $savedDraft['lines'][0]['product_id']);
        $this->assertEquals(10, $savedDraft['lines'][0]['quantity']);
        $this->assertEquals((string) $this->product2->id, $savedDraft['lines'][1]['product_id']);
        $this->assertEquals(4, $savedDraft['lines'][1]['quantity']);

        // Verify session was cleaned up
        $this->assertNull(session('tax_invoice_draft'));

        // Verify page rendered the new customer
        $response->assertSee('Metro Infra Projects');
        $response->assertSee('Deliver by Saturday morning at Gate 3');
    }

    public function test_scenario_b_party_creation_validation_failure_preserves_return_context_and_draft(): void
    {
        $draft = [
            'notes' => 'Preserved during validation check',
            'lines' => [
                [
                    'product_id' => (string) $this->product->id,
                    'quantity' => 3,
                    'unit_price' => 2500,
                ],
            ],
        ];

        $this->actingAs($this->user)->postJson(route('invoices.draft.save'), [
            'draft' => $draft,
        ]);

        // Submit incomplete form (missing name)
        $invalidPayload = [
            'name' => '',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'return_to' => 'invoices.create',
            'return_url' => route('invoices.create', ['returned_from' => 'party_create']),
        ];

        $failedResponse = $this->actingAs($this->user)
            ->from(route('masters.customers.create', [
                'return_to' => 'invoices.create',
                'return_url' => route('invoices.create', ['returned_from' => 'party_create']),
            ]))
            ->post(route('masters.customers.store'), $invalidPayload);

        $failedResponse->assertRedirect(route('masters.customers.create', [
            'return_to' => 'invoices.create',
            'return_url' => route('invoices.create', ['returned_from' => 'party_create']),
        ]));
        $failedResponse->assertSessionHasErrors(['name']);

        // Follow redirect back to party create page with old input
        $createPageResponse = $this->actingAs($this->user)
            ->withSession([
                '_old_input' => $invalidPayload,
                'tax_invoice_draft' => $draft,
            ])
            ->get(route('masters.customers.create', [
                'return_to' => 'invoices.create',
                'return_url' => route('invoices.create', ['returned_from' => 'party_create']),
            ]));

        $createPageResponse->assertStatus(200);
        $createPageResponse->assertSee('Adding Customer for Tax Invoice');
        $createPageResponse->assertSee('name="return_to" value="invoices.create"', false);

        // Correct the details and submit
        $validPayload = $invalidPayload;
        $validPayload['name'] = 'Corrected Enterprise PVT LTD';

        $successResponse = $this->actingAs($this->user)->post(route('masters.customers.store'), $validPayload);
        $successResponse->assertSessionHasNoErrors();
        $newCustomer = Customer::latest('id')->first();
        $this->assertNotNull($newCustomer);
        $this->assertStringContainsString('ENTERPRISE', strtoupper($newCustomer->name));

        $successResponse->assertRedirect();
        $target = $successResponse->headers->get('Location');
        $this->assertStringContainsString('customer_id='.$newCustomer->id, $target);

        // Verify return to tax invoice restores draft
        $invoiceResponse = $this->actingAs($this->user)
            ->withSession(['tax_invoice_draft' => $draft])
            ->get($target);

        $invoiceResponse->assertStatus(200);
        $this->assertEquals($newCustomer->id, $invoiceResponse->viewData('newCustomer')?->id);
        $this->assertEquals('Preserved during validation check', $invoiceResponse->viewData('savedDraft')['notes']);
    }

    public function test_scenario_c_cancel_party_creation_returns_to_invoices_with_all_data_preserved(): void
    {
        $draft = [
            'customerId' => (string) $this->existingCustomer->id,
            'notes' => 'Preserved notes before cancel',
            'deliveryState' => 'Maharashtra',
            'lines' => [
                [
                    'product_id' => (string) $this->product->id,
                    'unit' => 'PCS',
                    'quantity' => 5,
                    'unit_price' => 2500,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                    'tax_rate' => 18,
                ],
            ],
        ];

        // User cancelled on party create and clicked Cancel link pointing to /invoices/create?returned_from=party_create
        $response = $this->actingAs($this->user)
            ->withSession(['tax_invoice_draft' => $draft])
            ->get(route('invoices.create', [
                'returned_from' => 'party_create',
            ]));

        $response->assertStatus(200);
        $this->assertEquals($draft, $response->viewData('savedDraft'));
        $this->assertNull(session('tax_invoice_draft'));
        $response->assertSee('Preserved notes before cancel');
    }

    public function test_scenario_d_new_invoice_without_existing_items_selects_new_party(): void
    {
        $newCustomer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Brand New Client Ltd',
            'code' => 'CUST-NEW-01',
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('invoices.create', [
            'customer_id' => $newCustomer->id,
            'new_customer_id' => $newCustomer->id,
            'returned_from' => 'party_create',
        ]));

        $response->assertStatus(200);
        $this->assertEquals($newCustomer->id, $response->viewData('newCustomer')?->id);
        $this->assertNull($response->viewData('savedDraft'));
        $response->assertSee('Brand New Client Ltd');
    }

    public function test_scenario_e_existing_party_selection_still_works_normally(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create', [
            'customer_id' => $this->existingCustomer->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee($this->existingCustomer->name);
        $customers = $response->viewData('customers');
        $this->assertTrue($customers->contains('id', $this->existingCustomer->id));
    }
}
