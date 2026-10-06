<?php

namespace Tests\Feature\Hrms;

use App\Domains\Hrms\Models\Department;
use App\Domains\Hrms\Models\Designation;
use App\Domains\Hrms\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['hrms.view', 'hrms.create', 'hrms.edit', 'hrms.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(array $permissions = ['hrms.view', 'hrms.create', 'hrms.edit', 'hrms.manage']): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        return $user;
    }

    public function test_employee_code_auto_generates_when_left_blank(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post(route('hrms.employees.store'), [
            'name' => 'Anil Verma',
            'status' => 'active',
            'email' => 'anil@example.com',
            'employee_code' => '', // blank to trigger auto-generation
        ]);

        $response->assertRedirect(route('hrms.employees.index'));
        $employee = Employee::where('email', 'anil@example.com')->first();
        $this->assertNotNull($employee);
        $this->assertStringStartsWith('EMP-', $employee->employee_code);
    }

    public function test_login_user_cannot_be_linked_to_multiple_employees(): void
    {
        $admin = $this->makeUser();
        $linkedUser = User::factory()->create();

        Employee::create([
            'employee_code' => 'EMP-001',
            'name' => 'Existing Employee',
            'user_id' => $linkedUser->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('hrms.employees.store'), [
            'name' => 'Second Employee',
            'employee_code' => 'EMP-002',
            'user_id' => $linkedUser->id,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_cannot_assign_employee_as_their_own_manager(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create([
            'employee_code' => 'EMP-010',
            'name' => 'Self Manager Candidate',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->put(route('hrms.employees.update', $employee), [
            'name' => 'Self Manager Candidate',
            'employee_code' => 'EMP-010',
            'status' => 'active',
            'manager_id' => $employee->id,
        ]);

        $response->assertSessionHasErrors('manager_id');
    }

    public function test_circular_reporting_hierarchy_is_prevented(): void
    {
        $admin = $this->makeUser();

        // A manages B
        $empA = Employee::create(['employee_code' => 'EMP-A', 'name' => 'Manager A', 'status' => 'active']);
        $empB = Employee::create(['employee_code' => 'EMP-B', 'name' => 'Employee B', 'status' => 'active', 'manager_id' => $empA->id]);

        // Attempt to make B manager of A (creates loop: A -> B -> A)
        $response = $this->actingAs($admin)->put(route('hrms.employees.update', $empA), [
            'name' => 'Manager A',
            'employee_code' => 'EMP-A',
            'status' => 'active',
            'manager_id' => $empB->id,
        ]);

        $response->assertSessionHasErrors('manager_id');
    }

    public function test_department_with_assigned_employees_cannot_be_deleted(): void
    {
        $admin = $this->makeUser();
        $dept = Department::create(['name' => 'Engineering', 'code' => 'ENG', 'is_active' => true]);

        Employee::create([
            'employee_code' => 'EMP-020',
            'name' => 'Dev Staff',
            'department_id' => $dept->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->delete(route('hrms.departments.destroy', $dept));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }

    public function test_designation_with_assigned_employees_cannot_be_deleted(): void
    {
        $admin = $this->makeUser();
        $desig = Designation::create(['name' => 'Lead Architect', 'code' => 'ARCH', 'is_active' => true]);

        Employee::create([
            'employee_code' => 'EMP-021',
            'name' => 'Architect Staff',
            'designation_id' => $desig->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->delete(route('hrms.designations.destroy', $desig));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('designations', ['id' => $desig->id]);
    }
}

