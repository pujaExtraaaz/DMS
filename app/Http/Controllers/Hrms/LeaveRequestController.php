<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\LeaveBalance;
use App\Domains\Hrms\Models\LeaveRequest;
use App\Domains\Hrms\Models\LeaveType;
use App\Http\Controllers\Controller;
use App\Support\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function __construct(protected AuditLogService $auditLogService) {}

    public function index(Request $request): View
    {
        $items = LeaveRequest::query()
            ->with(['employee', 'leaveType', 'approver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('hrms.leave-requests.index', compact('items'));
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
        ]);

        $days = Carbon::parse($data['from_date'])->diffInDays(Carbon::parse($data['to_date'])) + 1;
        $data['days'] = $days;
        $data['status'] = 'pending';

        $leave = LeaveRequest::create($data);
        $this->auditLogService->record($leave, 'created');

        return $this->flashSuccess('Leave request submitted.', 'hrms.leave-requests.index');
    }

    public function approve(Request $request, LeaveRequest $leave_request): RedirectResponse
    {
        if ($leave_request->status !== 'pending') {
            return $this->flashError('Only pending leave can be approved.');
        }

        DB::transaction(function () use ($request, $leave_request) {
            $leave_request->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->input('approval_notes'),
            ]);

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

            $balance->used_balance = (float) $balance->used_balance + (float) $leave_request->days;
            $balance->closing_balance = (float) $balance->opening_balance - (float) $balance->used_balance;
            $balance->save();

            $this->auditLogService->record($leave_request, 'approved');
        });

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
        $this->auditLogService->record($leave_request, 'updated');

        return $this->flashSuccess('Leave rejected.', 'hrms.leave-requests.index');
    }
}
