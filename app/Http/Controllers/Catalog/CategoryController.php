<?php

namespace App\Http\Controllers\Catalog;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\Traits\ExportsListing;
use App\Support\Traits\SortableAndSearchable;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use ExportsListing, SortableAndSearchable;

    public function index(Request $request): View|Response
    {
        $query = Category::query()
            ->with('brand')
            ->when(
                $request->filled('brand_id'),
                fn ($q) => $q->where('brand_id', $request->brand_id)
            )
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        $this->applySearch(
            $query,
            $request->input('search'),
            ['name']
        );

        if ($export = $this->exportIfRequested($request, $query, 'Categories', 'Categories', ['Name', 'Brand', 'Status'], fn ($item) => [
            $item->name,
            $item->brand?->name ?? '',
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

        return view('masters.categories.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'brandId' => $request->input('brand_id'),
            'brands' => Brand::where('is_active', true)
                ->orderBy('name')
                ->get(),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('masters.categories.form', [
            'item' => new Category,
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            Category::create($this->validated($request));
        } catch (UniqueConstraintViolationException $e) {
            return back()->withInput()->withErrors([
                'name' => 'This category already exists for the selected brand.',
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicateCategoryException($e)) {
                return back()->withInput()->withErrors([
                    'name' => 'This category already exists for the selected brand.',
                ]);
            }
            throw $e;
        }

        return $this->flashSuccess(
            'Category created successfully.',
            'masters.categories.index'
        );
    }

    public function edit(Category $category): View
    {
        return view('masters.categories.form', [
            'item' => $category,
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(
        Request $request,
        Category $category
    ): RedirectResponse {
        try {
            $category->update($this->validated($request, $category));
        } catch (UniqueConstraintViolationException $e) {
            return back()->withInput()->withErrors([
                'name' => 'This category already exists for the selected brand.',
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicateCategoryException($e)) {
                return back()->withInput()->withErrors([
                    'name' => 'This category already exists for the selected brand.',
                ]);
            }
            throw $e;
        }

        return $this->flashSuccess(
            'Category updated successfully.',
            'masters.categories.index'
        );
    }

    public function destroy(
        Request $request,
        Category $category
    ): RedirectResponse|JsonResponse {
        $user = $request->user();

        // Preserve the existing authorization rules.
        if (
            $user
            && ! $user->hasAnyRole([
                'super-admin',
                'owner',
                'client-admin',
            ])
            && ! $user->can('masters.manage')
            && ! $user->can('masters.edit')
            && ! $user->can('masters.delete')
            && ! $user->can('products.manage')
            && ! $user->can('products.edit')
        ) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to delete categories.',
                ], 403);
            }

            return back()->with(
                'error',
                'Unauthorized: You do not have permission to delete categories.'
            );
        }

        // Prevent users from deleting categories belonging to another company.
        if (
            $user
            && ! $user->hasAnyRole(['super-admin', 'client-admin'])
            && $user->company_id
            && $category->brand?->company_id
            && (int) $category->brand->company_id !== (int) $user->company_id
        ) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This category belongs to another company.',
                ], 403);
            }

            return back()->with(
                'error',
                'Unauthorized: This category belongs to another company.'
            );
        }

        $categoryName = $category->name;

        try {
            $hasSubCategories = $category->subCategories()->exists();
            $hasProducts = $category->products()->exists();

            if ($hasSubCategories || $hasProducts) {
                // Archive records that have dependencies to preserve history.
                DB::transaction(function () use ($category) {
                    $category->update(['is_active' => false]);

                    $category->subCategories()->update([
                        'is_active' => false,
                    ]);
                });

                $dependencies = [];

                if ($hasSubCategories) {
                    $dependencies[] = 'subcategories';
                }

                if ($hasProducts) {
                    $dependencies[] = 'products';
                }

                $depText = implode(' and ', $dependencies);

                $message = "Category '{$categoryName}' is linked to {$depText} and has been safely archived to preserve historical records.";

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'archived' => true,
                    ]);
                }

                return $this->flashSuccess(
                    $message,
                    'masters.categories.index'
                );
            }

            // Permanently delete only when there are no dependent records.
            DB::transaction(function () use ($category) {
                $category->delete();
            });

            $message = "Category '{$categoryName}' deleted successfully.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'archived' => false,
                ]);
            }

            return $this->flashSuccess(
                $message,
                'masters.categories.index'
            );
        } catch (\Throwable $e) {
            report($e);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete category: ' . $e->getMessage(),
                ], 500);
            }

            return $this->flashError(
                'Failed to delete category: ' . $e->getMessage(),
                'masters.categories.index'
            );
        }
    }

    public function quickStore(Request $request): JsonResponse
    {
        $rawName = trim((string) $request->input('name', ''));
        if ($rawName === '') {
            return response()->json(['success' => false, 'message' => 'Category name is required.'], 422);
        }
        if (mb_strlen($rawName) > 255) {
            return response()->json(['success' => false, 'message' => 'Category name cannot exceed 255 characters.'], 422);
        }

        $brandId = $request->filled('brand_id') ? $request->integer('brand_id') : null;
        if (! $brandId || ! Brand::whereKey($brandId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Please select a brand before creating a category.'], 422);
        }

        $name = \App\Support\TitleCase::format($rawName);
        $existing = Category::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($rawName)])
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId), fn ($q) => $q->whereNull('brand_id'))
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Category already exists and has been selected.',
                'item' => [
                    'id' => (string) $existing->id,
                    'name' => $existing->name,
                    'brand_id' => $existing->brand_id ? (string) $existing->brand_id : null,
                ],
            ]);
        }

        try {
            $category = Category::create([
                'brand_id' => $brandId,
                'name' => $name,
                'is_active' => $request->boolean('is_active', true),
            ]);
        } catch (UniqueConstraintViolationException|QueryException $e) {
            $existing = Category::query()
                ->where('brand_id', $brandId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($rawName)])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Category already exists and has been selected.',
                    'item' => [
                        'id' => (string) $existing->id,
                        'name' => $existing->name,
                        'brand_id' => $existing->brand_id ? (string) $existing->brand_id : null,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'This category already exists for the selected brand.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'item' => [
                'id' => (string) $category->id,
                'name' => $category->name,
                'brand_id' => $category->brand_id ? (string) $category->brand_id : null,
            ],
        ], 201);
    }

    protected function validated(
        Request $request,
        ?Category $category = null
    ): array {
        if ($request->has('name')) {
            $rawName = trim((string) $request->input('name'));
            $request->merge(['name' => \App\Support\TitleCase::format($rawName)]);
        }

        $brandId = $request->filled('brand_id') ? $request->integer('brand_id') : null;

        $messages = [
            'brand_id.required' => 'Please select a brand before creating a category.',
            'brand_id.exists' => 'Please select a brand before creating a category.',
            'brand_id.integer' => 'Please select a brand before creating a category.',
            'name.required' => 'Category name is required.',
        ];

        $data = $request->validate([
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($brandId, $category) {
                    $cleanName = trim((string) $value);
                    if ($brandId && $cleanName !== '') {
                        $query = Category::query()
                            ->where('brand_id', $brandId)
                            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($cleanName)]);

                        if ($category) {
                            $query->where('id', '!=', $category->id);
                        }

                        if ($query->exists()) {
                            $fail('This category already exists for the selected brand.');
                        }
                    }
                },
            ],
            'is_active' => ['boolean'],
        ], $messages);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    protected function isDuplicateCategoryException(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'categories_brand_id_name_unique')
            || str_contains($message, 'categories.brand_id')
            || str_contains($message, 'categories.name')
            || (str_contains($message, 'Duplicate entry') && (str_contains($message, 'name') || str_contains($message, 'brand')));
    }
}