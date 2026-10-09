<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\ProductGroupRequest;
use Tally\Models\Company;
use Tally\Models\ProductGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductGroupController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Product groups',
                'message' => 'Select or create a company before managing product groups.',
            ]);
        }

        $groups = ProductGroup::flatten(
            $company->productGroups()->withCount(['children', 'products'])->get()
        );

        return view('tally::product-groups.index', [
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

        return view('tally::product-groups.create', [
            'company' => $company,
            'parents' => ProductGroup::flatten($company->productGroups()->where('is_active', true)->get()),
            'group' => new ProductGroup([
                'is_active' => true,
                'code' => ProductGroup::suggestCode($company),
            ]),
        ]);
    }

    public function store(ProductGroupRequest $request, WorkspaceContext $context): RedirectResponse
    {
        $group = $context->company()->productGroups()->create($request->validated());

        return redirect()
            ->route('books.tally.product-groups.show', $group)
            ->with('status', 'Product group created.');
    }

    public function show(WorkspaceContext $context, ProductGroup $productGroup): View
    {
        $productGroup->load([
            'parent',
            'children' => fn ($query) => $query->orderBy('name'),
            'products' => fn ($query) => $query->orderBy('name'),
        ]);

        return view('tally::product-groups.show', [
            'company' => $context->company(),
            'group' => $productGroup,
        ]);
    }

    public function edit(WorkspaceContext $context, ProductGroup $productGroup): View
    {
        $company = $context->company();

        return view('tally::product-groups.edit', [
            'company' => $company,
            'group' => $productGroup,
            'parents' => ProductGroup::flatten($company->productGroups()->get())
                ->reject(fn (array $row) => $row['group']->is($productGroup) || $row['group']->isDescendantOf($productGroup)),
        ]);
    }

    public function update(ProductGroupRequest $request, ProductGroup $productGroup): RedirectResponse
    {
        $productGroup->update($request->validated());

        return redirect()
            ->route('books.tally.product-groups.show', $productGroup)
            ->with('status', 'Product group updated.');
    }

    public function updateActivation(Request $request, ProductGroup $productGroup): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $productGroup->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', $productGroup->is_active ? 'Product group activated.' : 'Product group deactivated.');
    }

    public function destroy(ProductGroup $productGroup): RedirectResponse
    {
        $productGroup->loadCount(['children', 'products']);

        if (! $productGroup->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($productGroup, 'Delete the subgroups and products in this group first.');
        }

        $productGroup->delete();

        return redirect()
            ->route('books.tally.product-groups.index')
            ->with('status', 'Product group deleted.');
    }

    private function company(WorkspaceContext $context): Company|RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return redirect()->route('books.tally.product-groups.index');
        }

        return $company;
    }
}
