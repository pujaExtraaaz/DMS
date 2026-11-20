<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Department;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\EmployeeShift;
use App\Domains\Hrms\Models\Shift;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(): View
    {
        $items = Shift::query()->with('department')->orderBy('name')->paginate(20);
        $assignments = EmployeeShift::query()
            ->with(['employee', 'shift'])
            ->latest('effective_from')
            ->limit(20)
            ->get();

        return view('hrms.shifts.index', compact('items', 'assignments'));
    }

    public function create(): View
    {
        return view('hrms.shifts.form', [
            'item' => new Shift([
                'start_time' => '09:30:00',
                'end_time' => '18:30:00',
                'grace_minutes' => 15,
                'break_minutes' => 60,
                'half_day_minutes' => 240,
                'full_day_minutes' => 480,
                'is_active' => true,
            ]),
            'companies' => Company::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Shift::create($this->validated($request));

        return $this->flashSuccess('Shift created.', 'hrms.shifts.index');
    }

    public function edit(Shift $shift): View
    {
        return view('hrms.shifts.form', [
            'item' => $shift,
            'companies' => Company::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $shift->update($this->validated($request));

        return $this->flashSuccess('Shift updated.', 'hrms.shifts.index');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $shift->delete();

        return $this->flashSuccess('Shift deleted.', 'hrms.shifts.index');
    }

    public function assign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_id' => 'required|exists:shifts,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'weekly_off_mode' => 'required|in:fixed,rotational',
            'weekly_offs' => 'nullable|array',
            'weekly_offs.*' => 'integer|min:0|max:6',
            'rotational_every_n' => 'nullable|integer|min:1|max:12',
        ]);

        $offs = array_values(array_map('intval', $data['weekly_offs'] ?? [0]));
        if (($data['weekly_off_mode'] ?? 'fixed') === 'rotational') {
            $weekday = $offs[0] ?? 0;
            $everyN = (int) ($data['rotational_every_n'] ?? 2);
            $offs = [$weekday, max(1, $everyN)];
        }

        EmployeeShift::create([
            'employee_id' => $data['employee_id'],
            'shift_id' => $data['shift_id'],
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
            'weekly_off_mode' => $data['weekly_off_mode'],
            'weekly_offs' => $offs,
        ]);

        return $this->flashSuccess('Shift assigned to employee.', 'hrms.shifts.index');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'name' => 'required|string|max:120',
            'code' => 'nullable|string|max:30',
            'start_time' => 'required',
            'end_time' => 'required',
            'break_minutes' => 'nullable|integer|min:0',
            'grace_minutes' => 'nullable|integer|min:0',
            'half_day_minutes' => 'nullable|integer|min:1',
            'full_day_minutes' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
