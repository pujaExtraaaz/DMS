<?php

namespace App\Http\Controllers\Catalog;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

use App\Support\Traits\SortableAndSearchable;

class SubCategoryController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = SubCategory::query()->with('category')
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        $this->applySearch($query, $request->input('search'), ['name', 'code', 'detail']);

        $sortData = $this->applySorting(
            $query,
            $request,
            ['name', 'code', 'is_active', 'created_at'],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('masters.sub-categories.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'categoryId' => $request->input('category_id'),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('masters.sub-categories.form', [
            'item' => new SubCategory,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        SubCategory::create($this->validated($request));
        return $this->flashSuccess('Sub-category created successfully.', 'masters.sub-categories.index');
    }

    public function edit(SubCategory $sub_category): View
    {
        return view('masters.sub-categories.form', [
            'item' => $sub_category,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SubCategory $sub_category): RedirectResponse
    {
        $sub_category->update($this->validated($request, $sub_category));
        return $this->flashSuccess('Sub-category updated successfully.', 'masters.sub-categories.index');
    }

    public function destroy(Request $request, SubCategory $sub_category): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Enforce existing authentication and authorization rules
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('masters.manage') &&
            ! $user->can('masters.edit') &&
            ! $user->can('masters.delete') &&
            ! $user->can('products.manage') &&
            ! $user->can('products.edit') &&
            ! $user->can('products.delete')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to delete sub-categories.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: You do not have permission to delete sub-categories.');
        }

        // Multi-tenancy check if brand/category is associated and user is restricted to a company
        $categoryCompanyId = $sub_category->category?->brand?->company_id;
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && $categoryCompanyId && (int) $categoryCompanyId !== (int) $user->company_id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This sub-category belongs to another company.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: This sub-category belongs to another company.');
        }

        $subCategoryName = $sub_category->name;

        try {
            $hasProducts = $sub_category->products()->exists();
            $hasLeads = Schema::hasTable('leads') && Schema::hasColumn('leads', 'sub_category_id') && DB::table('leads')->where('sub_category_id', $sub_category->id)->exists();

            if ($hasProducts || $hasLeads) {
                $deps = [];
                if ($hasProducts) {
                    $count = $sub_category->products()->count();
                    $deps[] = "{$count} product(s)";
                }
                if ($hasLeads) {
                    $count = DB::table('leads')->where('sub_category_id', $sub_category->id)->count();
                    $deps[] = "{$count} lead(s)";
                }
                $depText = implode(' and ', $deps);
                $message = "Cannot delete sub-category '{$subCategoryName}' because it is linked to {$depText}. Please reassign or remove the linked records first.";

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                return back()->with('error', $message);
            }

            DB::transaction(function () use ($sub_category) {
                $sub_category->delete();
            });

            $message = "Sub-category '{$subCategoryName}' deleted successfully.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return $this->flashSuccess($message, 'masters.sub-categories.index');
        } catch (\Throwable $e) {
            report($e);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete sub-category: ' . $e->getMessage(),
                ], 500);
            }

            return $this->flashError('Failed to delete sub-category: ' . $e->getMessage(), 'masters.sub-categories.index');
        }
    }

    protected function validated(Request $request, ?SubCategory $sub = null): array
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:30',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }
}
