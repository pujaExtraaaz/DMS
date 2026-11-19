<?php

namespace App\Http\Controllers\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\CodeGenerator;
use App\Support\TitleCase;
use App\Support\Traits\ExportsListing;
use App\Support\Traits\SortableAndSearchable;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductTypeController extends Controller
{
    use ExportsListing, SortableAndSearchable;

    public function index(Request $request): View|JsonResponse|Response
    {
        if ($request->routeIs('masters.product-types.list')) {
            return $this->list();
        }

        // 1. Resolve raw filter inputs
        $rawBrandId = $request->input('brand_id');
        $rawCategoryId = $request->input('category_id');
        $rawSubCategoryId = $request->input('sub_category_id', $request->input('subcategory_id'));
        $rawProductTypeId = $request->input('product_type_id', $request->input('type_id'));

        // 2. Fetch all active brands & validate selected brand
        $allBrands = Brand::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $brandId = ($rawBrandId && $allBrands->contains('id', (int) $rawBrandId)) ? (int) $rawBrandId : null;

        // 3. Resolve categories belonging to the selected brand (or all active if no brand)
        $allCategories = Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'brand_id']);
        if ($brandId) {
            $categoriesForBrand = $allCategories->filter(fn ($c) => (int) $c->brand_id === (int) $brandId);
            if ($categoriesForBrand->isEmpty()) {
                $categoryIdsWithProducts = Product::query()
                    ->where('brand_id', $brandId)
                    ->whereNotNull('category_id')
                    ->distinct()
                    ->pluck('category_id')
                    ->all();
                $categoriesForBrand = $allCategories->filter(fn ($c) => in_array($c->id, $categoryIdsWithProducts, true));
            }
            $categories = $categoriesForBrand->values();
        } else {
            $categories = $allCategories;
        }

        // Validate and reset category selection if invalid for selected brand
        $categoryId = ($rawCategoryId && $categories->contains('id', (int) $rawCategoryId)) ? (int) $rawCategoryId : null;
        if ($rawCategoryId && ! $categoryId) {
            $rawSubCategoryId = null;
            $rawProductTypeId = null;
        }

        // 4. Resolve subcategories belonging to the selected category
        $allSubCategories = SubCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'category_id']);
        if ($categoryId) {
            $subCategories = $allSubCategories->filter(fn ($sc) => (int) $sc->category_id === (int) $categoryId)->values();
        } elseif ($brandId) {
            $categoryIdsForBrand = $categories->pluck('id')->all();
            $subCategories = $allSubCategories->filter(fn ($sc) => in_array($sc->category_id, $categoryIdsForBrand, true))->values();
        } else {
            $subCategories = $allSubCategories;
        }

        // Validate and reset subcategory selection if invalid for selected category
        $subCategoryId = ($rawSubCategoryId && $subCategories->contains('id', (int) $rawSubCategoryId)) ? (int) $rawSubCategoryId : null;
        if ($rawSubCategoryId && ! $subCategoryId) {
            $rawProductTypeId = null;
        }

        $selectedSubCategory = $subCategoryId ? SubCategory::query()->find($subCategoryId) : null;
        $hasHierarchyFilter = (bool) ($brandId || $categoryId || $subCategoryId);

        // 5. Resolve matching product types based on the selected hierarchy
        $allProductTypes = ProductType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        if ($hasHierarchyFilter) {
            $typeIdsQuery = Product::query()
                ->where(function ($q) {
                    $q->whereNotNull('product_type_id')
                      ->orWhereNotNull('product_type');
                })
                ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
                ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId));

            if ($subCategoryId) {
                $typeIdsQuery->where(function ($q) use ($subCategoryId, $selectedSubCategory, $categoryId) {
                    $q->where('sub_category_id', $subCategoryId);
                    if ($selectedSubCategory) {
                        $name = trim($selectedSubCategory->name);
                        $q->orWhere(function ($subQ) use ($name, $categoryId) {
                            $subQ->whereNull('sub_category_id')
                                ->where(function ($textQ) use ($name) {
                                    $textQ->where('name', 'like', "%{$name}%")
                                        ->orWhere('model', 'like', "%{$name}%");
                                });
                            if ($categoryId) {
                                $subQ->where('category_id', $categoryId);
                            }
                        });
                    }
                });
            }

            $matchingTypeIds = $typeIdsQuery->distinct()->pluck('product_type_id')->filter()->all();
            $typesForFilter = $allProductTypes->filter(fn ($pt) => in_array($pt->id, $matchingTypeIds, true))->values();
            if ($typesForFilter->isEmpty()) {
                $typesForFilter = $allProductTypes;
            }
        } else {
            $typesForFilter = $allProductTypes;
        }

        // Validate and reset product type selection if invalid for selected hierarchy
        $productTypeId = ($rawProductTypeId && $typesForFilter->contains('id', (int) $rawProductTypeId)) ? (int) $rawProductTypeId : null;

        // 6. Build Product Types query with accurate assigned products count under selected hierarchy
        $query = ProductType::query()
            ->withCount(['products' => function ($q) use ($brandId, $categoryId, $subCategoryId, $selectedSubCategory) {
                $q->when($brandId, fn ($bq) => $bq->where('brand_id', $brandId))
                  ->when($categoryId, fn ($cq) => $cq->where('category_id', $categoryId));

                if ($subCategoryId) {
                    $q->where(function ($subQ) use ($subCategoryId, $selectedSubCategory, $categoryId) {
                        $subQ->where('sub_category_id', $subCategoryId);
                        if ($selectedSubCategory) {
                            $name = trim($selectedSubCategory->name);
                            $subQ->orWhere(function ($textQ) use ($name, $categoryId) {
                                $textQ->whereNull('sub_category_id')
                                    ->where(function ($matchQ) use ($name) {
                                        $matchQ->where('name', 'like', "%{$name}%")
                                            ->orWhere('model', 'like', "%{$name}%");
                                    });
                                if ($categoryId) {
                                    $textQ->where('category_id', $categoryId);
                                }
                            });
                        }
                    });
                }
            }])
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->when($productTypeId, function ($q) use ($productTypeId) {
                $q->where('id', $productTypeId);
            })
            ->when($hasHierarchyFilter && ! $productTypeId && $typesForFilter->isNotEmpty(), function ($q) use ($typesForFilter) {
                $q->whereIn('id', $typesForFilter->pluck('id'));
            });

        $this->applySearch($query, $request->input('search'), ['name', 'code', 'description']);

        if ($export = $this->exportIfRequested($request, $query, 'Product-Types', 'Product Types', ['Name', 'Code', 'Description', 'Products', 'Status'], fn ($item) => [
            $item->name,
            $item->code,
            $item->description ?: '',
            (string) ($item->products_count ?? 0),
            $item->is_active ? 'Active' : 'Inactive',
        ])) {
            return $export;
        }

        $sortData = $this->applySorting(
            $query,
            $request,
            ['name', 'code', 'description', 'is_active', 'products_count', 'created_at'],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        // 7. Build Product Report Query (Matching Products in Master)
        $productsQuery = Product::query()
            ->with(['brand', 'category', 'subCategory', 'productType', 'baseUom'])
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId));

        if ($subCategoryId) {
            $productsQuery->where(function ($q) use ($subCategoryId, $selectedSubCategory, $categoryId) {
                $q->where('sub_category_id', $subCategoryId);
                if ($selectedSubCategory) {
                    $name = trim($selectedSubCategory->name);
                    $q->orWhere(function ($subQ) use ($name, $categoryId) {
                        $subQ->whereNull('sub_category_id')
                            ->where(function ($textQ) use ($name) {
                                $textQ->where('name', 'like', "%{$name}%")
                                    ->orWhere('model', 'like', "%{$name}%");
                            });
                        if ($categoryId) {
                            $subQ->where('category_id', $categoryId);
                        }
                    });
                }
            });
        }

        if ($productTypeId) {
            $productsQuery->where(function ($q) use ($productTypeId) {
                $q->where('product_type_id', $productTypeId);
                $pt = ProductType::query()->find($productTypeId);
                if ($pt) {
                    $q->orWhereRaw('LOWER(TRIM(COALESCE(product_type, ""))) = ?', [mb_strtolower(trim($pt->name))]);
                }
            });
        } else {
            // Requirement C: Products without an assigned product type must not appear in filtered results
            $productsQuery->where(function ($q) {
                $q->whereNotNull('product_type_id')
                  ->orWhereNotNull('product_type');
            });
        }

        if ($request->filled('search')) {
            $searchTerm = trim((string) $request->input('search'));
            $productsQuery->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('sku', 'like', "%{$searchTerm}%")
                  ->orWhere('model', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $productsQuery->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $productsQuery->where('is_active', false);
            }
        }

        $reportProductsCount = (clone $productsQuery)->count();
        $reportProducts = $productsQuery->orderBy('name')->paginate(15, ['*'], 'products_page')->withQueryString();

        $totalCount = ProductType::count();
        $activeCount = ProductType::where('is_active', true)->count();
        $inactiveCount = ProductType::where('is_active', false)->count();
        $totalAssignedProducts = Product::where(function ($q) {
            $q->whereNotNull('product_type_id')
              ->orWhereNotNull('product_type');
        })->count();

        return view('masters.product-types.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
            'inactiveCount' => $inactiveCount,
            'previewCode' => CodeGenerator::forProductType(),
            'catalog' => $this->catalogChoices(),
            // Cascading filters data
            'brands' => $allBrands,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'typesForFilter' => $typesForFilter,
            'brandId' => $brandId,
            'categoryId' => $categoryId,
            'subCategoryId' => $subCategoryId,
            'productTypeId' => $productTypeId,
            'hasHierarchyFilter' => $hasHierarchyFilter,
            'selectedSubCategory' => $selectedSubCategory,
            // Report data
            'reportProducts' => $reportProducts,
            'reportProductsCount' => $reportProductsCount,
            'totalAssignedProducts' => $totalAssignedProducts,
        ]);
    }

    public function list(): JsonResponse
    {
        return response()->json([
            'items' => ProductType::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'description', 'is_active']),
        ]);
    }

    public function create(): View
    {
        return view('masters.product-types.form', [
            'item' => new ProductType(['is_active' => true]),
            'previewCode' => CodeGenerator::forProductType(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $rawName = trim((string) $request->input('name', ''));

        if ($rawName === '') {
            $msg = 'Product Type Name is required.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return back()->withInput()->withErrors(['name' => $msg]);
        }

        if (mb_strlen($rawName) > 100) {
            $msg = 'Product Type Name cannot exceed 100 characters.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return back()->withInput()->withErrors(['name' => $msg]);
        }

        // Case-insensitive, whitespace-trimmed duplicate check
        $exists = ProductType::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($rawName)])->exists();
        if ($exists) {
            $msg = 'A product type with this name already exists.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return back()->withInput()->withErrors(['name' => $msg]);
        }

        $formattedName = TitleCase::format($rawName);

        // Generate or clean code
        $rawCode = trim((string) $request->input('code', ''));
        if ($rawCode === '') {
            $code = CodeGenerator::forProductType();
        } else {
            $code = strtoupper($rawCode);
        }

        $companyId = auth()->user()?->company_id ?? Company::defaultCompanyId() ?? null;
        $description = trim((string) $request->input('description', '')) ?: null;
        $isActive = $request->boolean('is_active', true);

        try {
            $productType = ProductType::create([
                'company_id' => $companyId,
                'name' => $formattedName,
                'code' => $code,
                'description' => $description,
                'is_active' => $isActive,
                'track_serial' => $request->boolean('track_serial'),
            ]);
            $this->syncSerialTracking($productType);

            $successMsg = "Product Type '{$formattedName}' created successfully.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'item' => [
                        'id' => $productType->id,
                        'name' => $productType->name,
                        'code' => $productType->code,
                        'description' => $productType->description,
                        'is_active' => $productType->is_active,
                        'created_at' => $productType->created_at?->format('d M Y'),
                    ],
                ], 201);
            }

            return redirect()->route('masters.product-types.index')->with('success', $successMsg);
        } catch (\Throwable $e) {
            if (
                str_contains($e->getMessage(), 'Integrity constraint violation') ||
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'UNIQUE constraint failed')
            ) {
                $msg = 'A product type with this name or code already exists.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $msg,
                        'errors' => ['name' => [$msg]],
                    ], 422);
                }
                return back()->withInput()->withErrors(['name' => $msg]);
            }

            report($e);
            $msg = 'An error occurred while saving the product type: ' . $e->getMessage();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return back()->withInput()->with('error', $msg);
        }
    }

    public function show(Request $request, ProductType $productType): View|JsonResponse
    {
        $productType->loadCount('products');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'item' => [
                    'id' => $productType->id,
                    'name' => $productType->name,
                    'code' => $productType->code,
                    'description' => $productType->description,
                    'is_active' => $productType->is_active,
                    'track_serial' => (bool) $productType->track_serial,
                    'products_count' => $productType->products_count,
                    'created_at' => $productType->created_at?->format('d M Y, h:i A'),
                    'updated_at' => $productType->updated_at?->format('d M Y, h:i A'),
                ],
            ]);
        }

        return view('masters.product-types.form', [
            'item' => $productType,
            'readOnly' => true,
        ]);
    }

    public function edit(ProductType $productType): View
    {
        return view('masters.product-types.form', [
            'item' => $productType,
            'previewCode' => $productType->code,
        ]);
    }

    public function update(Request $request, ProductType $productType): RedirectResponse|JsonResponse
    {
        $rawName = trim((string) $request->input('name', ''));

        if ($rawName === '') {
            $msg = 'Product Type Name is required.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return back()->withInput()->withErrors(['name' => $msg]);
        }

        if (mb_strlen($rawName) > 100) {
            $msg = 'Product Type Name cannot exceed 100 characters.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return back()->withInput()->withErrors(['name' => $msg]);
        }

        // Duplicate check excluding current
        $exists = ProductType::where('id', '!=', $productType->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($rawName)])
            ->exists();

        if ($exists) {
            $msg = 'A product type with this name already exists.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return back()->withInput()->withErrors(['name' => $msg]);
        }

        $formattedName = TitleCase::format($rawName);
        $description = trim((string) $request->input('description', '')) ?: null;
        $isActive = $request->boolean('is_active', true);

        try {
            $productType->update([
                'name' => $formattedName,
                'description' => $description,
                'is_active' => $isActive,
                'track_serial' => $request->boolean('track_serial'),
            ]);
            $this->syncSerialTracking($productType->fresh());

            $successMsg = "Product Type '{$formattedName}' updated successfully.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'item' => [
                        'id' => $productType->id,
                        'name' => $productType->name,
                        'code' => $productType->code,
                        'description' => $productType->description,
                        'is_active' => $productType->is_active,
                        'updated_at' => $productType->updated_at?->format('d M Y'),
                    ],
                ]);
            }

            return redirect()->route('masters.product-types.index')->with('success', $successMsg);
        } catch (\Throwable $e) {
            report($e);
            $msg = 'An error occurred while updating the product type: ' . $e->getMessage();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return back()->withInput()->with('error', $msg);
        }
    }

    public function destroy(Request $request, ProductType $productType): RedirectResponse|JsonResponse
    {
        $typeName = $productType->name;

        try {
            // Check if any products reference this product type (by ID or legacy name)
            $hasProducts = $productType->products()->exists() ||
                Product::where('product_type_id', $productType->id)
                    ->orWhereRaw('LOWER(TRIM(product_type)) = ?', [mb_strtolower(trim($productType->name))])
                    ->exists();

            if ($hasProducts) {
                // Safe deactivation to preserve product catalog integrity and reporting
                $productType->update(['is_active' => false]);

                $message = "Product Type '{$typeName}' is assigned to existing products and has been deactivated to preserve product records.";

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'archived' => true,
                    ]);
                }

                return redirect()->route('masters.product-types.index')->with('success', $message);
            }

            // Safe permanent delete when no products reference this type
            $productType->delete();

            $message = "Product Type '{$typeName}' deleted successfully.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'archived' => false,
                ]);
            }

            return redirect()->route('masters.product-types.index')->with('success', $message);
        } catch (\Throwable $e) {
            report($e);
            $msg = 'Failed to delete product type: ' . $e->getMessage();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }

            return redirect()->route('masters.product-types.index')->with('error', $msg);
        }
    }

    public function quickStore(Request $request): JsonResponse
    {
        $rawName = trim((string) $request->input('name', ''));

        if ($rawName === '') {
            return response()->json([
                'success' => false,
                'message' => 'Product Type Name is required.',
                'errors' => [
                    'name' => ['Product Type Name is required.'],
                ],
            ], 422);
        }

        if (mb_strlen($rawName) > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Product Type Name cannot exceed 100 characters.',
                'errors' => [
                    'name' => ['Product Type Name cannot exceed 100 characters.'],
                ],
            ], 422);
        }

        // Case-insensitive, whitespace-trimmed duplicate check
        $exists = ProductType::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($rawName)])->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A product type with this name already exists.',
                'errors' => [
                    'name' => ['A product type with this name already exists.'],
                ],
            ], 422);
        }

        $formattedName = TitleCase::format($rawName);

        // Generate or clean code
        $rawCode = trim((string) $request->input('code', ''));
        if ($rawCode === '') {
            $code = CodeGenerator::forProductType();
        } else {
            $code = strtoupper($rawCode);
        }

        $description = trim((string) $request->input('description', ''));
        if (mb_strlen($description) > 1000) {
            return response()->json([
                'success' => false,
                'message' => 'Description cannot exceed 1000 characters.',
                'errors' => [
                    'description' => ['Description cannot exceed 1000 characters.'],
                ],
            ], 422);
        }

        $companyId = auth()->user()?->company_id ?? Company::defaultCompanyId() ?? null;

        try {
            $productType = ProductType::create([
                'company_id' => $companyId,
                'name' => $formattedName,
                'code' => $code,
                'description' => $description !== '' ? $description : null,
                'is_active' => $request->boolean('is_active', true),
                'track_serial' => $request->boolean('track_serial'),
            ]);
            $this->syncSerialTracking($productType);

            return response()->json([
                'success' => true,
                'message' => 'Product Type created successfully.',
                'item' => [
                    'id' => $productType->id,
                    'name' => $productType->name,
                    'code' => $productType->code,
                    'description' => $productType->description,
                ],
            ], 201);
        } catch (\Throwable $e) {
            if (
                str_contains($e->getMessage(), 'Integrity constraint violation') ||
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'UNIQUE constraint failed')
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'A product type with this name already exists.',
                    'errors' => [
                        'name' => ['A product type with this name already exists.'],
                    ],
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the product type: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function syncSerialTracking(ProductType $productType): void
    {
        $name = mb_strtolower(trim($productType->name));
        $products = Product::query()
            ->where(function ($query) use ($productType, $name) {
                $query->where('product_type_id', $productType->id)
                    ->orWhereRaw('LOWER(TRIM(COALESCE(product_type, ""))) = ?', [$name]);
            })
            ->get();

        foreach ($products as $product) {
            $parts = array_values(array_filter(
                array_map('trim', explode(',', (string) $product->tracking_type)),
                fn ($part) => $part !== '' && $part !== 'none'
            ));

            if ($productType->track_serial) {
                if (! in_array('serial', $parts, true)) {
                    $parts[] = 'serial';
                }
            } else {
                $parts = array_values(array_filter($parts, fn ($part) => $part !== 'serial'));
            }

            $product->product_type_id = $productType->id;
            $product->tracking_type = $parts === [] ? 'none' : implode(',', $parts);
            $product->save();
        }
    }

    /**
     * @return array{brands: \Illuminate\Support\Collection, categories: \Illuminate\Support\Collection, subCategories: \Illuminate\Support\Collection, types: \Illuminate\Support\Collection, links: \Illuminate\Support\Collection}
     */
    protected function catalogChoices(): array
    {
        $subCategories = SubCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'category_id']);
        $subCatsByCategory = $subCategories->groupBy('category_id');

        $products = Product::query()
            ->where(function ($q) {
                $q->whereNotNull('product_type_id')
                  ->orWhereNotNull('product_type');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'model', 'brand_id', 'category_id', 'sub_category_id', 'product_type_id', 'product_type'])
            ->map(function ($p) use ($subCatsByCategory) {
                $subId = $p->sub_category_id;
                if (! $subId && $p->category_id && isset($subCatsByCategory[$p->category_id])) {
                    foreach ($subCatsByCategory[$p->category_id] as $sc) {
                        if (stripos($p->name, $sc->name) !== false || ($p->model && stripos($p->model, $sc->name) !== false)) {
                            $subId = $sc->id;
                            break;
                        }
                    }
                }
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku ?: '',
                    'brand_id' => $p->brand_id,
                    'category_id' => $p->category_id,
                    'sub_category_id' => $subId,
                    'product_type_id' => $p->product_type_id,
                ];
            });

        $links = $products->map(fn ($p) => [
            'product_type_id' => $p['product_type_id'],
            'brand_id' => $p['brand_id'],
            'category_id' => $p['category_id'],
            'sub_category_id' => $p['sub_category_id'],
        ])->unique()->values();

        return [
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'brand_id']),
            'subCategories' => $subCategories,
            'types' => ProductType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'track_serial']),
            'links' => $links,
            'products' => $products,
        ];
    }
}
