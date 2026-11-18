<?php

namespace Tests\Feature\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\PayHead;
use App\Domains\Hrms\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['hrms.view', 'hrms.create', 'hrms.edit', 'hrms.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['hrms.view', 'hrms.create', 'hrms.edit', 'hrms.manage']);

        return $user;
    }

    public function test_standard_pay_heads_are_seeded(): void
    {
        $this->assertDatabaseHas('pay_heads', ['code' => 'BASIC', 'is_system' => 1]);
        $this->assertDatabaseHas('pay_heads', ['code' => 'HRA', 'is_system' => 1]);
        $this->assertDatabaseHas('pay_heads', ['code' => 'ALLOWANCES', 'is_system' => 1]);
    }

    public function test_can_create_custom_pay_head(): void
    {
        $admin = $this->makeUser();

        $response = $this->actingAs($admin)->post(route('hrms.pay-heads.store'), [
            'name' => 'Conveyance',
            'code' => 'CONV',
            'type' => 'earning',
            'sort_order' => 40,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('hrms.pay-heads.index'));
        $this->assertDatabaseHas('pay_heads', ['code' => 'CONV', 'name' => 'Conveyance', 'is_system' => 0]);
    }

    public function test_can_create_salary_structure_with_components(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'EMP-501', 'name' => 'Ravi Kumar', 'status' => 'active']);

        $basic = PayHead::where('code', 'BASIC')->firstOrFail();
        $hra = PayHead::where('code', 'HRA')->firstOrFail();
        $allowances = PayHead::where('code', 'ALLOWANCES')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('hrms.salary-structures.store'), [
            'employee_id' => $employee->id,
            'name' => 'Standard Package',
            'effective_from' => '2026-04-01',
            'is_active' => 1,
            'lines' => [
                ['pay_head_id' => $basic->id, 'amount' => 30000],
                ['pay_head_id' => $hra->id, 'amount' => 12000],
                ['pay_head_id' => $allowances->id, 'amount' => 5000],
            ],
        ]);

        $response->assertRedirect(route('hrms.salary-structures.index'));

        $structure = SalaryStructure::where('employee_id', $employee->id)->first();
        $this->assertNotNull($structure);
        $this->assertEquals(30000, (float) $structure->basic);
        $this->assertEquals(12000, (float) $structure->hra);
        $this->assertEquals(5000, (float) $structure->allowances);
        $this->assertCount(3, $structure->lines);
    }

    public function test_employee_ifsc_lookup_autofills_bank_details(): void
    {
        Http::fake([
            'https://ifsc.razorpay.com/HDFC0000001' => Http::response([
                'BANK' => 'HDFC Bank',
                'BRANCH' => 'Mumbai Main',
                'ADDRESS' => 'Mumbai',
                'CITY' => 'Mumbai',
                'STATE' => 'Maharashtra',
            ], 200),
        ]);

        $admin = $this->makeUser();

        $response = $this->actingAs($admin)->getJson(route('hrms.employees.ifsc-lookup', ['ifsc' => 'HDFC0000001']));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('bank.bank_name', 'HDFC Bank')
            ->assertJsonPath('bank.branch_name', 'Mumbai Main');
    }
}
