<?php

namespace App\Http\Controllers\Catalog;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\CodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

use App\Support\Traits\SortableAndSearchable;

class BrandController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = Brand::query()
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
        return view('masters.brands.form', [
            'item' => new Brand,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
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
                    $payload['code'] = CodeGenerator::forBrand($payload['company_id'] ?? null);

                    return Brand::create($payload);
                });
                break;
            } catch (QueryException $e) {
                $isDuplicate = isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062;
                $message = $e->getMessage();

                // If duplicate code collided, retry with the next sequence
                $isCodeDuplicate = $isDuplicate && (
                    str_contains($message, 'brands_code_unique') ||
                    str_contains($message, 'brands.code') ||
                    str_contains($message, "for key 'code'")
                );

                if ($isCodeDuplicate) {
                    if ($attempt < $maxAttempts) {
                        usleep(random_int(10000, 30000));
                        continue;
                    }

                    return back()->withInput()->withErrors(['code' => 'Unable to generate a unique brand code due to concurrent requests. Please try again.']);
                }

                // If duplicate brand name
                $isNameDuplicate = $isDuplicate && (
                    str_contains($message, 'brands_name_unique') ||
                    str_contains($message, 'brands.name') ||
                    str_contains($message, "for key 'name'")
                );

                if ($isNameDuplicate) {
                    return back()->withInput()->withErrors(['name' => 'Brand name already exists.']);
                }

                if ($isDuplicate) {
                    return back()->withInput()->withErrors(['name' => 'Brand with this name or code already exists.']);
                }

                throw $e;
            }
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

        // Keep the original brand code unchanged on edit
        $data['code'] = $brand->code;

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

        // On edit, validate existing code if present (though it's preserved as original)
        if ($brand) {
            $rules['code'] = 'nullable|string|max:30|unique:brands,code,' . $brand->id;
        }

        $data = $request->validate($rules, [
            'name.unique' => 'Brand name already exists.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['company_id'] = $data['company_id'] ?? auth()->user()?->company_id;

        if ($brand) {
            $data['code'] = $brand->code;
        } else {
            // Never allow manual code entry on create
            unset($data['code']);
        }

        return $data;
    }
}