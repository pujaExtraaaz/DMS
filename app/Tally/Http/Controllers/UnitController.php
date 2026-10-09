<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\UnitRequest;
use Tally\Models\Company;
use Tally\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Units',
                'message' => 'Select or create a company before managing units.',
            ]);
        }

        $units = $this->filtered($request, $company)
            ->withCount(['primaryProducts', 'alternateProducts'])
            ->paginate(25)
            ->withQueryString();

        return view('tally::units.index', [
            'company' => $company,
            'units' => $units,
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function create(WorkspaceContext $context): View|RedirectResponse
    {
        $company = $this->company($context);

        if (! $company instanceof Company) {
            return $company;
        }

        return view('tally::units.create', [
            'company' => $company,
            'unit' => new Unit(['decimal_places' => 0, 'is_active' => true]),
        ]);
    }

    public function store(UnitRequest $request, WorkspaceContext $context): RedirectResponse
    {
        $unit = $context->company()->units()->create($request->validated());

        return redirect()
            ->route('books.tally.units.show', $unit)
            ->with('status', 'Unit created.');
    }

    public function show(WorkspaceContext $context, Unit $unit): View
    {
        $unit->loadCount(['primaryProducts', 'alternateProducts']);

        return view('tally::units.show', [
            'company' => $context->company(),
            'unit' => $unit,
        ]);
    }

    public function edit(WorkspaceContext $context, Unit $unit): View
    {
        return view('tally::units.edit', [
            'company' => $context->company(),
            'unit' => $unit,
        ]);
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return redirect()
            ->route('books.tally.units.show', $unit)
            ->with('status', 'Unit updated.');
    }

    public function updateActivation(Request $request, Unit $unit): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $unit->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', $unit->is_active ? 'Unit activated.' : 'Unit deactivated.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->loadCount(['primaryProducts', 'alternateProducts']);

        if (! $unit->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($unit, 'This unit cannot be deleted because products use it.');
        }

        $unit->delete();

        return redirect()
            ->route('books.tally.units.index')
            ->with('status', 'Unit deleted.');
    }

    private function filtered(Request $request, Company $company)
    {
        $query = $company->units();
        $search = trim($request->string('q')->toString());

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('symbol', 'like', $like);
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
            return redirect()->route('books.tally.units.index');
        }

        return $company;
    }
}
