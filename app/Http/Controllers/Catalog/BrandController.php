<?php

namespace App\Http\Controllers\Catalog;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

use App\Support\Traits\ExportsListing;
use App\Support\Traits\SortableAndSearchable;
use Symfony\Component\HttpFoundation\Response;

class BrandController extends Controller
{
    use ExportsListing, SortableAndSearchable;

    public function index(Request $request): View|Response
    {
        $query = Brand::query()
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        $this->applySearch($query, $request->input('search'), ['name', 'detail']);

        if ($export = $this->exportIfRequested($request, $query, 'Brands', 'Brands', ['Name', 'Details', 'Status'], fn ($item) => [
            $item->name,
            $item->detail ?: '',
            $item->is_active ? 'Active' : 'Inactive',
        ])) {
            return $export;
        }

        $sortData = $this->applySorting(
            $query,
            $request,
            ['name', 'is_active', 'created_at'],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('masters.brands.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        $defaultCompanyId = auth()->user()?->company_id ?? Company::defaultCompanyId();

        return view('masters.brands.form', [
            'item' => new Brand(['company_id' => $defaultCompanyId]),
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            Brand::create($data);
        } catch (QueryException $e) {
            $isDuplicate = isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062;
            $message = $e->getMessage();

            $isNameDuplicate = $isDuplicate && (
                str_contains($message, 'brands_name_unique') ||
                str_contains($message, 'brands.name') ||
                str_contains($message, "for key 'name'")
            );

            if ($isNameDuplicate || $isDuplicate) {
                return back()->withInput()->withErrors(['name' => 'Brand name already exists.']);
            }

            throw $e;
        }

        return $this->flashSuccess('Brand created successfully.', 'masters.brands.index');
    }

    public function edit(Brand $brand): View
    {
        return view('masters.brands.form', [
            'item' => $brand,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $this->validated($request, $brand);

        try {
            $brand->update($data);
        } catch (QueryException $e) {
            $isDuplicate = isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062;
            $message = $e->getMessage();

            if ($isDuplicate && (str_contains($message, 'brands_name_unique') || str_contains($message, 'brands.name') || str_contains($message, "for key 'name'"))) {
                return back()->withInput()->withErrors(['name' => 'Brand name already exists.']);
            }
            if ($isDuplicate) {
                return back()->withInput()->withErrors(['name' => 'Brand name already exists.']);
            }
            throw $e;
        }

        return $this->flashSuccess('Brand updated successfully.', 'masters.brands.index');
    }

    public function destroy(Request $request, Brand $brand): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Enforce existing authentication and authorization rules
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('masters.manage') &&
            ! $user->can('masters.edit') &&
            ! $user->can('masters.delete') &&
            ! $user->can('products.manage') &&
            ! $user->can('products.edit')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to delete brands.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: You do not have permission to delete brands.');
        }

        // Multi-tenancy company boundary check
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && $brand->company_id && (int) $brand->company_id !== (int) $user->company_id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This brand belongs to another company.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: This brand belongs to another company.');
        }

        $brandName = $brand->name;

        try {
            DB::transaction(function () use ($brand) {
                // Safely disassociate referencing entities so dependent records (products,
                // categories, schemes, targets) remain completely intact and functional,
                // matching the database foreign key nullOnDelete / cascadeOnDelete rules.
                Product::where('brand_id', $brand->id)->update(['brand_id' => null]);
                Category::where('brand_id', $brand->id)->update(['brand_id' => null]);

                if (Schema::hasTable('schemes')) {
                    DB::table('schemes')->where('brand_id', $brand->id)->update(['brand_id' => null]);
                }
                if (Schema::hasTable('target_periods')) {
                    DB::table('target_periods')->where('brand_id', $brand->id)->update(['brand_id' => null]);
                }
                if (Schema::hasTable('party_targets')) {
                    DB::table('party_targets')->where('brand_id', $brand->id)->update(['brand_id' => null]);
                }
                if (Schema::hasTable('region_brand_policies')) {
                    DB::table('region_brand_policies')->where('brand_id', $brand->id)->delete();
                }

                $brand->delete();
            });

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Brand '{$brandName}' deleted successfully.",
                ]);
            }

            return $this->flashSuccess("Brand '{$brandName}' deleted successfully.", 'masters.brands.index');
        } catch (\Throwable $e) {
            report($e);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete brand: ' . $e->getMessage(),
                ], 500);
            }

            return $this->flashError('Failed to delete brand: ' . $e->getMessage(), 'masters.brands.index');
        }
    }


    public function quickStore(Request $request): JsonResponse
    {
        $rawName = trim((string) $request->input('name', ''));
        if ($rawName === '') {
            return response()->json(['success' => false, 'message' => 'Brand Name is required.'], 422);
        }
        if (mb_strlen($rawName) > 255) {
            return response()->json(['success' => false, 'message' => 'Brand Name cannot exceed 255 characters.'], 422);
        }

        $detail = trim((string) $request->input('detail', ''));
        if (mb_strlen($detail) > 2000) {
            return response()->json(['success' => false, 'message' => 'Detail cannot exceed 2000 characters.'], 422);
        }

        $companyId = $request->filled('company_id')
            ? $request->integer('company_id')
            : (auth()->user()?->company_id ?? Company::defaultCompanyId());
        if ($companyId && ! Company::whereKey($companyId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Select a valid company.'], 422);
        }

        $existing = Brand::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($rawName)])
            ->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Brand already exists and has been selected.',
                'item' => [
                    'id' => (string) $existing->id,
                    'name' => $existing->name,
                ],
            ]);
        }

        $brand = Brand::create([
            'company_id' => $companyId,
            'name' => $rawName,
            'detail' => $detail !== '' ? $detail : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Brand created successfully.',
            'item' => [
                'id' => (string) $brand->id,
                'name' => $brand->name,
            ],
        ], 201);
    }

    protected function validated(Request $request, ?Brand $brand = null): array
    {
        if ($request->has('name')) {
            $request->merge(['name' => trim((string) $request->input('name'))]);
        }

        $rules = [
            'company_id' => 'nullable|exists:companies,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->ignore($brand?->id),
            ],
            'detail' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ];

        $data = $request->validate($rules, [
            'name.unique' => 'Brand name already exists.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['company_id'] = $data['company_id'] ?? auth()->user()?->company_id;

        return $data;
    }
}