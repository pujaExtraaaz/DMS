<?php

namespace Tests\Feature\Sales;

use App\Domains\Hrms\Models\Department;
use App\Domains\Hrms\Models\Designation;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesInvoiceEditProductAndSerialTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected Employee $salesperson;
    protected Uom $pcsUom;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
            'products.view', 'products.create', 'products.edit', 'products.manage',
            'masters.view', 'masters.manage',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->company = Company::create([
            'name' => 'Demo Solar Corp',
            'code' => 'DSC-01',
            'email' => 'finance@demosolar.com',
            'phone' => '9876543210',
            'state' => 'Maharashtra',
            'gstin' => '27ABCDE1234F1Z5',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Warehouse',
            'code' => 'MW01',
            'state' => 'Maharashtra',
            'is_main' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
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

        $dept = Department::create([
            'name' => 'Sales',
            'code' => 'SLS',
        ]);

        $desig = Designation::create([
            'name' => 'Executive',
            'code' => 'EXEC',
        ]);

        $this->salesperson = Employee::create([
            'name' => 'Amit Sharma',
            'employee_code' => 'EMP-AMIT',
            'company_id' => $this->company->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'status' => 'active',
            'is_salesperson' => true,
        ]);

        $customerType = CustomerType::create([
            'name' => 'Wholesaler',
            'code' => 'WHL',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Rohan Traders',
            'code' => 'CUST-ROHAN-01',
            'customer_type_id' => $customerType->id,
            'email' => 'rohan@traders.com',
            'phone' => '9898989898',
            'state' => 'Maharashtra',
            'shipping_state' => 'Maharashtra',
            'credit_days' => 30,
            'is_active' => true,
        ]);

        $this->pcsUom = Uom::create([
            'name' => 'Piece',
            'code' => 'PCS',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_direct_billing_tax_invoice_page_renders_edit_product_link_and_serial_block(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));

        $response->assertStatus(200);
        $response->assertSee('Edit Product');
        $response->assertSee('productEditUrl');
        $response->assertSee('Serial Numbers');
        $response->assertSee('isSerialTracked');
    }

    public function test_product_edit_submission_redirects_back_to_invoice_create(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Inverter 5kW',
            'sku' => 'INV-5KW-01',
            'serial_no' => 'SN-INV-001',
            'base_uom_id' => $this->pcsUom->id,
            'purchase_price' => 15000,
            'selling_price' => 20000,
            'tax_rate' => 18,
            'tracking_types' => ['serial'],
            'is_active' => true,
        ]);

        $returnUrl = route('invoices.create', ['returned_from' => 'product_edit']);

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), [
            'name' => 'Solar Inverter 5kW V2',
            'sku' => 'INV-5KW-01',
            'serial_no' => 'SN-INV-001',
            'base_uom_id' => $this->pcsUom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'purchase_price' => 15500,
            'selling_price' => 21000,
            'tax_rate' => 18,
            'tracking_types' => ['serial'],
            'is_active' => true,
            'return_to' => 'invoices.create',
            'return_url' => $returnUrl,
            'target_row' => 0,
        ]);

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        $this->assertStringContainsString('updated_product_id=' . $product->id, $target);
        $this->assertStringContainsString('returned_from=product_edit', $target);
        $this->assertStringContainsString('target_row=0', $target);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Solar Inverter 5kw V2',
            'selling_price' => 21000,
        ]);
    }

    public function test_tax_invoice_store_succeeds_when_valid_serials_are_provided_for_tracked_product(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Inverter 5kW',
            'sku' => 'INV-5KW-02',
            'serial_no' => 'SN-INV-002',
            'base_uom_id' => $this->pcsUom->id,
            'purchase_price' => 15000,
            'selling_price' => 20000,
            'tax_rate' => 18,
            'tracking_types' => ['serial'],
            'is_active' => true,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'payment_terms' => 'Net 30',
            'items' => [
                [
                    'product_id' => $product->id,
                    'uom_id' => $this->pcsUom->id,
                    'quantity' => 2,
                    'unit_price' => 20000,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                    'tax_rate' => 18,
                    'serial_numbers' => 'SN-INV-001, SN-INV-002',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
        ]);

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertCount(1, $invoice->items);

        $item = $invoice->items->first();
        $this->assertEquals(['SN-INV-001', 'SN-INV-002'], $item->serial_numbers);
    }

    public function test_tax_invoice_store_fails_when_serials_are_missing_for_tracked_product(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Battery 100Ah',
            'sku' => 'BAT-100-01',
            'serial_no' => 'SN-BAT-001',
            'base_uom_id' => $this->pcsUom->id,
            'purchase_price' => 8000,
            'selling_price' => 12000,
            'tax_rate' => 18,
            'tracking_types' => ['serial'],
            'is_active' => true,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'items' => [
                [
                    'product_id' => $product->id,
                    'uom_id' => $this->pcsUom->id,
                    'quantity' => 1,
                    'unit_price' => 12000,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                    'tax_rate' => 18,
                    'serial_numbers' => '',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasErrors(['items.0.serial_numbers']);
    }

    public function test_tax_invoice_store_fails_when_serial_count_does_not_match_quantity(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Battery 100Ah',
            'sku' => 'BAT-100-02',
            'serial_no' => 'SN-BAT-002',
            'base_uom_id' => $this->pcsUom->id,
            'purchase_price' => 8000,
            'selling_price' => 12000,
            'tax_rate' => 18,
            'tracking_types' => ['serial'],
            'is_active' => true,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'items' => [
                [
                    'product_id' => $product->id,
                    'uom_id' => $this->pcsUom->id,
                    'quantity' => 2,
                    'unit_price' => 12000,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                    'tax_rate' => 18,
                    'serial_numbers' => 'SN-ONLY-ONE',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasErrors(['items.0.serial_numbers']);
    }

    public function test_tax_invoice_store_fails_when_duplicate_serials_are_submitted(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Battery 100Ah',
            'sku' => 'BAT-100-03',
            'serial_no' => 'SN-BAT-003',
            'base_uom_id' => $this->pcsUom->id,
            'purchase_price' => 8000,
            'selling_price' => 12000,
            'tax_rate' => 18,
            'tracking_types' => ['serial'],
            'is_active' => true,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->format('Y-m-d'),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'items' => [
                [
                    'product_id' => $product->id,
                    'uom_id' => $this->pcsUom->id,
                    'quantity' => 2,
                    'unit_price' => 12000,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                    'tax_rate' => 18,
                    'serial_numbers' => 'SN-DUP-01, SN-DUP-01',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasErrors(['items.0.serial_numbers']);
    }

    public function test_invoice_show_and_edit_views_render_saved_serial_numbers(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Hybrid Inverter',
            'sku' => 'HYB-001',
            'serial_no' => 'SN-HYB-001',
            'base_uom_id' => $this->pcsUom->id,
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'tax_rate' => 18,
            'tracking_types' => ['serial'],
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson->id,
            'invoice_no' => 'INV-TEST-SN-001',
            'invoice_date' => now(),
            'subtotal' => 15000,
            'tax_amount' => 2700,
            'grand_total' => 17700,
            'price_type' => 'exclusive',
            'tax_type' => 'intra',
            'delivery_state' => 'Maharashtra',
            'status' => 'issued',
            'payment_status' => 'unpaid',
        ]);

        $invoice->items()->create([
            'product_id' => $product->id,
            'item_name' => $product->name,
            'uom_id' => $this->pcsUom->id,
            'quantity' => 1,
            'unit_price' => 15000,
            'tax_rate' => 18,
            'tax_amount' => 2700,
            'line_total' => 17700,
            'serial_numbers' => ['SN-HYB-999'],
        ]);

        $showResponse = $this->actingAs($this->user)->get(route('invoices.show', $invoice));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('SN-HYB-999');

        $editResponse = $this->actingAs($this->user)->get(route('invoices.edit', $invoice));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('SN-HYB-999');
    }
}
