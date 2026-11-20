<?php

namespace App\Domains\Hrms\Services;

use App\Domains\Hrms\Models\Attendance;
use App\Domains\Hrms\Models\LeaveRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveAttendanceSyncService
{
    public function __construct(protected LeaveDayCalculator $leaveDays) {}

    public function markApproved(LeaveRequest $leave): void
    {
        $leave->loadMissing(['employee', 'leaveType']);
        $employee = $leave->employee;
        if (! $employee || ! $leave->from_date || ! $leave->to_date) {
            return;
        }

        $marker = '[leave_id:'.$leave->id.']';
        $status = $leave->is_half_day ? 'half_day' : 'on_leave';

        foreach (CarbonPeriod::create($leave->from_date, $leave->to_date) as $date) {
            /** @var Carbon $date */
            if ($this->leaveDays->isNonWorkingDay($date, $employee)) {
                continue;
            }

            $existing = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date->toDateString())
                ->first();

            // Do not overwrite punched present/late days.
            if ($existing && in_array($existing->status, ['present', 'late'], true) && $existing->check_in) {
                continue;
            }

            Attendance::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'attendance_date' => $date->toDateString(),
                ],
                [
                    'status' => $status,
                    'check_in' => null,
                    'check_out' => null,
                    'hours_worked' => $leave->is_half_day ? 4 : 0,
                    'late_minutes' => 0,
                    'early_minutes' => 0,
                    'ot_minutes' => 0,
                    'source' => 'leave',
                    'notes' => trim(($existing?->notes ?? '').' '.$marker.' Approved leave'),
                ]
            );
        }
    }

    public function clearCancelled(LeaveRequest $leave): void
    {
        $marker = '[leave_id:'.$leave->id.']';

        Attendance::query()
            ->where('employee_id', $leave->employee_id)
            ->whereBetween('attendance_date', [
                Carbon::parse($leave->from_date)->toDateString(),
                Carbon::parse($leave->to_date)->toDateString(),
            ])
            ->where('notes', 'like', '%'.$marker.'%')
            ->whereIn('status', ['on_leave', 'half_day'])
            ->delete();
    }
}
