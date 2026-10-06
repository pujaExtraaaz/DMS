<?php

namespace Tests\Feature\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\LeaveBalance;
use App\Domains\Hrms\Models\LeaveRequest;
use App\Domains\Hrms\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
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

    public function test_leave_request_fails_if_insufficient_balance(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'EMP-201', 'name' => 'Sunita Rao', 'status' => 'active']);
        $leaveType = LeaveType::create(['name' => 'Casual Leave', 'default_days' => 5, 'is_active' => true]);

        LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'opening_balance' => 2,
            'used_balance' => 1,
            'closing_balance' => 1, // Only 1 day available
        ]);

        // Request 3 days (e.g. Wed to Fri: 2026-10-07 to 2026-10-09)
        $response = $this->actingAs($admin)->post(route('hrms.leave-requests.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'from_date' => '2026-10-07',
            'to_date' => '2026-10-09',
            'reason' => 'Family event',
        ]);

        $response->assertSessionHasErrors('leave_type_id');
        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_half_day_leave_request_records_half_day_duration(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'EMP-202', 'name' => 'Mohan Lal', 'status' => 'active']);
        $leaveType = LeaveType::create(['name' => 'Casual Leave', 'default_days' => 10, 'is_active' => true]);

        LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'opening_balance' => 10,
            'used_balance' => 0,
            'closing_balance' => 10,
        ]);

        $response = $this->actingAs($admin)->post(route('hrms.leave-requests.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'from_date' => '2026-10-07',
            'to_date' => '2026-10-07',
            'is_half_day' => 1,
            'reason' => 'Dentist appointment',
        ]);

        $response->assertRedirect(route('hrms.leave-requests.index'));
        $leave = LeaveRequest::first();
        $this->assertNotNull($leave);
        $this->assertEquals(0.5, (float) $leave->days);
    }

    public function test_leave_approval_deducts_balance_and_cancellation_restores_it(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'EMP-203', 'name' => 'Pooja Hegde', 'status' => 'active']);
        $leaveType = LeaveType::create(['name' => 'Privilege Leave', 'default_days' => 15, 'is_active' => true]);

        $balance = LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'opening_balance' => 15,
            'used_balance' => 0,
            'closing_balance' => 15,
        ]);

        // Create pending request for 2 days
        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'from_date' => '2026-10-12',
            'to_date' => '2026-10-13',
            'days' => 2.0,
            'status' => 'pending',
        ]);

        // Approve leave
        $approveResponse = $this->actingAs($admin)->post(route('hrms.leave-requests.approve', $leave));
        $approveResponse->assertRedirect();

        $leave->refresh();
        $balance->refresh();
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals(2.0, (float) $balance->used_balance);
        $this->assertEquals(13.0, (float) $balance->closing_balance);

        // Cancel approved leave
        $cancelResponse = $this->actingAs($admin)->post(route('hrms.leave-requests.cancel', $leave));
        $cancelResponse->assertRedirect();

        $leave->refresh();
        $balance->refresh();
        $this->assertEquals('cancelled', $leave->status);
        $this->assertEquals(0.0, (float) $balance->used_balance);
        $this->assertEquals(15.0, (float) $balance->closing_balance);
    }
}

