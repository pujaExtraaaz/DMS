<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\GodownRequest;
use Tally\Models\Company;
use Tally\Models\Godown;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GodownController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Godowns',
                'message' => 'Select or create a company before managing godowns.',
            ]);
        }

        $godowns = $this->filtered($request, $company)
            ->paginate(25)
            ->withQueryString();

        return view('tally::godowns.index', [
            'company' => $company,
            'godowns' => $godowns,
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function create(WorkspaceContext $context): View|RedirectResponse
    {
        $company = $this->company($context);

        if (! $company instanceof Company) {
            return $company;
        }

        return view('tally::godowns.create', [
            'company' => $company,
            'godown' => new Godown(['is_active' => true]),
        ]);
    }

    public function store(GodownRequest $request, WorkspaceContext $context): RedirectResponse
    {
        $godown = $context->company()->godowns()->create($request->validated());

        return redirect()
            ->route('books.tally.godowns.show', $godown)
            ->with('status', 'Godown created.');
    }

    public function show(WorkspaceContext $context, Godown $godown): View
    {
        return view('tally::godowns.show', [
            'company' => $context->company(),
            'godown' => $godown,
        ]);
    }

    public function edit(WorkspaceContext $context, Godown $godown): View
    {
        return view('tally::godowns.edit', [
            'company' => $context->company(),
            'godown' => $godown,
        ]);
    }

    public function update(GodownRequest $request, Godown $godown): RedirectResponse
    {
        $godown->update($request->validated());

        return redirect()
            ->route('books.tally.godowns.show', $godown)
            ->with('status', 'Godown updated.');
    }

    public function updateActivation(Request $request, Godown $godown): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $godown->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', $godown->is_active ? 'Godown activated.' : 'Godown deactivated.');
    }

    public function destroy(Godown $godown): RedirectResponse
    {
        if (! $godown->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($godown, 'This godown is used by stock or invoices and cannot be deleted.');
        }

        $godown->delete();

        return redirect()
            ->route('books.tally.godowns.index')
            ->with('status', 'Godown deleted.');
    }

    private function filtered(Request $request, Company $company)
    {
        $query = $company->godowns();
        $search = trim($request->string('q')->toString());

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('address', 'like', $like);
            });
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->orderBy('name');
    }

    private function company(WorkspaceContext $context): Company|RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return redirect()->route('books.tally.godowns.index');
        }

        return $company;
    }
}
