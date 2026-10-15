<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BusinessGroupController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = BusinessGroup::query()
            ->withCount('links');

        $this->applySearch($query, $request->input('search'), [
            'name',
            'code',
            'description',
        ]);

        [$sort, $direction] = $this->applySorting(
            $query,
            $request,
            [
                'name' => 'name',
                'code' => 'code',
                'links_count' => 'links_count',
                'is_active' => 'is_active',
            ],
            'name',
            'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('organization.business-groups.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(): View
    {
        return view('organization.business-groups.form', [
            'item' => new BusinessGroup,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'selectedCompanies' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $group = BusinessGroup::create($data);
        $group->companies()->sync($request->input('company_ids', []));
        return $this->flashSuccess('Business group created successfully.', 'organization.business-groups.index');
    }

    public function edit(BusinessGroup $business_group): View
    {
        return view('organization.business-groups.form', [
            'item' => $business_group,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'selectedCompanies' => $business_group->companies()->pluck('companies.id')->all(),
        ]);
    }

    public function update(Request $request, BusinessGroup $business_group): RedirectResponse
    {
        $business_group->update($this->validated($request, $business_group));
        $business_group->companies()->sync($request->input('company_ids', []));
        return $this->flashSuccess('Business group updated successfully.', 'organization.business-groups.index');
    }

    public function destroy(Request $request, BusinessGroup $business_group): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Check user permissions before deleting
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) && ! $user->can('organization.manage') && ! $user->can('organization.edit')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete sister concern group.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized to delete sister concern group.');
        }

        // Check if group is protected or blocked by dependencies
        $blockReason = $business_group->getDeleteBlockReason();
        if ($blockReason) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $blockReason,
                ], 422);
            }

            return back()->with('error', $blockReason);
        }

        $groupName = $business_group->name;

        DB::transaction(function () use ($business_group) {
            // Safely detach any pivot links and clear direct business_group_id on companies
            if (\Illuminate\Support\Facades\Schema::hasColumn('companies', 'business_group_id')) {
                Company::where('business_group_id', $business_group->id)->update(['business_group_id' => null]);
            }

            $business_group->companies()->detach();

            $business_group->delete();
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Sister concern group '{$groupName}' deleted successfully.",
            ]);
        }

        return $this->flashSuccess("Sister concern group '{$groupName}' deleted successfully.", 'organization.business-groups.index')
            ->with('success', "Sister concern group '{$groupName}' deleted successfully.");
    }

    protected function validated(Request $request, ?BusinessGroup $group = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:30|unique:business_groups,code'.($group ? ','.$group->id : ''),
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'company_ids' => 'nullable|array',
            'company_ids.*' => 'exists:companies,id',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        unset($data['company_ids']);
        return $data;
    }
}
