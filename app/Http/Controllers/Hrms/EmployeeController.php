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
use App\Support\CodeGenerator;
use App\Support\DocumentNumberService;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected DocumentNumberService $documentNumbers,
        protected AuditLogService $auditLogService,
    ) {}

    public function index(Request $request): View
    {
        $query = Employee::query()
            ->with(['department', 'designation', 'branch', 'manager'])
            ->forUserBranch()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['name', 'employee_code', 'email', 'phone'],
            ['department' => ['name'], 'designation' => ['name'], 'branch' => ['name']]
        );

        $allowedSorts = [
            'name' => 'name',
            'employee_code' => 'employee_code',
            'status' => 'status',
            'department' => function ($q, $dir) {
                $q->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
                  ->orderBy('departments.name', $dir)
                  ->select('employees.*');
            },
            'branch' => function ($q, $dir) {
                $q->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
                  ->orderBy('branches.name', $dir)
                  ->select('employees.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('hrms.employees.index', [
            'items' => $items,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'department_id']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        $companyId = auth()->user()?->company_id;

        return view('hrms.employees.form', [
            'item' => new Employee([
                'status' => 'active',
                'is_salesperson' => false,
                'employee_code' => CodeGenerator::forEmployee($companyId),
            ]),
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
            $data['employee_code'] = CodeGenerator::forEmployee($data['company_id'] ?? null);
        }

        $employee = Employee::create($data);
        $this->auditLogService->record($employee, 'created');

        return $this->flashSuccess('Employee created.', 'hrms.employees.index');
    }

    public function show(Employee $employee): View
    {
        $employee->load([
            'department',
            'designation',
            'branch',
            'manager',
            'directReports',
            'user',
            'documents',
            'leaveBalances.leaveType',
            'attendances' => fn ($q) => $q->latest('attendance_date')->limit(10),
            'expenseClaims' => fn ($q) => $q->latest('claim_date')->limit(5),
        ]);

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
            'user_id' => 'nullable|exists:users,id|unique:employees,user_id,'.($employee?->id ?? 'NULL'),
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
        ], [
            'user_id.unique' => 'This user account is already linked to another employee profile.',
        ]);

        if ($employee && !empty($data['manager_id'])) {
            $managerId = (int) $data['manager_id'];
            if ($managerId === (int) $employee->id) {
                throw ValidationException::withMessages([
                    'manager_id' => 'An employee cannot be their own reporting manager.',
                ]);
            }

            // Check circular reporting
            $visited = [(int) $employee->id];
            $current = Employee::find($managerId);
            while ($current && $current->manager_id) {
                if (in_array((int) $current->manager_id, $visited, true)) {
                    throw ValidationException::withMessages([
                        'manager_id' => 'Circular reporting hierarchy detected. The selected manager is already in this employee\'s direct reporting line.',
                    ]);
                }
                $visited[] = (int) $current->id;
                $current = Employee::find($current->manager_id);
            }
        }

        $data['is_salesperson'] = $request->boolean('is_salesperson');
        $data['company_id'] = $data['company_id'] ?? auth()->user()?->company_id;
        $data['branch_id'] = $data['branch_id'] ?? auth()->user()?->branch_id;

        return $data;
    }
}
