<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BusinessGroupController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View|JsonResponse
    {
        $query = BusinessGroup::query()
            ->with(['parentCompany', 'companies'])
            ->withCount('links');

        $this->applySearch($query, $request->input('search'), [
            'name',
            'code',
            'industry',
            'headquarters',
            'country',
            'description',
        ]);

        [$sort, $direction] = $this->applySorting(
            $query,
            $request,
            [
                'name' => 'name',
                'code' => 'code',
                'industry' => 'industry',
                'headquarters' => 'headquarters',
                'links_count' => 'links_count',
                'is_active' => 'is_active',
            ],
            'name',
            'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        // Attach computed metrics to each item
        foreach ($items as $item) {
            $metrics = $this->computeGroupMetrics($item);
            $item->calculated_turnover = $metrics['turnover_formatted'];
            $item->calculated_turnover_raw = $metrics['turnover'];
            $item->calculated_employees = $metrics['employees_count'];
            $item->calculated_companies_count = $metrics['companies_count'];
        }

        if ($request->wantsJson() && ! $request->hasHeader('X-DMS-Dynamic')) {
            return response()->json([
                'success' => true,
                'items' => $items,
            ]);
        }

        $tabCounts = app(SisterConcernController::class)->getTabCounts();
        $companies = Company::where('is_active', true)->orderBy('name')->get();

        return view('organization.business-groups.index', [
            'items' => $items,
            'companies' => $companies,
            'tabCounts' => $tabCounts,
            'search' => $request->string('search'),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(): View
    {
        return view('organization.business-groups.form', [
            'item' => new BusinessGroup([
                'country' => 'India',
                'logo_emoji' => '🏢',
                'is_active' => true,
            ]),
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'selectedCompanies' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validated($request);
        $companyIds = $request->input('company_ids', []);

        // Ensure parent company is included in linked companies
        if (! empty($data['parent_company_id']) && ! in_array($data['parent_company_id'], $companyIds)) {
            $companyIds[] = (int) $data['parent_company_id'];
        }

        $group = BusinessGroup::create($data);
        $group->companies()->sync($companyIds);

        // Update parent company's direct business_group_id if not set
        if (! empty($data['parent_company_id'])) {
            Company::where('id', $data['parent_company_id'])
                ->whereNull('business_group_id')
                ->update(['business_group_id' => $group->id]);
        }

        if ($request->wantsJson()) {
            $group->load(['parentCompany', 'companies']);
            $metrics = $this->computeGroupMetrics($group);
            $group->calculated_turnover = $metrics['turnover_formatted'];
            $group->calculated_employees = $metrics['employees_count'];
            $group->calculated_companies_count = $metrics['companies_count'];

            return response()->json([
                'success' => true,
                'message' => "Business group '{$group->name}' created successfully.",
                'group' => $group,
            ]);
        }

        return $this->flashSuccess("Business group '{$group->name}' created successfully.", 'organization.business-groups.index');
    }

    public function show(Request $request, BusinessGroup $business_group): View|JsonResponse
    {
        $business_group->load(['parentCompany', 'companies']);
        $metrics = $this->computeGroupMetrics($business_group);
        $business_group->calculated_turnover = $metrics['turnover_formatted'];
        $business_group->calculated_turnover_raw = $metrics['turnover'];
        $business_group->calculated_employees = $metrics['employees_count'];
        $business_group->calculated_companies_count = $metrics['companies_count'];

        return response()->json([
            'success' => true,
            'group' => $business_group,
            'metrics' => $metrics,
        ]);
    }

    public function edit(BusinessGroup $business_group): View|JsonResponse
    {
        $business_group->load(['parentCompany', 'companies']);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'group' => $business_group,
                'selectedCompanies' => $business_group->companies()->pluck('companies.id')->all(),
            ]);
        }

        return view('organization.business-groups.form', [
            'item' => $business_group,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'selectedCompanies' => $business_group->companies()->pluck('companies.id')->all(),
        ]);
    }

    public function update(Request $request, BusinessGroup $business_group): RedirectResponse|JsonResponse
    {
        $data = $this->validated($request, $business_group);
        $companyIds = $request->input('company_ids', []);

        if (! empty($data['parent_company_id']) && ! in_array($data['parent_company_id'], $companyIds)) {
            $companyIds[] = (int) $data['parent_company_id'];
        }

        $business_group->update($data);
        $business_group->companies()->sync($companyIds);

        if (! empty($data['parent_company_id'])) {
            Company::where('id', $data['parent_company_id'])
                ->whereNull('business_group_id')
                ->update(['business_group_id' => $business_group->id]);
        }

        if ($request->wantsJson()) {
            $business_group->load(['parentCompany', 'companies']);
            $metrics = $this->computeGroupMetrics($business_group);
            $business_group->calculated_turnover = $metrics['turnover_formatted'];
            $business_group->calculated_employees = $metrics['employees_count'];
            $business_group->calculated_companies_count = $metrics['companies_count'];

            return response()->json([
                'success' => true,
                'message' => "Business group '{$business_group->name}' updated successfully.",
                'group' => $business_group,
            ]);
        }

        return $this->flashSuccess("Business group '{$business_group->name}' updated successfully.", 'organization.business-groups.index');
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
            if (Schema::hasColumn('companies', 'business_group_id')) {
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

    public function computeGroupMetrics(BusinessGroup $group): array
    {
        $companyIds = $group->companies->pluck('id')->all();
        if ($group->parent_company_id && ! in_array($group->parent_company_id, $companyIds)) {
            $companyIds[] = $group->parent_company_id;
        }

        $turnover = 0.0;
        if (! empty($companyIds) && Schema::hasTable('warehouses') && Schema::hasTable('orders')) {
            $whIds = Warehouse::whereIn('company_id', $companyIds)->pluck('id')->toArray();
            if (! empty($whIds)) {
                $turnover = (float) DB::table('orders')
                    ->whereIn('warehouse_id', $whIds)
                    ->where('status', '!=', 'cancelled')
                    ->sum('grand_total');
            }
        }
        if ($turnover <= 0 && Schema::hasTable('invoices') && ! empty($companyIds)) {
            if (Schema::hasColumn('invoices', 'company_id')) {
                $turnover = (float) DB::table('invoices')
                    ->whereIn('company_id', $companyIds)
                    ->where('status', '!=', 'cancelled')
                    ->sum('grand_total');
            }
        }

        $employeesCount = 0;
        if (! empty($companyIds) && class_exists(Employee::class) && Schema::hasTable('employees')) {
            $employeesCount = Employee::whereIn('company_id', $companyIds)->count();
        }

        $turnoverInLakhs = round($turnover / 100000, 2);
        $turnoverFormatted = $turnoverInLakhs >= 1
            ? '₹' . number_format($turnoverInLakhs, 2) . ' L'
            : ($turnover > 0 ? '₹' . number_format($turnover, 2) : '₹0.00');

        return [
            'turnover' => $turnover,
            'turnover_formatted' => $turnoverFormatted,
            'employees_count' => $employeesCount,
            'companies_count' => count($companyIds),
        ];
    }

    protected function validated(Request $request, ?BusinessGroup $group = null): array
    {
        $hasCompanies = Company::count() > 0;
        $parentRule = $hasCompanies ? 'required|exists:companies,id' : 'nullable|exists:companies,id';

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:30|unique:business_groups,code' . ($group ? ',' . $group->id : ''),
            'parent_company_id' => $parentRule,
            'incorporation_date' => 'nullable|date',
            'headquarters' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'industry' => 'nullable|string|max:150',
            'website' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'logo_emoji' => 'nullable|string|max:20',
            'is_active' => 'nullable',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'company_ids' => 'nullable|array',
            'company_ids.*' => 'exists:companies,id',
        ], [
            'name.required' => 'Group Name is required.',
            'code.required' => 'Group Code is required.',
            'code.unique' => 'This Group Code is already in use.',
            'parent_company_id.required' => 'Parent Company is required.',
            'parent_company_id.exists' => 'Selected Parent Company does not exist.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['country'] = $data['country'] ?? 'India';
        $data['logo_emoji'] = $data['logo_emoji'] ?? '🏢';

        unset($data['company_ids']);

        return $data;
    }
}
