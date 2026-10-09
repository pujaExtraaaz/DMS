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
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseInvoiceCreditDaysTest extends TestCase
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
            'name' => 'Acme Trading Co',
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

        $customerType = CustomerType::create([
            'name' => 'Vendor',
            'code' => 'VND',
            'is_active' => true,
        ]);

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Quality Goods Vendor',
            'code' => 'SUP-003',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $customerType->id,
            'credit_days' => 30,
            'phone' => '9876543210',
            'state' => 'Maharashtra',
            'is_active' => true,
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
            'name' => 'Central Warehouse',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Component Y',
            'sku' => 'CMP-002',
            'serial_no' => 'SN-CMP-002',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 100.00,
            'selling_price' => 150.00,
            'is_active' => true,
        ]);
    }

    public function test_create_invoice_page_renders_quick_options_custom_input_and_preview(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create'));

        $response->assertStatus(200);

        // Quick options for 30 days, 45 days, and 50 days
        $response->assertSee('30 Days');
        $response->assertSee('45 Days');
        $response->assertSee('50 Days');

        // Custom numeric input for credit_days
        $response->assertSee('name="credit_days"', false);
        $response->assertSee('x-model.number="creditDays"', false);

        // Due date preview & handler
        $response->assertSee('Due Date Preview:', false);
        $response->assertSee('recalcDueDate()', false);
        $response->assertSee('setCreditDays(30)', false);
        $response->assertSee('setCreditDays(45)', false);
        $response->assertSee('setCreditDays(50)', false);
    }

    public function test_preset_30_days_calculates_and_persists_due_date(): void
    {
        $invoiceDate = '2026-10-10';
        $expectedDueDate = Carbon::parse($invoiceDate)->addDays(30)->toDateString(); // 2026-11-09

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => $invoiceDate,
            'credit_days' => 30,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 100.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = PurchaseInvoice::latest('id')->first();
        $this->assertEquals(30, $invoice->credit_days);
        $this->assertEquals($expectedDueDate, $invoice->due_date?->toDateString());
    }

    public function test_preset_45_days_calculates_and_persists_due_date(): void
    {
        $invoiceDate = '2026-10-10';
        $expectedDueDate = Carbon::parse($invoiceDate)->addDays(45)->toDateString(); // 2026-11-24

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => $invoiceDate,
            'credit_days' => 45,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 100.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = PurchaseInvoice::latest('id')->first();
        $this->assertEquals(45, $invoice->credit_days);
        $this->assertEquals($expectedDueDate, $invoice->due_date?->toDateString());
    }

    public function test_preset_50_days_calculates_and_persists_due_date(): void
    {
        $invoiceDate = '2026-10-10';
        $expectedDueDate = Carbon::parse($invoiceDate)->addDays(50)->toDateString(); // 2026-11-29

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => $invoiceDate,
            'credit_days' => 50,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 100.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = PurchaseInvoice::latest('id')->first();
        $this->assertEquals(50, $invoice->credit_days);
        $this->assertEquals($expectedDueDate, $invoice->due_date?->toDateString());
    }

    public function test_custom_credit_days_calculates_and_persists_due_date(): void
    {
        $invoiceDate = '2026-10-15';
        $expectedDueDate = Carbon::parse($invoiceDate)->addDays(15)->toDateString(); // 2026-10-30

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => $invoiceDate,
            'credit_days' => 15,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 100.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = PurchaseInvoice::latest('id')->first();
        $this->assertEquals(15, $invoice->credit_days);
        $this->assertEquals($expectedDueDate, $invoice->due_date?->toDateString());
    }

    public function test_invoice_show_and_preview_display_saved_credit_days_and_due_date(): void
    {
        $invoiceDate = Carbon::parse('2026-10-10');
        $dueDate = $invoiceDate->copy()->addDays(45);

        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-CRD-001',
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'credit_days' => 45,
            'status' => 'posted',
            'subtotal' => 100.00,
            'tax_amount' => 0.00,
            'grand_total' => 100.00,
            'created_by' => $this->user->id,
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 1,
            'unit_cost' => 100,
            'tax_percent' => 0,
            'line_total' => 100,
        ]);

        // Show page
        $showResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $invoice));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($dueDate->format('d M Y')); // 24 Nov 2026
        $showResponse->assertSee('(45 days)');

        // Preview page
        $previewResponse = $this->actingAs($this->user)->get(route('purchasing.invoices.preview', $invoice));
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee($dueDate->format('d/m/Y')); // 24/11/2026
        $previewResponse->assertSee('(45 days)');
    }

    public function test_blank_credit_days_handled_safely(): void
    {
        $invoiceDate = '2026-10-10';

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => $invoiceDate,
            'credit_days' => null,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_cost' => 100.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = PurchaseInvoice::latest('id')->first();
        $this->assertNotNull($invoice);
    }
}

