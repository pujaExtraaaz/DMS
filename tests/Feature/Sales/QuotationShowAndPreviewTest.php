<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Sales\Models\QuotationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationShowAndPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customer;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Quotation Test Co',
            'code' => 'QTC',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole('super-admin');

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Acme Corp',
            'code' => 'ACM001',
            'is_active' => true,
        ]);

        $this->uom = Uom::firstOrCreate(
            ['name' => 'PCS'],
            ['code' => 'PCS', 'is_active' => true]
        );

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Display Monitor 24"',
            'sku' => 'MON-24',
            'serial_no' => 'SN-MON-24',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);
    }

    public function test_quotation_show_and_items_load_successfully(): void
    {
        $quotation = Quotation::create([
            'company_id' => $this->company->id,
            'quotation_no' => 'QTN-2026-0001',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->user->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 1000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 180.00,
            'grand_total' => 1180.00,
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_price' => 500.00,
            'discount_amount' => 0.00,
            'line_total' => 1000.00,
        ]);

        // Access GET /quotations/{id}
        $response = $this->actingAs($this->user)->get(route('quotations.show', $quotation));

        $response->assertStatus(200);
        $response->assertSee('QTN-2026-0001');
        $response->assertSee('Display Monitor 24"');
    }

    public function test_quotation_preview_loads_successfully(): void
    {
        $quotation = Quotation::create([
            'company_id' => $this->company->id,
            'quotation_no' => 'QTN-2026-0002',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->user->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 500.00,
            'discount_amount' => 0.00,
            'tax_amount' => 90.00,
            'grand_total' => 590.00,
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 1,
            'unit_price' => 500.00,
            'discount_amount' => 0.00,
            'line_total' => 500.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('quotations.preview', $quotation));

        $response->assertStatus(200);
        $response->assertSee('QTN-2026-0002');
    }
}
