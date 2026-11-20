<?php

namespace App\Domains\Hrms\Services;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\EmployeeShift;
use App\Domains\Hrms\Models\Holiday;
use App\Domains\Hrms\Models\HolidayCalendar;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveDayCalculator
{
    /**
     * Count leave days between dates, excluding Sundays, compulsory holidays, and weekly-offs.
     */
    public function countDays(
        Carbon|string $from,
        Carbon|string $to,
        ?Employee $employee = null,
        bool $halfDay = false,
        bool $shortLeave = false,
        float $shortLeaveHours = 0
    ): float {
        if ($halfDay) {
            return 0.5;
        }

        if ($shortLeave) {
            $hours = max(0.5, $shortLeaveHours);
            // Treat 8 hours as 1 day.
            return round(min(1, $hours / 8), 2);
        }

        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        if ($end->lt($start)) {
            return 0;
        }

        $holidayDates = $this->compulsoryHolidayDates(
            $start->year,
            $end->year,
            $employee?->company_id,
            $employee?->branch_id
        );

        $weeklyOffs = $this->weeklyOffWeekdays($employee, $start);

        $days = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            /** @var Carbon $date */
            if ($date->isSunday()) {
                continue;
            }
            if (in_array((int) $date->dayOfWeek, $weeklyOffs, true)) {
                continue;
            }
            if (isset($holidayDates[$date->toDateString()])) {
                continue;
            }
            $days++;
        }

        return (float) max(0, $days);
    }

    /**
     * @return array<string, true> keyed by Y-m-d
     */
    public function compulsoryHolidayDates(
        int $fromYear,
        int $toYear,
        ?int $companyId = null,
        ?int $branchId = null
    ): array {
        $calendarIds = HolidayCalendar::query()
            ->whereBetween('year', [min($fromYear, $toYear), max($fromYear, $toYear)])
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id');
                if ($companyId) {
                    $q->orWhere('company_id', $companyId);
                }
            })
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id');
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->pluck('id');

        if ($calendarIds->isEmpty()) {
            return [];
        }

        $dates = [];
        Holiday::query()
            ->whereIn('holiday_calendar_id', $calendarIds)
            ->where('kind', 'compulsory')
            ->get(['holiday_date'])
            ->each(function (Holiday $holiday) use (&$dates) {
                $dates[$holiday->holiday_date->toDateString()] = true;
            });

        return $dates;
    }

    /**
     * @return list<int> Carbon dayOfWeek values (0=Sun … 6=Sat) that are off on $asOf
     */
    public function weeklyOffWeekdays(?Employee $employee, Carbon $asOf): array
    {
        if (! $employee) {
            return [];
        }

        $assignment = EmployeeShift::query()
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $asOf->toDateString())
            ->where(function ($q) use ($asOf) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $asOf->toDateString());
            })
            ->latest('effective_from')
            ->first();

        if (! $assignment || empty($assignment->weekly_offs)) {
            return [];
        }

        $offs = array_values(array_map('intval', $assignment->weekly_offs));

        // Fixed: every listed weekday is off.
        if (($assignment->weekly_off_mode ?? 'fixed') !== 'rotational') {
            return $offs;
        }

        // Rotational: first value = weekday off; second optional value = every Nth week (default 2).
        $weekday = $offs[0] ?? 0;
        $everyN = max(1, $offs[1] ?? 2);
        if ((int) $asOf->dayOfWeek !== $weekday) {
            return [];
        }

        $anchor = Carbon::parse($assignment->effective_from)->startOfWeek(Carbon::SUNDAY);
        $current = $asOf->copy()->startOfWeek(Carbon::SUNDAY);
        $weeks = (int) $anchor->diffInWeeks($current);
        if ($weeks < 0) {
            return [];
        }

        return ($weeks % $everyN === 0) ? [$weekday] : [];
    }

    public function isCompulsoryHoliday(Carbon $date, ?Employee $employee = null): bool
    {
        $holidays = $this->compulsoryHolidayDates(
            $date->year,
            $date->year,
            $employee?->company_id,
            $employee?->branch_id
        );

        return isset($holidays[$date->toDateString()]);
    }

    public function isNonWorkingDay(Carbon $date, ?Employee $employee = null): bool
    {
        // Only treat Sunday as a default off when no shift assignment exists.
        $assignmentExists = $employee && EmployeeShift::query()
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date->toDateString());
            })
            ->exists();

        if (! $assignmentExists && $date->isSunday()) {
            return true;
        }

        $offs = $this->weeklyOffWeekdays($employee, $date);
        if (in_array((int) $date->dayOfWeek, $offs, true)) {
            return true;
        }

        return $this->isCompulsoryHoliday($date, $employee);
    }
}
