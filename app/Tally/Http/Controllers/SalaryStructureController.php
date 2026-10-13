<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\PayHead;
use Tally\Models\SalaryStructure;
use Tally\Payroll\SalaryStructureService;

class SalaryStructureController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Salary structures',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::salary-structures.index', [
            'company' => $company,
            'structures' => SalaryStructure::query()
                ->withCount('lines')
                ->where('company_id', $company->id)
                ->orderBy('name')
                ->paginate(25),
            'payHeads' => PayHead::query()->where('company_id', $company->id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, SalaryStructureService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('acct_salary_structures', 'name')->where('company_id', $company->id)],
            'lines' => ['nullable', 'array'],
            'lines.*.pay_head_id' => ['required', 'integer', Rule::exists('acct_pay_heads', 'id')->where('company_id', $company->id)],
            'lines.*.amount' => ['nullable', 'numeric'],
            'lines.*.percent_of_basic' => ['nullable', 'numeric'],
            'lines.*.is_earning' => ['nullable', 'boolean'],
        ]);

        $structure = $service->save($company, $data);

        return redirect()->to(tally_route('books.tally.salary-structures.show', $structure))
            ->with('status', 'Salary structure created.');
    }

    public function show(WorkspaceContext $context, SalaryStructure $salaryStructure): View
    {
        abort_unless($context->company() && $salaryStructure->company_id === $context->company()->id, 404);
        $salaryStructure->load('lines.payHead');

        return view('tally::salary-structures.show', [
            'company' => $context->company(),
            'structure' => $salaryStructure,
        ]);
    }
}
