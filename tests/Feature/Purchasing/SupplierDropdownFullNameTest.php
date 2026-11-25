<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierDropdownFullNameTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Customer $shortSupplier;
    protected Customer $mediumSupplier;
    protected Customer $longSupplier;
    protected CustomerType $vendorType;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'purchases.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchases.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'purchase-orders.edit', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Acme Trading Co',
            'code' => 'ATC-01',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($superAdminRole);

        $this->vendorType = CustomerType::create([
            'name' => 'Vendor',
            'code' => 'VEND',
        ]);

        // Short name
        $this->shortSupplier = Customer::create([
            'name' => 'Apex Corp',
            'code' => 'SUP-001',
            'company_id' => $this->company->id,
            'customer_type_id' => $this->vendorType->id,
            'party_type' => 'supplier',
            'is_active' => true,
            'credit_days' => 15,
        ]);

        // Medium name
        $this->mediumSupplier = Customer::create([
            'name' => 'National Precision Instruments & Hardware Distribution Ltd',
            'code' => 'SUP-002',
            'company_id' => $this->company->id,
            'customer_type_id' => $this->vendorType->id,
            'party_type' => 'supplier',
            'is_active' => true,
            'credit_days' => 30,
        ]);

        // Very long name (80+ characters)
        $this->longSupplier = Customer::create([
            'name' => 'Hindustan Heavy Infrastructure, Electrical Engineering & Industrial Solutions Private Limited',
            'code' => 'SUP-003',
            'company_id' => $this->company->id,
            'customer_type_id' => $this->vendorType->id,
            'party_type' => 'supplier',
            'is_active' => true,
            'credit_days' => 45,
        ]);
    }

    /**
     * Test that Purchase Order Create page renders full supplier names in dropdown with title attribute.
     */
    public function test_purchase_order_create_dropdown_displays_full_supplier_names(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('purchasing.orders.create'));

        $response->assertStatus(200);

        // Verify short supplier name is fully present
        $response->assertSee(e($this->shortSupplier->name), false);
        $response->assertSee('title="' . e($this->shortSupplier->name) . '"', false);

        // Verify medium supplier name is fully present without abbreviation or truncation
        $response->assertSee(e($this->mediumSupplier->name), false);
        $response->assertSee('title="' . e($this->mediumSupplier->name) . '"', false);

        // Verify very long supplier name is fully present without abbreviation or truncation
        $response->assertSee(e($this->longSupplier->name), false);
        $response->assertSee('title="' . e($this->longSupplier->name) . '"', false);
    }

    /**
     * Test that Purchase Order Edit page renders full supplier name and preserves selected state.
     */
    public function test_purchase_order_edit_dropdown_displays_full_supplier_name(): void
    {
        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Warehouse',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $order = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->longSupplier->id,
            'warehouse_id' => $warehouse->id,
            'po_no' => 'PO-TEST-001',
            'po_date' => '2026-11-23',
            'expected_date' => '2026-11-30',
            'credit_days' => 45,
            'status' => 'draft',
            'subtotal' => 1000.00,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('purchasing.orders.edit', $order));

        $response->assertStatus(200);

        // Verify very long supplier name is rendered in full on edit page
        $response->assertSee(e($this->longSupplier->name), false);
        $response->assertSee('title="' . e($this->longSupplier->name) . '"', false);
    }

    /**
     * Test that searchable-select.js includes multi-line wrapping and dynamic width calculations.
     */
    public function test_searchable_select_script_contains_full_name_and_wrapping_support(): void
    {
        $scriptPath = public_path('js/searchable-select.js');
        $this->assertFileExists($scriptPath);

        $content = file_get_contents($scriptPath);

        // Check option wrapping CSS rules
        $this->assertStringContainsString('word-break: break-word;', $content);
        $this->assertStringContainsString('overflow-wrap: anywhere;', $content);
        $this->assertStringContainsString('.dms-searchable-option-text {', $content);

        // Check trigger title setting for tooltips
        $this->assertStringContainsString('labelSpan.title = selectedOpt.text;', $content);
        $this->assertStringContainsString('trigger.title = selectedOpt.text;', $content);

        // Check option title setting for tooltips
        $this->assertStringContainsString('optEl.title = opt.text || \'\';', $content);
        $this->assertStringContainsString('textSpan.title = opt.text || \'\';', $content);

        // Check dynamic width calculation
        $this->assertStringContainsString('finalMenuWidth = Math.max(rect.width, baseMinWidth);', $content);
    }
}
