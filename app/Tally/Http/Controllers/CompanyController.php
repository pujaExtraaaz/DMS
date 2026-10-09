<?php

namespace Tally\Http\Controllers;

use Tally\Audit\AuditLogger;
use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\StoreCompanyRequest;
use Tally\Http\Requests\UpdateCompanyRequest;
use Tally\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::query()
            ->withCount(['branches', 'financialYears'])
            ->orderBy('name')
            ->paginate(25);

        return view('tally::companies.index', [
            'companies' => $companies,
        ]);
    }

    public function create(): View
    {
        return view('tally::companies.create');
    }

    public function store(StoreCompanyRequest $request, WorkspaceContext $context): RedirectResponse
    {
        $company = Company::provision($request->validated());

        if ($company->is_active) {
            $context->setCompany($company);
        }

        return redirect()
            ->route('books.tally.companies.show', $company)
            ->with('status', 'Company created.');
    }

    public function show(Company $company): View
    {
        $company->load([
            'branches' => fn ($query) => $query->orderBy('name'),
            'financialYears' => fn ($query) => $query->orderByDesc('start_date'),
        ]);

        return view('tally::companies.show', [
            'company' => $company,
        ]);
    }

    public function edit(Company $company): View
    {
        return view('tally::companies.edit', [
            'company' => $company,
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company, WorkspaceContext $context): RedirectResponse
    {
        $company->update($request->validated());

        if (! $company->is_active && $context->company()?->is($company)) {
            $context->clear();
        }

        return redirect()
            ->route('books.tally.companies.show', $company)
            ->with('status', 'Company updated.');
    }

    public function updateActivation(Request $request, Company $company, WorkspaceContext $context): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $company->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! $company->is_active && $context->company()?->is($company)) {
            $context->clear();
        }

        return back()->with('status', $company->is_active ? 'Company activated.' : 'Company deactivated.');
    }

    public function destroy(Company $company, WorkspaceContext $context, AuditLogger $audit): RedirectResponse
    {
        $name = $company->name;

        if ($context->company()?->is($company)) {
            $context->clear();
        }

        $company->deleteUnused();
        $audit->record('deleted', 'company', null, 'Company '.$name.' deleted.');

        return redirect()
            ->route('books.tally.companies.index')
            ->with('status', 'Company deleted.');
    }
}
