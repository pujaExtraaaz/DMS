<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Controllers\Concerns\ActivatesRecords;
use Tally\Models\CostCentre;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CostCentreController extends Controller
{
    use ActivatesRecords;
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Cost centres',
                'message' => 'Select or create a company before managing cost centres.',
            ]);
        }

        $search = trim($request->string('q')->toString());

        return view('tally::cost-centres.index', [
            'company' => $company,
            'centres' => $company->costCentres()->with('category')
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
            return redirect()->route('books.tally.cost-centres.index');
        }

        return view('tally::cost-centres.form', [
            'company' => $context->company(),
            'centre' => new CostCentre(['is_active' => true]),
            'categories' => $context->company()->costCategories()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $context->company()->costCentres()->create($this->validated($request, $context));

        return redirect()->route('books.tally.cost-centres.index')->with('status', 'Cost centre created.');
    }

    public function edit(WorkspaceContext $context, CostCentre $costCentre): View
    {
        return view('tally::cost-centres.form', [
            'company' => $context->company(),
            'centre' => $costCentre,
            'categories' => $context->company()->costCategories()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, WorkspaceContext $context, CostCentre $costCentre): RedirectResponse
    {
        $costCentre->update($this->validated($request, $context, $costCentre));

        return redirect()->route('books.tally.cost-centres.index')->with('status', 'Cost centre updated.');
    }

    public function updateActivation(Request $request, CostCentre $costCentre): RedirectResponse
    {
        return $this->setActive($request, $costCentre, 'Cost centre');
    }

    public function destroy(CostCentre $costCentre): RedirectResponse
    {
        if (! $costCentre->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($costCentre, 'This cost centre is used on a voucher or a budget.');
        }

        $costCentre->delete();

        return redirect()->route('books.tally.cost-centres.index')->with('status', 'Cost centre deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, WorkspaceContext $context, ?CostCentre $centre = null): array
    {
        $company = $context->company();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('cost_centres', 'name')->where(fn ($query) => $query->where('company_id', $company->id))->ignore($centre)],
            'code' => ['nullable', 'string', 'max:32'],
            'cost_category_id' => ['nullable', 'integer', Rule::exists('cost_categories', 'id')->where(fn ($query) => $query->where('company_id', $company->id))],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['code'] = $data['code'] ?? null;
        $data['cost_category_id'] = $data['cost_category_id'] ?? null;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
