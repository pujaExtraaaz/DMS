<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Controllers\Concerns\ActivatesRecords;
use Tally\Models\CostCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CostCategoryController extends Controller
{
    use ActivatesRecords;
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Cost categories',
                'message' => 'Select or create a company before managing cost categories.',
            ]);
        }

        $search = trim($request->string('q')->toString());

        return view('tally::cost-categories.index', [
            'company' => $company,
            'categories' => $company->costCategories()
                ->withCount('centres')
                ->when($search !== '', function ($query) use ($search) {
                    $like = '%'.addcslashes($search, '%_\\').'%';
                    $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('code', 'like', $like));
                })
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
            'filters' => ['q' => $search],
        ]);
    }

    public function create(WorkspaceContext $context): View|RedirectResponse
    {
        if (! $context->company()) {
            return redirect()->route('books.tally.cost-categories.index');
        }

        return view('tally::cost-categories.form', [
            'company' => $context->company(),
            'category' => new CostCategory(['is_active' => true]),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $category = $context->company()->costCategories()->create($this->validated($request, $context));

        return redirect()->route('books.tally.cost-categories.index')->with('status', $category->name.' created.');
    }

    public function edit(WorkspaceContext $context, CostCategory $costCategory): View
    {
        return view('tally::cost-categories.form', [
            'company' => $context->company(),
            'category' => $costCategory,
        ]);
    }

    public function update(Request $request, WorkspaceContext $context, CostCategory $costCategory): RedirectResponse
    {
        $costCategory->update($this->validated($request, $context, $costCategory));

        return redirect()->route('books.tally.cost-categories.index')->with('status', 'Cost category updated.');
    }

    public function updateActivation(Request $request, CostCategory $costCategory): RedirectResponse
    {
        return $this->setActive($request, $costCategory, 'Cost category');
    }

    public function destroy(CostCategory $costCategory): RedirectResponse
    {
        if (! $costCategory->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($costCategory, 'This category still has cost centres.');
        }

        $costCategory->delete();

        return redirect()->route('books.tally.cost-categories.index')->with('status', 'Cost category deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, WorkspaceContext $context, ?CostCategory $category = null): array
    {
        $companyId = $context->company()->id;
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'code' => trim((string) $request->input('code')) ?: null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('cost_categories', 'name')->where(fn ($query) => $query->where('company_id', $companyId))->ignore($category)],
            'code' => ['nullable', 'string', 'max:32', Rule::unique('cost_categories', 'code')->where(fn ($query) => $query->where('company_id', $companyId))->ignore($category)],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
