<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Attendance;
use App\Domains\Hrms\Models\Employee;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $items = Attendance::query()
            ->with('employee')
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('attendance_date', $request->date))
            ->latest('attendance_date')
            ->paginate(20)
            ->withQueryString();

        return view('hrms.attendances.index', [
            'items' => $items,
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('hrms.attendances.form', [
            'item' => new Attendance([
                'attendance_date' => now()->toDateString(),
                'status' => 'present',
            ]),
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|string|max:30',
            'hours_worked' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        $data['recorded_by'] = auth()->id();

        Attendance::updateOrCreate(
            [
                'employee_id' => $data['employee_id'],
                'attendance_date' => $data['attendance_date'],
            ],
            $data
        );

        return $this->flashSuccess('Attendance saved.', 'hrms.attendances.index');
    }
}
