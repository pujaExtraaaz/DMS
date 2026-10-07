<?php

namespace App\Http\Controllers\Catalog;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function destroy(SubCategory $sub_category): RedirectResponse
    {
        $sub_category->delete();
        return $this->flashSuccess('Sub-category deleted successfully.', 'masters.sub-categories.index');
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
