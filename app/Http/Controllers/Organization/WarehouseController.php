<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Support\Traits\SortableAndSearchable;

class WarehouseController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = Warehouse::query()->with(['company', 'branch'])
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

        return view('organization.warehouses.index', [
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
        $defaultCompanyId = auth()->user()?->company_id ?? Company::defaultCompanyId();

        return view('organization.warehouses.form', [
            'item' => new Warehouse(['company_id' => $defaultCompanyId]),
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

    public function destroy(Request $request, Warehouse $warehouse): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Check user permissions before deleting
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) && ! $user->can('organization.manage') && ! $user->can('organization.edit')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete warehouse.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized to delete warehouse.');
        }

        // Prevent deleting a warehouse if it belongs to another company or unauthorized tenant
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && (int) $warehouse->company_id !== (int) $user->company_id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This warehouse belongs to another company.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: This warehouse belongs to another company.');
        }

        $warehouseName = $warehouse->getRawOriginal('name') ?: $warehouse->name;

        \Illuminate\Support\Facades\DB::transaction(function () use ($warehouse) {
            $warehouse->is_default = false;
            $warehouse->is_active = false;
            $warehouse->save();
            $warehouse->delete();
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Warehouse '{$warehouseName}' deleted successfully.",
            ]);
        }

        return $this->flashSuccess("Warehouse '{$warehouseName}' deleted successfully.", 'organization.warehouses.index')
            ->with('success', "Warehouse '{$warehouseName}' deleted successfully.");
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
