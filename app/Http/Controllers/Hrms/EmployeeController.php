<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Department;
use App\Domains\Hrms\Models\Designation;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        protected DocumentNumberService $documentNumbers,
        protected AuditLogService $auditLogService,
    ) {}

    public function index(Request $request): View
    {
        $items = Employee::query()
            ->with(['department', 'designation', 'branch', 'manager'])
            ->forUserBranch()
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search');
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('employee_code', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('hrms.employees.index', compact('items'));
    }

    public function create(): View
    {
        return view('hrms.employees.form', [
            'item' => new Employee(['status' => 'active', 'is_salesperson' => false]),
            'companies' => Company::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'designations' => Designation::where('is_active', true)->orderBy('name')->get(),
            'managers' => Employee::where('status', 'active')->orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        if (empty($data['employee_code'])) {
            $data['employee_code'] = $this->documentNumbers->next('EMP');
        }

        $employee = Employee::create($data);
        $this->auditLogService->record($employee, 'created');

        return $this->flashSuccess('Employee created.', 'hrms.employees.index');
    }

    public function show(Employee $employee): View
    {
        $employee->load(['department', 'designation', 'branch', 'manager', 'user', 'documents', 'leaveBalances.leaveType']);

        return view('hrms.employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        return view('hrms.employees.form', [
            'item' => $employee,
            'companies' => Company::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'designations' => Designation::where('is_active', true)->orderBy('name')->get(),
            'managers' => Employee::where('status', 'active')->where('id', '!=', $employee->id)->orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $employee->update($this->validated($request, $employee));
        $this->auditLogService->record($employee, 'updated');

        return $this->flashSuccess('Employee updated.', 'hrms.employees.index');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return $this->flashSuccess('Employee deleted.', 'hrms.employees.index');
    }

    protected function validated(Request $request, ?Employee $employee = null): array
    {
        $data = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'manager_id' => 'nullable|exists:employees,id',
            'employee_code' => 'nullable|string|max:40|unique:employees,employee_code,'.($employee?->id ?? 'NULL'),
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'joining_date' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'is_salesperson' => 'boolean',
            'status' => 'required|string|max:30',
            'address' => 'nullable|string',
        ]);
        $data['is_salesperson'] = $request->boolean('is_salesperson');
        $data['company_id'] = $data['company_id'] ?? auth()->user()?->company_id;
        $data['branch_id'] = $data['branch_id'] ?? auth()->user()?->branch_id;

        return $data;
    }
}
