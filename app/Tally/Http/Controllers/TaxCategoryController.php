<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\TaxCategoryRequest;
use Tally\Models\TaxCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaxCategoryController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Tax categories',
                'message' => 'Select or create a company before managing tax categories.',
            ]);
        }

        $search = trim($request->string('q')->toString());
        $categories = $company->taxCategories()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('tally::tax-categories.index', [
            'company' => $company,
            'categories' => $categories,
            'filters' => ['q' => $search],
        ]);
    }

    public function create(WorkspaceContext $context): View|RedirectResponse
    {
        if (! $context->company()) {
            return redirect()->route('books.tally.tax-categories.index');
        }

        return view('tally::tax-categories.create', [
            'company' => $context->company(),
            'category' => new TaxCategory(['is_active' => true]),
        ]);
    }

    public function store(TaxCategoryRequest $request, WorkspaceContext $context): RedirectResponse
    {
        $category = $context->company()->taxCategories()->create($request->validated());

        return redirect()->route('books.tally.tax-categories.show', $category)->with('status', 'Tax category created.');
    }

    public function show(WorkspaceContext $context, TaxCategory $taxCategory): View
    {
        return view('tally::tax-categories.show', [
            'company' => $context->company(),
            'category' => $taxCategory->loadCount('rates'),
        ]);
    }

    public function edit(WorkspaceContext $context, TaxCategory $taxCategory): View
    {
        return view('tally::tax-categories.edit', [
            'company' => $context->company(),
            'category' => $taxCategory,
        ]);
    }

    public function update(TaxCategoryRequest $request, TaxCategory $taxCategory): RedirectResponse
    {
        $taxCategory->update($request->validated());

        return redirect()->route('books.tally.tax-categories.show', $taxCategory)->with('status', 'Tax category updated.');
    }

    public function updateActivation(Request $request, TaxCategory $taxCategory): RedirectResponse
    {
        $request->validate(['is_active' => ['required', 'boolean']]);
        $taxCategory->update(['is_active' => $request->boolean('is_active')]);

        return back()->with('status', $taxCategory->is_active ? 'Tax category activated.' : 'Tax category deactivated.');
    }

    public function destroy(TaxCategory $taxCategory): RedirectResponse
    {
        if (! $taxCategory->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($taxCategory, 'This tax category is used by tax rates and cannot be deleted.');
        }

        $taxCategory->delete();

        return redirect()->route('books.tally.tax-categories.index')->with('status', 'Tax category deleted.');
    }
}
