<?php

namespace App\Http\Controllers\Catalog;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Support\Traits\SortableAndSearchable;

class CategoryController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = Category::query()->with('brand')
            ->when($request->filled('brand_id'), fn ($q) => $q->where('brand_id', $request->brand_id))
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

        return view('masters.categories.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'brandId' => $request->input('brand_id'),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('masters.categories.form', [
            'item' => new Category,
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));
        return $this->flashSuccess('Category created successfully.', 'masters.categories.index');
    }

    public function edit(Category $category): View
    {
        return view('masters.categories.form', [
            'item' => $category,
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));
        return $this->flashSuccess('Category updated successfully.', 'masters.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();
        return $this->flashSuccess('Category deleted successfully.', 'masters.categories.index');
    }

    protected function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:30|unique:categories,code'.($category ? ','.$category->id : ''),
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }
}
