<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\AssetVerification;
use Tally\Models\FixedAsset;

class AssetVerificationController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Asset verification',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::asset-verifications.index', [
            'company' => $company,
            'rows' => AssetVerification::query()
                ->with('fixedAsset')
                ->where('company_id', $company->id)
                ->latest('verification_date')
                ->paginate(25),
            'assets' => FixedAsset::query()
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->orderBy('asset_code')
                ->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'fixed_asset_id' => ['required', 'integer', Rule::exists('acct_fixed_assets', 'id')->where('company_id', $company->id)],
            'verification_date' => ['required', 'date'],
            'verified_by' => ['nullable', 'string', 'max:120'],
            'condition' => ['required', Rule::in(['good', 'fair', 'poor', 'damaged', 'missing'])],
            'found' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        AssetVerification::query()->create([
            'company_id' => $company->id,
            'fixed_asset_id' => $data['fixed_asset_id'],
            'verification_date' => $data['verification_date'],
            'verified_by' => ($data['verified_by'] ?? null) ?: null,
            'condition' => $data['condition'],
            'found' => $request->boolean('found', true),
            'notes' => ($data['notes'] ?? null) ?: null,
        ]);

        return back()->with('status', 'Verification recorded.');
    }
}
