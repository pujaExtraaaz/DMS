<?php

namespace Tally\Http\Controllers;

use Tally\Banking\BankAccountService;
use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\StoreLedgerRequest;
use Tally\Http\Requests\UpdateLedgerRequest;
use Tally\Models\AccountGroup;
use Tally\Models\Company;
use Tally\Models\Ledger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class LedgerController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Ledgers',
                'message' => 'Select or create a company before managing ledgers.',
            ]);
        }

        $ledgers = $this->filtered($request, $company)
            ->with('accountGroup')
            ->paginate(25)
            ->withQueryString();

        return view('tally::ledgers.index', [
            'company' => $company,
            'ledgers' => $ledgers,
            'groups' => AccountGroup::flatten($company->accountGroups()->orderBy('name')->get()),
            'filters' => $request->only(['q', 'account_group_id', 'status', 'sort', 'direction']),
        ]);
    }

    public function create(WorkspaceContext $context): View|RedirectResponse
    {
        $company = $this->company($context);

        if (! $company instanceof Company) {
            return $company;
        }

        return view('tally::ledgers.create', [
            'company' => $company,
            'groups' => AccountGroup::flatten($company->accountGroups()->where('is_active', true)->get()),
            'bankGroupIds' => app(BankAccountService::class)->bankGroupIds($company),
            'ledger' => new Ledger([
                'opening_balance' => 0,
                'opening_balance_type' => 'debit',
                'is_active' => true,
            ]),
        ]);
    }

    public function store(StoreLedgerRequest $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return redirect()->route('books.tally.companies.index')->with('error', 'Select a company before saving a ledger.');
        }

        $data = $request->validated();
        $ledger = $company->ledgers()->create(Arr::except($data, ['bank_name', 'account_number', 'ifsc']));
        app(BankAccountService::class)->sync($ledger, Arr::only($data, ['bank_name', 'account_number', 'ifsc']));
        $this->rememberOpening($context, $ledger);

        return redirect()
            ->route('books.tally.ledgers.show', $ledger)
            ->with('status', 'Ledger created.');
    }

    public function show(WorkspaceContext $context, Ledger $ledger): View
    {
        $ledger->load(['accountGroup.parent', 'bankAccount']);

        $neighbors = $context->company()->ledgers()->orderBy('name')->orderBy('id');

        return view('tally::ledgers.show', [
            'company' => $context->company(),
            'ledger' => $ledger,
            'previousLedger' => (clone $neighbors)->where('name', '<', $ledger->name)->orderByDesc('name')->first(),
            'nextLedger' => (clone $neighbors)->where('name', '>', $ledger->name)->orderBy('name')->first(),
        ]);
    }

    public function edit(WorkspaceContext $context, Ledger $ledger): View
    {
        $company = $context->company();

        return view('tally::ledgers.edit', [
            'company' => $company,
            'ledger' => $ledger->load('bankAccount'),
            'bankGroupIds' => app(BankAccountService::class)->bankGroupIds($company),
            'groups' => AccountGroup::flatten(
                $company->accountGroups()
                    ->where(function ($query) use ($ledger) {
                        $query->where('is_active', true)->orWhere('id', $ledger->account_group_id);
                    })
                    ->get()
            ),
        ]);
    }

    public function update(UpdateLedgerRequest $request, Ledger $ledger): RedirectResponse
    {
        if ($ledger->is_system) {
            $ledger->update($request->validated());
        } else {
            $data = $request->validated();
            $ledger->update(Arr::except($data, ['bank_name', 'account_number', 'ifsc']));
            app(BankAccountService::class)->sync($ledger, Arr::only($data, ['bank_name', 'account_number', 'ifsc']));
        }

        $this->rememberOpening(app(WorkspaceContext::class), $ledger->fresh());

        return redirect()
            ->route('books.tally.ledgers.show', $ledger)
            ->with('status', 'Ledger updated.');
    }

    public function updateActivation(Request $request, Ledger $ledger): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $ledger->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', $ledger->is_active ? 'Ledger activated.' : 'Ledger deactivated.');
    }

    public function destroy(Ledger $ledger): RedirectResponse
    {
        if ($ledger->is_system) {
            return app(\Tally\Audit\AuditLogger::class)->deny($ledger, 'System ledgers cannot be deleted.');
        }

        if (! $ledger->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($ledger, 'This ledger cannot be deleted because accounting entries depend on it.');
        }

        $ledger->delete();

        return redirect()
            ->route('books.tally.ledgers.index')
            ->with('status', 'Ledger deleted.');
    }

    private function filtered(Request $request, Company $company)
    {
        $query = $company->ledgers();
        $search = trim($request->string('q')->toString());

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        if ($request->filled('account_group_id')) {
            $query->where('account_group_id', $request->integer('account_group_id'));
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $sort = in_array($request->input('sort'), ['name', 'code', 'opening_balance'], true)
            ? $request->input('sort')
            : 'name';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction)->orderBy('name');
    }

    private function rememberOpening(WorkspaceContext $context, ?Ledger $ledger): void
    {
        $year = $context->financialYear();

        if ($ledger && $year) {
            app(\Tally\Accounting\LedgerOpeningBook::class)->remember($ledger, $year);
        }
    }

    private function company(WorkspaceContext $context): Company|RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return redirect()->route('books.tally.ledgers.index');
        }

        return $company;
    }
}
