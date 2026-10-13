<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\Employee;
use Tally\Models\EmployeeLoan;
use Tally\Payroll\EmployeeLoanService;

class EmployeeLoanController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Employee loans',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::employee-loans.index', [
            'company' => $company,
            'loans' => EmployeeLoan::query()
                ->with('employee')
                ->where('company_id', $company->id)
                ->latest('loan_date')
                ->paginate(25),
            'employees' => Employee::query()->where('company_id', $company->id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, EmployeeLoanService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('acct_employees', 'id')->where('company_id', $company->id)],
            'loan_date' => ['required', 'date'],
            'principal' => ['required', 'numeric', 'gt:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'tenure_months' => ['nullable', 'integer', 'min:1', 'max:600'],
        ]);

        $service->create($company, $data);

        return back()->with('status', 'Employee loan created.');
    }

    public function show(WorkspaceContext $context, EmployeeLoan $employeeLoan): View
    {
        abort_unless($context->company() && $employeeLoan->company_id === $context->company()->id, 404);
        $employeeLoan->load('employee');

        return view('tally::employee-loans.show', [
            'company' => $context->company(),
            'loan' => $employeeLoan,
        ]);
    }
}
