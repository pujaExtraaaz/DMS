<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Inventory\Models\ProductSerial;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseInvoiceFieldSizesAndSerialsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $supplier;
    protected Warehouse $warehouse;
    protected Product $serialTrackedProduct;
    protected Product $standardProduct;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'purchaser', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.manage', 'guard_name' => 'web']);
        $role->syncPermissions(['purchases.view', 'purchases.create', 'purchases.edit', 'purchases.manage']);

        $this->company = Company::create([
            'name' => 'Avit Digital Systems',
            'code' => 'ADS-01',
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
            'code' => 'MWH-01',
            'is_active' => true,
        ]);

        $cType = CustomerType::create([
            'name' => 'Vendor',
            'code' => 'VND',
            'is_active' => true,
        ]);

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'HighTech Components Pvt Ltd',
            'code' => 'HTC-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $cType->id,
            'credit_days' => 30,
            'phone' => '9876543210',
            'state' => 'Punjab',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->serialTrackedProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Smart Controller Board Pro',
            'sku' => 'SCB-PRO-01',
            'serial_no' => 'SN-SCB-PRO-01',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 12500.00,
            'tax_rate' => 18.00,
            'tracking_type' => 'serial',
            'is_active' => true,
        ]);

        $this->standardProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Connecting Cable 2M',
            'sku' => 'CC-2M',
            'serial_no' => 'SN-CC-2M',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 350.00,
            'tax_rate' => 18.00,
            'tracking_type' => 'none',
            'is_active' => true,
        ]);
    }

    public function test_create_invoice_page_renders_improved_field_sizes_and_serial_number_controls(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create'));

        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Table wrapper has horizontal scrolling on smaller screens
        $this->assertStringContainsString('overflow-x-auto', $content);
        $this->assertStringContainsString('min-w-[1360px]', $content);

        // 2. Table headers have generous minimum widths
        $this->assertStringContainsString('min-w-[340px]', $content); // Product column
        $this->assertStringContainsString('min-w-[110px]', $content); // Qty
        $this->assertStringContainsString('min-w-[130px]', $content); // Unit Cost
        $this->assertStringContainsString('min-w-[95px]', $content);  // Tax %
        $this->assertStringContainsString('min-w-[125px]', $content); // Batch MRP
        $this->assertStringContainsString('min-w-[140px]', $content); // Line Total

        // 3. Serial number controls and responsive flex layout
        $this->assertStringContainsString('Serial Numbers', $content);
        $this->assertStringContainsString('+ Add Serial', $content);
        $this->assertStringContainsString('addSerial(i)', $content);
        $this->assertStringContainsString('removeSerial(i, sIdx)', $content);
        $this->assertStringContainsString('isSerialTracked', $content);

        // 4. Serial number input sizing: minimum width 100-140px and non-overlapping remove button
        $this->assertStringContainsString('min-w-[130px]', $content);
        $this->assertStringContainsString('max-w-[220px]', $content);
        $this->assertStringContainsString('h-9', $content);
        $this->assertStringContainsString('font-mono', $content);

        // 5. Notes & Totals section alignment
        $this->assertStringContainsString('Notes / Remarks', $content);
        $this->assertStringContainsString('Taxable Subtotal:', $content);
        $this->assertStringContainsString('Grand Total:', $content);
    }

    public function test_can_create_purchase_invoice_with_multiple_and_long_serial_numbers(): void
    {
        $longSerial1 = 'SN-2026-SCB-PRO-98402914-US-ALPHA';
        $longSerial2 = 'SN-2026-SCB-PRO-98402915-US-BETA';

        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'SUP-INV-2026-001',
            'credit_days' => 30,
            'freight_charge' => 250.00,
            'other_charges' => 50.00,
            'notes' => 'Testing line items and long serial numbers layout.',
            'items' => [
                [
                    'product_id' => $this->serialTrackedProduct->id,
                    'quantity' => 2,
                    'unit_cost' => 12500.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                    'batch_number' => 'BATCH-SCB-2026',
                    'batch_mrp' => 16000.00,
                    'serial_numbers' => [
                        $longSerial1,
                        $longSerial2,
                    ],
                ],
                [
                    'product_id' => $this->standardProduct->id,
                    'quantity' => 5,
                    'unit_cost' => 350.00,
                    'tax_percent' => 18.00,
                    'cgst_percent' => 9.00,
                    'sgst_percent' => 9.00,
                    'batch_number' => '',
                    'batch_mrp' => 500.00,
                    'serial_numbers' => [],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertStatus(302);

        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'SUP-INV-2026-001')->first();
        $this->assertNotNull($invoice);

        // Subtotal = (2 * 12500) + (5 * 350) = 25000 + 1750 = 26750.00
        // Tax = 26750 * 0.18 = 4815.00
        // Freight = 250.00, Other = 50.00
        // Grand Total = 26750 + 4815 + 250 + 50 = 31865.00
        $this->assertEquals(26750.00, (float) $invoice->subtotal);
        $this->assertEquals(4815.00, (float) $invoice->tax_amount);
        $this->assertEquals(31865.00, (float) $invoice->grand_total);

        // Verify serial numbers were saved in ProductSerial
        $serials = ProductSerial::whereIn('serial_number', [$longSerial1, $longSerial2])->get();
        $this->assertCount(2, $serials);
        $this->assertTrue($serials->pluck('serial_number')->contains($longSerial1));
        $this->assertTrue($serials->pluck('serial_number')->contains($longSerial2));
    }

    public function test_edit_invoice_page_renders_improved_field_sizes_and_preloaded_serials(): void
    {
        $longSerial1 = 'SN-EDIT-TEST-001';
        $longSerial2 = 'SN-EDIT-TEST-002';

        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-EDIT-' . uniqid(),
            'supplier_invoice_no' => 'SUP-INV-EDIT-01',
            'invoice_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 25000.00,
            'tax_amount' => 4500.00,
            'grand_total' => 29500.00,
            'credit_days' => 30,
            'due_date' => now()->addDays(30)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $item = PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $this->serialTrackedProduct->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_cost' => 12500.00,
            'tax_percent' => 18.00,
            'cgst_percent' => 9.00,
            'sgst_percent' => 9.00,
            'line_total' => 29500.00,
        ]);

        ProductSerial::create([
            'product_id' => $this->serialTrackedProduct->id,
            'warehouse_id' => $this->warehouse->id,
            'serial_number' => $longSerial1,
            'status' => 'in_stock',
            'source_type' => $item->getMorphClass(),
            'source_id' => $item->id,
        ]);

        ProductSerial::create([
            'product_id' => $this->serialTrackedProduct->id,
            'warehouse_id' => $this->warehouse->id,
            'serial_number' => $longSerial2,
            'status' => 'in_stock',
            'source_type' => $item->getMorphClass(),
            'source_id' => $item->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.edit', $invoice));

        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Table wrapper and header sizing present on edit page
        $this->assertStringContainsString('overflow-x-auto', $content);
        $this->assertStringContainsString('min-w-[1360px]', $content);
        $this->assertStringContainsString('min-w-[340px]', $content);
        $this->assertStringContainsString('+ Add Serial', $content);

        // 2. Preloaded serial numbers in invoiceItems JSON
        $this->assertStringContainsString($longSerial1, $content);
        $this->assertStringContainsString($longSerial2, $content);
    }
}

