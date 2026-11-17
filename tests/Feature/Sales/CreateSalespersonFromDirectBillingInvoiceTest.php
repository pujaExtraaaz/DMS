<?php

namespace Tests\Feature\Sales;

use App\Domains\Hrms\Models\Department;
use App\Domains\Hrms\Models\Designation;
use App\Domains\Hrms\Models\Employee;
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

class CreateSalespersonFromDirectBillingInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected Department $department;
    protected Designation $designation;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);

        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'sales.view', 'sales.create', 'sales.edit', 'sales.manage',
            'customers.view', 'customers.create',
            'products.view', 'products.create',
            'hrms.view', 'hrms.create', 'hrms.edit', 'hrms.manage',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->company = Company::create([
            'name' => 'Avit Digital Systems',
            'code' => 'AVIT-01',
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'name' => 'Head Office',
            'code' => 'HO-01',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->user->assignRole($adminRole);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'is_active' => true,
        ]);

        $customerType = CustomerType::create([
            'name' => 'Dealer',
            'code' => 'DLR',
            'is_active' => true,
        ]);

        Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Demo Customer',
            'code' => 'CUST-001',
            'customer_type_id' => $customerType->id,
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Sales & Marketing',
            'code' => 'SALES',
            'is_active' => true,
        ]);

        $this->designation = Designation::create([
            'company_id' => $this->company->id,
            'name' => 'Senior Sales Executive',
            'code' => 'SR-SE',
            'is_active' => true,
        ]);
    }

    public function test_tax_invoice_page_renders_create_salesperson_button_and_modal(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.create'));

        $response->assertStatus(200);
        $response->assertSee('+ Create Salesperson');
        $response->assertSee('openSalespersonModal()', false);
        $response->assertSee('Create New Salesperson');
        $response->assertSee('Save Salesperson');
        $response->assertSee('Full Employee Form', false);
        $response->assertSee('Sales &amp; Marketing', false);
        $response->assertSee('Senior Sales Executive');
    }

    public function test_quick_add_salesperson_creates_employee_and_user_and_returns_json(): void
    {
        $payload = [
            'name' => 'Vikram Malhotra',
            'employee_code' => 'EMP-SALES-99',
            'phone' => '9876543210',
            'email' => 'vikram.sales@example.com',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)->postJson(route('invoices.salespersons.quick-add'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $salespersonData = $response->json('salesperson');
        $this->assertNotEmpty($salespersonData['id']);
        $this->assertEquals('Vikram Malhotra', $salespersonData['name']);

        // Assert employee created
        $employee = Employee::where('employee_code', 'EMP-SALES-99')->first();
        $this->assertNotNull($employee);
        $this->assertTrue($employee->is_salesperson);
        $this->assertEquals('9876543210', $employee->phone);
        $this->assertEquals($this->company->id, $employee->company_id);

        // Assert user created and assigned salesperson role
        $linkedUser = User::find($salespersonData['id']);
        $this->assertNotNull($linkedUser);
        $this->assertEquals('vikram.sales@example.com', $linkedUser->email);
        $this->assertTrue($linkedUser->hasRole('salesperson'));
        $this->assertEquals($linkedUser->id, $employee->user_id);
    }

    public function test_quick_add_salesperson_without_code_auto_generates_code_using_code_generator(): void
    {
        $payload = [
            'name' => 'Rohit Saxena',
            'employee_code' => '',
            'phone' => '9811122233',
            'email' => 'rohit.sales@example.com',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)->postJson(route('invoices.salespersons.quick-add'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $salespersonData = $response->json('salesperson');
        $generatedCode = $salespersonData['employee_code'];

        $this->assertNotEmpty($generatedCode);
        $this->assertStringStartsWith('EMP-', $generatedCode);

        $employee = Employee::where('employee_code', $generatedCode)->first();
        $this->assertNotNull($employee);
        $this->assertEquals('Rohit Saxena', $employee->name);
        $this->assertTrue($employee->is_salesperson);
    }

    public function test_quick_add_salesperson_with_duplicate_code_fails_validation(): void
    {
        Employee::create([
            'company_id' => $this->company->id,
            'name' => 'Pre-existing Employee',
            'employee_code' => 'EMP-DUP-101',
            'is_salesperson' => false,
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'New Duplicate Person',
            'employee_code' => 'EMP-DUP-101',
            'phone' => '9876500000',
        ];

        $response = $this->actingAs($this->user)->postJson(route('invoices.salespersons.quick-add'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['employee_code']);
    }

    public function test_quick_add_salesperson_requires_name(): void
    {
        $payload = [
            'name' => '',
            'phone' => '9876543210',
        ];

        $response = $this->actingAs($this->user)->postJson(route('invoices.salespersons.quick-add'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_employee_create_page_shows_banner_and_cancel_when_return_url_passed(): void
    {
        $response = $this->actingAs($this->user)->get(route('hrms.employees.create', [
            'return_to' => 'invoices.create',
            'return_url' => route('invoices.create'),
            'is_salesperson' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Adding Salesperson for Tax Invoice');
        $response->assertSee('Cancel &amp; Go Back', false);
        $response->assertSee(route('invoices.create'));
    }

    public function test_employee_store_with_return_to_invoices_redirects_with_new_salesperson_id(): void
    {
        $payload = [
            'name' => 'Aman Sharma',
            'employee_code' => 'EMP-SALES-02',
            'email' => 'aman.sales@example.com',
            'phone' => '9876543211',
            'is_salesperson' => '1',
            'status' => 'active',
            'return_to' => 'invoices.create',
            'return_url' => route('invoices.create'),
        ];

        $response = $this->actingAs($this->user)->post(route('hrms.employees.store'), $payload);

        $employee = Employee::where('employee_code', 'EMP-SALES-02')->first();
        $this->assertNotNull($employee);
        $this->assertTrue($employee->is_salesperson);

        $salesUser = User::where('email', 'aman.sales@example.com')->first();
        $this->assertNotNull($salesUser);
        $this->assertTrue($salesUser->hasRole('salesperson'));

        $response->assertRedirect(route('invoices.create', [
            'salesperson_id' => $salesUser->id,
            'new_salesperson_id' => $salesUser->id,
            'returned_from' => 'salesperson_create',
        ]));
    }

    public function test_invoice_create_restores_draft_and_selects_new_salesperson(): void
    {
        $salesUser = User::factory()->create([
            'name' => 'Pooja Verma',
            'email' => 'pooja.sales@example.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $salesUser->assignRole('salesperson');

        $draft = [
            'customer_id' => 1,
            'reference_no' => 'REF-INLINE-TEST-999',
            'remarks' => 'Preserved during salesperson creation',
            'items' => [
                [
                    'product_id' => 1,
                    'quantity' => 5,
                    'unit_price' => 1500,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['tax_invoice_draft' => $draft])
            ->get(route('invoices.create', [
                'salesperson_id' => $salesUser->id,
                'new_salesperson_id' => $salesUser->id,
                'returned_from' => 'salesperson_create',
            ]));

        $response->assertStatus(200);
        $response->assertSessionMissing('tax_invoice_draft');
        $this->assertEquals($salesUser->id, $response->viewData('newSalesperson')?->id);
        $salespersons = $response->viewData('salespersons');
        $this->assertTrue($salespersons->contains('id', $salesUser->id));
        $response->assertSee($salesUser->name);
    }
}
