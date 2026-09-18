<?php

namespace App\Http\Controllers\Catalog;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $items = Category::query()->with('brand')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')->orWhere('code', 'like', '%'.$request->search.'%');
            }))
            ->latest()->paginate(15)->withQueryString();
        return view('masters.categories.index', ['items' => $items, 'search' => $request->string('search')]);
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
