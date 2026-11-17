<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Master\Models\CustomCity;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BranchType;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\IndianCities;
use App\Support\IndianStates;
use App\Support\NameCase;
use App\Support\Traits\SortableAndSearchable;
use App\Support\WorldCountries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

        $this->applySearch($query, $request->input('search'), ['name', 'code', 'state', 'city', 'address', 'phone', 'email', 'branch_type']);

        $sortData = $this->applySorting(
            $query,
            $request,
            ['name', 'is_active', 'created_at'],
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

        return view('organization.branches.form', $this->formData(new Branch([
            'company_id' => $defaultCompanyId,
            'country' => 'India',
            'is_active' => true,
            'is_default' => false,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $branch = Branch::create($data);
            $this->syncDefaultFlag($branch);
        });

        return $this->flashSuccess('Branch created successfully.', 'organization.branches.index');
    }

    public function edit(Branch $branch): View
    {
        return view('organization.branches.form', $this->formData($branch));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $data = $this->validated($request, $branch);

        DB::transaction(function () use ($branch, $data) {
            $branch->update($data);
            $this->syncDefaultFlag($branch->fresh());
        });

        return $this->flashSuccess('Branch updated successfully.', 'organization.branches.index');
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) && ! $user->can('organization.manage') && ! $user->can('organization.edit')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete branch.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized to delete branch.');
        }

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

        DB::transaction(function () use ($branch) {
            foreach ($branch->warehouses as $warehouse) {
                $warehouse->update(['is_active' => false]);
                $warehouse->delete();
            }

            \App\Models\User::where('branch_id', $branch->id)->update(['branch_id' => null]);
            if (Schema::hasTable('user_branch_access')) {
                DB::table('user_branch_access')->where('branch_id', $branch->id)->delete();
            }

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

    public function cities(Request $request): JsonResponse
    {
        $state = IndianStates::normalizeName((string) $request->query('state', ''));
        $cities = IndianCities::getCitiesForState($state);

        return response()->json([
            'success' => true,
            'state' => $state,
            'cities' => $cities,
            'count' => count($cities),
        ]);
    }

    public function storeCity(Request $request): JsonResponse
    {
        $state = IndianStates::normalizeName(trim((string) $request->input('state', '')));
        $name = NameCase::format(trim((string) $request->input('name', ''))) ?? '';

        if ($state === '' || ! in_array($state, IndianStates::namesAlphabetical(), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Select a valid state before adding a city.',
            ], 422);
        }

        if ($name === '') {
            return response()->json([
                'success' => false,
                'message' => 'City name is required.',
            ], 422);
        }

        if (mb_strlen($name) > 255) {
            return response()->json([
                'success' => false,
                'message' => 'City name cannot exceed 255 characters.',
            ], 422);
        }

        $existing = IndianCities::getCitiesForState($state);
        foreach ($existing as $city) {
            if (strcasecmp($city, $name) === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'This city is already in the list.',
                    'item' => ['state' => $state, 'name' => $city],
                ]);
            }
        }

        if (! IndianCities::isValidCityForState($name, $state)) {
            return response()->json([
                'success' => false,
                'message' => 'This city belongs to another state.',
            ], 422);
        }

        $city = CustomCity::query()->firstOrCreate([
            'state' => $state,
            'name' => $name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'City added for this state.',
            'item' => ['state' => $city->state, 'name' => $city->name],
        ], 201);
    }

    public function show(Branch $branch): View
    {
        $branch->load(['company', 'warehouses' => fn ($q) => $q->orderBy('name')]);

        return view('organization.branches.show', [
            'item' => $branch,
        ]);
    }

    public function storeBranchType(Request $request): JsonResponse
    {
        $rawName = trim((string) $request->input('name', ''));
        $name = NameCase::format($rawName) ?? '';

        if ($name === '') {
            return response()->json([
                'success' => false,
                'message' => 'Branch type name is required.',
                'errors' => ['name' => ['Branch type name is required.']],
            ], 422);
        }

        if (mb_strlen($name) > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Branch type name cannot exceed 100 characters.',
                'errors' => ['name' => ['Branch type name cannot exceed 100 characters.']],
            ], 422);
        }

        $existing = BranchType::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Branch type already exists.',
                'item' => ['id' => $existing->id, 'name' => $existing->name],
            ]);
        }

        $type = BranchType::create([
            'name' => $name,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch type created.',
            'item' => ['id' => $type->id, 'name' => $type->name],
        ], 201);
    }

    protected function formData(Branch $item): array
    {
        $branchTypes = collect(Branch::DEFAULT_BRANCH_TYPES)
            ->map(fn ($name) => ['name' => $name])
            ->values();

        if (Schema::hasTable('branch_types')) {
            $dbTypes = BranchType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($t) => ['name' => $t->name]);
            $branchTypes = $branchTypes
                ->concat($dbTypes)
                ->unique(fn ($t) => mb_strtolower($t['name']))
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        }

        return [
            'item' => $item,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'branchTypes' => $branchTypes,
            'states' => IndianStates::namesAlphabetical(),
            'stateCities' => IndianCities::all(),
            'countries' => WorldCountries::all(),
        ];
    }

    protected function validated(Request $request, ?Branch $branch = null): array
    {
        $stateNames = IndianStates::namesAlphabetical();
        $countryNames = WorldCountries::all();

        if ($request->filled('name')) {
            $request->merge(['name' => NameCase::format(trim((string) $request->input('name')))]);
        }
        if ($request->filled('branch_type')) {
            $request->merge(['branch_type' => NameCase::format(trim((string) $request->input('branch_type')))]);
        }
        if ($request->filled('city')) {
            $request->merge(['city' => NameCase::format(trim((string) $request->input('city')))]);
        }
        if ($request->filled('address')) {
            $request->merge(['address' => NameCase::format(trim((string) $request->input('address')))]);
        }
        if ($request->filled('address_line_2')) {
            $request->merge(['address_line_2' => NameCase::format(trim((string) $request->input('address_line_2')))]);
        }
        if ($request->filled('state')) {
            $request->merge(['state' => IndianStates::normalizeName((string) $request->input('state'))]);
        }

        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'alternate_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'alternate_email' => 'nullable|email|max:255',
            'address' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:255',
            'state' => ['required', 'string', 'max:100', Rule::in($stateNames)],
            'city' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) use ($request) {
                    $state = IndianStates::normalizeName((string) $request->input('state', ''));
                    if ($state === '' || $value === null || $value === '') {
                        return;
                    }
                    $cities = IndianCities::getCitiesForState($state);
                    $ok = collect($cities)->contains(fn ($c) => strcasecmp((string) $c, (string) $value) === 0);
                    if (! $ok) {
                        $fail('Selected city does not belong to the selected state.');
                    }
                },
            ],
            'country' => ['required', 'string', 'max:100', Rule::in($countryNames)],
            'pincode' => 'nullable|string|max:12',
            'branch_type' => 'required|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ], [
            'name.required' => 'Branch name is required.',
            'phone.required' => 'Phone is required.',
            'address.required' => 'Address Line 1 is required.',
            'state.required' => 'State is required.',
            'city.required' => 'City is required.',
            'country.required' => 'Country is required.',
            'branch_type.required' => 'Branch type is required.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');
        $data['country'] = $data['country'] ?: 'India';
        $data['address_line_2'] = filled($data['address_line_2'] ?? null) ? trim((string) $data['address_line_2']) : null;
        $data['alternate_phone'] = filled($data['alternate_phone'] ?? null) ? trim((string) $data['alternate_phone']) : null;
        $data['email'] = filled($data['email'] ?? null) ? trim((string) $data['email']) : null;
        $data['alternate_email'] = filled($data['alternate_email'] ?? null) ? trim((string) $data['alternate_email']) : null;
        $data['notes'] = filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null;

        // Ensure custom branch type exists in lookup table when used.
        if (Schema::hasTable('branch_types') && filled($data['branch_type'] ?? null)) {
            BranchType::query()->firstOrCreate(
                ['name' => $data['branch_type']],
                ['is_active' => true]
            );
        }

        if ($branch && filled($branch->getRawOriginal('code'))) {
            $data['code'] = $branch->getRawOriginal('code');
        } else {
            $data['code'] = \App\Support\CodeGenerator::forBranch((int) $data['company_id']);
        }

        return $data;
    }

    protected function syncDefaultFlag(?Branch $branch): void
    {
        if (! $branch || ! Schema::hasColumn('branches', 'is_default')) {
            return;
        }

        if ($branch->is_default) {
            Branch::query()
                ->where('company_id', $branch->company_id)
                ->whereKeyNot($branch->id)
                ->update(['is_default' => false]);
        }
    }
}
