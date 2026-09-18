<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(Request $request): View
    {
        $items = Warehouse::query()->with(['company', 'branch'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')->orWhere('code', 'like', '%'.$request->search.'%');
            }))
            ->latest()->paginate(15)->withQueryString();

        return view('organization.warehouses.index', ['items' => $items, 'search' => $request->string('search')]);
    }

    public function create(): View
    {
        return view('organization.warehouses.form', [
            'item' => new Warehouse,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Warehouse::create($this->validated($request));
        return $this->flashSuccess('Warehouse created successfully.', 'organization.warehouses.index');
    }

    public function edit(Warehouse $warehouse): View
    {
        return view('organization.warehouses.form', [
            'item' => $warehouse,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($this->validated($request, $warehouse));
        return $this->flashSuccess('Warehouse updated successfully.', 'organization.warehouses.index');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $warehouse->delete();
        return $this->flashSuccess('Warehouse deleted successfully.', 'organization.warehouses.index');
    }

    protected function validated(Request $request, ?Warehouse $warehouse = null): array
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:30',
            'address' => 'nullable|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);
        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }
}
