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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseInvoiceWithoutBatchSellingTest extends TestCase
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
            'name' => 'Apex Suppliers Ltd',
            'code' => 'SUP-001',
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
            'name' => 'Industrial Widget A',
            'sku' => 'WID-001',
            'serial_no' => 'SN-WID-001',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 150.00,
            'selling_price' => 250.00,
            'is_active' => true,
        ]);
    }

    public function test_purchase_invoice_create_page_does_not_contain_batch_selling_price_column_or_input(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create'));

        $response->assertStatus(200);
        $response->assertDontSee('Batch Selling Price');
        $response->assertDontSee('placeholder="Selling Price"', false);
        $response->assertDontSee('items[${i}][selling_price]', false);
        $response->assertDontSee('row.selling_price');
    }

    public function test_purchase_invoice_can_be_created_without_selling_price(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => now()->toDateString(),
            'supplier_invoice_number' => 'INV-SUP-123',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_cost' => 150.00,
                    'cgst_percent' => 9,
                    'sgst_percent' => 9,
                    'batch_number' => 'BATCH-2026-01',
                    'batch_mrp' => 300.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchasing.invoices.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_invoices', [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_no' => 'INV-SUP-123',
        ]);

        $this->assertDatabaseHas('purchase_invoice_items', [
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_cost' => 150.00,
            'batch_no' => 'BATCH-2026-01',
            'batch_mrp' => 300.00,
            'batch_selling_price' => null,
        ]);
    }

    public function test_purchase_invoice_show_page_renders_cleanly_without_batch_selling(): void
    {
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PI-2026-0001',
            'invoice_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 1500,
            'tax_amount' => 270,
            'grand_total' => 1770,
            'created_by' => $this->user->id,
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 10,
            'unit_cost' => 150,
            'tax_percent' => 18,
            'cgst_percent' => 9,
            'sgst_percent' => 9,
            'cgst_amount' => 135,
            'sgst_amount' => 135,
            'line_total' => 1770,
            'other_vendor_rate' => null,
            'batch_no' => 'BATCH-2026-01',
            'batch_mrp' => 300,
            'batch_selling_price' => 250, // Legacy row with selling price
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertDontSee('Batch Selling Price');
        $response->assertSee('PI-2026-0001');
        $response->assertSee('BATCH-2026-01');
    }
}
