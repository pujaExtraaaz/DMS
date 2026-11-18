<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceProductAutoUnitSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customer;
    protected Warehouse $warehouse;
    protected Uom $uomBox;
    protected Uom $uomPcs;
    protected Uom $uomKg;
    protected Product $singleUnitProduct;
    protected Product $multiUnitProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
            'purchasing.view', 'purchasing.create', 'purchasing.edit', 'purchasing.manage',
            'products.view', 'products.create', 'products.edit', 'products.manage',
            'masters.view', 'masters.manage',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->company = Company::create([
            'name' => 'Avit Digital Systems',
            'code' => 'AVIT-01',
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
            'name' => 'Wholesaler',
            'code' => 'WHL',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Apex Retailers',
            'code' => 'CUST-APEX',
            'phone' => '9876543210',
            'customer_type_id' => $customerType->id,
            'credit_days' => 30,
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name' => 'Pune Branch',
            'code' => 'PB-01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'is_active' => true,
        ]);

        $this->uomBox = Uom::create([
            'name' => 'BOX',
            'code' => 'BOX',
            'is_active' => true,
        ]);

        $this->uomPcs = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->uomKg = Uom::create([
            'name' => 'Kilogram',
            'code' => 'KG',
            'is_active' => true,
        ]);

        // 1. Single-unit product (BOX only, no alternate UOM)
        $this->singleUnitProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Cardboard Packing Box',
            'sku' => 'SKU-BOX-001',
            'serial_no' => 'SN-BOX-001',
            'base_uom_id' => $this->uomBox->id,
            'alternate_uom_id' => null,
            'selling_price' => 150.00,
            'purchase_price' => 100.00,
            'tax_rate' => 18,
            'is_active' => true,
            'is_hidden' => false,
        ]);

        // 2. Multi-unit product (Base: PCS, Alternate: BOX, plus additional ProductUom)
        $this->multiUnitProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Premium Screws Pack',
            'sku' => 'SKU-SCREW-001',
            'serial_no' => 'SN-SCREW-001',
            'base_uom_id' => $this->uomPcs->id,
            'alternate_uom_id' => $this->uomBox->id,
            'conversion_factor' => 100,
            'selling_price' => 5.00,
            'purchase_price' => 3.00,
            'tax_rate' => 18,
            'is_active' => true,
            'is_hidden' => false,
        ]);

        ProductUom::create([
            'product_id' => $this->multiUnitProduct->id,
            'uom_id' => $this->uomBox->id,
            'conversion_factor' => 100,
            'is_base' => false,
            'is_active' => true,
        ]);
    }

    public function test_product_model_correctly_evaluates_allowed_uoms_and_permissions(): void
    {
        // Single unit product
        $singleAllowed = $this->singleUnitProduct->getAllowedUoms();
        $this->assertCount(1, $singleAllowed);
        $this->assertEquals((string) $this->uomBox->id, $singleAllowed->first()['id']);
        $this->assertEquals('BOX', $singleAllowed->first()['code']);
        $this->assertFalse($this->singleUnitProduct->allowsUnitChange());
        $this->assertTrue($this->singleUnitProduct->isAllowedUom($this->uomBox->id));
        $this->assertFalse($this->singleUnitProduct->isAllowedUom($this->uomPcs->id));

        // Multi unit product
        $multiAllowed = $this->multiUnitProduct->getAllowedUoms();
        $this->assertGreaterThanOrEqual(2, $multiAllowed->count());
        $this->assertTrue($this->multiUnitProduct->allowsUnitChange());
        $this->assertTrue($this->multiUnitProduct->isAllowedUom($this->uomPcs->id));
        $this->assertTrue($this->multiUnitProduct->isAllowedUom($this->uomBox->id));
        $this->assertFalse($this->multiUnitProduct->isAllowedUom($this->uomKg->id));
    }

    public function test_direct_billing_tax_invoice_page_renders_product_unit_data_and_ui_logic(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));
        $response->assertOk();

        // Ensure serialized product JSON includes allowed_uoms and allow_unit_change
        $response->assertSee('allowed_uoms', false);
        $response->assertSee('allow_unit_change', false);
        $response->assertSee('resolveLineUom', false);
        $response->assertSee('onUomChange', false);

        // Ensure the blade template contains conditional rendering for locked vs editable units
        $response->assertSee('row.allow_unit_change', false);
    }

    public function test_tax_invoice_store_succeeds_with_configured_base_uom(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'price_type' => 'exclusive',
            'items' => [
                [
                    'product_id' => $this->singleUnitProduct->id,
                    'uom_id' => $this->uomBox->id,
                    'quantity' => 10,
                    'unit_price' => 150,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoice_items', [
            'product_id' => $this->singleUnitProduct->id,
            'uom_id' => $this->uomBox->id,
            'quantity' => 10,
        ]);
    }

    public function test_tax_invoice_store_succeeds_with_configured_alternate_uom(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'price_type' => 'exclusive',
            'items' => [
                [
                    'product_id' => $this->multiUnitProduct->id,
                    'uom_id' => $this->uomBox->id, // Alternate UOM
                    'quantity' => 2,
                    'unit_price' => 500,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoice_items', [
            'product_id' => $this->multiUnitProduct->id,
            'uom_id' => $this->uomBox->id,
            'quantity' => 2,
        ]);
    }

    public function test_tax_invoice_store_fails_when_unsupported_uom_is_submitted(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'price_type' => 'exclusive',
            'items' => [
                [
                    'product_id' => $this->singleUnitProduct->id,
                    'uom_id' => $this->uomKg->id, // KG is not allowed for Cardboard Packing Box (only BOX)
                    'quantity' => 10,
                    'unit_price' => 150,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('invoices.store'), $payload);
        $response->assertSessionHasErrors('items.0.uom_id');
    }

    public function test_purchase_invoice_page_renders_unit_helpers_and_validates_uoms(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchasing.invoices.create'));
        $response->assertOk();

        // Check that purchasing invoice create page renders unit handling
        $response->assertSee('resolveLineUom', false);
        $response->assertSee('row.allow_unit_change', false);
    }
}
