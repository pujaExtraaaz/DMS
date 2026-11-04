<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxInvoiceSalespersonTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $salesperson1;
    protected User $salesperson2;
    protected Company $company;
    protected Customer $customer;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $spRole = Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);

        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
            'reports.sales', 'reports.view',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->company = Company::create([
            'name' => 'Avit Digital Systems',
            'code' => 'AVIT-01',
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($role);

        $this->salesperson1 = User::factory()->create([
            'name' => 'Ramesh Sharma',
            'company_id' => $this->company->id,
        ]);
        $this->salesperson1->assignRole($spRole);

        $this->salesperson2 = User::factory()->create([
            'name' => 'Pooja Verma',
            'company_id' => $this->company->id,
        ]);
        $this->salesperson2->assignRole($spRole);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => Carbon::now()->subMonths(2)->startOfMonth()->toDateString(),
            'ends_on' => Carbon::now()->addMonths(10)->endOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Apex Enterprises',
            'code' => 'CUST-001',
            'salesperson_id' => $this->salesperson1->id,
            'state' => 'Maharashtra',
            'shipping_state' => 'Maharashtra',
            'credit_days' => 30,
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name' => 'Mumbai Branch',
            'code' => 'MB-01',
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
            'company_id' => $this->company->id,
            'name' => 'Enterprise Switch 24-Port',
            'sku' => 'SW-24P-001',
            'serial_no' => 'PRD-SW-24P-001',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 15000,
            'tax_rate' => 18,
            'is_active' => true,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
        ]);
    }

    public function test_tax_invoice_create_form_renders_salesperson_dropdown(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('invoices.create'));

        $response->assertOk();
        $response->assertSee('Salesperson');
        $response->assertSee('Select Salesperson (Optional)');
        $response->assertSee($this->salesperson1->name);
        $response->assertSee($this->salesperson2->name);
    }

    public function test_can_create_tax_invoice_with_assigned_salesperson(): void
    {
        $today = Carbon::now()->toDateString();
        $payload = [
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson1->id,
            'invoice_date' => $today,
            'credit_days' => 15,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 15000,
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('invoices.store'), $payload);

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $response->assertRedirect(route('invoices.show', $invoice));

        $this->assertEquals($this->salesperson1->id, $invoice->salesperson_id);
        $this->assertEquals('Ramesh Sharma', $invoice->salesperson->name);
    }

    public function test_salesperson_is_optional_and_historical_sales_without_salesperson_are_valid(): void
    {
        $today = Carbon::now()->toDateString();
        $payload = [
            'customer_id' => $this->customer->id,
            'salesperson_id' => '',
            'invoice_date' => $today,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 15000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('invoices.store'), $payload);

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $response->assertRedirect(route('invoices.show', $invoice));

        $this->assertNull($invoice->salesperson_id);
        $this->assertNull($invoice->salesperson);
    }

    public function test_salesperson_persists_when_invoice_is_edited_and_can_be_updated(): void
    {
        $today = Carbon::now()->toDateString();
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-TEST-001',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson1->id,
            'invoice_date' => $today,
            'due_date' => $today,
            'due_date_basis' => 'custom',
            'status' => 'issued',
            'subtotal' => 15000,
            'tax_amount' => 2700,
            'grand_total' => 17700,
            'paid_amount' => 0,
        ]);

        // Edit form renders salesperson correctly
        $editResponse = $this->actingAs($this->adminUser)->get(route('invoices.edit', $invoice));
        $editResponse->assertOk();
        $editResponse->assertSee('Salesperson');
        $editResponse->assertSee($this->salesperson1->name);

        // Update salesperson to salesperson2
        $updateResponse = $this->actingAs($this->adminUser)->put(route('invoices.update', $invoice), [
            'salesperson_id' => $this->salesperson2->id,
            'notes' => 'Updated notes',
        ]);
        $updateResponse->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals($this->salesperson2->id, $invoice->salesperson_id);
        $this->assertEquals('Pooja Verma', $invoice->salesperson->name);
    }

    public function test_invoice_views_and_exports_display_salesperson(): void
    {
        $today = Carbon::now()->toDateString();
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-TEST-002',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson1->id,
            'invoice_date' => $today,
            'due_date' => $today,
            'due_date_basis' => 'custom',
            'status' => 'issued',
            'subtotal' => 15000,
            'tax_amount' => 2700,
            'grand_total' => 17700,
            'paid_amount' => 0,
        ]);

        // Show page
        $showResponse = $this->actingAs($this->adminUser)->get(route('invoices.show', $invoice));
        $showResponse->assertOk();
        $showResponse->assertSee('Ramesh Sharma');

        // Preview page
        $previewResponse = $this->actingAs($this->adminUser)->get(route('invoices.preview', $invoice));
        $previewResponse->assertOk();
        $previewResponse->assertSee('Salesperson: Ramesh Sharma');

        // PDF endpoint
        $pdfResponse = $this->actingAs($this->adminUser)->get(route('invoices.pdf', $invoice));
        $pdfResponse->assertOk();

        // PDF view content
        $pdfHtml = view('sales.invoices.pdf', [
            'invoice' => $invoice,
            'company' => $this->company,
            'invoiceQrDataUri' => '',
            'upiQrDataUri' => '',
        ])->render();
        $this->assertStringContainsString('Salesperson: Ramesh Sharma', $pdfHtml);
    }

    public function test_sales_report_filters_by_salesperson_and_all_salespersons(): void
    {
        $today = Carbon::now()->toDateString();

        // Invoice 1: Ramesh Sharma
        $inv1 = Invoice::create([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-REP-001',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson1->id,
            'invoice_date' => $today,
            'due_date' => $today,
            'due_date_basis' => 'custom',
            'status' => 'issued',
            'subtotal' => 10000,
            'tax_amount' => 1800,
            'grand_total' => 11800,
            'paid_amount' => 0,
        ]);

        // Invoice 2: Pooja Verma
        $inv2 = Invoice::create([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-REP-002',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson2->id,
            'invoice_date' => $today,
            'due_date' => $today,
            'due_date_basis' => 'custom',
            'status' => 'paid',
            'subtotal' => 20000,
            'tax_amount' => 3600,
            'grand_total' => 23600,
            'paid_amount' => 23600,
        ]);

        // Invoice 3: Unassigned (no salesperson)
        $inv3 = Invoice::create([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-REP-003',
            'customer_id' => $this->customer->id,
            'salesperson_id' => null,
            'invoice_date' => $today,
            'due_date' => $today,
            'due_date_basis' => 'custom',
            'status' => 'issued',
            'subtotal' => 5000,
            'tax_amount' => 900,
            'grand_total' => 5900,
            'paid_amount' => 0,
        ]);

        // Filter by Salesperson 1 (Ramesh Sharma)
        $res1 = $this->actingAs($this->adminUser)->get(route('reports.sales', [
            'date_from' => $today,
            'date_to' => $today,
            'salesperson_id' => $this->salesperson1->id,
        ]));
        $res1->assertOk();
        $res1->assertSee('INV-REP-001');
        $res1->assertDontSee('INV-REP-002');
        $res1->assertDontSee('INV-REP-003');

        // Filter by Salesperson 2 (Pooja Verma)
        $res2 = $this->actingAs($this->adminUser)->get(route('reports.sales', [
            'date_from' => $today,
            'date_to' => $today,
            'salesperson_id' => $this->salesperson2->id,
        ]));
        $res2->assertOk();
        $res2->assertDontSee('INV-REP-001');
        $res2->assertSee('INV-REP-002');
        $res2->assertDontSee('INV-REP-003');

        // Filter by Unassigned
        $resUnassigned = $this->actingAs($this->adminUser)->get(route('reports.sales', [
            'date_from' => $today,
            'date_to' => $today,
            'salesperson_id' => 'unassigned',
        ]));
        $resUnassigned->assertOk();
        $resUnassigned->assertDontSee('INV-REP-001');
        $resUnassigned->assertDontSee('INV-REP-002');
        $resUnassigned->assertSee('INV-REP-003');

        // All Salespersons (empty salesperson_id) restores all results
        $resAll = $this->actingAs($this->adminUser)->get(route('reports.sales', [
            'date_from' => $today,
            'date_to' => $today,
            'salesperson_id' => '',
        ]));
        $resAll->assertOk();
        $resAll->assertSee('INV-REP-001');
        $resAll->assertSee('INV-REP-002');
        $resAll->assertSee('INV-REP-003');

        // CSV export includes Salesperson header and values
        $csvResponse = $this->actingAs($this->adminUser)->get('/reports/sales?export=csv&date_from='.$today.'&date_to='.$today.'&salesperson_id='.$this->salesperson1->id);
        $csvResponse->assertOk();
        $content = $csvResponse->streamedContent();
        $this->assertStringContainsString('Salesperson', $content);
        $this->assertStringContainsString('Ramesh Sharma', $content);
        $this->assertStringNotContainsString('Pooja Verma', $content);
    }

    public function test_sales_order_conversion_or_selection_populates_salesperson(): void
    {
        $today = Carbon::now()->toDateString();
        $order = Order::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->salesperson2->id,
            'order_no' => 'SO-2026-SP1',
            'order_date' => $today,
            'status' => 'approved',
            'subtotal' => 15000,
            'tax_amount' => 2700,
            'grand_total' => 17700,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 1,
            'unit_price' => 15000,
            'line_total' => 15000,
        ]);

        // API endpoint salesOrderData returns salesperson_id
        $apiResponse = $this->actingAs($this->adminUser)->getJson(route('invoices.sales-order-data', $order));
        $apiResponse->assertOk();
        $apiResponse->assertJsonPath('order.salesperson_id', $this->salesperson2->id);

        // Direct store with order_id and no salesperson_id falls back to order's salesperson
        $payload = [
            'customer_id' => $this->customer->id,
            'order_id' => $order->id,
            'invoice_date' => $today,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 15000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('invoices.store'), $payload);
        $invoice = Invoice::where('order_id', $order->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($this->salesperson2->id, $invoice->salesperson_id);
    }
}
