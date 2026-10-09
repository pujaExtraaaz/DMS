<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\LeaveBalance;
use App\Domains\Hrms\Models\LeaveRequest;
use App\Domains\Hrms\Models\LeaveType;
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

    public function __construct(protected AuditLogService $auditLogService) {}

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
        ]);

        $isHalfDay = $request->boolean('is_half_day');
        if ($isHalfDay) {
            $days = 0.5;
            $data['to_date'] = $data['from_date'];
        } else {
            $start = Carbon::parse($data['from_date']);
            $end = Carbon::parse($data['to_date']);
            $days = 0;
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                if (! $date->isSunday()) {
                    $days++;
                }
            }
            if ($days == 0) {
                $days = 1;
            }
        }

        // Validate available leave balance
        $year = (int) Carbon::parse($data['from_date'])->format('Y');
        $balance = LeaveBalance::where('employee_id', $data['employee_id'])
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('year', $year)
            ->first();

        $leaveType = LeaveType::find($data['leave_type_id']);
        $available = $balance ? (float) $balance->closing_balance : (float) ($leaveType?->default_days ?? 0);

        if ($available < $days) {
            return back()->withInput()->withErrors([
                'leave_type_id' => "Insufficient leave balance. Available: {$available} days, Requested: {$days} days.",
            ]);
        }

        $data['days'] = $days;
        $data['status'] = 'pending';
        unset($data['is_half_day']);

        $leave = LeaveRequest::create($data);
        $this->auditLogService->record($leave, 'created');

        return $this->flashSuccess('Leave request submitted.', 'hrms.leave-requests.index');
    }

    public function approve(Request $request, LeaveRequest $leave_request): RedirectResponse
    {
        if ($leave_request->status !== 'pending') {
            return $this->flashError('Only pending leave can be approved.');
        }

        try {
            DB::transaction(function () use ($request, $leave_request) {
                $year = (int) $leave_request->from_date->format('Y');
                $balance = LeaveBalance::query()->firstOrCreate(
                    [
                        'employee_id' => $leave_request->employee_id,
                        'leave_type_id' => $leave_request->leave_type_id,
                        'year' => $year,
                    ],
                    [
                        'opening_balance' => $leave_request->leaveType?->default_days ?? 0,
                        'used_balance' => 0,
                        'closing_balance' => $leave_request->leaveType?->default_days ?? 0,
                    ]
                );

                if ((float) $balance->closing_balance < (float) $leave_request->days) {
                    throw new \RuntimeException("Insufficient leave balance remaining ({$balance->closing_balance} days available).");
                }

                $leave_request->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'approval_notes' => $request->input('approval_notes'),
                ]);

                $balance->used_balance = (float) $balance->used_balance + (float) $leave_request->days;
                $balance->closing_balance = (float) $balance->opening_balance - (float) $balance->used_balance;
                $balance->save();

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

        DB::transaction(function () use ($request, $leave_request) {
            $wasApproved = $leave_request->status === 'approved';

            $leave_request->update([
                'status' => 'cancelled',
                'approval_notes' => trim(($leave_request->approval_notes ?? '') . ' [Cancelled]'),
            ]);

            if ($wasApproved) {
                $year = (int) $leave_request->from_date->format('Y');
                $balance = LeaveBalance::where('employee_id', $leave_request->employee_id)
                    ->where('leave_type_id', $leave_request->leave_type_id)
                    ->where('year', $year)
                    ->first();

                if ($balance) {
                    $balance->used_balance = max(0, (float) $balance->used_balance - (float) $leave_request->days);
                    $balance->closing_balance = (float) $balance->opening_balance - (float) $balance->used_balance;
                    $balance->save();
                }
            }

            $this->auditLogService->record($leave_request, 'cancelled');
        });

        return $this->flashSuccess('Leave request cancelled and balance restored.', 'hrms.leave-requests.index');
    }
}
