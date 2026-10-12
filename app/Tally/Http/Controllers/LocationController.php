<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\Location;

class LocationController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Locations',
                'message' => 'Select a company before managing locations.',
            ]);
        }

        return view('tally::locations.index', [
            'company' => $company,
            'locations' => Location::query()
                ->with('branch')
                ->where('company_id', $company->id)
                ->orderBy('name')
                ->paginate(25),
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('acct_locations', 'name')->where('company_id', $company->id)],
            'code' => ['nullable', 'string', 'max:30'],
            'branch_id' => ['nullable', 'integer', Rule::exists('acct_branches', 'id')->where('company_id', $company->id)],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Location::query()->create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Location created.');
    }

    public function update(Request $request, WorkspaceContext $context, Location $location): RedirectResponse
    {
        abort_unless($context->company() && $location->company_id === $context->company()->id, 404);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('acct_locations', 'name')
                    ->where('company_id', $context->company()->id)
                    ->ignore($location->id),
            ],
            'code' => ['nullable', 'string', 'max:30'],
            'branch_id' => ['nullable', 'integer', Rule::exists('acct_branches', 'id')->where('company_id', $context->company()->id)],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $location->update([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Location updated.');
    }
}
