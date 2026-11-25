<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Organization\Models\WarehouseType;
use App\Http\Controllers\Controller;
use App\Support\IndianCities;
use App\Support\IndianStates;
use App\Support\NameCase;
use App\Support\Traits\ExportsListing;
use App\Support\Traits\SortableAndSearchable;
use App\Support\WorldCountries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WarehouseController extends Controller
{
    use SortableAndSearchable, ExportsListing;

    public function index(Request $request): View|Response|StreamedResponse
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

        $this->applySearch($query, $request->input('search'), ['name', 'code', 'city', 'state', 'warehouse_type', 'contact_person']);

        $sortData = $this->applySorting(
            $query,
            $request,
            ['name', 'is_active', 'created_at'],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        if ($export = $this->exportIfRequested(
            $request,
            $query,
            'warehouses',
            'Warehouses Master',
            ['Warehouse Name', 'Code', 'Company', 'Branch', 'Type', 'Contact Person', 'Phone', 'Email', 'Address', 'City', 'State', 'Status', 'Created At'],
            fn (Warehouse $w) => [
                'name' => $w->name,
                'code' => $w->code ?? '',
                'company' => $w->company?->name ?? '',
                'branch' => $w->branch?->name ?? '',
                'type' => $w->warehouse_type ?? '',
                'contact_person' => $w->contact_person ?? '',
                'phone' => $w->phone ?? '',
                'email' => $w->email ?? '',
                'address' => $w->address ?? '',
                'city' => $w->city ?? '',
                'state' => $w->state ?? '',
                'status' => $w->is_active ? 'Active' : 'Inactive',
                'created_at' => optional($w->created_at)->format('Y-m-d H:i:s'),
            ]
        )) {
            return $export;
        }

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

        return view('organization.warehouses.form', $this->formData(new Warehouse([
            'company_id' => $defaultCompanyId,
            'country' => 'India',
            'is_active' => true,
            'is_default' => false,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $warehouse = Warehouse::create($data);
        if (! empty($data['is_default'])) {
            $this->clearOtherDefaults($warehouse);
        }

        return $this->flashSuccess('Warehouse created successfully.', 'organization.warehouses.index');
    }

    public function show(Warehouse $warehouse): View
    {
        $warehouse->load(['company', 'branch']);

        return view('organization.warehouses.show', [
            'item' => $warehouse,
        ]);
    }

    public function edit(Warehouse $warehouse): View
    {
        return view('organization.warehouses.form', $this->formData($warehouse));
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $this->validated($request, $warehouse);
        $warehouse->update($data);
        if (! empty($data['is_default'])) {
            $this->clearOtherDefaults($warehouse);
        }

        return $this->flashSuccess('Warehouse updated successfully.', 'organization.warehouses.index');
    }

    public function destroy(Request $request, Warehouse $warehouse): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) && ! $user->can('organization.manage') && ! $user->can('organization.edit')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete warehouse.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized to delete warehouse.');
        }

        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && (int) $warehouse->company_id !== (int) $user->company_id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This warehouse belongs to another company.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: This warehouse belongs to another company.');
        }

        if ($reason = $warehouse->getDeleteBlockReason()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $reason], 422);
            }

            return back()->with('error', $reason);
        }

        $warehouseName = $warehouse->getRawOriginal('name') ?: $warehouse->name;

        DB::transaction(function () use ($warehouse) {
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

    public function storeWarehouseType(Request $request): JsonResponse
    {
        $rawName = trim((string) $request->input('name', ''));
        $name = NameCase::format($rawName) ?? '';

        if ($name === '') {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse type name is required.',
                'errors' => ['name' => ['Warehouse type name is required.']],
            ], 422);
        }

        if (mb_strlen($name) > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse type name cannot exceed 100 characters.',
                'errors' => ['name' => ['Warehouse type name cannot exceed 100 characters.']],
            ], 422);
        }

        $existing = WarehouseType::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Warehouse type already exists.',
                'item' => ['id' => $existing->id, 'name' => $existing->name],
            ]);
        }

        $type = WarehouseType::create([
            'name' => $name,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Warehouse type created.',
            'item' => ['id' => $type->id, 'name' => $type->name],
        ], 201);
    }

    protected function formData(Warehouse $item): array
    {
        $warehouseTypes = collect(Warehouse::DEFAULT_WAREHOUSE_TYPES)
            ->map(fn ($name) => $name)
            ->values();

        if (Schema::hasTable('warehouse_types')) {
            $dbTypes = WarehouseType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name');
            $warehouseTypes = $warehouseTypes
                ->concat($dbTypes)
                ->unique(fn ($name) => mb_strtolower((string) $name))
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        }

        return [
            'item' => $item,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'company_id', 'name']),
            'warehouseTypes' => $warehouseTypes,
            'states' => IndianStates::namesAlphabetical(),
            'stateCities' => IndianCities::all(),
            'countries' => WorldCountries::all(),
            'citiesUrl' => route('organization.branches.cities'),
            'cityStoreUrl' => route('organization.branches.cities.quick-store'),
            'warehouseTypeStoreUrl' => route('organization.warehouses.warehouse-types.quick-store'),
        ];
    }

    protected function validated(Request $request, ?Warehouse $warehouse = null): array
    {
        $companyId = $request->integer('company_id') ?: null;
        $stateNames = IndianStates::namesAlphabetical();
        $countryNames = WorldCountries::all();

        foreach (['name', 'contact_person', 'address', 'address_line_2', 'city', 'warehouse_type'] as $field) {
            if ($request->filled($field)) {
                $request->merge([$field => NameCase::format(trim((string) $request->input($field)))]);
            }
        }
        if ($request->filled('state')) {
            $request->merge(['state' => IndianStates::normalizeName((string) $request->input('state'))]);
        }

        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30',
            'warehouse_type' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:30',
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
            'notes' => 'nullable|string|max:2000',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ], [
            'branch_id.exists' => 'The selected branch does not belong to the selected company.',
            'address.required' => 'Address Line 1 is required.',
            'state.required' => 'State is required.',
            'city.required' => 'City is required.',
            'country.required' => 'Country is required.',
        ]);

        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active');
        $data['country'] = $data['country'] ?: 'India';
        $data['warehouse_type'] = filled($data['warehouse_type'] ?? null) ? trim((string) $data['warehouse_type']) : null;
        $data['contact_person'] = filled($data['contact_person'] ?? null) ? trim((string) $data['contact_person']) : null;
        $data['contact_phone'] = filled($data['contact_phone'] ?? null) ? trim((string) $data['contact_phone']) : null;
        $data['address_line_2'] = filled($data['address_line_2'] ?? null) ? trim((string) $data['address_line_2']) : null;
        $data['notes'] = filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null;

        if (blank($data['code'] ?? null)) {
            if ($warehouse && filled($warehouse->code)) {
                $data['code'] = $warehouse->code;
            } else {
                $generated = \App\Support\CodeGenerator::forWarehouse(isset($data['company_id']) ? (int) $data['company_id'] : null);
                $data['code'] = filled($generated)
                    ? $generated
                    : $this->warehouseCode((string) $data['name'], (int) $data['branch_id'], $warehouse?->id);
            }
        }

        return $data;
    }

    protected function clearOtherDefaults(Warehouse $warehouse): void
    {
        Warehouse::query()
            ->where('company_id', $warehouse->company_id)
            ->whereKeyNot($warehouse->id)
            ->update(['is_default' => false]);
    }

    protected function warehouseCode(string $name, int $branchId, ?int $ignoreId = null): string
    {
        $base = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $name));
        $base = substr($base !== '' ? $base : 'WH', 0, 24);
        $code = $base;
        $suffix = 2;

        while (Warehouse::query()
            ->where('branch_id', $branchId)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('code', $code)
            ->exists()) {
            $tail = (string) $suffix;
            $code = substr($base, 0, 30 - strlen($tail)).$tail;
            $suffix++;
        }

        return $code;
    }
}
