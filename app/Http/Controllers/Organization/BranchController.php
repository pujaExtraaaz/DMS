<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $items = Branch::query()
            ->with('company')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')->orWhere('code', 'like', '%'.$request->search.'%');
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('organization.branches.index', ['items' => $items, 'search' => $request->string('search')]);
    }

    public function create(): View
    {
        return view('organization.branches.form', [
            'item' => new Branch,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Branch::create($this->validated($request));

        return $this->flashSuccess('Branch created successfully.', 'organization.branches.index');
    }

    public function edit(Branch $branch): View
    {
        return view('organization.branches.form', [
            'item' => $branch,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $branch->update($this->validated($request, $branch));

        return $this->flashSuccess('Branch updated successfully.', 'organization.branches.index');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $branch->delete();

        return $this->flashSuccess('Branch deleted successfully.', 'organization.branches.index');
    }

    protected function validated(Request $request, ?Branch $branch = null): array
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:30',
            'address' => 'nullable|string',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:12',
            'phone' => 'nullable|string|max:20',
            'gstin' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
