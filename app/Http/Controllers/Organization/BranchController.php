<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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
        $defaultCompanyId = auth()->user()?->company_id ?? Company::defaultCompanyId();

        return view('organization.branches.form', [
            'item' => new Branch(['company_id' => $defaultCompanyId]),
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

    public function destroy(Request $request, Branch $branch): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Check user permissions before deleting
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) && ! $user->can('organization.manage') && ! $user->can('organization.edit')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete branch.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized to delete branch.');
        }

        // Prevent deleting a branch if it belongs to another company or unauthorized tenant
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && (int) $branch->company_id !== (int) $user->company_id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This branch belongs to another company.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: This branch belongs to another company.');
        }

        $branchName = $branch->getRawOriginal('name') ?: $branch->name;

        \Illuminate\Support\Facades\DB::transaction(function () use ($branch) {
            // Automatically handle linked warehouses: soft-delete and deactivate them
            foreach ($branch->warehouses as $warehouse) {
                $warehouse->update(['is_active' => false]);
                $warehouse->delete();
            }

            // Unassign default branch from assigned users and clean up branch access pivot
            \App\Models\User::where('branch_id', $branch->id)->update(['branch_id' => null]);
            if (\Illuminate\Support\Facades\Schema::hasTable('user_branch_access')) {
                \Illuminate\Support\Facades\DB::table('user_branch_access')->where('branch_id', $branch->id)->delete();
            }

            // Mark branch inactive and soft delete it
            $branch->is_active = false;
            $branch->save();
            $branch->delete();
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Branch '{$branchName}' deleted successfully.",
            ]);
        }

        return $this->flashSuccess("Branch '{$branchName}' deleted successfully.", 'organization.branches.index')
            ->with('success', "Branch '{$branchName}' deleted successfully.");
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
