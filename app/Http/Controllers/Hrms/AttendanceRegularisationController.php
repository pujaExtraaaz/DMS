<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Attendance;
use App\Domains\Hrms\Models\AttendanceRegularisation;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Services\AttendanceCalculator;
use App\Http\Controllers\Controller;
use App\Support\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceRegularisationController extends Controller
{
    public function __construct(
        protected AttendanceCalculator $calculator,
        protected AuditLogService $auditLogService
    ) {}

    public function index(): View
    {
        $items = AttendanceRegularisation::query()
            ->with(['employee', 'approver'])
            ->latest()
            ->paginate(20);

        return view('hrms.regularisations.index', compact('items'));
    }

    public function create(): View
    {
        return view('hrms.regularisations.form', [
            'item' => new AttendanceRegularisation,
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'type' => 'required|in:missing_punch,correction,other',
            'requested_check_in' => 'nullable',
            'requested_check_out' => 'nullable',
            'reason' => 'nullable|string',
        ]);
        $data['status'] = 'pending';
        AttendanceRegularisation::create($data);

        return $this->flashSuccess('Regularisation request submitted.', 'hrms.regularisations.index');
    }

    public function approve(Request $request, AttendanceRegularisation $regularisation): RedirectResponse
    {
        if ($regularisation->status !== 'pending') {
            return $this->flashError('Only pending requests can be approved.');
        }

        DB::transaction(function () use ($request, $regularisation) {
            $employee = $regularisation->employee;
            $date = Carbon::parse($regularisation->attendance_date);
            $attendance = $this->calculator->upsertDay(
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
                'approval_notes' => $request->input('approval_notes'),
            ]);
            $this->auditLogService->record($regularisation, 'approved');
        });

        return $this->flashSuccess('Regularisation approved and attendance updated.', 'hrms.regularisations.index');
    }

    public function reject(Request $request, AttendanceRegularisation $regularisation): RedirectResponse
    {
        if ($regularisation->status !== 'pending') {
            return $this->flashError('Only pending requests can be rejected.');
        }
        $regularisation->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->input('approval_notes'),
        ]);

        return $this->flashSuccess('Regularisation rejected.', 'hrms.regularisations.index');
    }
}
