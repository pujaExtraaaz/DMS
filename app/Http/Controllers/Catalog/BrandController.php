<?php

namespace App\Http\Controllers\Catalog;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\CodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $items = Brand::query()
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')->orWhere('code', 'like', '%'.$request->search.'%');
            }))
            ->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('masters.brands.index', ['items' => $items, 'search' => $request->string('search')]);
    }

    public function create(): View
    {
        return view('masters.brands.form', [
            'item' => new Brand,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            Brand::create($this->validated($request));
        } catch (QueryException $e) {
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062) {
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
        try {
            $brand->update($this->validated($request, $brand));
        } catch (QueryException $e) {
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062) {
                return back()->withInput()->withErrors(['name' => 'Brand name already exists.']);
            }
            throw $e;
        }

        return $this->flashSuccess('Brand updated successfully.', 'masters.brands.index');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();
        return $this->flashSuccess('Brand deleted successfully.', 'masters.brands.index');
    }

    protected function validated(Request $request, ?Brand $brand = null): array
    {
        if ($request->has('name')) {
            $request->merge(['name' => trim((string) $request->input('name'))]);
        }

        $data = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->ignore($brand?->id),
            ],
            'code' => 'nullable|string|max:30|unique:brands,code'.($brand ? ','.$brand->id : ''),
            'detail' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ], [
            'name.unique' => 'Brand name already exists.',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['company_id'] = $data['company_id'] ?? auth()->user()?->company_id;

        // Auto-generate a globally unique brand code when the user leaves it blank.
        if (blank($data['code'] ?? null)) {
            $data['code'] = CodeGenerator::forBrand($data['company_id'] ?? null);
        }

        return $data;
    }
}