<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\LeaveBalance;
use App\Domains\Hrms\Models\LeaveRequest;
use App\Domains\Hrms\Models\LeaveType;
use App\Domains\Hrms\Services\LeaveAttendanceSyncService;
use App\Domains\Hrms\Services\LeaveBalanceService;
use App\Domains\Hrms\Services\LeaveDayCalculator;
use App\Http\Controllers\Controller;
use App\Support\AuditLogService;
use App\Support\Traits\SortableAndSearchable;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected AuditLogService $auditLogService,
        protected LeaveDayCalculator $dayCalculator,
        protected LeaveBalanceService $balanceService,
        protected LeaveAttendanceSyncService $attendanceSync
    ) {}

    public function index(Request $request): View
    {
        $query = LeaveRequest::query()
            ->with(['employee', 'leaveType', 'approver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['reason', 'status'],
            ['employee' => ['name', 'employee_code'], 'leaveType' => ['name']]
        );

        $allowedSorts = [
            'from_date' => 'from_date',
            'days' => 'days',
            'status' => 'status',
            'created_at' => 'created_at',
            'employee' => function ($q, $dir) {
                $q->join('employees', 'leave_requests.employee_id', '=', 'employees.id')
                  ->orderBy('employees.name', $dir)
                  ->select('leave_requests.*');
            },
            'leave_type' => function ($q, $dir) {
                $q->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
                  ->orderBy('leave_types.name', $dir)
                  ->select('leave_requests.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'created_at',
            defaultDirection: 'desc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('hrms.leave-requests.index', [
            'items' => $items,
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'employee_id']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('hrms.leave-requests.form', [
            'item' => new LeaveRequest,
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'nullable|string',
            'is_half_day' => 'boolean',
            'is_short_leave' => 'boolean',
            'short_leave_hours' => 'nullable|numeric|min:0.5|max:8',
            'session' => 'nullable|in:first_half,second_half',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $isHalfDay = $request->boolean('is_half_day');
        $isShortLeave = $request->boolean('is_short_leave');
        $shortHours = (float) ($data['short_leave_hours'] ?? 2);

        $policy = $this->balanceService->resolvePolicy($employee, (int) $data['leave_type_id']);
        if ($isHalfDay && $policy && ! $policy->allow_half_day) {
            return back()->withInput()->withErrors(['is_half_day' => 'Half-day leave is not allowed under the applicable policy.']);
        }
        if ($isShortLeave && $policy && ! $policy->allow_short_leave) {
            return back()->withInput()->withErrors(['is_short_leave' => 'Short leave is not allowed under the applicable policy.']);
        }

        $days = $this->dayCalculator->countDays(
            $data['from_date'],
            $data['to_date'],
            $employee,
            $isHalfDay,
            $isShortLeave,
            $shortHours
        );

        if ($days <= 0) {
            return back()->withInput()->withErrors([
                'from_date' => 'Selected dates fall entirely on weekly offs / holidays. No leave days to apply.',
            ]);
        }

        $year = (int) Carbon::parse($data['from_date'])->format('Y');
        $balance = LeaveBalance::where('employee_id', $data['employee_id'])
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('year', $year)
            ->first();

        $leaveType = LeaveType::find($data['leave_type_id']);
        $available = $balance ? (float) $balance->closing_balance : (float) ($leaveType?->default_days ?? 0);

        // LWP can exceed balance
        $isLwp = strtoupper((string) ($leaveType?->code ?? '')) === 'LWP' || ! ($leaveType?->is_paid ?? true);
        if (! $isLwp && $available < $days) {
            return back()->withInput()->withErrors([
                'leave_type_id' => "Insufficient leave balance. Available: {$available} days, Requested: {$days} days.",
            ]);
        }

        $leave = LeaveRequest::create([
            'employee_id' => $data['employee_id'],
            'leave_type_id' => $data['leave_type_id'],
            'from_date' => $data['from_date'],
            'to_date' => $isHalfDay || $isShortLeave ? $data['from_date'] : $data['to_date'],
            'days' => $days,
            'is_half_day' => $isHalfDay,
            'is_short_leave' => $isShortLeave,
            'short_leave_hours' => $isShortLeave ? $shortHours : null,
            'session' => $data['session'] ?? null,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);
        $this->auditLogService->record($leave, 'created');

        return $this->flashSuccess('Leave request submitted.', 'hrms.leave-requests.index');
    }

    public function approve(Request $request, LeaveRequest $leave_request): RedirectResponse
    {
        if ($leave_request->status !== 'pending') {
            return $this->flashError('Only pending leave can be approved.');
        }

        if (! $this->canApprove($leave_request)) {
            return $this->flashError('Only the reporting manager or HR can approve this leave.');
        }

        try {
            DB::transaction(function () use ($request, $leave_request) {
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
                    'approval_notes' => $request->input('approval_notes'),
                ]);

                $this->attendanceSync->markApproved($leave_request->fresh(['employee', 'leaveType']));
                $this->auditLogService->record($leave_request, 'approved');
            });
        } catch (\RuntimeException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Leave approved.', 'hrms.leave-requests.index');
    }

    public function reject(Request $request, LeaveRequest $leave_request): RedirectResponse
    {
        if ($leave_request->status !== 'pending') {
            return $this->flashError('Only pending leave can be rejected.');
        }

        if (! $this->canApprove($leave_request)) {
            return $this->flashError('Only the reporting manager or HR can reject this leave.');
        }

        $leave_request->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->input('approval_notes'),
        ]);
        $this->auditLogService->record($leave_request, 'rejected');

        return $this->flashSuccess('Leave rejected.', 'hrms.leave-requests.index');
    }

    public function cancel(Request $request, LeaveRequest $leave_request): RedirectResponse
    {
        if (! in_array($leave_request->status, ['pending', 'approved'])) {
            return $this->flashError('Only pending or approved leave requests can be cancelled.');
        }

        DB::transaction(function () use ($leave_request) {
            $wasApproved = $leave_request->status === 'approved';

            $leave_request->update([
                'status' => 'cancelled',
                'approval_notes' => trim(($leave_request->approval_notes ?? '').' [Cancelled]'),
            ]);

            if ($wasApproved) {
                $year = (int) $leave_request->from_date->format('Y');
                $balance = LeaveBalance::where('employee_id', $leave_request->employee_id)
                    ->where('leave_type_id', $leave_request->leave_type_id)
                    ->where('year', $year)
                    ->first();

                if ($balance) {
                    $balance->used_balance = max(0, (float) $balance->used_balance - (float) $leave_request->days);
                    $this->balanceService->recalculateClosing($balance);
                }

                $this->attendanceSync->clearCancelled($leave_request);
            }

            $this->auditLogService->record($leave_request, 'cancelled');
        });

        return $this->flashSuccess('Leave request cancelled and balance restored.', 'hrms.leave-requests.index');
    }

    protected function canApprove(LeaveRequest $leave): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyPermission') && $user->hasAnyPermission(['hrms.manage', 'hrms.edit'])) {
            return true;
        }
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'client-admin'])) {
            return true;
        }

        $employee = $leave->employee()->with('manager.user')->first();
        $managerUserId = $employee?->manager?->user_id;

        return $managerUserId && (int) $managerUserId === (int) $user->id;
    }
}
