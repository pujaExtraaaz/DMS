<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\LeaveBalance;
use App\Domains\Hrms\Models\LeaveType;
use App\Http\Controllers\Controller;
use App\Support\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveBalanceController extends Controller
{
    public function __construct(protected AuditLogService $auditLogService) {}

    public function index(Request $request): View
    {
        $year = (int) $request->input('year', now()->year);

        $items = LeaveBalance::query()
            ->with(['employee.department', 'leaveType'])
            ->where('year', $year)
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('leave_type_id'), fn ($q) => $q->where('leave_type_id', $request->leave_type_id))
            ->orderBy('employee_id')
            ->paginate(20)
            ->withQueryString();

        return view('hrms.leave-balances.index', [
            'items' => $items,
            'year' => $year,
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('hrms.leave-balances.form', [
            'item' => new LeaveBalance(['year' => now()->year, 'opening_balance' => 0]),
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2020|max:2035',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        $balance = LeaveBalance::query()->firstOrNew([
            'employee_id' => $data['employee_id'],
            'leave_type_id' => $data['leave_type_id'],
            'year' => $data['year'],
        ]);

        $balance->opening_balance = $data['opening_balance'];
        $balance->used_balance = $balance->used_balance ?? 0;
        $balance->closing_balance = (float) $balance->opening_balance - (float) $balance->used_balance;
        $balance->save();

        $this->auditLogService->record($balance, 'saved');

        return $this->flashSuccess('Leave balance updated.', 'hrms.leave-balances.index', ['year' => $data['year']]);
    }
}

