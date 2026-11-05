<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Order\Services\OrderConversionService;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxInvoicePricingAndGstTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Customer $intraCustomer;
    protected Customer $interCustomer;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $product18;
    protected Product $product12;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
            'purchasing.invoices.view', 'purchasing.invoices.create', 'purchasing.invoices.edit',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->company = Company::create([
            'name' => 'Avit Tech Solutions',
            'code' => 'AVIT-TECH',
            'state' => 'Maharashtra',
            'gstin' => '27ABCDE1234F1Z5',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Accounts Manager',
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($role);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => Carbon::now()->subMonths(2)->startOfMonth()->toDateString(),
            'ends_on' => Carbon::now()->addMonths(10)->endOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        $this->intraCustomer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Mumbai Local Retailer',
            'code' => 'CUST-MUM',
            'state' => 'Maharashtra',
            'shipping_state' => 'Maharashtra',
            'gstin' => '27BBBBB1234F1Z1',
            'credit_days' => 15,
            'is_active' => true,
        ]);

        $this->interCustomer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Gujarat Tech Hub',
            'code' => 'CUST-GUJ',
            'state' => 'Gujarat',
            'shipping_state' => 'Gujarat',
            'gstin' => '24CCCCC1234F1Z2',
            'credit_days' => 30,
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name' => 'Mumbai Head Office',
            'code' => 'HO-01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MUM',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->product18 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Gigabit Router 18%',
            'sku' => 'ROUTER-18',
            'serial_no' => 'PRD-ROUTER-18',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 1000.00,
            'tax_rate' => 18.00,
            'hsn_code' => '851762',
            'tracking_type' => 'none',
            'is_active' => true,
        ]);

        $this->product12 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Patch Cable 12%',
            'sku' => 'CABLE-12',
            'serial_no' => 'PRD-CABLE-12',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 200.00,
            'tax_rate' => 12.00,
            'hsn_code' => '854442',
            'tracking_type' => 'none',
            'is_active' => true,
        ]);
    }

    public function test_tax_exclusive_intra_state_calculates_cgst_and_sgst(): void
    {
        $payload = [
            'customer_id' => $this->intraCustomer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'items' => [
                [
                    'product_id' => $this->product18->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 1000.00, // 2 * 1000 = 2000 taxable
                    'discount_amount' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('exclusive', $invoice->price_type);
        $this->assertEquals('intra', $invoice->tax_type);
        $this->assertEquals('Maharashtra', $invoice->delivery_state);

        // Subtotal = 2000, CGST 9% = 180, SGST 9% = 180, IGST = 0, Total = 2360
        $this->assertEquals(2000.00, (float) $invoice->subtotal);
        $this->assertEquals(2000.00, (float) $invoice->taxable_amount);
        $this->assertEquals(180.00, (float) $invoice->cgst_amount);
        $this->assertEquals(180.00, (float) $invoice->sgst_amount);
        $this->assertEquals(0.00, (float) $invoice->igst_amount);
        $this->assertEquals(360.00, (float) $invoice->tax_amount);
        $this->assertEquals(2360.00, (float) $invoice->grand_total);

        // Verify line item
        $item = $invoice->items->first();
        $this->assertEquals('exclusive', $item->price_type);
        $this->assertEquals(1000.00, (float) $item->entered_unit_price);
        $this->assertEquals(2000.00, (float) $item->taxable_amount);
        $this->assertEquals(9.00, (float) $item->cgst_rate);
        $this->assertEquals(9.00, (float) $item->sgst_rate);
        $this->assertEquals(0.00, (float) $item->igst_rate);
        $this->assertEquals(180.00, (float) $item->cgst_amount);
        $this->assertEquals(180.00, (float) $item->sgst_amount);
        $this->assertEquals(0.00, (float) $item->igst_amount);
        $this->assertEquals(2360.00, (float) $item->line_total);
    }

    public function test_tax_exclusive_inter_state_calculates_igst(): void
    {
        $payload = [
            'customer_id' => $this->interCustomer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'exclusive',
            'delivery_state' => 'Gujarat',
            'items' => [
                [
                    'product_id' => $this->product18->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 1000.00,
                    'discount_amount' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('exclusive', $invoice->price_type);
        $this->assertEquals('inter', $invoice->tax_type);
        $this->assertEquals('Gujarat', $invoice->delivery_state);

        // Subtotal = 1000, CGST = 0, SGST = 0, IGST 18% = 180, Total = 1180
        $this->assertEquals(1000.00, (float) $invoice->subtotal);
        $this->assertEquals(1000.00, (float) $invoice->taxable_amount);
        $this->assertEquals(0.00, (float) $invoice->cgst_amount);
        $this->assertEquals(0.00, (float) $invoice->sgst_amount);
        $this->assertEquals(180.00, (float) $invoice->igst_amount);
        $this->assertEquals(180.00, (float) $invoice->tax_amount);
        $this->assertEquals(1180.00, (float) $invoice->grand_total);

        // Verify line item
        $item = $invoice->items->first();
        $this->assertEquals('exclusive', $item->price_type);
        $this->assertEquals(18.00, (float) $item->igst_rate);
        $this->assertEquals(180.00, (float) $item->igst_amount);
        $this->assertEquals(0.00, (float) $item->cgst_amount);
        $this->assertEquals(0.00, (float) $item->sgst_amount);
    }

    public function test_tax_inclusive_intra_state_extracts_taxes_cleanly(): void
    {
        // 1 item with price 1180 tax inclusive at 18% -> base is 1000, tax is 180 (CGST 90 + SGST 90)
        $payload = [
            'customer_id' => $this->intraCustomer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'inclusive',
            'delivery_state' => 'Maharashtra',
            'items' => [
                [
                    'product_id' => $this->product18->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 1180.00, // Inclusive price
                    'discount_amount' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('inclusive', $invoice->price_type);
        $this->assertEquals('intra', $invoice->tax_type);

        $this->assertEquals(1000.00, (float) $invoice->taxable_amount);
        $this->assertEquals(90.00, (float) $invoice->cgst_amount);
        $this->assertEquals(90.00, (float) $invoice->sgst_amount);
        $this->assertEquals(0.00, (float) $invoice->igst_amount);
        $this->assertEquals(180.00, (float) $invoice->tax_amount);
        // Customer pays exactly 1180.00 without double counting tax!
        $this->assertEquals(1180.00, (float) $invoice->grand_total);

        $item = $invoice->items->first();
        $this->assertEquals('inclusive', $item->price_type);
        $this->assertEquals(1180.00, (float) $item->entered_unit_price);
        $this->assertEquals(1000.00, (float) $item->taxable_amount);
        $this->assertEquals(90.00, (float) $item->cgst_amount);
        $this->assertEquals(90.00, (float) $item->sgst_amount);
        $this->assertEquals(1180.00, (float) $item->line_total);
    }

    public function test_tax_inclusive_inter_state_extracts_igst(): void
    {
        $payload = [
            'customer_id' => $this->interCustomer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'inclusive',
            'delivery_state' => 'Gujarat',
            'items' => [
                [
                    'product_id' => $this->product18->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 1180.00,
                    'discount_amount' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('inclusive', $invoice->price_type);
        $this->assertEquals('inter', $invoice->tax_type);

        $this->assertEquals(1000.00, (float) $invoice->taxable_amount);
        $this->assertEquals(0.00, (float) $invoice->cgst_amount);
        $this->assertEquals(0.00, (float) $invoice->sgst_amount);
        $this->assertEquals(180.00, (float) $invoice->igst_amount);
        $this->assertEquals(1180.00, (float) $invoice->grand_total);
    }

    public function test_round_off_applied_manually_and_automatically(): void
    {
        // 1 item with unit_price 100.55 at 18% tax
        // Exclusive: Base = 100.55, Tax 18% = 18.10, Total before round = 118.65
        // Automatic round off = round(118.65) - 118.65 = 119 - 118.65 = +0.35
        // Grand total = 119.00
        $payload = [
            'customer_id' => $this->intraCustomer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'exclusive',
            'delivery_state' => 'Maharashtra',
            'items' => [
                [
                    'product_id' => $this->product18->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 100.55,
                    'discount_amount' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();
        $this->assertEquals(0.35, (float) $invoice->round_off);
        $this->assertEquals(119.00, (float) $invoice->grand_total);

        // Now test manual round off override
        $manualPayload = [
            'customer_id' => $this->intraCustomer->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'exclusive',
            'round_off' => -0.65, // User manually overrides to -0.65 -> 118.00
            'delivery_state' => 'Maharashtra',
            'items' => [
                [
                    'product_id' => $this->product18->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 100.55,
                    'discount_amount' => 0,
                ],
            ],
        ];

        $res2 = $this->actingAs($this->adminUser)->post(route('invoices.store'), $manualPayload);
        $res2->assertSessionHasNoErrors();

        $manualInvoice = Invoice::latest('id')->first();
        $this->assertEquals(-0.65, (float) $manualInvoice->round_off);
        $this->assertEquals(118.00, (float) $manualInvoice->grand_total);
    }

    public function test_order_conversion_preserves_intra_vs_inter_and_round_off(): void
    {
        $order = Order::create([
            'order_no' => 'SO-TEST-ROUND-001',
            'customer_id' => $this->interCustomer->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'approved',
            'subtotal' => 1000.00,
            'tax_amount' => 180.00,
            'round_off' => 0.00,
            'grand_total' => 1180.00,
            'order_date' => Carbon::now()->toDateString(),
            'price_type' => 'exclusive',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product18->id,
            'uom_id' => $this->uom->id,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'tax_percent' => 18.00,
            'line_total' => 1000.00,
        ]);

        $conversionService = app(OrderConversionService::class);
        $invoice = $conversionService->convertToInvoice($order);

        $this->assertNotNull($invoice);
        $this->assertEquals('inter', $invoice->tax_type);
        $this->assertEquals(180.00, (float) $invoice->igst_amount);
        $this->assertEquals(0.00, (float) $invoice->cgst_amount);
        $this->assertEquals(0.00, (float) $invoice->sgst_amount);
        $this->assertEquals(1180.00, (float) $invoice->grand_total);
    }

    public function test_invoice_preview_displays_gst_breakdown_and_round_off(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'invoice_no' => 'INV-PREV-001',
            'customer_id' => $this->intraCustomer->id,
            'invoice_date' => Carbon::now(),
            'price_type' => 'exclusive',
            'tax_type' => 'intra',
            'delivery_state' => 'Maharashtra',
            'subtotal' => 2000.00,
            'taxable_amount' => 2000.00,
            'cgst_amount' => 180.00,
            'sgst_amount' => 180.00,
            'igst_amount' => 0.00,
            'tax_amount' => 360.00,
            'round_off' => -0.50,
            'grand_total' => 2359.50,
            'status' => 'issued',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->product18->id,
            'uom_id' => $this->uom->id,
            'item_name' => 'Gigabit Router 18%',
            'quantity' => 2,
            'unit_price' => 1000.00,
            'entered_unit_price' => 1000.00,
            'taxable_amount' => 2000.00,
            'tax_rate' => 18.00,
            'cgst_rate' => 9.00,
            'sgst_rate' => 9.00,
            'cgst_amount' => 180.00,
            'sgst_amount' => 180.00,
            'tax_amount' => 360.00,
            'line_total' => 2360.00,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('invoices.preview', $invoice));
        $response->assertStatus(200);
        $response->assertSee('Central Tax (CGST)');
        $response->assertSee('State Tax (SGST)');
        $response->assertSee('Round Off');
        $response->assertSee('2,359.50');
    }

    public function test_purchase_invoice_service_supports_inclusive_and_exclusive_pricing(): void
    {
        $supplier = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'National Telecom Supplies',
            'code' => 'SUPP-001',
            'is_active' => true,
        ]);

        $service = app(PurchaseOrderService::class);

        // Exclusive purchase invoice
        $invExclusive = $service->createInvoice([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'exclusive',
            'round_off' => 0.40,
            'items' => [
                [
                    'product_id' => $this->product18->id,
                    'quantity' => 1,
                    'unit_cost' => 1000.00,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
            ],
        ], $this->adminUser);

        $this->assertEquals('exclusive', $invExclusive->price_type);
        $this->assertEquals(1000.00, (float) $invExclusive->taxable_amount);
        $this->assertEquals(90.00, (float) $invExclusive->cgst_amount);
        $this->assertEquals(90.00, (float) $invExclusive->sgst_amount);
        $this->assertEquals(0.40, (float) $invExclusive->round_off);
        $this->assertEquals(1180.40, (float) $invExclusive->grand_total);

        // Inclusive purchase invoice
        $invInclusive = $service->createInvoice([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => Carbon::now()->toDateString(),
            'price_type' => 'inclusive',
            'items' => [
                [
                    'product_id' => $this->product18->id,
                    'quantity' => 1,
                    'unit_cost' => 1180.00, // 1180 inclusive of 18% -> 1000 base + 180 tax
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                ],
            ],
        ], $this->adminUser);

        $this->assertEquals('inclusive', $invInclusive->price_type);
        $this->assertEquals(1000.00, (float) $invInclusive->taxable_amount);
        $this->assertEquals(90.00, (float) $invInclusive->cgst_amount);
        $this->assertEquals(90.00, (float) $invInclusive->sgst_amount);
        $this->assertEquals(1180.00, (float) $invInclusive->grand_total);
    }
}

