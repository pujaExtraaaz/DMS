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
use App\Domains\Purchasing\Models\SupplierPayable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseInvoiceFreightAndTotalTest extends TestCase
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
            'name' => 'Apex Logistics & Supplies',
            'code' => 'SUP-002',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $customerType->id,
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
            'name' => 'Industrial Component X',
            'sku' => 'CMP-001',
            'serial_no' => 'SN-CMP-001',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 200.00,
            'selling_price' => 300.00,
            'is_active' => true,
        ]);
    }

    public function test_purchase_invoice_persists_freight_and_calculates_backend_grand_total_accurately(): void
    {
        // 5 units * 200 = 1000 subtotal. 9% CGST (90) + 9% SGST (90) = 180 tax.
        // Freight charge = 250.00, Other charges = 75.00.
        // Expected Grand Total = 1000 + 180 + 250 + 75 = 1505.00.
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'INV-FRT-001',
            'freight_charge' => 250.00,
            'other_charges' => 75.00,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_cost' => 200.00,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                    'batch_number' => 'BATCH-FRT-01',
                    'batch_mrp' => 350.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-FRT-001')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(1000.00, (float) $invoice->subtotal);
        $this->assertEquals(180.00, (float) $invoice->tax_amount);
        $this->assertEquals(250.00, (float) $invoice->freight_charge);
        $this->assertEquals(75.00, (float) $invoice->other_charges);
        $this->assertEquals(1505.00, (float) $invoice->grand_total);
        $this->assertEquals(1505.00, (float) $invoice->total_amount);

        // Supplier payable debit must equal grand total (including freight)
        $payable = SupplierPayable::where('reference_id', $invoice->id)->first();
        $this->assertNotNull($payable);
        $this->assertEquals(1505.00, (float) $payable->debit);
    }

    public function test_purchase_invoice_show_page_displays_saved_freight_and_charges(): void
    {
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-FRT-001',
            'supplier_invoice_no' => 'SUP-INV-FRT-001',
            'invoice_date' => now()->toDateString(),
            'status' => 'posted',
            'subtotal' => 1000.00,
            'tax_amount' => 180.00,
            'freight_charge' => 250.00,
            'other_charges' => 75.00,
            'grand_total' => 1505.00,
            'created_by' => $this->user->id,
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 5,
            'unit_cost' => 200,
            'tax_percent' => 18,
            'cgst_percent' => 9,
            'sgst_percent' => 9,
            'cgst_amount' => 90,
            'sgst_amount' => 90,
            'line_total' => 1180,
            'batch_no' => 'BATCH-FRT-01',
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertSee('Freight / Landed Charge:');
        $response->assertSee('250.00');
        $response->assertSee('Other Charges:');
        $response->assertSee('75.00');
        $response->assertSee('1,505.00');
    }

    public function test_purchase_invoice_preview_displays_saved_freight_and_grand_total(): void
    {
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-FRT-002',
            'supplier_invoice_no' => 'SUP-INV-FRT-002',
            'invoice_date' => now()->toDateString(),
            'status' => 'posted',
            'subtotal' => 1000.00,
            'tax_amount' => 180.00,
            'freight_charge' => 250.00,
            'other_charges' => 75.00,
            'grand_total' => 1505.00,
            'qr_token' => \Illuminate\Support\Str::random(32),
            'created_by' => $this->user->id,
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 5,
            'unit_cost' => 200,
            'tax_percent' => 18,
            'cgst_percent' => 9,
            'sgst_percent' => 9,
            'cgst_amount' => 90,
            'sgst_amount' => 90,
            'line_total' => 1180,
            'batch_no' => 'BATCH-FRT-02',
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.preview', $invoice));

        $response->assertStatus(200);
        $response->assertSee('Freight / Landed Charge:');
        $response->assertSee('250.00');
        $response->assertSee('1,505.00');
    }

    public function test_zero_or_null_freight_defaults_safely(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'INV-NO-FRT',
            'freight_charge' => null,
            'other_charges' => null,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_cost' => 100.00,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $invoice = PurchaseInvoice::where('supplier_invoice_no', 'INV-NO-FRT')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(0.00, (float) $invoice->freight_charge);
        $this->assertEquals(0.00, (float) $invoice->other_charges);
        $this->assertEquals(200.00, (float) $invoice->grand_total);
    }
}
