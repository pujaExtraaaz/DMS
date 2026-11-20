<?php

namespace Tests\Feature\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\Holiday;
use App\Domains\Hrms\Models\HolidayCalendar;
use App\Domains\Hrms\Models\LeaveBalance;
use App\Domains\Hrms\Models\LeavePolicy;
use App\Domains\Hrms\Models\LeaveRequest;
use App\Domains\Hrms\Models\LeaveType;
use App\Domains\Hrms\Models\PayrollRun;
use App\Domains\Hrms\Models\SalaryStructure;
use App\Domains\Hrms\Models\Shift;
use App\Domains\Hrms\Services\AttendanceCalculator;
use App\Domains\Hrms\Services\LeaveDayCalculator;
use App\Domains\Hrms\Services\LeaveBalanceService;
use App\Domains\Hrms\Services\PayrollCalculator;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HrmsPhaseExtensionsTest extends TestCase
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

    public function test_leave_day_calculator_skips_compulsory_holidays(): void
    {
        $calendar = HolidayCalendar::create([
            'year' => 2026,
            'name' => '2026 Main',
            'is_published' => true,
        ]);
        Holiday::create([
            'holiday_calendar_id' => $calendar->id,
            'holiday_date' => '2026-10-08', // Thursday
            'name' => 'Test Holiday',
            'category' => 'national',
            'kind' => 'compulsory',
        ]);

        $days = app(LeaveDayCalculator::class)->countDays('2026-10-07', '2026-10-09');
        // Wed + Fri (Thu holiday) = 2
        $this->assertSame(2.0, $days);
    }

    public function test_leave_policy_allocation_sets_balance(): void
    {
        $employee = Employee::create(['employee_code' => 'E1', 'name' => 'Asha', 'status' => 'active']);
        $type = LeaveType::create(['name' => 'Casual Leave', 'code' => 'CL', 'default_days' => 12, 'is_active' => true]);
        LeavePolicy::create([
            'leave_type_id' => $type->id,
            'name' => 'CL Staff',
            'annual_entitlement' => 12,
            'accrual_frequency' => 'yearly',
            'carry_forward_max' => 5,
            'is_active' => true,
        ]);

        app(LeaveBalanceService::class)->allocateForEmployee($employee, 2026);

        $balance = LeaveBalance::where('employee_id', $employee->id)->where('leave_type_id', $type->id)->where('year', 2026)->first();
        $this->assertNotNull($balance);
        $this->assertEquals(12.0, (float) $balance->accrued_balance);
        $this->assertEquals(12.0, (float) $balance->closing_balance);
    }

    public function test_holiday_aware_leave_request_store(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'E2', 'name' => 'Ravi', 'status' => 'active']);
        $type = LeaveType::create(['name' => 'Casual Leave', 'code' => 'CL', 'default_days' => 10, 'is_active' => true, 'is_paid' => true]);
        LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'year' => 2026,
            'opening_balance' => 10,
            'accrued_balance' => 0,
            'used_balance' => 0,
            'closing_balance' => 10,
        ]);

        $calendar = HolidayCalendar::create(['year' => 2026, 'name' => 'Y', 'is_published' => true]);
        Holiday::create([
            'holiday_calendar_id' => $calendar->id,
            'holiday_date' => '2026-10-08',
            'name' => 'H',
            'category' => 'festival',
            'kind' => 'compulsory',
        ]);

        $response = $this->actingAs($admin)->post(route('hrms.leave-requests.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'from_date' => '2026-10-07',
            'to_date' => '2026-10-09',
            'reason' => 'Trip',
        ]);

        $response->assertRedirect(route('hrms.leave-requests.index'));
        $leave = LeaveRequest::first();
        $this->assertNotNull($leave);
        $this->assertEquals(2.0, (float) $leave->days);
    }

    public function test_attendance_calculator_marks_late_with_grace(): void
    {
        $employee = Employee::create(['employee_code' => 'E3', 'name' => 'Kiran', 'status' => 'active']);
        $shift = Shift::create([
            'name' => 'General',
            'start_time' => '09:30:00',
            'end_time' => '18:30:00',
            'grace_minutes' => 15,
            'half_day_minutes' => 240,
            'full_day_minutes' => 480,
            'is_active' => true,
        ]);
        \App\Domains\Hrms\Models\EmployeeShift::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2026-01-01',
            'weekly_offs' => [0],
            'weekly_off_mode' => 'fixed',
        ]);

        $calc = app(AttendanceCalculator::class)->calculate(
            $employee,
            Carbon::parse('2026-10-07'),
            '10:00:00',
            '18:30:00'
        );

        $this->assertSame('late', $calc['status']);
        $this->assertGreaterThan(0, $calc['late_minutes']);
    }

    public function test_payroll_run_computes_net_from_structure(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'E4', 'name' => 'Neha', 'status' => 'active']);
        SalaryStructure::create([
            'employee_id' => $employee->id,
            'basic' => 30000,
            'hra' => 10000,
            'allowances' => 5000,
            'deductions' => 2000,
            'is_active' => true,
            'effective_from' => '2026-01-01',
        ]);

        $response = $this->actingAs($admin)->post(route('hrms.payroll-runs.store'), [
            'year' => 2026,
            'month' => 10,
        ]);

        $run = PayrollRun::first();
        $this->assertNotNull($run);
        $response->assertRedirect(route('hrms.payroll-runs.show', $run));
        $this->assertGreaterThan(0, $run->lines()->count());
        $this->assertEquals('draft', $run->status);
    }

    public function test_essl_push_ingests_punches_when_enabled(): void
    {
        config(['hrms.essl.enabled' => true, 'hrms.essl.token' => 'test-token']);
        $employee = Employee::create([
            'employee_code' => 'E5',
            'biometric_code' => '1001',
            'name' => 'Bio User',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/essl/punches', [
            'punches' => [
                [
                    'biometric_code' => '1001',
                    'punched_at' => '2026-10-07 09:35:00',
                    'direction' => 'in',
                    'external_id' => 'dev-1',
                ],
                [
                    'biometric_code' => '1001',
                    'punched_at' => '2026-10-07 18:40:00',
                    'direction' => 'out',
                    'external_id' => 'dev-2',
                ],
            ],
        ], ['Authorization' => 'Bearer test-token']);

        $response->assertOk()->assertJsonPath('imported', 2);
        $this->assertDatabaseHas('attendance_punches', ['biometric_code' => '1001', 'employee_id' => $employee->id]);
    }

    public function test_rotational_weekly_off_every_second_sunday(): void
    {
        $employee = Employee::create(['employee_code' => 'E6', 'name' => 'Rot Off', 'status' => 'active']);
        $shift = Shift::create([
            'name' => 'Gen',
            'start_time' => '09:30:00',
            'end_time' => '18:30:00',
            'is_active' => true,
        ]);
        \App\Domains\Hrms\Models\EmployeeShift::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2026-10-04', // Sunday week 0
            'weekly_off_mode' => 'rotational',
            'weekly_offs' => [0, 2], // Sunday every 2nd week
        ]);

        $calc = app(LeaveDayCalculator::class);
        // 2026-10-04 is Sunday, week 0 → off
        $this->assertTrue($calc->isNonWorkingDay(Carbon::parse('2026-10-04'), $employee));
        // 2026-10-11 Sunday week 1 → working
        $this->assertFalse($calc->isNonWorkingDay(Carbon::parse('2026-10-11'), $employee));
        // 2026-10-18 Sunday week 2 → off
        $this->assertTrue($calc->isNonWorkingDay(Carbon::parse('2026-10-18'), $employee));
    }

    public function test_statutory_deductions_apply_pf_and_pt(): void
    {
        $deductions = app(\App\Domains\Hrms\Services\StatutoryDeductionService::class)->apply(
            ['BASIC' => 20000, 'HRA' => 8000],
            []
        );

        $this->assertArrayHasKey('PF', $deductions);
        $this->assertEquals(1800.0, $deductions['PF']); // 12% of 15000 ceiling
        $this->assertArrayHasKey('PT', $deductions);
        $this->assertGreaterThan(0, $deductions['PT']);
    }

    public function test_comp_off_credits_from_ot(): void
    {
        $employee = Employee::create(['employee_code' => 'E7', 'name' => 'OT User', 'status' => 'active']);
        $type = LeaveType::where('code', 'COFF')->first()
            ?? LeaveType::create(['name' => 'Compensatory Off', 'code' => 'COFF', 'default_days' => 0, 'is_paid' => true, 'is_active' => true]);

        \App\Domains\Hrms\Models\Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-07',
            'status' => 'present',
            'check_in' => '09:30:00',
            'check_out' => '20:30:00',
            'ot_minutes' => 480,
            'hours_worked' => 10,
        ]);

        $result = app(\App\Domains\Hrms\Services\CompOffService::class)->creditForPeriod(
            $employee,
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-31')
        );

        $this->assertEquals(1.0, $result['credited_days']);
        $balance = LeaveBalance::where('employee_id', $employee->id)->where('leave_type_id', $type->id)->first();
        $this->assertNotNull($balance);
        $this->assertEquals(1.0, (float) $balance->accrued_balance);
    }

    public function test_punch_conflict_index_lists_mixed_sources(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'E8', 'name' => 'Conflict', 'status' => 'active']);

        \App\Domains\Hrms\Models\AttendancePunch::create([
            'employee_id' => $employee->id,
            'biometric_code' => 'x',
            'punched_at' => '2026-10-07 09:30:00',
            'source' => 'essl',
            'direction' => 'in',
        ]);
        \App\Domains\Hrms\Models\AttendancePunch::create([
            'employee_id' => $employee->id,
            'biometric_code' => 'x',
            'punched_at' => '2026-10-07 09:32:00',
            'source' => 'web',
            'direction' => 'in',
        ]);

        $response = $this->actingAs($admin)->get(route('hrms.punch-conflicts.index', [
            'from' => '2026-10-01',
            'to' => '2026-10-31',
        ]));

        $response->assertOk();
        $response->assertSee('Conflict');
        $response->assertSee('Prefer eSSL');
    }

    public function test_salary_revision_history_page(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'E9', 'name' => 'Hist', 'status' => 'active']);
        SalaryStructure::create([
            'employee_id' => $employee->id,
            'name' => 'Rev 1',
            'basic' => 25000,
            'is_active' => false,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-03-31',
        ]);
        SalaryStructure::create([
            'employee_id' => $employee->id,
            'name' => 'Rev 2',
            'basic' => 30000,
            'is_active' => true,
            'effective_from' => '2026-04-01',
        ]);

        $response = $this->actingAs($admin)->get(route('hrms.salary-structures.history', $employee));
        $response->assertOk()->assertSee('Rev 1')->assertSee('Rev 2');
    }

    public function test_leave_approval_marks_on_leave_attendance(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'E10', 'name' => 'Leave Att', 'status' => 'active']);
        $type = LeaveType::create(['name' => 'Casual Leave', 'code' => 'CL', 'default_days' => 10, 'is_active' => true, 'is_paid' => true]);
        LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'year' => 2026,
            'opening_balance' => 10,
            'accrued_balance' => 0,
            'used_balance' => 0,
            'closing_balance' => 10,
        ]);

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'from_date' => '2026-10-07',
            'to_date' => '2026-10-07',
            'days' => 1,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->post(route('hrms.leave-requests.approve', $leave))->assertRedirect();

        $attendance = \App\Domains\Hrms\Models\Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-10-07')
            ->first();
        $this->assertNotNull($attendance);
        $this->assertSame('on_leave', $attendance->status);
        $this->assertSame('leave', $attendance->source);
    }

    public function test_comp_off_is_idempotent(): void
    {
        $employee = Employee::create(['employee_code' => 'E11', 'name' => 'Idem', 'status' => 'active']);
        $type = LeaveType::where('code', 'COFF')->first()
            ?? LeaveType::create(['name' => 'Compensatory Off', 'code' => 'COFF', 'default_days' => 0, 'is_paid' => true, 'is_active' => true]);

        \App\Domains\Hrms\Models\Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-08',
            'status' => 'present',
            'check_in' => '09:30:00',
            'check_out' => '20:30:00',
            'ot_minutes' => 480,
            'hours_worked' => 10,
        ]);

        $svc = app(\App\Domains\Hrms\Services\CompOffService::class);
        $first = $svc->creditForPeriod($employee, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $second = $svc->creditForPeriod($employee, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->assertEquals(1.0, $first['credited_days']);
        $this->assertEquals(0.0, $second['credited_days']);
        $balance = LeaveBalance::where('employee_id', $employee->id)->where('leave_type_id', $type->id)->first();
        $this->assertEquals(1.0, (float) $balance->accrued_balance);
    }

    public function test_ess_payslip_pdf_accessible_without_hrms_permission(): void
    {
        $user = User::factory()->create();
        $employee = Employee::create([
            'employee_code' => 'E12',
            'name' => 'Ess User',
            'status' => 'active',
            'user_id' => $user->id,
        ]);
        $run = PayrollRun::create([
            'year' => 2026,
            'month' => 10,
            'status' => 'approved',
            'created_by' => $user->id,
        ]);
        $line = \App\Domains\Hrms\Models\PayrollRunLine::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'payable_days' => 22,
            'present_days' => 22,
            'leave_days' => 0,
            'lwp_days' => 0,
            'ot_minutes' => 0,
            'gross_earnings' => 40000,
            'total_deductions' => 2000,
            'net_payable' => 38000,
            'earnings_json' => ['BASIC' => 30000],
            'deductions_json' => ['PF' => 1800],
        ]);

        $response = $this->actingAs($user)->get(route('ess.payslips.pdf', $line));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_dashboard_on_leave_includes_approved_leave_request(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'E13', 'name' => 'Dash Leave', 'status' => 'active']);
        $type = LeaveType::create(['name' => 'CL', 'code' => 'CL', 'is_active' => true, 'is_paid' => true]);
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'from_date' => '2026-10-15',
            'to_date' => '2026-10-15',
            'days' => 1,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('hrms.dashboard', ['date' => '2026-10-15']));
        $response->assertOk()->assertSee('Dash Leave');
    }
}
