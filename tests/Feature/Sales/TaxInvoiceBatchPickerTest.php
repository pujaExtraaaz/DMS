<?php

namespace Tests\Feature\Sales;

use App\Domains\Inventory\Models\ProductBatch;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxInvoiceBatchPickerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customer;
    protected Warehouse $warehouse;
    protected Uom $uom;
    protected Product $productWithBatch;
    protected Product $productWithoutBatch;
    protected ProductBatch $batch1;
    protected ProductBatch $batch2;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
            'products.view', 'products.create',
            'inventory.view', 'inventory.batches',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $role->givePermissionTo(Permission::all());

        $this->company = Company::create([
            'name' => 'Nexus Retail Ltd',
            'code' => 'NEX-01',
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
            'name' => 'Distributor',
            'code' => 'DIST',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Horizon Traders',
            'code' => 'CUST-HORIZON',
            'phone' => '9822012345',
            'customer_type_id' => $customerType->id,
            'credit_days' => 30,
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name' => 'Mumbai Branch',
            'code' => 'MB-01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Central Depot',
            'code' => 'CD-01',
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Numbers',
            'code' => 'NOS',
            'is_active' => true,
        ]);

        $this->productWithBatch = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Sensor X-200',
            'sku' => 'IS-X200',
            'serial_no' => 'PRD-IS-X200',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 1500.00,
            'tax_rate' => 18.0,
            'is_active' => true,
        ]);

        $this->productWithoutBatch = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Connecting Cable 2M',
            'sku' => 'CC-2M',
            'serial_no' => 'PRD-CC-2M',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 250.00,
            'tax_rate' => 18.0,
            'is_active' => true,
        ]);

        $this->batch1 = ProductBatch::create([
            'product_id' => $this->productWithBatch->id,
            'warehouse_id' => $this->warehouse->id,
            'uom_id' => $this->uom->id,
            'batch_no' => 'BATCH-2026-A',
            'mfg_date' => now()->subMonths(2)->toDateString(),
            'expiry_date' => now()->addMonths(10)->toDateString(),
            'quantity' => 50,
            'unit_cost' => 1000.00,
            'selling_price' => 1500.00,
        ]);

        $this->batch2 = ProductBatch::create([
            'product_id' => $this->productWithBatch->id,
            'warehouse_id' => $this->warehouse->id,
            'uom_id' => $this->uom->id,
            'batch_no' => 'BATCH-2026-B',
            'mfg_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addMonths(11)->toDateString(),
            'quantity' => 100,
            'unit_cost' => 1050.00,
            'selling_price' => 1550.00,
        ]);
    }

    public function test_batch_lookup_api_returns_batches_for_tracked_product(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('inventory.batches.lookup', $this->productWithBatch->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'product_id',
            'batches' => [
                '*' => ['id', 'batch_no', 'warehouse', 'available_qty', 'selling_price']
            ]
        ]);
        $response->assertJsonCount(2, 'batches');
        $response->assertJsonFragment(['batch_no' => 'BATCH-2026-A']);
        $response->assertJsonFragment(['batch_no' => 'BATCH-2026-B']);
    }

    public function test_batch_lookup_api_returns_empty_batches_when_product_has_no_batches(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('inventory.batches.lookup', $this->productWithoutBatch->id));

        $response->assertStatus(200);
        $response->assertJson([
            'product_id' => $this->productWithoutBatch->id,
            'batches' => [],
        ]);
    }

    public function test_tax_invoice_page_initializes_batch_picker_and_encloses_modal_inside_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));

        $response->assertStatus(200);

        // Global safe fallback is defined on window before salesInvoiceForm initializes
        $response->assertSee('window.batchPicker = window.batchPicker ||', false);
        $response->assertSee('Alpine.data(\'batchPicker\'', false);
        $response->assertSee('Alpine.data(\'salesInvoiceForm\'', false);

        // Required batchPicker properties exist in salesInvoiceForm
        $response->assertSee('batchPicker:', false);
        $response->assertSee('loading: false', false);
        $response->assertSee('targetIdx: null', false);
        $response->assertSee('batches: []', false);

        // Modal is inside form and uses safe closeBatchPicker
        $html = $response->getContent();
        $invoiceFormPos = strpos($html, 'action="' . route('invoices.store') . '"');
        $this->assertNotFalse($invoiceFormPos, 'Invoice form must exist in DOM');

        $invoiceFormEndPos = strpos($html, '</form>', $invoiceFormPos);
        $this->assertNotFalse($invoiceFormEndPos, 'Invoice form end tag must exist in DOM');

        $batchModalPos = strpos($html, 'x-show="batchPicker.open"');
        $this->assertNotFalse($batchModalPos, 'Batch picker modal must exist in DOM');

        $this->assertGreaterThan($invoiceFormPos, $batchModalPos, 'Batch picker modal must be after <form> opening tag');
        $this->assertLessThan($invoiceFormEndPos, $batchModalPos, 'Batch picker modal must be enclosed within <form> component scope');

        // Verify closeBatchPicker handler exists
        $response->assertSee('closeBatchPicker()', false);
        $response->assertSee('openBatchPicker(idx)', false);
        $response->assertSee('applyBatch(b)', false);
    }

    public function test_tax_invoice_can_be_stored_with_batch_and_without_batch_line_items(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'notes' => 'Test batch order invoice',
            'price_type' => 'exclusive',
            'round_off' => 0,
            'items' => [
                [
                    'product_id' => $this->productWithBatch->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 5,
                    'unit_price' => 1500.00,
                    'discount_type' => 'flat',
                    'discount_value' => 100.00,
                    'batch_no' => 'BATCH-2026-A',
                ],
                [
                    'product_id' => $this->productWithoutBatch->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 10,
                    'unit_price' => 250.00,
                    'discount_type' => 'percent',
                    'discount_value' => 5.0,
                    'batch_no' => '',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
        ]);

        $invoice = Invoice::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertCount(2, $invoice->items);

        $itemWithBatch = $invoice->items->firstWhere('product_id', $this->productWithBatch->id);
        $this->assertNotNull($itemWithBatch);
        $this->assertEquals('BATCH-2026-A', $itemWithBatch->batch_no);
        $this->assertEquals(5, (float)$itemWithBatch->quantity);

        $itemWithoutBatch = $invoice->items->firstWhere('product_id', $this->productWithoutBatch->id);
        $this->assertNotNull($itemWithoutBatch);
        $this->assertTrue(empty($itemWithoutBatch->batch_no));
        $this->assertEquals(10, (float)$itemWithoutBatch->quantity);
    }
}
