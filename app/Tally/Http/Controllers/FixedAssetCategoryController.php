<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\FixedAssetCategory;
use Tally\Models\Ledger;

class FixedAssetCategoryController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Fixed asset categories',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::fixed-asset-categories.index', [
            'company' => $company,
            'categories' => FixedAssetCategory::query()
                ->where('company_id', $company->id)
                ->withCount('assets')
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        $company = $context->company();
        abort_unless($company, 404);

        return view('tally::fixed-asset-categories.form', [
            'company' => $company,
            'category' => new FixedAssetCategory([
                'depreciation_method' => 'straight_line',
                'useful_life_years' => 5,
                'residual_percent' => 5,
                'is_active' => true,
            ]),
            'ledgers' => Ledger::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $this->validated($request, $company->id);
        $category = FixedAssetCategory::query()->create($data + ['company_id' => $company->id]);

        return redirect()->to(tally_route('books.tally.fixed-asset-categories.index'))
            ->with('status', $category->name.' created.');
    }

    public function edit(WorkspaceContext $context, FixedAssetCategory $fixedAssetCategory): View
    {
        abort_unless($context->company() && $fixedAssetCategory->company_id === $context->company()->id, 404);

        return view('tally::fixed-asset-categories.form', [
            'company' => $context->company(),
            'category' => $fixedAssetCategory,
            'ledgers' => Ledger::query()->where('company_id', $context->company()->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, WorkspaceContext $context, FixedAssetCategory $fixedAssetCategory): RedirectResponse
    {
        abort_unless($context->company() && $fixedAssetCategory->company_id === $context->company()->id, 404);

        $fixedAssetCategory->update($this->validated($request, $context->company()->id, $fixedAssetCategory));

        return redirect()->to(tally_route('books.tally.fixed-asset-categories.index'))
            ->with('status', 'Category updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $companyId, ?FixedAssetCategory $category = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('acct_fixed_asset_categories', 'name')->where('company_id', $companyId)->ignore($category),
            ],
            'depreciation_method' => ['required', Rule::in(['straight_line', 'wdv'])],
            'useful_life_years' => ['required', 'numeric', 'gt:0', 'max:100'],
            'residual_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'asset_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $companyId)],
            'accum_depr_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $companyId)],
            'depr_expense_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $companyId)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
