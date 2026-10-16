<?php

namespace App\Http\Controllers\Catalog;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Http\Controllers\Controller;
use App\Support\CodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'previewCode' => CodeGenerator::forCategory(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                DB::transaction(function () use ($data) {
                    $payload = $data;
                    $payload['code'] = CodeGenerator::forCategory();

                    return Category::create($payload);
                });
                break;
            } catch (QueryException $e) {
                $isDuplicate = isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062;
                $message = $e->getMessage();

                $isCodeDuplicate = $isDuplicate && (
                    str_contains($message, 'categories_code_unique') ||
                    str_contains($message, 'categories.code') ||
                    str_contains($message, "for key 'code'")
                );

                if ($isCodeDuplicate) {
                    if ($attempt < $maxAttempts) {
                        usleep(random_int(10000, 30000));
                        continue;
                    }

                    return back()->withInput()->withErrors(['code' => 'Unable to generate a unique category code due to concurrent requests. Please try again.']);
                }

                if ($isDuplicate) {
                    return back()->withInput()->withErrors(['name' => 'Category with this name or code already exists.']);
                }

                throw $e;
            }
        }

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
        $data = $this->validated($request, $category);
        $data['code'] = $category->code;

        $category->update($data);

        return $this->flashSuccess('Category updated successfully.', 'masters.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();
        return $this->flashSuccess('Category deleted successfully.', 'masters.categories.index');
    }

    protected function validated(Request $request, ?Category $category = null): array
    {
        $rules = [
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ];

        if ($category) {
            $rules['code'] = 'nullable|string|max:30|unique:categories,code,'.$category->id;
        }

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');

        if ($category) {
            $data['code'] = $category->code;
        } else {
            unset($data['code']);
        }

        return $data;
    }
}
