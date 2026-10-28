<?php

namespace App\Http\Controllers\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Inventory\Models\ProductSerial;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductPriceHistory;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\CodeGenerator;
use App\Support\WarrantyPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

use App\Support\Traits\ExportsListing;
use App\Support\Traits\SortableAndSearchable;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    use ExportsListing, SortableAndSearchable;

    public function index(Request $request): View|Response
    {
        // 1. Resolve raw filter inputs
        $rawBrandId = $request->input('brand_id');
        $rawCategoryId = $request->input('category_id');
        $rawSubCategoryId = $request->input('sub_category_id', $request->input('subcategory_id'));

        // 2. Fetch all active brands
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $brandId = ($rawBrandId && $brands->contains('id', (int) $rawBrandId)) ? (int) $rawBrandId : null;

        // 3. Fetch all active categories & resolve categories relevant to selected brand
        $allCategories = Category::where('is_active', true)->orderBy('name')->get();

        if ($brandId) {
            // Check if categories exist that belong to this brand
            $categoriesForBrand = $allCategories->filter(function ($c) use ($brandId) {
                return (int) $c->brand_id === (int) $brandId;
            });

            // If no direct brand_id match, check if any categories have products under this brand
            if ($categoriesForBrand->isEmpty()) {
                $categoryIdsWithProducts = Product::where('brand_id', $brandId)
                    ->whereNotNull('category_id')
                    ->distinct()
                    ->pluck('category_id')
                    ->all();

                $categoriesForBrand = $allCategories->filter(function ($c) use ($categoryIdsWithProducts) {
                    return in_array($c->id, $categoryIdsWithProducts, true);
                });
            }

            // If still empty (no brand-category relationship found), show available categories without incorrectly excluding records
            $categories = $categoriesForBrand->isNotEmpty() ? $categoriesForBrand->values() : $allCategories;
        } else {
            $categories = $allCategories;
        }

        // Validate and reset category selection if invalid for selected brand
        $categoryId = ($rawCategoryId && $categories->contains('id', (int) $rawCategoryId)) ? (int) $rawCategoryId : null;
        if ($rawCategoryId && ! $categoryId) {
            // Reset dependent subcategory if parent category became invalid
            $rawSubCategoryId = null;
        }

        // 4. Fetch all active subcategories & resolve subcategories for selected category and brand
        $allSubCategories = SubCategory::where('is_active', true)->orderBy('name')->get();

        if ($categoryId) {
            // Subcategories belonging to the selected category
            $subCatsForCategory = $allSubCategories->filter(function ($sc) use ($categoryId) {
                return (int) $sc->category_id === (int) $categoryId;
            });

            if ($brandId && $subCatsForCategory->isNotEmpty()) {
                $subCatIdsWithBrandProducts = Product::where('brand_id', $brandId)
                    ->where('category_id', $categoryId)
                    ->whereNotNull('sub_category_id')
                    ->distinct()
                    ->pluck('sub_category_id')
                    ->all();

                if (! empty($subCatIdsWithBrandProducts)) {
                    $brandSpecificSubCats = $subCatsForCategory->filter(function ($sc) use ($subCatIdsWithBrandProducts) {
                        return in_array($sc->id, $subCatIdsWithBrandProducts, true);
                    });
                    if ($brandSpecificSubCats->isNotEmpty()) {
                        $subCatsForCategory = $brandSpecificSubCats;
                    }
                }
            }

            $subCategories = $subCatsForCategory->values();
        } elseif ($brandId) {
            // Brand is selected, but Category is 'All'
            $subCatIdsForBrand = Product::where('brand_id', $brandId)
                ->whereNotNull('sub_category_id')
                ->distinct()
                ->pluck('sub_category_id')
                ->all();

            $brandSubCats = $allSubCategories->filter(function ($sc) use ($categories, $subCatIdsForBrand) {
                return in_array($sc->id, $subCatIdsForBrand, true) || $categories->contains('id', $sc->category_id);
            });

            $subCategories = $brandSubCats->isNotEmpty() ? $brandSubCats->values() : $allSubCategories;
        } else {
            $subCategories = $allSubCategories;
        }

        // Validate and reset subcategory selection if invalid
        $subCategoryId = ($rawSubCategoryId && $subCategories->contains('id', (int) $rawSubCategoryId)) ? (int) $rawSubCategoryId : null;

        // 5. Query Products
        $query = Product::query()
            ->with(['baseUom', 'brand', 'category', 'subCategory', 'productType'])
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($subCategoryId, fn ($q) => $q->where('sub_category_id', $subCategoryId))
            ->when($request->filled('product_type'), function ($q) use ($request) {
                $val = $request->input('product_type');
                $q->where(function ($sub) use ($val) {
                    $sub->where('product_type', $val)
                        ->orWhere('product_type_id', $val);
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        $this->applySearch($query, $request->input('search'), ['name', 'sku', 'serial_no', 'hsn_code', 'series', 'model', 'product_type'], [
            'serials' => ['serial_number'],
        ]);

        if ($export = $this->exportIfRequested($request, $query, 'Products', 'Products', ['Name', 'Part Number', 'Model', 'Brand', 'Category', 'Subcategory', 'Product Type', 'Base UOM', 'GST Rate', 'Status'], fn ($item) => [
            $item->name,
            $item->sku ?: '',
            $item->model ?: '',
            $item->brand?->name ?? '',
            $item->category?->name ?? '',
            $item->subCategory?->name ?? '',
            $item->productType?->name ?? $item->product_type ?? '',
            $item->baseUom?->name ?? '',
            $item->tax_rate !== null ? (string) $item->tax_rate : '',
            $item->is_active ? 'Active' : 'Inactive',
        ])) {
            return $export;
        }

        $sortData = $this->applySorting(
            $query,
            $request,
            [
                'name' => 'name',
                'sku' => 'sku',
                'serial_no' => 'serial_no',
                'series' => 'series',
                'model' => 'model',
                'models' => 'model',
                'product_type' => 'product_type',
                'is_active' => 'is_active',
                'created_at' => 'created_at',
            ],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        $serialSearch = trim((string) $request->input('search'));
        $serialMatches = $serialSearch === ''
            ? collect()
            : ProductSerial::query()
                ->with(['product', 'customer', 'salesInvoice'])
                ->where('serial_number', $serialSearch)
                ->limit(10)
                ->get();

        return view('masters.products.index', [
            'items' => $items,
            'serialMatches' => $serialMatches,
            'brands' => $brands,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'allCategoriesData' => $allCategories->map(fn ($c) => [
                'id' => (string) $c->id,
                'brand_id' => $c->brand_id ? (string) $c->brand_id : null,
                'name' => $c->name,
            ])->values(),
            'allSubCategoriesData' => $allSubCategories->map(fn ($sc) => [
                'id' => (string) $sc->id,
                'category_id' => $sc->category_id ? (string) $sc->category_id : null,
                'name' => $sc->name,
            ])->values(),
            'productTypes' => ProductType::where('is_active', true)->orderBy('name')->get(),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
            'search' => $request->string('search'),
            'brandId' => $brandId,
            'categoryId' => $categoryId,
            'subCategoryId' => $subCategoryId,
            'productType' => $request->string('product_type'),
            'status' => $request->string('status'),
        ]);
    }

    public function assignType(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $typeId = $request->input('product_type_id');
        $typeName = null;

        if (!empty($typeId)) {
            $pt = ProductType::find($typeId);
            $typeName = $pt?->name;
        }

        $product->update([
            'product_type_id' => $typeId ?: null,
            'product_type' => $typeName,
        ]);

        $msg = "Product Type updated for '{$product->name}'.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'product_type' => $typeName,
            ]);
        }

        return back()->with('success', $msg);
    }

    public function create(): View
    {
        return view('masters.products.form', $this->formData(new Product));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $request) {
            $data['serial_no'] = CodeGenerator::forProductSerial();

            $product = Product::create($data);

            $this->syncBaseUom($product);
            $this->syncAlternateUoms($request, $product);
        });

        return $this->flashSuccess('Product created successfully.', 'masters.products.index');
    }

    public function show(Product $product): View
    {
        $product->load(['brand', 'category', 'subCategory', 'productType', 'baseUom', 'alternateUom']);

        return view('masters.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $product->load(['productUoms.uom', 'priceHistories.uom', 'baseUom', 'alternateUom']);

        if (! $product->alternate_uom_id) {
            $alt = $product->productUoms->firstWhere('is_base', false);
            if ($alt) {
                $product->alternate_uom_id = $alt->uom_id;
                $product->conversion_factor = $alt->conversion_factor;
                $product->alt_selling_price = $alt->selling_price;
                $product->alt_trade_price = $alt->trade_price;
                $product->alt_purchase_price = $alt->purchase_price;
                $product->alt_calculation_mrp = $alt->mrp;
            }
        }

        return view('masters.products.form', $this->formData($product));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);

        DB::transaction(function () use ($data, $product, $request) {
            $product->update($data);
            $this->syncBaseUom($product->fresh());
            $this->syncAlternateUoms($request, $product->fresh());
        });

        return $this->flashSuccess('Product updated successfully.', 'masters.products.index');
    }

    public function destroy(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('products.manage') &&
            ! $user->can('products.edit') &&
            ! $user->can('masters.manage') &&
            ! $user->can('masters.edit')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to delete products.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized to delete product.');
        }

        // Prevent deleting a product if it belongs to another company or unauthorized tenant
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && (int) $product->company_id !== (int) $user->company_id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This product belongs to another company.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: This product belongs to another company.');
        }

        $productName = $product->getRawOriginal('name') ?: $product->name;

        try {
            DB::transaction(function () use ($product) {
                // Deactivate the product and soft-delete it so that all historical purchases,
                // sales, orders, serial numbers, and transactions remain completely intact.
                $product->is_active = false;
                $product->save();
                $product->delete();
            });

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Product '{$productName}' deleted successfully.",
                ]);
            }

            return $this->flashSuccess("Product '{$productName}' deleted successfully.", 'masters.products.index')
                ->with('success', "Product '{$productName}' deleted successfully.");
        } catch (\Throwable $e) {
            report($e);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete product: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to delete product: ' . $e->getMessage());
        }
    }

    protected function formData(Product $item): array
    {
        return [
            'item' => $item,
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'subCategories' => SubCategory::where('is_active', true)->orderBy('name')->get(),
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'productTypes' => ProductType::where('is_active', true)->orderBy('name')->get(),
            'priceHistory' => $item->exists
                ? ProductPriceHistory::query()->where('product_id', $item->id)->orderByDesc('effective_from')->limit(30)->with('uom', 'creator')->get()
                : collect(),
        ];
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        if (! $request->filled('sku') && $request->filled('part_number')) {
            $request->merge(['sku' => $request->input('part_number')]);
        }

        if ($request->has('name')) {
            $rawName = trim((string) $request->input('name'));
            $request->merge([
                'name' => \App\Support\TitleCase::format($rawName),
            ]);
        }

        if ($request->boolean('sku_not_applicable')) {
            $request->merge(['sku' => null]);
        }

        $altNotApplicable = $request->boolean('alternate_uom_not_applicable');
        if ($altNotApplicable) {
            $request->merge([
                'alternate_uom_id' => null,
                'conversion_factor' => null,
            ]);
        }

        if (! $altNotApplicable && $request->has('product_uoms.0.uom_id') && ! $request->filled('alternate_uom_id')) {
            $request->merge([
                'alternate_uom_id' => $request->input('product_uoms.0.uom_id'),
                'conversion_factor' => $request->input('product_uoms.0.conversion_factor', $request->input('conversion_factor', 1)),
                'alt_purchase_price' => $request->input('product_uoms.0.purchase_price', $request->input('alt_purchase_price')),
                'alt_trade_price' => $request->input('product_uoms.0.trade_price', $request->input('alt_trade_price')),
                'alt_selling_price' => $request->input('product_uoms.0.selling_price', $request->input('alt_selling_price')),
                'alt_calculation_mrp' => $request->input('product_uoms.0.mrp', $request->input('alt_calculation_mrp')),
            ]);
        }

        if ($request->filled('height_in') && ! $request->filled('height_cm')) {
            $request->merge(['height_cm' => round((float) $request->input('height_in') * 2.54, 2)]);
        }
        if ($request->filled('length_in') && ! $request->filled('length_cm')) {
            $request->merge(['length_cm' => round((float) $request->input('length_in') * 2.54, 2)]);
        }
        if ($request->filled('width_in') && ! $request->filled('width_cm')) {
            $request->merge(['width_cm' => round((float) $request->input('width_in') * 2.54, 2)]);
        }

        if ($request->filled('height_cm') && ! $request->filled('height_in')) {
            $request->merge(['height_in' => round((float) $request->input('height_cm') / 2.54, 2)]);
        }
        if ($request->filled('length_cm') && ! $request->filled('length_in')) {
            $request->merge(['length_in' => round((float) $request->input('length_cm') / 2.54, 2)]);
        }
        if ($request->filled('width_cm') && ! $request->filled('width_in')) {
            $request->merge(['width_in' => round((float) $request->input('width_cm') / 2.54, 2)]);
        }

        $skipDimensions = $request->boolean('dimensions_not_applicable');
        $isFormSubmission = ! $skipDimensions && (
            $request->boolean('has_dimensions')
            || $request->has('height_cm')
            || $request->has('length_cm')
            || $request->has('width_cm')
            || $request->has('net_weight')
        );

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required_unless:sku_not_applicable,1|nullable|string|max:50|unique:products,sku'.($product ? ','.$product->id : ''),
            'sku_not_applicable' => 'nullable|boolean',
            'alternate_uom_not_applicable' => 'nullable|boolean',
            'dimensions_not_applicable' => 'nullable|boolean',
            'model' => 'nullable|string|max:100',
            'models' => 'nullable|string|max:100',
            'product_type_id' => 'nullable|exists:product_types,id',
            'product_type' => 'nullable|string|max:100',
            'hsn_code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'specification' => 'nullable|string',
            'warranty' => 'nullable|string|max:100',
            'height_cm' => $isFormSubmission ? 'required|numeric|gt:0' : 'nullable|numeric|gt:0',
            'length_cm' => $isFormSubmission ? 'required|numeric|gt:0' : 'nullable|numeric|gt:0',
            'width_cm' => $isFormSubmission ? 'required|numeric|gt:0' : 'nullable|numeric|gt:0',
            'height_in' => 'nullable|numeric|gt:0',
            'length_in' => 'nullable|numeric|gt:0',
            'width_in' => 'nullable|numeric|gt:0',
            'net_weight' => $isFormSubmission ? 'required|numeric|gt:0' : 'nullable|numeric|gt:0',
            'weight_unit' => 'nullable|string|max:20',
            'base_uom_id' => 'required|exists:uoms,id',
            'alternate_uom_id' => ($altNotApplicable ? 'nullable' : 'required').'|exists:uoms,id|different:base_uom_id',
            'conversion_factor' => ($altNotApplicable ? 'nullable' : 'required').'|numeric|gt:0',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'tax_rate' => [$request->exists('tax_rate') ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'],
            'selling_price' => 'nullable|numeric|min:0',
            'trade_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_price_tax_basis' => 'nullable|in:'.implode(',', array_keys(Product::PURCHASE_TAX_BASES)),
            'supply_type' => 'nullable|in:'.implode(',', array_keys(Product::SUPPLY_TYPES)),
            'costing_method' => 'nullable|in:'.implode(',', array_keys(Product::COSTING_METHODS)),
            'market_valuation_method' => 'nullable|in:'.implode(',', array_keys(Product::MARKET_VALUATION_METHODS)),
            'provide_behaviour' => 'nullable|boolean',
            'ignore_physical_count_diff' => 'nullable|boolean',
            'ignore_negative_balances' => 'nullable|boolean',
            'treat_sales_as_new_manufacture' => 'nullable|boolean',
            'treat_purchases_as_consumed' => 'nullable|boolean',
            'treat_rejections_inward_as_scrap' => 'nullable|boolean',
            'calculation_mrp' => 'nullable|numeric|min:0',
            'alt_selling_price' => 'nullable|numeric|min:0',
            'alt_trade_price' => 'nullable|numeric|min:0',
            'alt_purchase_price' => 'nullable|numeric|min:0',
            'alt_calculation_mrp' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_type' => 'nullable|in:percent,flat',
            'discount_value' => 'nullable|numeric|min:0',
            'selling_discount_type' => 'nullable|in:percent,flat',
            'selling_discount_value' => 'nullable|numeric|min:0',
            'apply_discount_on_payable' => 'nullable|boolean',
            'warranty_months' => 'nullable|integer|min:0',
            'warranty_terms' => 'nullable|string',
            'sender_warranty_months' => 'nullable|integer|min:0',
            'sender_warranty_terms' => 'nullable|string',
            'customer_warranty_months' => 'nullable|integer|min:0',
            'customer_warranty_terms' => 'nullable|string',
            'catalog_link' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:4096',
            'remove_image' => 'nullable|boolean',
            'tracking_type' => 'nullable',
            'tracking_type.*' => 'nullable|string|in:none,serial,batch',
            'min_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'aging_threshold_days' => 'nullable|integer|min:0',
            'credit_period_days' => 'nullable|integer|min:0',
            'payment_period_days' => 'nullable|integer|min:0',
            'lifespan_days' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'product_uoms' => 'nullable|array',
            'product_uoms.*.uom_id' => 'nullable|exists:uoms,id',
            'product_uoms.*.label' => 'nullable|string|max:60',
            'product_uoms.*.conversion_factor' => 'nullable|numeric|gt:0',
            'product_uoms.*.selling_price' => 'nullable|numeric|min:0',
            'product_uoms.*.trade_price' => 'nullable|numeric|min:0',
            'product_uoms.*.purchase_price' => 'nullable|numeric|min:0',
            'product_uoms.*.mrp' => 'nullable|numeric|min:0',
            'product_uoms.*.is_default_sales' => 'nullable|boolean',
        ], [
            'base_uom_id.required' => 'The base UOM field is required.',
            'alternate_uom_id.required' => 'The alternative UOM field is required.',
            'alternate_uom_id.different' => 'The alternative UOM and base UOM must be different.',
            'conversion_factor.required' => 'The conversion quantity is required.',
            'conversion_factor.gt' => 'The conversion quantity must be greater than zero.',
            'sku.required' => 'The part number field is required.',
            'sku.unique' => 'The part number has already been taken.',
            'height_cm.required' => 'The height (CM) field is required.',
            'height_cm.gt' => 'The height (CM) must be greater than zero.',
            'length_cm.required' => 'The length (CM) field is required.',
            'length_cm.gt' => 'The length (CM) must be greater than zero.',
            'width_cm.required' => 'The width (CM) field is required.',
            'width_cm.gt' => 'The width (CM) must be greater than zero.',
            'net_weight.required' => 'The net weight field is required.',
            'net_weight.gt' => 'The net weight must be greater than zero.',
        ], [
            'sku' => 'part number',
            'base_uom_id' => 'base UOM',
            'alternate_uom_id' => 'alternative UOM',
            'conversion_factor' => 'conversion quantity',
            'height_cm' => 'height (CM)',
            'length_cm' => 'length (CM)',
            'width_cm' => 'width (CM)',
            'height_in' => 'height (Inches)',
            'length_in' => 'length (Inches)',
            'width_in' => 'width (Inches)',
            'net_weight' => 'net weight',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['model'] = $data['model'] ?? $data['models'] ?? ($product?->model ?? null);
        $data['series'] = $request->input('series', $product?->series ?? null);
        $data['color_variant'] = $request->input('color_variant', $product?->color_variant ?? null);

        $productTypeId = $request->input('product_type_id');
        $productTypeName = $request->input('product_type');

        if ($request->has('product_type_id') || $request->has('product_type')) {
            if (!empty($productTypeId)) {
                $pt = ProductType::find($productTypeId);
                $productTypeName = $pt?->name ?? $productTypeName;
            } elseif (!empty($productTypeName)) {
                $pt = ProductType::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($productTypeName))])->first();
                if ($pt) {
                    $productTypeId = $pt->id;
                }
            } else {
                $productTypeId = null;
                $productTypeName = null;
            }
        } else {
            $productTypeId = $product?->product_type_id ?? null;
            $productTypeName = $product?->product_type ?? null;
        }

        $data['product_type_id'] = $productTypeId;
        $data['product_type'] = $productTypeName;
        unset($data['models'], $data['sku_not_applicable'], $data['alternate_uom_not_applicable'], $data['dimensions_not_applicable']);
        $data['apply_discount_on_payable'] = $request->has('apply_discount_on_payable')
            ? $request->boolean('apply_discount_on_payable')
            : ($product?->apply_discount_on_payable ?? false);
        $data['tax_rate'] = $data['tax_rate'] ?? 0;
        $data['selling_price'] = $data['selling_price'] ?? 0;
        $data['trade_price'] = $data['trade_price'] ?? 0;
        $data['purchase_price'] = $data['purchase_price'] ?? 0;
        $data['calculation_mrp'] = $data['calculation_mrp'] ?? 0;
        $data['supply_type'] = $request->exists('supply_type')
            ? ($data['supply_type'] ?? 'goods')
            : ($product?->supply_type ?? 'goods');
        $data['purchase_price_tax_basis'] = $request->exists('purchase_price_tax_basis')
            ? ($data['purchase_price_tax_basis'] ?? 'exclusive')
            : ($product?->purchase_price_tax_basis ?? 'exclusive');
        $data['costing_method'] = $request->exists('costing_method')
            ? ($data['costing_method'] ?: null)
            : $product?->costing_method;
        $data['market_valuation_method'] = $request->exists('market_valuation_method')
            ? ($data['market_valuation_method'] ?? 'avg_price')
            : ($product?->market_valuation_method ?? 'avg_price');
        $data['provide_behaviour'] = $request->exists('provide_behaviour')
            ? $request->boolean('provide_behaviour')
            : (bool) ($product?->provide_behaviour ?? false);
        foreach (array_keys(Product::BEHAVIOUR_FLAGS) as $behaviourFlag) {
            $data[$behaviourFlag] = $data['provide_behaviour'] && $request->exists($behaviourFlag)
                ? $request->boolean($behaviourFlag)
                : ($data['provide_behaviour'] ? (bool) ($product?->{$behaviourFlag} ?? false) : false);
        }

        if ($altNotApplicable) {
            $data['alternate_uom_id'] = null;
            $data['conversion_factor'] = null;
            $data['alt_selling_price'] = null;
            $data['alt_purchase_price'] = null;
            $data['alt_trade_price'] = null;
            $data['alt_calculation_mrp'] = null;
        } else {
            $conversion = (float) ($data['conversion_factor'] ?? 1);
            $data['conversion_factor'] = $conversion;

            $data['alt_selling_price'] = (isset($data['alt_selling_price']) && $data['alt_selling_price'] !== null && $data['alt_selling_price'] !== '')
                ? (float) $data['alt_selling_price']
                : round(((float) $data['selling_price']) * $conversion, 2);

            $data['alt_purchase_price'] = (isset($data['alt_purchase_price']) && $data['alt_purchase_price'] !== null && $data['alt_purchase_price'] !== '')
                ? (float) $data['alt_purchase_price']
                : round(((float) $data['purchase_price']) * $conversion, 2);

            $data['alt_trade_price'] = (isset($data['alt_trade_price']) && $data['alt_trade_price'] !== null && $data['alt_trade_price'] !== '')
                ? (float) $data['alt_trade_price']
                : round(((float) $data['trade_price']) * $conversion, 2);

            $data['alt_calculation_mrp'] = (isset($data['alt_calculation_mrp']) && $data['alt_calculation_mrp'] !== null && $data['alt_calculation_mrp'] !== '')
                ? (float) $data['alt_calculation_mrp']
                : round(((float) $data['calculation_mrp']) * $conversion, 2);
        }

        $data['discount_percent'] = $data['discount_percent'] ?? ($product?->discount_percent ?? 0);
        $data['discount_type'] = $data['discount_type'] ?? ($product?->discount_type ?? 'percent');
        $data['discount_value'] = $data['discount_value'] ?? ($product?->discount_value ?? 0);
        $data['selling_discount_type'] = $data['selling_discount_type'] ?? ($product?->selling_discount_type ?? 'percent');
        $data['selling_discount_value'] = $data['selling_discount_value'] ?? ($product?->selling_discount_value ?? 0);

        $data['warranty'] = $request->has('warranty')
            ? (filled($request->input('warranty')) ? trim((string) $request->input('warranty')) : null)
            : ($product?->warranty ?? null);

        $data['height_cm'] = filled($request->input('height_cm'))
            ? (float) $request->input('height_cm')
            : ($product?->height_cm ?? null);
        $data['length_cm'] = filled($request->input('length_cm'))
            ? (float) $request->input('length_cm')
            : ($product?->length_cm ?? null);
        $data['width_cm'] = filled($request->input('width_cm'))
            ? (float) $request->input('width_cm')
            : ($product?->width_cm ?? null);

        $data['height_in'] = filled($request->input('height_in'))
            ? (float) $request->input('height_in')
            : ($data['height_cm'] ? round($data['height_cm'] / 2.54, 2) : ($product?->height_in ?? null));
        $data['length_in'] = filled($request->input('length_in'))
            ? (float) $request->input('length_in')
            : ($data['length_cm'] ? round($data['length_cm'] / 2.54, 2) : ($product?->length_in ?? null));
        $data['width_in'] = filled($request->input('width_in'))
            ? (float) $request->input('width_in')
            : ($data['width_cm'] ? round($data['width_cm'] / 2.54, 2) : ($product?->width_in ?? null));

        $data['net_weight'] = filled($request->input('net_weight'))
            ? (float) $request->input('net_weight')
            : ($product?->net_weight ?? null);
        if ($skipDimensions) {
            $data['height_cm'] = null;
            $data['length_cm'] = null;
            $data['width_cm'] = null;
            $data['height_in'] = null;
            $data['length_in'] = null;
            $data['width_in'] = null;
            $data['net_weight'] = null;
        }
        $data['weight_unit'] = filled($request->input('weight_unit'))
            ? trim((string) $request->input('weight_unit'))
            : ($product?->weight_unit ?? 'KG');
        $data['warranty_months'] = $request->has('warranty')
            ? WarrantyPeriod::months(is_string($data['warranty'] ?? null) ? $data['warranty'] : null)
            : ($data['warranty_months'] ?? ($product?->warranty_months ?? null));
        $data['warranty_terms'] = $data['warranty_terms'] ?? ($product?->warranty_terms ?? null);
        $data['sender_warranty_months'] = $data['sender_warranty_months'] ?? ($product?->sender_warranty_months ?? null);
        $data['sender_warranty_terms'] = $data['sender_warranty_terms'] ?? ($product?->sender_warranty_terms ?? null);
        $data['customer_warranty_months'] = $data['customer_warranty_months'] ?? ($product?->customer_warranty_months ?? null);
        $data['customer_warranty_terms'] = $data['customer_warranty_terms'] ?? ($product?->customer_warranty_terms ?? null);

        $trackingType = $request->input('tracking_type');
        if (is_array($trackingType)) {
            $cleaned = array_values(array_unique(array_filter($trackingType, fn ($t) => filled($t) && $t !== 'none')));
            $data['tracking_type'] = empty($cleaned) ? 'none' : implode(',', $cleaned);
        } elseif (is_string($trackingType) && filled($trackingType)) {
            $data['tracking_type'] = $trackingType;
        } else {
            $data['tracking_type'] = 'none';
        }

        $typeId = $data['product_type_id'] ?? $request->input('product_type_id');
        if ($typeId && ProductType::query()->whereKey($typeId)->where('track_serial', true)->exists()) {
            $parts = array_values(array_filter(explode(',', (string) $data['tracking_type']), fn ($part) => $part !== '' && $part !== 'none'));
            if (! in_array('serial', $parts, true)) {
                $parts[] = 'serial';
            }
            $data['tracking_type'] = implode(',', $parts);
        }

        $data['min_stock'] = $data['min_stock'] ?? 0;
        $data['reorder_level'] = $data['reorder_level'] ?? 0;
        $data['company_id'] = auth()->user()?->company_id ?? Company::defaultCompanyId() ?? Company::query()->value('id');

        unset($data['image'], $data['remove_image'], $data['product_uoms']);

        if ($request->boolean('remove_image') && $product?->image_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($product?->image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        return $data;
    }

    protected function syncBaseUom(Product $product): void
    {
        ProductUom::updateOrCreate(
            ['product_id' => $product->id, 'uom_id' => $product->base_uom_id],
            [
                'conversion_factor' => 1,
                'is_base' => true,
                'label' => 'Base',
                'selling_price' => $product->selling_price,
                'trade_price' => $product->trade_price,
                'purchase_price' => $product->purchase_price,
                'mrp' => $product->calculation_mrp,
                'is_default_sales' => true,
                'is_active' => true,
            ],
        );
    }

    protected function syncAlternateUoms(Request $request, Product $product): void
    {
        $submittedRows = collect($request->input('product_uoms', []))
            ->filter(fn ($r) => filled($r['uom_id'] ?? null) && (int) $r['uom_id'] !== (int) $product->base_uom_id)
            ->values();

        $rows = collect();

        // 1. Primary alternate UOM
        if ($product->alternate_uom_id && (int) $product->alternate_uom_id !== (int) $product->base_uom_id) {
            $altUom = $product->alternateUom ?? Uom::find($product->alternate_uom_id);
            $rows->push([
                'uom_id' => (int) $product->alternate_uom_id,
                'conversion_factor' => (float) ($product->conversion_factor ?? 1),
                'label' => $altUom?->name ?? 'Alternative',
                'selling_price' => $product->alt_selling_price,
                'trade_price' => $product->alt_trade_price,
                'purchase_price' => $product->alt_purchase_price,
                'mrp' => $product->alt_calculation_mrp,
                'is_default_sales' => false,
            ]);
        }

        // 2. Additional submitted rows
        foreach ($submittedRows as $r) {
            $uomId = (int) $r['uom_id'];
            if ($uomId === (int) $product->alternate_uom_id || $uomId === (int) $product->base_uom_id) {
                continue;
            }
            $factor = (float) ($r['conversion_factor'] ?? 1);
            $rows->push([
                'uom_id' => $uomId,
                'conversion_factor' => $factor,
                'label' => $r['label'] ?? null,
                'selling_price' => isset($r['selling_price']) && $r['selling_price'] !== '' ? (float) $r['selling_price'] : round(((float) $product->selling_price) * $factor, 2),
                'trade_price' => isset($r['trade_price']) && $r['trade_price'] !== '' ? (float) $r['trade_price'] : round(((float) $product->trade_price) * $factor, 2),
                'purchase_price' => isset($r['purchase_price']) && $r['purchase_price'] !== '' ? (float) $r['purchase_price'] : round(((float) $product->purchase_price) * $factor, 2),
                'mrp' => isset($r['mrp']) && $r['mrp'] !== '' ? (float) $r['mrp'] : round(((float) $product->calculation_mrp) * $factor, 2),
                'is_default_sales' => (bool) ($r['is_default_sales'] ?? false),
            ]);
        }

        ProductUom::query()
            ->where('product_id', $product->id)
            ->where('is_base', false)
            ->delete();

        foreach ($rows as $r) {
            ProductUom::create([
                'product_id' => $product->id,
                'uom_id' => $r['uom_id'],
                'conversion_factor' => $r['conversion_factor'],
                'is_base' => false,
                'label' => $r['label'],
                'selling_price' => $r['selling_price'],
                'trade_price' => $r['trade_price'],
                'purchase_price' => $r['purchase_price'],
                'mrp' => $r['mrp'],
                'is_default_sales' => $r['is_default_sales'],
                'is_active' => true,
            ]);
        }
    }
}