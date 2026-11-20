<?php

namespace App\Domains\Hrms\Services;

use App\Domains\Hrms\Models\Attendance;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\LeaveBalance;
use App\Domains\Hrms\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CompOffService
{
    public function __construct(protected LeaveDayCalculator $leaveDays) {}

    /**
     * Credit compensatory off for a period from OT minutes and holiday work.
     *
     * @return array{credited_days: float, attendance_ids: list<int>, skipped: bool, message?: string}
     */
    public function creditForPeriod(Employee $employee, Carbon $from, Carbon $to): array
    {
        $code = (string) config('hrms.comp_off.leave_type_code', 'COFF');
        $type = LeaveType::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $type) {
            throw new RuntimeException(
                "Comp-off leave type '{$code}' is missing. Create/activate it under Leave Types before crediting."
            );
        }

        $otPerDay = max(60, (int) config('hrms.comp_off.ot_minutes_per_day', 480));
        $creditHoliday = (bool) config('hrms.comp_off.credit_holiday_work', true);

        $rows = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->where(function ($q) {
                $q->where('ot_minutes', '>', 0)
                    ->orWhereIn('status', ['present', 'late', 'half_day']);
            })
            ->get()
            // Idempotency: skip days already credited.
            ->filter(fn (Attendance $row) => ! str_contains((string) $row->notes, '[coff_credited]'));

        $days = 0.0;
        $ids = [];

        foreach ($rows as $row) {
            $date = Carbon::parse($row->attendance_date);
            $holidayWork = $creditHoliday && $this->leaveDays->isCompulsoryHoliday($date, $employee)
                && in_array($row->status, ['present', 'late', 'half_day'], true)
                && ($row->check_in || $row->hours_worked);

            if ($holidayWork) {
                $days += $row->status === 'half_day' ? 0.5 : 1.0;
                $ids[] = $row->id;
                continue;
            }

            $ot = (int) ($row->ot_minutes ?? 0);
            if ($ot >= $otPerDay) {
                $days += round($ot / $otPerDay, 2);
                $ids[] = $row->id;
            } elseif ($ot >= (int) ($otPerDay / 2)) {
                $days += 0.5;
                $ids[] = $row->id;
            }
        }

        if ($days <= 0) {
            return ['credited_days' => 0.0, 'attendance_ids' => [], 'skipped' => false];
        }

        DB::transaction(function () use ($employee, $type, $to, $days, $ids) {
            $year = (int) $to->year;
            $balance = LeaveBalance::query()->firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $type->id,
                    'year' => $year,
                ],
                [
                    'opening_balance' => 0,
                    'carried_forward' => 0,
                    'accrued_balance' => 0,
                    'used_balance' => 0,
                    'encashed_balance' => 0,
                    'closing_balance' => 0,
                ]
            );
            $balance->accrued_balance = (float) ($balance->accrued_balance ?? 0) + $days;
            $balance->closing_balance = round(
                (float) $balance->opening_balance
                + (float) $balance->accrued_balance
                - (float) $balance->used_balance
                - (float) ($balance->encashed_balance ?? 0),
                2
            );
            $balance->save();

            Attendance::query()
                ->whereIn('id', $ids)
                ->get()
                ->each(function (Attendance $row) {
                    $row->update([
                        'notes' => trim(($row->notes ?? '').' [coff_credited]'),
                    ]);
                });
        });

        return ['credited_days' => $days, 'attendance_ids' => array_values(array_unique($ids)), 'skipped' => false];
    }

    public function creditCompanyMonth(int $year, int $month, ?int $companyId = null): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $summary = [];

        $employees = Employee::query()
            ->where('status', 'active')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get();

        foreach ($employees as $employee) {
            $result = $this->creditForPeriod($employee, $start, $end);
            if ($result['credited_days'] > 0) {
                $summary[] = [
                    'employee_id' => $employee->id,
                    'name' => $employee->name,
                    'days' => $result['credited_days'],
                ];
            }
        }

        return $summary;
    }
}
