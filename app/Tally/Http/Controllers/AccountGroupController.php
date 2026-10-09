<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\DefaultAccountGroups;
use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\StoreAccountGroupRequest;
use Tally\Http\Requests\UpdateAccountGroupRequest;
use Tally\Models\AccountGroup;
use Tally\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountGroupController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Account groups',
                'message' => 'Select or create a company before managing account groups.',
            ]);
        }

        $groups = AccountGroup::flatten(
            $company->accountGroups()->withCount(['children', 'ledgers'])->get()
        );

        return view('tally::account-groups.index', [
            'company' => $company,
            'groups' => $groups,
        ]);
    }

    public function create(WorkspaceContext $context): View|RedirectResponse
    {
        $company = $this->company($context);

        if (! $company instanceof Company) {
            return $company;
        }

        return view('tally::account-groups.create', [
            'company' => $company,
            'parents' => AccountGroup::flatten($company->accountGroups()->orderBy('name')->get()),
            'group' => new AccountGroup(['is_active' => true]),
        ]);
    }

    public function store(StoreAccountGroupRequest $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return redirect()->route('books.tally.companies.index')->with('error', 'Select a company before saving a group.');
        }

        $group = $company->accountGroups()->create($request->validated());

        return redirect()
            ->route('books.tally.account-groups.show', $group)
            ->with('status', 'Account group created.');
    }

    public function show(WorkspaceContext $context, AccountGroup $accountGroup): View
    {
        $accountGroup->load([
            'parent',
            'children' => fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
            'ledgers' => fn ($query) => $query->orderBy('name'),
        ]);

        return view('tally::account-groups.show', [
            'company' => $context->company(),
            'group' => $accountGroup,
        ]);
    }

    public function edit(WorkspaceContext $context, AccountGroup $accountGroup): View
    {
        $company = $context->company();

        return view('tally::account-groups.edit', [
            'company' => $company,
            'group' => $accountGroup,
            'parents' => AccountGroup::flatten($company->accountGroups()->get())
                ->reject(fn (array $row) => $row['group']->is($accountGroup) || $row['group']->isDescendantOf($accountGroup)),
        ]);
    }

    public function update(UpdateAccountGroupRequest $request, AccountGroup $accountGroup): RedirectResponse
    {
        $accountGroup->update($request->validated());

        if (! $accountGroup->is_system) {
            $accountGroup->refresh()->load('parent');

            if ($accountGroup->parent && $accountGroup->nature !== $accountGroup->parent->nature) {
                $accountGroup->nature = $accountGroup->parent->nature;
                $accountGroup->save();
            }

            $accountGroup->applyNatureToDescendants();
        }

        return redirect()
            ->route('books.tally.account-groups.show', $accountGroup)
            ->with('status', 'Account group updated.');
    }

    public function updateActivation(Request $request, AccountGroup $accountGroup): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $accountGroup->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', $accountGroup->is_active ? 'Account group activated.' : 'Account group deactivated.');
    }

    public function destroy(AccountGroup $accountGroup): RedirectResponse
    {
        if ($accountGroup->is_system) {
            return app(\Tally\Audit\AuditLogger::class)->deny($accountGroup, 'System groups cannot be deleted.');
        }

        if (! $accountGroup->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($accountGroup, 'Delete the ledgers and subgroups in this group first.');
        }

        $accountGroup->delete();

        return redirect()
            ->route('books.tally.account-groups.index')
            ->with('status', 'Account group deleted.');
    }

    public function prepareStandard(WorkspaceContext $context, DefaultAccountGroups $chart): RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return redirect()->route('books.tally.companies.index')->with('error', 'Select a company first.');
        }

        $chart->seed($company);

        return redirect()
            ->route('books.tally.account-groups.index')
            ->with('status', 'Standard account groups are ready.');
    }

    private function company(WorkspaceContext $context): Company|RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return redirect()->route('books.tally.account-groups.index');
        }

        return $company;
    }
}
