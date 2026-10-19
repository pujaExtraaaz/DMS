<?php

namespace App\Http\Controllers\Catalog;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
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
            'brands' => Brand::where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return $this->flashSuccess(
            'Category created successfully.',
            'masters.categories.index'
        );
    }

    public function edit(Category $category): View
    {
        return view('masters.categories.form', [
            'item' => $category,
            'brands' => Brand::where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(
        Request $request,
        Category $category
    ): RedirectResponse {
        $category->update($this->validated($request, $category));

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

    protected function validated(
        Request $request,
        ?Category $category = null
    ): array {
        $data = $request->validate([
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}