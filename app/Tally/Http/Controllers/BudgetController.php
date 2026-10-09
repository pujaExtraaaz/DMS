<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\BudgetReport;
use Tally\Context\WorkspaceContext;
use Tally\Models\Budget;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    public function index(WorkspaceContext $context, BudgetReport $report): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', [
                'title' => 'Budgets',
                'message' => 'Select a company and financial year before managing budgets.',
            ]);
        }

        $budgets = $company->budgets()->with(['ledger', 'accountGroup', 'costCentre'])->where('financial_year_id', $year->id)->orderBy('name')->get();

        return view('tally::budgets.index', [
            'company' => $company,
            'year' => $year,
            'rows' => $budgets->map(fn (Budget $budget) => [
                'budget' => $budget,
                'figures' => $report->compare($budget),
            ]),
        ]);
    }

    public function create(WorkspaceContext $context): View|RedirectResponse
    {
        if (! $context->company() || ! $context->financialYear()) {
            return redirect()->route('books.tally.budgets.index');
        }

        return view('tally::budgets.form', $this->formData($context, new Budget([
            'period_start' => $context->financialYear()->start_date,
            'period_end' => $context->financialYear()->end_date,
        ])));
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $context->company()->budgets()->create($this->validated($request, $context) + [
            'financial_year_id' => $context->financialYear()->id,
        ]);

        return redirect()->route('books.tally.budgets.index')->with('status', 'Budget created.');
    }

    public function edit(WorkspaceContext $context, Budget $budget): View
    {
        return view('tally::budgets.form', $this->formData($context, $budget));
    }

    public function update(Request $request, WorkspaceContext $context, Budget $budget): RedirectResponse
    {
        $budget->update($this->validated($request, $context));

        return redirect()->route('books.tally.budgets.index')->with('status', 'Budget updated.');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $budget->delete();

        return redirect()->route('books.tally.budgets.index')->with('status', 'Budget deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(WorkspaceContext $context, Budget $budget): array
    {
        $company = $context->company();

        return [
            'company' => $company,
            'year' => $context->financialYear(),
            'budget' => $budget,
            'ledgers' => $company->ledgers()->where('is_active', true)->orderBy('name')->get(),
            'groups' => $company->accountGroups()->orderBy('name')->get(),
            'centres' => $company->costCentres()->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, WorkspaceContext $context): array
    {
        $company = $context->company();
        $year = $context->financialYear();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'period_start' => ['required', 'date', 'after_or_equal:'.$year->start_date->toDateString(), 'before_or_equal:'.$year->end_date->toDateString()],
            'period_end' => ['required', 'date', 'after_or_equal:period_start', 'before_or_equal:'.$year->end_date->toDateString()],
            'ledger_id' => ['nullable', 'integer', Rule::exists('ledgers', 'id')->where(fn ($query) => $query->where('company_id', $company->id))],
            'account_group_id' => ['nullable', 'integer', Rule::exists('account_groups', 'id')->where(fn ($query) => $query->where('company_id', $company->id))],
            'cost_centre_id' => ['nullable', 'integer', Rule::exists('cost_centres', 'id')->where(fn ($query) => $query->where('company_id', $company->id))],
            'amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ]);

        if (empty($data['ledger_id']) && empty($data['account_group_id']) && empty($data['cost_centre_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'ledger_id' => 'Choose a ledger, an account group, or a cost centre.',
            ]);
        }

        foreach (['ledger_id', 'account_group_id', 'cost_centre_id'] as $field) {
            $data[$field] = $data[$field] ?? null;
        }

        return $data;
    }
}
