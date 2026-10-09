<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Support\Traits\SortableAndSearchable;

class BranchController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = Branch::query()
            ->with('company')
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        $this->applySearch($query, $request->input('search'), ['name', 'code', 'city']);

        $sortData = $this->applySorting(
            $query,
            $request,
            ['name', 'code', 'is_active', 'created_at'],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('organization.branches.index', [
            'items' => $items,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'companyId' => $request->input('company_id'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
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
