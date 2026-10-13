<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\EmployeeAdvance;
use Tally\Payables\EmployeeAdvanceService;

class EmployeeAdvanceController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Employee advances', 'message' => 'Select a company first.']);
        }

        return view('tally::employee-advances.index', [
            'company' => $company,
            'advances' => EmployeeAdvance::query()
                ->with('employeeLedger')
                ->where('company_id', $company->id)
                ->latest('advance_date')
                ->paginate(25),
            'ledgers' => $company->ledgers()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, EmployeeAdvanceService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'employee_ledger_id' => ['required', 'integer'],
            'advance_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['open', 'settled', 'cancelled'])],
        ]);

        $service->save($company, $data);

        return back()->with('status', 'Employee advance saved.');
    }
}
