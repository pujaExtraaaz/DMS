<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Attendance;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\LeaveBalance;
use App\Domains\Hrms\Models\LeaveRequest;
use App\Domains\Hrms\Models\PayrollRun;
use App\Http\Controllers\Controller;
use App\Support\ReportExporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrmsDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : now();

        $attendances = Attendance::query()
            ->with('employee')
            ->whereDate('attendance_date', $date->toDateString())
            ->get();

        $byStatus = $attendances->groupBy('status');

        $onLeaveAttendance = $byStatus->get('on_leave', collect())->merge($byStatus->get('half_day', collect()));
        $approvedLeaveToday = LeaveRequest::query()
            ->with(['employee', 'leaveType'])
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $date->toDateString())
            ->whereDate('to_date', '>=', $date->toDateString())
            ->get();

        // Merge approved leave into on-leave list even if attendance row is missing.
        $onLeaveByEmployee = $onLeaveAttendance->keyBy('employee_id');
        foreach ($approvedLeaveToday as $leave) {
            if (! $onLeaveByEmployee->has($leave->employee_id)) {
                $onLeaveByEmployee->put($leave->employee_id, (object) [
                    'employee' => $leave->employee,
                    'check_in' => null,
                    'check_out' => null,
                    'status' => $leave->is_half_day ? 'half_day' : 'on_leave',
                    'leaveType' => $leave->leaveType,
                ]);
            }
        }

        return view('hrms.dashboard.index', [
            'date' => $date,
            'present' => $byStatus->get('present', collect())->merge($byStatus->get('late', collect())),
            'absent' => $byStatus->get('absent', collect()),
            'late' => $byStatus->get('late', collect()),
            'onLeave' => $onLeaveByEmployee->values(),
            'pendingLeave' => LeaveRequest::where('status', 'pending')->count(),
            'pendingRegularisations' => \App\Domains\Hrms\Models\AttendanceRegularisation::where('status', 'pending')->count(),
            'activeEmployees' => Employee::where('status', 'active')->count(),
            'latestPayroll' => PayrollRun::latest()->first(),
            'leaveBalances' => LeaveBalance::with(['employee', 'leaveType'])
                ->where('year', (int) $date->year)
                ->orderByDesc('closing_balance')
                ->limit(15)
                ->get(),
        ]);
    }

    public function attendanceRegister(Request $request): View|StreamedResponse
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rows = Attendance::query()
            ->with('employee')
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->orderBy('attendance_date')
            ->orderBy('employee_id')
            ->get();

        if ($request->input('export') === 'csv') {
            $headers = ['Date', 'Employee', 'Code', 'In', 'Out', 'Status', 'Hours', 'Late', 'OT'];
            $csv = [];
            foreach ($rows as $row) {
                $csv[] = [
                    $row->attendance_date?->toDateString(),
                    $row->employee?->name,
                    $row->employee?->employee_code,
                    $row->check_in,
                    $row->check_out,
                    $row->status,
                    $row->hours_worked,
                    $row->late_minutes,
                    $row->ot_minutes,
                ];
            }

            return ReportExporter::csv("attendance-register-{$year}-{$month}.csv", $headers, $csv);
        }

        return view('hrms.reports.attendance-register', [
            'rows' => $rows,
            'month' => $month,
            'year' => $year,
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
            'filters' => $request->only(['month', 'year', 'employee_id']),
        ]);
    }

    public function leaveUtilisation(Request $request): View|StreamedResponse
    {
        $year = (int) $request->input('year', now()->year);
        $balances = LeaveBalance::with(['employee', 'leaveType'])
            ->where('year', $year)
            ->orderBy('employee_id')
            ->get();

        if ($request->input('export') === 'csv') {
            $headers = ['Employee', 'Type', 'Opening', 'Accrued', 'Used', 'Encashed', 'Closing'];
            $csv = [];
            foreach ($balances as $b) {
                $csv[] = [
                    $b->employee?->name,
                    $b->leaveType?->name,
                    $b->opening_balance,
                    $b->accrued_balance,
                    $b->used_balance,
                    $b->encashed_balance,
                    $b->closing_balance,
                ];
            }

            return ReportExporter::csv("leave-utilisation-{$year}.csv", $headers, $csv);
        }

        return view('hrms.reports.leave-utilisation', [
            'balances' => $balances,
            'year' => $year,
        ]);
    }

    public function lateOt(Request $request): View|StreamedResponse
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rows = Attendance::query()
            ->with('employee')
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) {
                $q->where('late_minutes', '>', 0)->orWhere('ot_minutes', '>', 0);
            })
            ->orderBy('attendance_date')
            ->get();

        if ($request->input('export') === 'csv') {
            $headers = ['Date', 'Employee', 'Late Minutes', 'OT Minutes', 'Status'];
            $csv = [];
            foreach ($rows as $row) {
                $csv[] = [
                    $row->attendance_date?->toDateString(),
                    $row->employee?->name,
                    $row->late_minutes,
                    $row->ot_minutes,
                    $row->status,
                ];
            }

            return ReportExporter::csv("late-ot-{$year}-{$month}.csv", $headers, $csv);
        }

        return view('hrms.reports.late-ot', [
            'rows' => $rows,
            'month' => $month,
            'year' => $year,
        ]);
    }
}
