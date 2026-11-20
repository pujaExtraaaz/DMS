<?php

namespace App\Http\Controllers\Ess;

use App\Domains\Hrms\Models\Attendance;
use App\Domains\Hrms\Models\AttendanceRegularisation;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\HolidayCalendar;
use App\Domains\Hrms\Models\LeaveBalance;
use App\Domains\Hrms\Models\LeaveRequest;
use App\Domains\Hrms\Models\LeaveType;
use App\Domains\Hrms\Models\PayrollRun;
use App\Domains\Hrms\Models\PayrollRunLine;
use App\Domains\Hrms\Services\AttendanceCalculator;
use App\Domains\Hrms\Services\LeaveAttendanceSyncService;
use App\Domains\Hrms\Services\LeaveBalanceService;
use App\Domains\Hrms\Services\LeaveDayCalculator;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EssPortalController extends Controller
{
    public function __construct(
        protected LeaveDayCalculator $dayCalculator,
        protected LeaveBalanceService $balanceService,
        protected LeaveAttendanceSyncService $attendanceSync,
        protected AttendanceCalculator $attendanceCalculator
    ) {}

    protected function employeeOrAbort()
    {
        $employee = auth()->user()?->employee;
        abort_unless($employee, 403, 'No employee profile linked to this user.');

        return $employee;
    }

    public function home(): View
    {
        $employee = $this->employeeOrAbort();

        return view('ess.home', [
            'employee' => $employee,
            'balances' => LeaveBalance::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('year', (int) now()->year)
                ->get(),
            'pendingLeave' => LeaveRequest::where('employee_id', $employee->id)->where('status', 'pending')->count(),
            'recentAttendance' => Attendance::where('employee_id', $employee->id)->latest('attendance_date')->limit(7)->get(),
        ]);
    }

    public function holidays(): View
    {
        $employee = $this->employeeOrAbort();
        $year = (int) request('year', now()->year);

        $calendars = HolidayCalendar::query()
            ->with('holidays')
            ->where('year', $year)
            ->where('is_published', true)
            ->where(function ($q) use ($employee) {
                $q->whereNull('company_id');
                if ($employee->company_id) {
                    $q->orWhere('company_id', $employee->company_id);
                }
            })
            ->where(function ($q) use ($employee) {
                $q->whereNull('branch_id');
                if ($employee->branch_id) {
                    $q->orWhere('branch_id', $employee->branch_id);
                }
            })
            ->get();

        return view('ess.holidays', compact('employee', 'calendars', 'year'));
    }

    public function leave(): View
    {
        $employee = $this->employeeOrAbort();

        return view('ess.leave', [
            'employee' => $employee,
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
            'balances' => LeaveBalance::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('year', (int) now()->year)
                ->get(),
            'requests' => LeaveRequest::with('leaveType')
                ->where('employee_id', $employee->id)
                ->latest()
                ->limit(30)
                ->get(),
        ]);
    }

    public function storeLeave(Request $request): RedirectResponse
    {
        $employee = $this->employeeOrAbort();
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'nullable|string',
            'is_half_day' => 'boolean',
            'is_short_leave' => 'boolean',
            'short_leave_hours' => 'nullable|numeric|min:0.5|max:8',
            'session' => 'nullable|in:first_half,second_half',
        ]);

        $isHalfDay = $request->boolean('is_half_day');
        $isShortLeave = $request->boolean('is_short_leave');
        $days = $this->dayCalculator->countDays(
            $data['from_date'],
            $data['to_date'],
            $employee,
            $isHalfDay,
            $isShortLeave,
            (float) ($data['short_leave_hours'] ?? 2)
        );

        if ($days <= 0) {
            return back()->withInput()->withErrors(['from_date' => 'No working days in the selected range.']);
        }

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $data['leave_type_id'],
            'from_date' => $data['from_date'],
            'to_date' => $isHalfDay || $isShortLeave ? $data['from_date'] : $data['to_date'],
            'days' => $days,
            'is_half_day' => $isHalfDay,
            'is_short_leave' => $isShortLeave,
            'short_leave_hours' => $isShortLeave ? ($data['short_leave_hours'] ?? 2) : null,
            'session' => $data['session'] ?? null,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return $this->flashSuccess('Leave request submitted.', 'ess.leave');
    }

    public function attendance(): View
    {
        $employee = $this->employeeOrAbort();
        $month = (int) request('month', now()->month);
        $year = (int) request('year', now()->year);
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return view('ess.attendance', [
            'employee' => $employee,
            'rows' => Attendance::where('employee_id', $employee->id)
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('attendance_date')
                ->get(),
            'regularisations' => AttendanceRegularisation::where('employee_id', $employee->id)->latest()->limit(10)->get(),
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function storeRegularisation(Request $request): RedirectResponse
    {
        $employee = $this->employeeOrAbort();
        $data = $request->validate([
            'attendance_date' => 'required|date',
            'type' => 'required|in:missing_punch,correction,other',
            'requested_check_in' => 'nullable',
            'requested_check_out' => 'nullable',
            'reason' => 'nullable|string',
        ]);
        $data['employee_id'] = $employee->id;
        $data['status'] = 'pending';
        AttendanceRegularisation::create($data);

        return $this->flashSuccess('Regularisation submitted.', 'ess.attendance');
    }

    public function payslips(): View
    {
        $employee = $this->employeeOrAbort();
        $lines = PayrollRunLine::query()
            ->with('run')
            ->where('employee_id', $employee->id)
            ->whereHas('run', fn ($q) => $q->whereIn('status', ['approved', 'locked']))
            ->latest()
            ->limit(24)
            ->get();

        return view('ess.payslips', compact('employee', 'lines'));
    }

    public function payslip(PayrollRunLine $line): View
    {
        $employee = $this->employeeOrAbort();
        abort_unless((int) $line->employee_id === (int) $employee->id, 403);
        $line->load(['employee.department', 'run.company', 'run.branch']);
        abort_unless(in_array($line->run?->status, ['approved', 'locked'], true), 404);

        return view('hrms.payroll-runs.payslip', [
            'run' => $line->run,
            'line' => $line,
            'pdfRoute' => route('ess.payslips.pdf', $line),
        ]);
    }

    public function payslipPdf(PayrollRunLine $line): Response
    {
        $employee = $this->employeeOrAbort();
        abort_unless((int) $line->employee_id === (int) $employee->id, 403);
        $line->load(['employee.department', 'run.company', 'run.branch']);
        abort_unless(in_array($line->run?->status, ['approved', 'locked'], true), 404);

        $run = $line->run;
        $pdf = Pdf::loadView('hrms.payroll-runs.payslip-pdf', [
            'run' => $run,
            'line' => $line,
        ])->setPaper('a4', 'portrait');

        $code = $employee->employee_code ?? $employee->id;

        return $pdf->download("payslip-{$run->year}-{$run->month}-{$code}.pdf");
    }

    public function approvals(): View
    {
        $manager = $this->employeeOrAbort();
        $reportIds = Employee::query()->where('manager_id', $manager->id)->pluck('id');

        return view('ess.approvals', [
            'employee' => $manager,
            'leaveRequests' => LeaveRequest::with(['employee', 'leaveType'])
                ->whereIn('employee_id', $reportIds)
                ->where('status', 'pending')
                ->latest()
                ->get(),
            'regularisations' => AttendanceRegularisation::with('employee')
                ->whereIn('employee_id', $reportIds)
                ->where('status', 'pending')
                ->latest()
                ->get(),
        ]);
    }

    public function approveLeave(LeaveRequest $leave_request): RedirectResponse
    {
        $manager = $this->employeeOrAbort();
        $leave_request->loadMissing(['employee', 'leaveType']);
        abort_unless((int) $leave_request->employee?->manager_id === (int) $manager->id, 403);
        if ($leave_request->status !== 'pending') {
            return $this->flashError('Only pending leave can be approved.');
        }

        try {
            DB::transaction(function () use ($leave_request) {
                $year = (int) $leave_request->from_date->format('Y');
                $leaveType = $leave_request->leaveType;
                $isLwp = strtoupper((string) ($leaveType?->code ?? '')) === 'LWP' || ! ($leaveType?->is_paid ?? true);

                if (! $isLwp) {
                    $balance = LeaveBalance::query()->firstOrCreate(
                        [
                            'employee_id' => $leave_request->employee_id,
                            'leave_type_id' => $leave_request->leave_type_id,
                            'year' => $year,
                        ],
                        [
                            'opening_balance' => $leaveType?->default_days ?? 0,
                            'used_balance' => 0,
                            'closing_balance' => $leaveType?->default_days ?? 0,
                        ]
                    );
                    if ((float) $balance->closing_balance < (float) $leave_request->days) {
                        throw new \RuntimeException("Insufficient leave balance remaining ({$balance->closing_balance} days available).");
                    }
                    $balance->used_balance = (float) $balance->used_balance + (float) $leave_request->days;
                    $this->balanceService->recalculateClosing($balance);
                }

                $leave_request->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
                $this->attendanceSync->markApproved($leave_request->fresh(['employee', 'leaveType']));
            });
        } catch (\RuntimeException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Leave approved.', 'ess.approvals');
    }

    public function rejectLeave(LeaveRequest $leave_request): RedirectResponse
    {
        $manager = $this->employeeOrAbort();
        $leave_request->loadMissing('employee');
        abort_unless((int) $leave_request->employee?->manager_id === (int) $manager->id, 403);
        if ($leave_request->status !== 'pending') {
            return $this->flashError('Only pending leave can be rejected.');
        }
        $leave_request->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $this->flashSuccess('Leave rejected.', 'ess.approvals');
    }

    public function approveRegularisation(AttendanceRegularisation $regularisation): RedirectResponse
    {
        $manager = $this->employeeOrAbort();
        $regularisation->loadMissing('employee');
        abort_unless((int) $regularisation->employee?->manager_id === (int) $manager->id, 403);
        if ($regularisation->status !== 'pending') {
            return $this->flashError('Only pending regularisation can be approved.');
        }

        DB::transaction(function () use ($regularisation) {
            $employee = $regularisation->employee;
            $date = Carbon::parse($regularisation->attendance_date);
            $attendance = $this->attendanceCalculator->upsertDay(
                $employee,
                $date,
                $regularisation->requested_check_in,
                $regularisation->requested_check_out,
                'manual'
            );
            $attendance->update(['is_regularised' => true, 'recorded_by' => auth()->id()]);
            $regularisation->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        return $this->flashSuccess('Regularisation approved.', 'ess.approvals');
    }

    public function rejectRegularisation(AttendanceRegularisation $regularisation): RedirectResponse
    {
        $manager = $this->employeeOrAbort();
        $regularisation->loadMissing('employee');
        abort_unless((int) $regularisation->employee?->manager_id === (int) $manager->id, 403);
        if ($regularisation->status !== 'pending') {
            return $this->flashError('Only pending regularisation can be rejected.');
        }
        $regularisation->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $this->flashSuccess('Regularisation rejected.', 'ess.approvals');
    }
}
