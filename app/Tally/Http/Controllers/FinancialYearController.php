<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\StoreFinancialYearRequest;
use Tally\Http\Requests\UpdateFinancialYearRequest;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinancialYearController extends Controller
{
    public function current(WorkspaceContext $context): View|RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Financial years',
                'message' => 'Select or create a company before managing financial years.',
            ]);
        }

        return redirect()->route('books.tally.companies.financial-years.index', $company);
    }

    public function index(Company $company): View
    {
        $financialYears = $company->financialYears()->orderByDesc('start_date')->paginate(25);

        return view('tally::financial-years.index', [
            'company' => $company,
            'financialYears' => $financialYears,
        ]);
    }

    public function create(Company $company): View
    {
        return view('tally::financial-years.create', [
            'company' => $company,
        ]);
    }

    public function store(StoreFinancialYearRequest $request, Company $company, WorkspaceContext $context): RedirectResponse
    {
        $financialYear = $company->financialYears()->create($request->validated());

        if ($context->company()?->is($company) && $financialYear->is_active && ! $context->financialYear()) {
            $context->setFinancialYear($financialYear);
        }

        return redirect()
            ->route('books.tally.companies.financial-years.show', [$company, $financialYear])
            ->with('status', 'Financial year created.');
    }

    public function show(Company $company, FinancialYear $financialYear): View
    {
        return view('tally::financial-years.show', [
            'company' => $company,
            'financialYear' => $financialYear,
        ]);
    }

    public function edit(Company $company, FinancialYear $financialYear): View
    {
        return view('tally::financial-years.edit', [
            'company' => $company,
            'financialYear' => $financialYear,
        ]);
    }

    public function update(UpdateFinancialYearRequest $request, Company $company, FinancialYear $financialYear, WorkspaceContext $context): RedirectResponse
    {
        $financialYear->update($request->validated());

        if (! $financialYear->is_active) {
            $context->forgetFinancialYear($financialYear);
            $context->resolve();
        }

        return redirect()
            ->route('books.tally.companies.financial-years.show', [$company, $financialYear])
            ->with('status', 'Financial year updated.');
    }

    public function updateActivation(Request $request, Company $company, FinancialYear $financialYear, WorkspaceContext $context): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $financialYear->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! $financialYear->is_active) {
            $context->forgetFinancialYear($financialYear);
            $context->resolve();
        }

        return back()->with('status', $financialYear->is_active ? 'Financial year activated.' : 'Financial year deactivated.');
    }

    public function destroy(Company $company, FinancialYear $financialYear, WorkspaceContext $context): RedirectResponse
    {
        if (! $financialYear->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($financialYear, 'This financial year has vouchers, invoices, or stock and cannot be deleted.');
        }

        $context->forgetFinancialYear($financialYear);
        $financialYear->delete();
        $context->resolve();

        return redirect()
            ->route('books.tally.companies.financial-years.index', $company)
            ->with('status', 'Financial year deleted.');
    }
}
