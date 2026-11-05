<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxInvoicePriceTypeToggleTest extends TestCase
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

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->company = Company::create([
            'name' => 'Demo Enterprise',
            'state' => 'Maharashtra',
            'gstin' => '27ABCDE1234F1Z5',
            'email' => 'demo@example.com',
            'phone' => '9876543210',
            'address' => '123 Business Park',
            'pincode' => '400001',
        ]);

        $this->user->update(['company_id' => $this->company->id]);

        $customerType = CustomerType::firstOrCreate(
            ['name' => 'Retail'],
            ['code' => 'RET', 'is_active' => true]
        );

        $this->customer = Customer::create([
            'code' => 'CUST001',
            'name' => 'Acme Corporation',
            'state' => 'Maharashtra',
            'shipping_state' => 'Maharashtra',
            'gstin' => '27GHIJK5678L1Z2',
            'email' => 'acme@example.com',
            'phone' => '9876543211',
            'billing_address' => '456 Customer Road',
            'shipping_address' => '456 Customer Road',
            'customer_type_id' => $customerType->id,
            'company_id' => $this->company->id,
        ]);

        $this->uom = Uom::create([
            'code' => 'NOS',
            'name' => 'Numbers',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Industrial Widget',
            'sku' => 'WIDGET-01',
            'serial_no' => 'PRD-WIDGET-01',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 100.00,
            'tax_rate' => 18.00,
            'tracking_type' => 'none',
        ]);

        FinancialYear::create([
            'name' => 'FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_create_page_renders_safe_button_based_tax_mode_toggle(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));

        $response->assertOk();

        // 1. Check toggle buttons use type="button" to prevent accidental form submission
        $response->assertSee('Tax Exclusive', false);
        $response->assertSee('Tax Inclusive', false);
        $response->assertSee('type="button"', false);
        $response->assertSee('setPriceType(\'exclusive\')', false);
        $response->assertSee('setPriceType(\'inclusive\')', false);

        // 2. Check hidden field for price_type exists to submit selected state
        $response->assertSee('<input type="hidden" name="price_type" :value="priceType">', false);

        // 3. Check dynamic price column headers for inclusive vs exclusive
        $response->assertSee('priceType === \'inclusive\' ? \'Unit Price (Inc GST) *\' : \'Unit Price (₹) *\'', false);

        // 4. Check that grandTotal is pure and does not mutate roundOff inside template
        $content = $response->getContent();
        $this->assertStringNotContainsString('if (!this.isManualRoundOff) { this.roundOff =', $content);
        $this->assertStringContainsString('setPriceType(type)', $content);
    }

    public function test_can_create_invoice_with_tax_exclusive_mode(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-07-15',
            'price_type' => 'exclusive',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 10,
                    'unit_price' => 100.00, // Base taxable price
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
            'price_type' => 'exclusive',
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 100.00,
            'taxable_amount' => 1000.00,
            'tax_amount' => 180.00, // 18% on 1000
            'line_total' => 1180.00,
        ]);
    }

    public function test_can_create_invoice_with_tax_inclusive_mode(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-07-15',
            'price_type' => 'inclusive',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 10,
                    'unit_price' => 118.00, // Tax inclusive entered price
                    'discount_type' => 'flat',
                    'discount_value' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
            'price_type' => 'inclusive',
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 100.00, // Stored base taxable unit price after extraction (1000 / 10 = 100)
            'taxable_amount' => 1000.00, // Extracted: 1180 * 100 / 118 = 1000
            'tax_amount' => 180.00,
            'line_total' => 1180.00,
        ]);
    }
}
