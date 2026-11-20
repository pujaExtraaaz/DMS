<?php

namespace App\Domains\Hrms\Services;

use App\Domains\Hrms\Models\Attendance;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\LeaveEncashment;
use App\Domains\Hrms\Models\LeaveRequest;
use App\Domains\Hrms\Models\LeaveType;
use App\Domains\Hrms\Models\PayrollInput;
use App\Domains\Hrms\Models\PayrollRun;
use App\Domains\Hrms\Models\PayrollRunLine;
use App\Domains\Hrms\Models\SalaryStructure;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class PayrollCalculator
{
    public function __construct(
        protected LeaveDayCalculator $leaveDays,
        protected AttendanceCalculator $attendanceCalculator,
        protected StatutoryDeductionService $statutory
    ) {}

    public function computeRun(PayrollRun $run): PayrollRun
    {
        if ($run->isLocked()) {
            throw new \RuntimeException('Payroll run is locked.');
        }

        $start = Carbon::create($run->year, $run->month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $employees = Employee::query()
            ->where('status', 'active')
            ->when($run->company_id, fn ($q) => $q->where('company_id', $run->company_id))
            ->when($run->branch_id, fn ($q) => $q->where('branch_id', $run->branch_id))
            ->with(['salaryStructure.lines.payHead'])
            ->get();

        DB::transaction(function () use ($run, $employees, $start, $end) {
            $run->lines()->delete();

            foreach ($employees as $employee) {
                $line = $this->computeEmployee($employee, $start, $end);
                PayrollRunLine::create(array_merge($line, [
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                ]));

                PayrollInput::query()->updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'year' => $run->year,
                        'month' => $run->month,
                    ],
                    [
                        'basic' => $line['earnings_json']['BASIC'] ?? 0,
                        'allowances' => ($line['gross_earnings'] - ($line['earnings_json']['BASIC'] ?? 0)),
                        'deductions' => $line['total_deductions'],
                        'incentives' => $line['earnings_json']['INCENTIVES'] ?? 0,
                        'advances' => $line['deductions_json']['ADVANCE'] ?? 0,
                        'net_payable' => $line['net_payable'],
                        'status' => 'draft',
                    ]
                );
            }
        });

        return $run->fresh(['lines.employee']);
    }

    /**
     * @return array<string, mixed>
     */
    public function computeEmployee(Employee $employee, Carbon $start, Carbon $end): array
    {
        $structure = SalaryStructure::query()
            ->with('lines.payHead')
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->where(function ($q) use ($end) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $end->toDateString());
            })
            ->where(function ($q) use ($start) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $start->toDateString());
            })
            ->latest('effective_from')
            ->first();

        $workingDays = 0;
        $present = 0;
        $leaveDays = 0;
        $lwpDays = 0;
        $otMinutes = 0;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            /** @var Carbon $date */
            if ($this->leaveDays->isNonWorkingDay($date, $employee)) {
                continue;
            }
            $workingDays++;

            $attendance = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date->toDateString())
                ->first();

            $leave = LeaveRequest::query()
                ->with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('from_date', '<=', $date->toDateString())
                ->whereDate('to_date', '>=', $date->toDateString())
                ->first();

            if ($leave) {
                $code = strtoupper((string) ($leave->leaveType?->code ?? ''));
                if ($code === 'LWP' || ! ($leave->leaveType?->is_paid ?? true)) {
                    $lwpDays += $leave->is_half_day ? 0.5 : 1;
                } else {
                    $leaveDays += $leave->is_half_day ? 0.5 : 1;
                    $present += $leave->is_half_day ? 0.5 : 1;
                }
                continue;
            }

            if (! $attendance || in_array($attendance->status, ['absent'], true)) {
                $lwpDays++;
                continue;
            }

            if ($attendance->status === 'half_day') {
                $present += 0.5;
                $lwpDays += 0.5;
            } else {
                $present++;
            }
            $otMinutes += (int) ($attendance->ot_minutes ?? 0);
        }

        $payableDays = max(0, $workingDays - $lwpDays);
        $earnings = [];
        $deductions = [];

        if ($structure) {
            foreach ($structure->lines as $line) {
                $code = strtoupper((string) ($line->payHead?->code ?? 'OTHER'));
                $amount = (float) $line->amount;
                if ($workingDays > 0 && $payableDays < $workingDays) {
                    $amount = round($amount * ($payableDays / $workingDays), 2);
                }
                if (($line->payHead?->type ?? 'earning') === 'deduction') {
                    $deductions[$code] = ($deductions[$code] ?? 0) + $amount;
                } else {
                    $earnings[$code] = ($earnings[$code] ?? 0) + $amount;
                }
            }
            // Legacy columns if no lines
            if ($structure->lines->isEmpty()) {
                $factor = $workingDays > 0 ? ($payableDays / $workingDays) : 1;
                $earnings['BASIC'] = round((float) $structure->basic * $factor, 2);
                $earnings['HRA'] = round((float) $structure->hra * $factor, 2);
                $earnings['ALLOWANCES'] = round((float) $structure->allowances * $factor, 2);
                $deductions['OTHER_DED'] = round((float) $structure->deductions * $factor, 2);
            }
        }

        // OT at simple hourly = basic/hourly * OT hours (basic monthly / working days / 8)
        if ($otMinutes > 0 && isset($earnings['BASIC']) && $workingDays > 0) {
            $hourly = ((float) $earnings['BASIC'] / max(1, $payableDays ?: $workingDays)) / 8;
            $earnings['OT'] = round($hourly * ($otMinutes / 60), 2);
        }

        // Approved leave encashments approved in this payroll month
        $encashments = LeaveEncashment::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('approved_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                    ->orWhere(function ($q2) use ($start) {
                        $q2->whereNull('approved_at')
                            ->where('year', (int) $start->year)
                            ->whereMonth('updated_at', (int) $start->month);
                    });
            })
            ->get();

        $encashAmount = 0.0;
        foreach ($encashments as $enc) {
            $amt = (float) ($enc->amount ?? 0);
            if ($amt <= 0) {
                $basicMonthly = (float) ($structure?->basic ?? ($earnings['BASIC'] ?? 0));
                // Prefer unprorated structure basic when available
                if ($structure && $structure->lines->isNotEmpty()) {
                    $basicLine = $structure->lines->first(fn ($l) => strtoupper((string) ($l->payHead?->code ?? '')) === 'BASIC');
                    $basicMonthly = (float) ($basicLine?->amount ?? $basicMonthly);
                }
                $amt = round(($basicMonthly / 30) * (float) $enc->days, 2);
            }
            $encashAmount += $amt;
        }
        if ($encashAmount > 0) {
            $earnings['ENCASHMENT'] = ($earnings['ENCASHMENT'] ?? 0) + round($encashAmount, 2);
        }

        $deductions = $this->statutory->apply($earnings, $deductions);

        $gross = array_sum($earnings);
        $totalDed = array_sum($deductions);

        return [
            'payable_days' => (int) round($payableDays),
            'present_days' => (int) round($present),
            'leave_days' => (int) round($leaveDays),
            'lwp_days' => (int) round($lwpDays),
            'ot_minutes' => $otMinutes,
            'gross_earnings' => round($gross, 2),
            'total_deductions' => round($totalDed, 2),
            'net_payable' => round($gross - $totalDed, 2),
            'earnings_json' => $earnings,
            'deductions_json' => $deductions,
        ];
    }
}
