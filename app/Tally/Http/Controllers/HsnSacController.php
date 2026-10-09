<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\HsnSacRequest;
use Tally\Models\HsnSac;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HsnSacController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'HSN / SAC',
                'message' => 'Select or create a company before managing HSN and SAC codes.',
            ]);
        }

        $search = trim($request->string('q')->toString());
        $records = $company->hsnSacs()->with('taxRate')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($query) use ($like) {
                    $query->where('code', 'like', $like)->orWhere('description', 'like', $like);
                });
            })
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        return view('tally::hsn-sacs.index', [
            'company' => $company,
            'records' => $records,
            'filters' => ['q' => $search],
        ]);
    }

    public function create(WorkspaceContext $context): View|RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return redirect()->route('books.tally.hsn-sacs.index');
        }

        return view('tally::hsn-sacs.create', [
            'company' => $company,
            'record' => new HsnSac(['is_active' => true, 'kind' => 'hsn']),
            'rates' => $company->taxRates()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(HsnSacRequest $request, WorkspaceContext $context): RedirectResponse
    {
        $record = $context->company()->hsnSacs()->create($request->validated());

        return redirect()->route('books.tally.hsn-sacs.show', $record)->with('status', 'HSN/SAC created.');
    }

    public function show(WorkspaceContext $context, HsnSac $hsnSac): View
    {
        return view('tally::hsn-sacs.show', [
            'company' => $context->company(),
            'record' => $hsnSac->load('taxRate'),
        ]);
    }

    public function edit(WorkspaceContext $context, HsnSac $hsnSac): View
    {
        $company = $context->company();

        return view('tally::hsn-sacs.edit', [
            'company' => $company,
            'record' => $hsnSac,
            'rates' => $company->taxRates()
                ->where(function ($query) use ($hsnSac) {
                    $query->where('is_active', true);

                    if ($hsnSac->tax_rate_id) {
                        $query->orWhere('id', $hsnSac->tax_rate_id);
                    }
                })
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(HsnSacRequest $request, HsnSac $hsnSac): RedirectResponse
    {
        $hsnSac->update($request->validated());

        return redirect()->route('books.tally.hsn-sacs.show', $hsnSac)->with('status', 'HSN/SAC updated.');
    }

    public function updateActivation(Request $request, HsnSac $hsnSac): RedirectResponse
    {
        $request->validate(['is_active' => ['required', 'boolean']]);
        $hsnSac->update(['is_active' => $request->boolean('is_active')]);

        return back()->with('status', $hsnSac->is_active ? 'HSN/SAC activated.' : 'HSN/SAC deactivated.');
    }

    public function destroy(HsnSac $hsnSac): RedirectResponse
    {
        if (! $hsnSac->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($hsnSac, 'This HSN/SAC is used by products or invoices and cannot be deleted.');
        }

        $hsnSac->delete();

        return redirect()->route('books.tally.hsn-sacs.index')->with('status', 'HSN/SAC deleted.');
    }
}
