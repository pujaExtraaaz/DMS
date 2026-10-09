<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\StoreBranchRequest;
use Tally\Http\Requests\UpdateBranchRequest;
use Tally\Models\Branch;
use Tally\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function current(WorkspaceContext $context): View|RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Branches',
                'message' => 'Select or create a company before managing branches.',
            ]);
        }

        return redirect()->route('books.tally.companies.branches.index', $company);
    }

    public function index(Company $company): View
    {
        $branches = $company->branches()->orderBy('name')->paginate(25);

        return view('tally::branches.index', [
            'company' => $company,
            'branches' => $branches,
        ]);
    }

    public function create(Company $company): View
    {
        return view('tally::branches.create', [
            'company' => $company,
        ]);
    }

    public function store(StoreBranchRequest $request, Company $company, WorkspaceContext $context): RedirectResponse
    {
        $branch = $company->branches()->create($request->validated());

        if ($context->company()?->is($company) && $branch->is_active && $context->branches()->count() === 1) {
            $context->setBranch($branch);
        }

        return redirect()
            ->route('books.tally.companies.branches.show', [$company, $branch])
            ->with('status', 'Branch created.');
    }

    public function show(Company $company, Branch $branch): View
    {
        return view('tally::branches.show', [
            'company' => $company,
            'branch' => $branch,
        ]);
    }

    public function edit(Company $company, Branch $branch): View
    {
        return view('tally::branches.edit', [
            'company' => $company,
            'branch' => $branch,
        ]);
    }

    public function update(UpdateBranchRequest $request, Company $company, Branch $branch, WorkspaceContext $context): RedirectResponse
    {
        $branch->update($request->validated());

        if (! $branch->is_active) {
            $context->forgetBranch($branch);
            $context->resolve();
        }

        return redirect()
            ->route('books.tally.companies.branches.show', [$company, $branch])
            ->with('status', 'Branch updated.');
    }

    public function updateActivation(Request $request, Company $company, Branch $branch, WorkspaceContext $context): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $branch->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! $branch->is_active) {
            $context->forgetBranch($branch);
            $context->resolve();
        }

        return back()->with('status', $branch->is_active ? 'Branch activated.' : 'Branch deactivated.');
    }

    public function destroy(Company $company, Branch $branch, WorkspaceContext $context): RedirectResponse
    {
        if (! $branch->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($branch, 'This branch has vouchers, invoices, or stock and cannot be deleted.');
        }

        $context->forgetBranch($branch);
        $branch->delete();
        $context->resolve();

        return redirect()
            ->route('books.tally.companies.branches.index', $company)
            ->with('status', 'Branch deleted.');
    }
}
