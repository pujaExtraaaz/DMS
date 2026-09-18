<?php

namespace App\Http\Controllers\Catalog;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $items = SubCategory::query()->with('category')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')->orWhere('code', 'like', '%'.$request->search.'%');
            }))
            ->latest()->paginate(15)->withQueryString();
        return view('masters.sub-categories.index', ['items' => $items, 'search' => $request->string('search')]);
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
