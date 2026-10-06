<?php

namespace Tests\Feature\Hrms;

use App\Domains\Hrms\Models\Attendance;
use App\Domains\Hrms\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttendanceTest extends TestCase
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

    public function test_attendance_can_be_corrected_via_update(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'EMP-101', 'name' => 'Kavita Singh', 'status' => 'active']);

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-01',
            'check_in' => '09:45',
            'status' => 'late',
            'recorded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->put(route('hrms.attendances.update', $attendance), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-01',
            'check_in' => '09:15',
            'check_out' => '18:15',
            'status' => 'present',
            'hours_worked' => 9.0,
            'notes' => 'Corrected morning punch error',
        ]);

        $response->assertRedirect(route('hrms.attendances.index'));
        $attendance->refresh();
        $this->assertEquals('present', $attendance->status);
        $this->assertEquals(9.0, (float) $attendance->hours_worked);
        $this->assertEquals('Corrected morning punch error', $attendance->notes);
    }

    public function test_daily_bulk_attendance_marks_multiple_employees(): void
    {
        $admin = $this->makeUser();
        $emp1 = Employee::create(['employee_code' => 'EMP-102', 'name' => 'User One', 'status' => 'active']);
        $emp2 = Employee::create(['employee_code' => 'EMP-103', 'name' => 'User Two', 'status' => 'active']);

        $payload = [
            'attendance_date' => '2026-10-05',
            'records' => [
                [
                    'employee_id' => $emp1->id,
                    'status' => 'present',
                    'check_in' => '09:30',
                    'check_out' => '18:30',
                    'notes' => 'On time',
                ],
                [
                    'employee_id' => $emp2->id,
                    'status' => 'half_day',
                    'check_in' => '09:30',
                    'check_out' => '14:00',
                    'notes' => 'Medical leave 2nd half',
                ],
            ],
        ];

        $response = $this->actingAs($admin)->post(route('hrms.attendances.bulk.store'), $payload);

        $response->assertRedirect();
        
        $att1 = Attendance::where('employee_id', $emp1->id)->first();
        $this->assertNotNull($att1);
        $this->assertEquals('present', $att1->status);
        $this->assertEquals(9.0, (float) $att1->hours_worked);

        $att2 = Attendance::where('employee_id', $emp2->id)->first();
        $this->assertNotNull($att2);
        $this->assertEquals('half_day', $att2->status);
        $this->assertEquals(4.5, (float) $att2->hours_worked);
    }

    public function test_live_punch_in_and_punch_out_sequence(): void
    {
        $user = $this->makeUser();
        $employee = Employee::create([
            'employee_code' => 'EMP-104',
            'name' => 'Live Puncher',
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        // Step 1: Punch in
        $response1 = $this->actingAs($user)->post(route('hrms.attendances.punch'));
        $response1->assertRedirect();

        $today = today()->toDateString();
        $record = Attendance::where('employee_id', $employee->id)->whereDate('attendance_date', $today)->first();
        $this->assertNotNull($record);
        $this->assertNotNull($record->check_in);
        $this->assertNull($record->check_out);
        $this->assertEquals('present', $record->status);

        // Step 2: Punch out
        $response2 = $this->actingAs($user)->post(route('hrms.attendances.punch'));
        $response2->assertRedirect();

        $record->refresh();
        $this->assertNotNull($record->check_out);

        // Step 3: Punch again should reject
        $response3 = $this->actingAs($user)->post(route('hrms.attendances.punch'));
        $response3->assertSessionHas('error');
    }
}
