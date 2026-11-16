<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxInvoiceDiscountLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $role->givePermissionTo(Permission::all());

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

        Customer::create([
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

        Warehouse::create([
            'name' => 'Pune Main Warehouse',
            'code' => 'PMW-01',
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
    }

    public function test_tax_invoice_table_renders_expanded_discount_column_and_aligned_layout(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));

        $response->assertStatus(200);

        // Verify table has expanded minimum width for responsive horizontal scroll
        $response->assertSee('min-w-[1440px]', false);

        // Verify Discount header has generous minimum width
        $response->assertSee('min-w-[190px] w-48 text-left', false);
        $response->assertSee('Discount');

        // Verify Discount row cell layout and controls
        $response->assertSee('min-w-[190px] w-48', false);
        $response->assertSee('items[${i}][discount_type]', false);
        $response->assertSee('items[${i}][discount_value]', false);
        $response->assertSee('value="percent"', false);
        $response->assertSee('value="flat"', false);

        // Verify discount helper display
        $response->assertSee('`- ${formatCurrency(lineDiscount(row))}`', false);

        // Verify vertical alignment standardized with h-[38px] containers
        $response->assertSee('h-[38px] flex items-center justify-end font-mono text-xs text-slate-700', false);
        $response->assertSee('h-[38px] flex items-center justify-center font-semibold text-xs text-slate-700', false);
        $response->assertSee('h-[38px] flex items-center justify-end font-bold font-mono text-slate-900', false);
        $response->assertSee('h-[38px] flex items-center justify-center', false);
    }
}
