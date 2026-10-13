<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Assets\FixedAssetService;
use Tally\Context\WorkspaceContext;
use Tally\Models\FixedAsset;
use Tally\Models\FixedAssetCategory;

class FixedAssetController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Fixed assets',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::fixed-assets.index', [
            'company' => $company,
            'assets' => FixedAsset::query()
                ->with('category')
                ->where('company_id', $company->id)
                ->orderBy('asset_code')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        $company = $context->company();
        abort_unless($company, 404);

        return view('tally::fixed-assets.form', [
            'company' => $company,
            'asset' => new FixedAsset([
                'acquisition_date' => now()->toDateString(),
                'status' => 'active',
            ]),
            'categories' => FixedAssetCategory::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'parents' => FixedAsset::query()
                ->where('company_id', $company->id)
                ->whereNull('parent_asset_id')
                ->orderBy('asset_code')
                ->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, FixedAssetService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $asset = $service->create($company, $this->validated($request, $company->id));

        return redirect()->to(tally_route('books.tally.fixed-assets.show', $asset))
            ->with('status', 'Fixed asset created.');
    }

    public function show(WorkspaceContext $context, FixedAsset $fixedAsset): View
    {
        abort_unless($context->company() && $fixedAsset->company_id === $context->company()->id, 404);
        $fixedAsset->load(['category', 'parent', 'children', 'depreciationEntries' => fn ($q) => $q->latest('period')]);

        return view('tally::fixed-assets.show', [
            'company' => $context->company(),
            'asset' => $fixedAsset,
        ]);
    }

    public function capitalize(Request $request, WorkspaceContext $context, FixedAsset $fixedAsset, FixedAssetService $service): RedirectResponse
    {
        abort_unless($context->company() && $fixedAsset->company_id === $context->company()->id, 404);

        $data = $request->validate([
            'capitalization_date' => ['required', 'date'],
        ]);

        $service->capitalize($fixedAsset, $data['capitalization_date']);

        return back()->with('status', 'CIP asset capitalized.');
    }

    public function dispose(Request $request, WorkspaceContext $context, FixedAsset $fixedAsset, FixedAssetService $service): RedirectResponse
    {
        abort_unless($context->company() && $fixedAsset->company_id === $context->company()->id, 404);

        $data = $request->validate([
            'disposal_date' => ['required', 'date'],
            'disposal_proceeds' => ['nullable', 'numeric', 'min:0'],
            'proceeds_ledger_id' => ['nullable', 'integer'],
            'gain_loss_ledger_id' => ['nullable', 'integer'],
        ]);

        $service->dispose(
            $fixedAsset,
            $data['disposal_date'],
            $data['disposal_proceeds'] ?? 0,
            [
                'proceeds_ledger_id' => $data['proceeds_ledger_id'] ?? null,
                'gain_loss_ledger_id' => $data['gain_loss_ledger_id'] ?? null,
            ],
            $request->user(),
        );

        return redirect()->to(tally_route('books.tally.fixed-assets.show', $fixedAsset))
            ->with('status', 'Asset disposed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $companyId): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'integer', Rule::exists('acct_fixed_asset_categories', 'id')->where('company_id', $companyId)],
            'asset_code' => [
                'nullable', 'string', 'max:40',
                Rule::unique('acct_fixed_assets', 'asset_code')->where('company_id', $companyId),
            ],
            'name' => ['required', 'string', 'max:160'],
            'acquisition_date' => ['required', 'date'],
            'capitalization_date' => ['nullable', 'date'],
            'gross_value' => ['required', 'numeric', 'gt:0'],
            'residual_value' => ['nullable', 'numeric', 'min:0'],
            'accumulated_depreciation' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'custodian' => ['nullable', 'string', 'max:120'],
            'is_cip' => ['nullable', 'boolean'],
            'parent_asset_id' => ['nullable', 'integer', Rule::exists('acct_fixed_assets', 'id')->where('company_id', $companyId)],
        ]);
    }
}
