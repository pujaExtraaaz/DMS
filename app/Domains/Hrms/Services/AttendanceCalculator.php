<?php

namespace App\Domains\Hrms\Services;

use App\Domains\Hrms\Models\Attendance;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\EmployeeShift;
use App\Domains\Hrms\Models\LeaveRequest;
use App\Domains\Hrms\Models\LeaveType;
use App\Domains\Hrms\Models\Shift;
use Carbon\Carbon;

class AttendanceCalculator
{
    public function __construct(protected LeaveDayCalculator $leaveDays) {}

    public function resolveShift(Employee $employee, Carbon $date): ?Shift
    {
        $assignment = EmployeeShift::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date->toDateString());
            })
            ->latest('effective_from')
            ->first();

        if ($assignment?->shift) {
            return $assignment->shift;
        }

        return Shift::query()
            ->where('is_active', true)
            ->when($employee->department_id, fn ($q) => $q->where(function ($q2) use ($employee) {
                $q2->whereNull('department_id')->orWhere('department_id', $employee->department_id);
            }))
            ->orderByRaw('department_id is null')
            ->first();
    }

    public function approvedLeaveForDate(Employee $employee, Carbon $date): ?LeaveRequest
    {
        return LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $date->toDateString())
            ->whereDate('to_date', '>=', $date->toDateString())
            ->first();
    }

    /**
     * @return array{status:string,hours_worked:?float,late_minutes:int,early_minutes:int,ot_minutes:int,shift_id:?int}
     */
    public function calculate(Employee $employee, Carbon $date, ?string $checkIn, ?string $checkOut): array
    {
        if ($this->leaveDays->isNonWorkingDay($date, $employee)) {
            return [
                'status' => 'weekly_off',
                'hours_worked' => null,
                'late_minutes' => 0,
                'early_minutes' => 0,
                'ot_minutes' => 0,
                'shift_id' => null,
            ];
        }

        $leave = $this->approvedLeaveForDate($employee, $date);
        if ($leave) {
            $code = strtoupper((string) ($leave->leaveType?->code ?? ''));
            $status = $code === 'LWP' ? 'absent' : 'on_leave';
            if ($leave->is_half_day) {
                $status = 'half_day';
            }

            return [
                'status' => $status,
                'hours_worked' => $leave->is_half_day ? 4.0 : null,
                'late_minutes' => 0,
                'early_minutes' => 0,
                'ot_minutes' => 0,
                'shift_id' => $this->resolveShift($employee, $date)?->id,
            ];
        }

        $shift = $this->resolveShift($employee, $date);
        if (! $checkIn && ! $checkOut) {
            return [
                'status' => 'absent',
                'hours_worked' => 0,
                'late_minutes' => 0,
                'early_minutes' => 0,
                'ot_minutes' => 0,
                'shift_id' => $shift?->id,
            ];
        }

        $in = $checkIn ? Carbon::parse($date->toDateString().' '.$checkIn) : null;
        $out = $checkOut ? Carbon::parse($date->toDateString().' '.$checkOut) : null;
        if ($in && $out && $out->lt($in)) {
            $out->addDay();
        }

        $hours = ($in && $out) ? round($in->diffInMinutes($out) / 60, 2) : null;
        $late = 0;
        $early = 0;
        $ot = 0;
        $status = 'present';

        if ($shift && $in) {
            $shiftStart = Carbon::parse($date->toDateString().' '.$shift->start_time);
            $graceEnd = $shiftStart->copy()->addMinutes((int) $shift->grace_minutes);
            if ($in->gt($graceEnd)) {
                $late = (int) $graceEnd->diffInMinutes($in);
                $status = 'late';
            }
        }

        if ($shift && $out) {
            $shiftEnd = Carbon::parse($date->toDateString().' '.$shift->end_time);
            if ($out->lt($shiftEnd)) {
                $early = (int) $out->diffInMinutes($shiftEnd);
            }
            if ($out->gt($shiftEnd)) {
                $ot = (int) $shiftEnd->diffInMinutes($out);
            }
        }

        $workedMinutes = $hours !== null ? (int) round($hours * 60) : 0;
        if ($shift) {
            if ($workedMinutes > 0 && $workedMinutes < (int) $shift->half_day_minutes) {
                $status = 'absent';
            } elseif ($workedMinutes > 0 && $workedMinutes < (int) $shift->full_day_minutes) {
                $status = 'half_day';
            }
        } elseif ($hours !== null && $hours < 4) {
            $status = 'half_day';
        }

        return [
            'status' => $status,
            'hours_worked' => $hours,
            'late_minutes' => $late,
            'early_minutes' => $early,
            'ot_minutes' => $ot,
            'shift_id' => $shift?->id,
        ];
    }

    public function upsertDay(Employee $employee, Carbon $date, ?string $checkIn, ?string $checkOut, string $source = 'system'): Attendance
    {
        $calc = $this->calculate($employee, $date, $checkIn, $checkOut);

        return Attendance::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'attendance_date' => $date->toDateString(),
            ],
            [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => $calc['status'] === 'weekly_off' ? 'present' : $calc['status'],
                'hours_worked' => $calc['hours_worked'],
                'late_minutes' => $calc['late_minutes'],
                'early_minutes' => $calc['early_minutes'],
                'ot_minutes' => $calc['ot_minutes'],
                'shift_id' => $calc['shift_id'],
                'source' => $source,
                'notes' => $calc['status'] === 'weekly_off' ? 'Weekly off / holiday' : null,
            ]
        );
    }
}
