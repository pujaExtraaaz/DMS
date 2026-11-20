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
use App\Domains\Master\Models\TaxRate;
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
use Illuminate\Validation\ValidationException;
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

        $canViewHidden = $this->canViewHidden();
        $canHideProduct = $this->canHideProduct();
        $canUnhideProduct = $this->canUnhideProduct();

        $rawStatus = $request->input('status');
        $searchTerm = trim((string) $request->input('search'));
        $isSearching = ($searchTerm !== '');

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
            });

        // Visibility & Status Filtering:
        // By default, regular listing displays only products that are not hidden (is_hidden = false).
        // If status == 'hidden' and user is authorized, display hidden products.
        // If user searches for a product, matching hidden products are included in results for authorized users.
        // Search results must not accidentally expose hidden products when not searched or when Hidden is not selected.
        if ($rawStatus === 'hidden' && $canViewHidden) {
            $query->where('is_hidden', true);
        } elseif ($rawStatus === 'active') {
            $query->where('is_active', true);
            if (! $isSearching || ! $canViewHidden) {
                $query->where('is_hidden', false);
            }
        } elseif ($rawStatus === 'inactive') {
            $query->where('is_active', false);
            if (! $isSearching || ! $canViewHidden) {
                $query->where('is_hidden', false);
            }
        } else {
            // Default: All Visible Products (or 'all_visible' or empty)
            if (! $isSearching || ! $canViewHidden) {
                $query->where('is_hidden', false);
            }
        }

        // Prefix search only (S → starts with S, So → starts with So).
        // Match name / part number / serial — not mid-string or unrelated model fragments.
        $this->applySearch($query, $request->input('search'), ['name', 'sku', 'serial_no'], [
            'serials' => ['serial_number'],
        ]);

        if ($export = $this->exportProducts($request, $query)) {
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
                'is_hidden' => 'is_hidden',
                'created_at' => 'created_at',
            ],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 5 || $perPage > 200) {
            $perPage = 15;
        }

        $items = $query->paginate($perPage)->withQueryString();

        $newProductId = $request->input('new_product_id');
        $updatedProductId = $request->input('updated_product_id');
        $bannerProduct = null;

        if ($newProductId) {
            $inCurrentPage = $items->contains('id', (int) $newProductId);
            if (! $inCurrentPage) {
                $bannerProduct = Product::with(['baseUom', 'brand', 'category', 'subCategory', 'productType'])->find($newProductId);
            }
        }

        $serialSearch = trim((string) $request->input('search'));
        $serialMatches = $serialSearch === ''
            ? collect()
            : ProductSerial::query()
                ->with(['product', 'customer', 'salesInvoice'])
                ->where('serial_number', $serialSearch)
                ->limit(10)
                ->get();

        $productIds = $items->pluck('id')->all();
        if ($bannerProduct && ! in_array($bannerProduct->id, $productIds, true)) {
            $productIds[] = $bannerProduct->id;
        }
        $stockSummary = app(\App\Domains\Inventory\Services\InTransitStockService::class)->getAggregatedStockForProducts($productIds);

        return view('masters.products.index', [
            'items' => $items,
            'stockSummary' => $stockSummary,
            'serialMatches' => $serialMatches,
            'bannerProduct' => $bannerProduct,
            'newProductId' => $newProductId,
            'updatedProductId' => $updatedProductId,
            'perPage' => $perPage,
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
            'canViewHidden' => $canViewHidden,
            'canHideProduct' => $canHideProduct,
            'canUnhideProduct' => $canUnhideProduct,
            'exportColumnGroups' => $this->getExportColumnGroups(),
            'defaultExportColumns' => $this->getDefaultExportColumnKeys(),
        ]);
    }

    public function hide(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        if (! $this->canHideProduct()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to hide products.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized to hide product.');
        }

        $user = auth()->user();
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
            $product->hide();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Product '{$productName}' marked as hidden.",
                ]);
            }

            return back()->with('success', "Product '{$productName}' marked as hidden.");
        } catch (\Throwable $e) {
            report($e);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to hide product: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to hide product: ' . $e->getMessage());
        }
    }

    public function unhide(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        if (! $this->canUnhideProduct()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to unhide products.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized to unhide product.');
        }

        $user = auth()->user();
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
            $product->unhide();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Product '{$productName}' restored to regular product listing.",
                ]);
            }

            return back()->with('success', "Product '{$productName}' restored to regular product listing.");
        } catch (\Throwable $e) {
            report($e);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to unhide product: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to unhide product: ' . $e->getMessage());
        }
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

    public function create(Request $request): View
    {
        return view('masters.products.form', [
            'returnTo' => $request->query('return_to', old('return_to')),
            'returnUrl' => $this->safeInternalReturnUrl($request->query('return_url', old('return_url'))),
            'targetRow' => $request->query('target_row', old('target_row')),
            'orderId' => $request->query('order_id', old('order_id')),
            'invoiceId' => $request->query('invoice_id', old('invoice_id')),
            ...$this->formData(new Product),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $isManualSerial = $request->filled('serial_no');
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($data, $request, $isManualSerial) {
                    $insertData = $data;
                    if (! $isManualSerial || empty($insertData['serial_no'])) {
                        $insertData['serial_no'] = CodeGenerator::forProductSerial();
                    }

                    $product = Product::create($insertData);

                    $this->syncBaseUom($product);
                    $this->syncAlternateUoms($request, $product);

                    $returnTo = $request->input('return_to');
                    $returnUrl = $this->safeInternalReturnUrl($request->input('return_url'));
                    $targetRow = $request->input('target_row');
                    $orderId = $request->input('order_id');

                    if ($returnTo === 'orders.create') {
                        $params = ['new_product_id' => $product->id];
                        if ($targetRow !== null && $targetRow !== '') {
                            $params['target_row'] = $targetRow;
                        }

                        return redirect()->route('orders.create', $params)
                            ->with('status', "Product '{$product->name}' created successfully.")
                            ->with('success', "Product '{$product->name}' created successfully.");
                    }

                    if ($returnTo === 'orders.edit' && $orderId) {
                        $params = ['order' => $orderId, 'new_product_id' => $product->id];
                        if ($targetRow !== null && $targetRow !== '') {
                            $params['target_row'] = $targetRow;
                        }

                        return redirect()->route('orders.edit', $params)
                            ->with('status', "Product '{$product->name}' created successfully.")
                            ->with('success', "Product '{$product->name}' created successfully.");
                    }

                    if ($returnTo === 'invoices.create') {
                        $params = ['new_product_id' => $product->id];
                        if ($targetRow !== null && $targetRow !== '') {
                            $params['target_row'] = $targetRow;
                        }

                        return redirect()->route('invoices.create', $params)
                            ->with('status', "Product '{$product->name}' created successfully. You can continue preparing your tax invoice.")
                            ->with('success', "Product '{$product->name}' created successfully. You can continue preparing your tax invoice.");
                    }

                    if (in_array($returnTo, ['purchasing.invoices.create', 'purchase_invoice', 'purchases.invoices.create'], true)) {
                        $params = ['new_product_id' => $product->id];
                        if ($targetRow !== null && $targetRow !== '') {
                            $params['target_row'] = $targetRow;
                        }

                        $msg = "Product '{$product->name}' created successfully. You can now add it to your purchase invoice.";

                        return redirect()->route('purchasing.invoices.create', $params)
                            ->with('status', $msg)
                            ->with('success', $msg);
                    }

                    if ($returnUrl) {
                        $separator = str_contains($returnUrl, '?') ? '&' : '?';
                        $redirectUrl = $returnUrl . $separator . 'new_product_id=' . $product->id;
                        if ($targetRow !== null && $targetRow !== '') {
                            $redirectUrl .= '&target_row=' . urlencode((string) $targetRow);
                        }

                        $isInvoice = str_contains($returnUrl, '/invoices') || str_contains($returnUrl, 'invoices.create');
                        $isPurchase = str_contains($returnUrl, '/purchasing');

                        if ($isInvoice) {
                            $msg = "Product '{$product->name}' created successfully. You can continue preparing your tax invoice.";
                        } elseif ($isPurchase) {
                            $msg = "Product '{$product->name}' created successfully. You can now add it to your purchase invoice.";
                        } else {
                            $msg = "Product '{$product->name}' created successfully.";
                        }

                        return redirect($redirectUrl)
                            ->with('status', $msg)
                            ->with('success', $msg);
                    }

                    return $this->flashSuccess('Product created successfully.', 'masters.products.index');
                });
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                if ($isManualSerial) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'serial_no' => 'The product serial number has already been taken.',
                    ]);
                }

                if ($attempt >= $maxAttempts) {
                    return back()->withInput()->withErrors([
                        'serial_no' => 'Could not generate a unique serial number. Please try again.',
                    ]);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                if ($this->isDuplicateSerialException($e)) {
                    if ($isManualSerial) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'serial_no' => 'The product serial number has already been taken.',
                        ]);
                    }

                    if ($attempt >= $maxAttempts) {
                        return back()->withInput()->withErrors([
                            'serial_no' => 'Could not generate a unique serial number. Please try again.',
                        ]);
                    }
                } else {
                    throw $e;
                }
            }
        }

        return back()->withInput()->withErrors([
            'serial_no' => 'Could not generate a unique serial number. Please try again.',
        ]);
    }

    public function show(Request $request, Product $product): View|JsonResponse
    {
        if ($request->wantsJson() || $request->ajax()) {
            $product->loadMissing(['brand', 'category', 'subCategory', 'productType', 'baseUom', 'alternateUom']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->unit ?? ($product->baseUom?->name ?? ($product->baseUom?->code ?? 'PCS')),
                    'tax_rate' => (float) ($product->tax_rate ?? 0),
                    'mrp' => (float) ($product->mrp ?? ($product->calculation_mrp ?? 0)),
                    'purchase_price' => (float) ($product->purchase_price ?? 0),
                    'tracking_type' => $product->tracking_type ?? 'none',
                    'tracking_types' => $product->tracking_types,
                    'is_serial_tracked' => $product->isSerialTracked(),
                    'track_serial' => $product->isSerialTracked(),
                    'is_batch_tracked' => $product->isBatchTracked(),
                ],
            ]);
        }

        $product->load(['brand', 'category', 'subCategory', 'productType', 'baseUom', 'alternateUom']);
        $returnUrl = $this->safeInternalReturnUrl($request->query('return_url'));

        return view('masters.products.show', compact('product', 'returnUrl'));
    }

    public function edit(Request $request, Product $product): View
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

        return view('masters.products.form', [
            'returnTo' => $request->query('return_to', old('return_to')),
            'returnUrl' => $this->safeInternalReturnUrl($request->query('return_url', old('return_url'))),
            'targetRow' => $request->query('target_row', old('target_row')),
            'orderId' => $request->query('order_id', old('order_id')),
            'invoiceId' => $request->query('invoice_id', old('invoice_id')),
            ...$this->formData($product),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);

        if (! $request->filled('serial_no')) {
            unset($data['serial_no']);
        }

        try {
            DB::transaction(function () use ($data, $product, $request) {
                $product->update($data);
                $this->syncBaseUom($product->fresh());
                $this->syncAlternateUoms($request, $product->fresh());
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return back()->withInput()->withErrors([
                'serial_no' => 'The product serial number has already been taken.',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($this->isDuplicateSerialException($e)) {
                return back()->withInput()->withErrors([
                    'serial_no' => 'The product serial number has already been taken.',
                ]);
            }
            throw $e;
        }

        $returnUrl = $this->safeInternalReturnUrl($request->input('return_url'));
        $returnTo = $request->input('return_to');
        $targetRow = $request->input('target_row');
        $invoiceId = $request->input('invoice_id');

        if ($returnTo === 'invoices.create') {
            $params = [
                'updated_product_id' => $product->id,
                'returned_from' => 'product_edit',
            ];
            if ($targetRow !== null && $targetRow !== '') {
                $params['target_row'] = $targetRow;
            }

            return redirect()->route('invoices.create', $params)
                ->with('status', "Product '{$product->name}' updated successfully. You can continue preparing your tax invoice.")
                ->with('success', "Product '{$product->name}' updated successfully. You can continue preparing your tax invoice.");
        }

        if ($returnTo === 'invoices.edit') {
            if ($returnUrl) {
                $separator = str_contains($returnUrl, '?') ? '&' : '?';
                $redirectUrl = $returnUrl . $separator . 'updated_product_id=' . $product->id . '&returned_from=product_edit';
                if ($targetRow !== null && $targetRow !== '') {
                    $redirectUrl .= '&target_row=' . urlencode((string) $targetRow);
                }

                return redirect($redirectUrl)
                    ->with('status', "Product '{$product->name}' updated successfully. You can continue editing the tax invoice.")
                    ->with('success', "Product '{$product->name}' updated successfully. You can continue editing the tax invoice.");
            } elseif ($invoiceId) {
                $params = [
                    'invoice' => $invoiceId,
                    'updated_product_id' => $product->id,
                    'returned_from' => 'product_edit',
                ];
                if ($targetRow !== null && $targetRow !== '') {
                    $params['target_row'] = $targetRow;
                }

                return redirect()->route('invoices.edit', $params)
                    ->with('status', "Product '{$product->name}' updated successfully. You can continue editing the tax invoice.")
                    ->with('success', "Product '{$product->name}' updated successfully. You can continue editing the tax invoice.");
            }
        }

        if ($returnUrl) {
            $separator = str_contains($returnUrl, '?') ? '&' : '?';
            $redirectUrl = $returnUrl . $separator . 'updated_product_id=' . $product->id . '&returned_from=product_edit';
            if ($targetRow !== null && $targetRow !== '') {
                $redirectUrl .= '&target_row=' . urlencode((string) $targetRow);
            }

            return redirect($redirectUrl)
                ->with('status', "Product '{$product->name}' updated successfully.")
                ->with('success', "Product '{$product->name}' updated successfully.");
        }

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
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $subCategories = SubCategory::where('is_active', true)->orderBy('name')->get();
        $uoms = Uom::where('is_active', true)->orderBy('name')->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $productTypes = ProductType::where('is_active', true)->orderBy('name')->get();

        // Rehydrate inactive catalog FKs so edit screens keep the selected values visible.
        if ($item->exists) {
            if ($item->brand_id && ! $brands->contains('id', $item->brand_id)) {
                $inactive = Brand::query()->find($item->brand_id);
                if ($inactive) {
                    $brands = $brands->prepend($inactive)->unique('id')->values();
                }
            }
            if ($item->category_id && ! $categories->contains('id', $item->category_id)) {
                $inactive = Category::query()->find($item->category_id);
                if ($inactive) {
                    $categories = $categories->prepend($inactive)->unique('id')->values();
                }
            }
            if ($item->sub_category_id && ! $subCategories->contains('id', $item->sub_category_id)) {
                $inactive = SubCategory::query()->find($item->sub_category_id);
                if ($inactive) {
                    $subCategories = $subCategories->prepend($inactive)->unique('id')->values();
                }
            }
            if ($item->base_uom_id && ! $uoms->contains('id', $item->base_uom_id)) {
                $inactive = Uom::query()->find($item->base_uom_id);
                if ($inactive) {
                    $uoms = $uoms->prepend($inactive)->unique('id')->values();
                }
            }
            if ($item->alternate_uom_id && ! $uoms->contains('id', $item->alternate_uom_id)) {
                $inactive = Uom::query()->find($item->alternate_uom_id);
                if ($inactive) {
                    $uoms = $uoms->push($inactive)->unique('id')->values();
                }
            }
            if ($item->company_id && ! $companies->contains('id', $item->company_id)) {
                $inactive = Company::query()->find($item->company_id);
                if ($inactive) {
                    $companies = $companies->prepend($inactive)->unique('id')->values();
                }
            }
            if ($item->product_type_id && ! $productTypes->contains('id', $item->product_type_id)) {
                $inactive = ProductType::query()->find($item->product_type_id);
                if ($inactive) {
                    $productTypes = $productTypes->prepend($inactive)->unique('id')->values();
                }
            }
        }

        return [
            'item' => $item,
            'uoms' => $uoms,
            'brands' => $brands,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'companies' => $companies,
            'productTypes' => $productTypes,
            'taxRates' => TaxRate::query()->where('is_active', true)->orderBy('rate')->get(['id', 'name', 'rate']),
            'priceHistory' => $item->exists
                ? ProductPriceHistory::query()->where('product_id', $item->id)->orderByDesc('effective_from')->limit(30)->with('uom', 'creator')->get()
                : collect(),
        ];
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        if (! $product) {
            $routeProduct = $request->route('product');
            if ($routeProduct instanceof Product) {
                $product = $routeProduct;
            } elseif (is_numeric($routeProduct)) {
                $product = Product::query()->find((int) $routeProduct);
            } elseif ($request->filled('id') && is_numeric($request->input('id'))) {
                $product = Product::query()->find((int) $request->input('id'));
            } elseif ($request->filled('product_id') && is_numeric($request->input('product_id'))) {
                $product = Product::query()->find((int) $request->input('product_id'));
            }
        }

        $productId = $product?->id;

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
            'return_to' => 'nullable|string|max:100',
            'return_url' => 'nullable|string|max:500',
            'order_id' => 'nullable|integer',
            'target_row' => 'nullable|integer',
            'invoice_id' => 'nullable|integer',
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($product, $productId, $request) {
                    $normalized = mb_strtolower(trim((string) $value));
                    if ($normalized === '') {
                        return;
                    }

                    // When updating an existing product: allow the existing product to retain its current name.
                    if ($productId && $product && $product->exists) {
                        $currentNormalizedName = mb_strtolower(trim((string) $product->name));
                        if ($normalized === $currentNormalizedName) {
                            return;
                        }
                    }

                    $query = Product::query()
                        ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized]);

                    if ($productId) {
                        $query->where('id', '!=', $productId);
                    }

                    // Prefer same company scope when available (company isolation).
                    $companyId = $request->filled('company_id')
                        ? $request->integer('company_id')
                        : ($product?->company_id ?: ($request->user()?->company_id));

                    if ($companyId) {
                        $query->where(function ($q) use ($companyId) {
                            $q->where('company_id', $companyId)->orWhereNull('company_id');
                        });
                    }

                    // Same brand + same part number (when provided) strengthens the match.
                    if ($request->filled('brand_id')) {
                        $brandMatch = (clone $query)
                            ->where('brand_id', $request->integer('brand_id'))
                            ->first();
                        if ($brandMatch) {
                            $fail('A product with this name already exists under the selected brand. Duplicate product entries are not allowed.');

                            return;
                        }
                    }

                    if ($query->exists()) {
                        $fail('A product with this name already exists. Duplicate product entries are not allowed.');
                    }
                },
            ],
            'sku' => 'required_unless:sku_not_applicable,1|nullable|string|max:50|unique:products,sku'.($productId ? ','.$productId : ''),
            'serial_no' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($productId) {
                    if ($value !== null && $value !== '') {
                        $query = DB::table('products')->where('serial_no', $value);
                        if ($productId) {
                            $query->where('id', '!=', $productId);
                        }
                        if ($query->exists()) {
                            $fail('The product serial number has already been taken.');
                        }
                    }
                },
            ],
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
            'brand_id' => 'required|exists:brands,id',
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
            'track_serial' => 'nullable|boolean',
            'tracking_type' => 'nullable',
            'tracking_type.*' => 'nullable|string|in:none,serial,batch',
            'min_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'aging_threshold_days' => 'nullable|integer|min:0',
            'credit_period_days' => 'nullable|integer|min:0',
            'payment_period_days' => 'nullable|integer|min:0',
            'lifespan_days' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_hidden' => 'nullable|boolean',
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
            'brand_id.required' => 'Please select a brand for this product.',
            'brand_id.exists' => 'Please select a valid brand for this product.',
            'base_uom_id.required' => 'The base UOM field is required.',
            'alternate_uom_id.required' => 'The alternative UOM field is required.',
            'alternate_uom_id.different' => 'The alternative UOM and base UOM must be different.',
            'conversion_factor.required' => 'The conversion quantity is required.',
            'conversion_factor.gt' => 'The conversion quantity must be greater than zero.',
            'sku.required' => 'The part number field is required.',
            'sku.unique' => 'The part number has already been taken. Duplicate product entries are not allowed.',
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

        $this->assertCatalogHierarchy($data);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_hidden'] = $request->has('is_hidden') ? $request->boolean('is_hidden') : ($product?->is_hidden ?? false);
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
            $defaultVal = Product::DEFAULT_BEHAVIOUR_VALUES[$behaviourFlag] ?? false;
            if ($request->exists($behaviourFlag)) {
                $data[$behaviourFlag] = $request->boolean($behaviourFlag);
            } elseif ($product && $product->exists) {
                $data[$behaviourFlag] = (bool) $product->{$behaviourFlag};
            } else {
                $data[$behaviourFlag] = $defaultVal;
            }
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

        if ($request->has('track_serial')) {
            $tracks = empty($data['tracking_type']) || $data['tracking_type'] === 'none'
                ? []
                : array_map('trim', explode(',', (string) $data['tracking_type']));
            if ($request->boolean('track_serial')) {
                if (! in_array('serial', $tracks, true)) {
                    $tracks[] = 'serial';
                }
            } else {
                $tracks = array_values(array_filter($tracks, fn ($t) => $t !== 'serial'));
            }
            $data['tracking_type'] = empty($tracks) ? 'none' : implode(',', $tracks);
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

        unset($data['return_to'], $data['return_url'], $data['order_id'], $data['target_row'], $data['invoice_id']);

        return $data;
    }

    /**
     * Allow redirect only to same-app relative or absolute URLs (open-redirect safe).
     * Returns a path relative to the app root (no subdirectory prefix) so
     * redirect() + APP_URL=http://host/dms does not produce /dms/dms/...
     */
    protected function safeInternalReturnUrl(mixed $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);
        if ($url === '' || str_starts_with($url, '//')) {
            return null;
        }

        // Relative path starting with single /
        if (str_starts_with($url, '/')) {
            return $this->stripAppBasePath($url);
        }

        $parsed = parse_url($url);
        if ($parsed === false || empty($parsed['host'])) {
            return null;
        }

        $currentHost = request()->getHost();
        $appUrlHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $allowedHosts = array_filter([$currentHost, $appUrlHost, 'localhost', '127.0.0.1']);

        if (in_array(strtolower($parsed['host']), array_map('strtolower', $allowedHosts), true)) {
            $path = $parsed['path'] ?? '/';
            $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
            $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

            return $this->stripAppBasePath($path . $query . $fragment);
        }

        return null;
    }

    /**
     * Strip subdirectory base path (/dms) so Laravel url generation does not double it.
     */
    protected function stripAppBasePath(string $pathAndQuery): string
    {
        $bases = array_unique(array_filter([
            rtrim((string) request()->getBasePath(), '/'),
            rtrim((string) (parse_url((string) config('app.url'), PHP_URL_PATH) ?: ''), '/'),
        ]));

        foreach ($bases as $base) {
            if ($base === '' || $base === '/') {
                continue;
            }

            if ($pathAndQuery === $base) {
                return '/';
            }

            if (str_starts_with($pathAndQuery, $base.'/')) {
                return substr($pathAndQuery, strlen($base)) ?: '/';
            }

            // Also handle "...?query" when path equals base exactly
            if (str_starts_with($pathAndQuery, $base.'?')) {
                return '/'.substr($pathAndQuery, strlen($base));
            }
        }

        return $pathAndQuery;
    }

    /**
     * Ensure Brand → Category → Subcategory relationships are consistent.
     *
     * @param  array<string, mixed>  $data
     */
    protected function assertCatalogHierarchy(array &$data): void
    {
        $brandId = ! empty($data['brand_id']) ? (int) $data['brand_id'] : null;
        $categoryId = ! empty($data['category_id']) ? (int) $data['category_id'] : null;
        $subCategoryId = ! empty($data['sub_category_id']) ? (int) $data['sub_category_id'] : null;

        if ($subCategoryId && ! $categoryId) {
            $sub = SubCategory::query()->find($subCategoryId);
            if ($sub?->category_id) {
                $categoryId = (int) $sub->category_id;
                $data['category_id'] = $categoryId;
            }
        }

        if ($categoryId) {
            $category = Category::query()->find($categoryId);
            if (! $category) {
                throw ValidationException::withMessages([
                    'category_id' => 'Please select a valid category.',
                ]);
            }

            if ($category->brand_id && $brandId && (int) $category->brand_id !== $brandId) {
                throw ValidationException::withMessages([
                    'category_id' => 'Selected category does not belong to the selected brand.',
                ]);
            }

            // If category has a brand and product brand is empty, inherit it.
            if (! $brandId && $category->brand_id) {
                $brandId = (int) $category->brand_id;
                $data['brand_id'] = $brandId;
            }
        }

        if ($subCategoryId) {
            $subCategory = SubCategory::query()->find($subCategoryId);
            if (! $subCategory) {
                throw ValidationException::withMessages([
                    'sub_category_id' => 'Please select a valid subcategory.',
                ]);
            }

            if (! $categoryId) {
                throw ValidationException::withMessages([
                    'category_id' => 'Please select a category before choosing a subcategory.',
                ]);
            }

            if ((int) $subCategory->category_id !== $categoryId) {
                throw ValidationException::withMessages([
                    'sub_category_id' => 'Selected subcategory does not belong to the selected category.',
                ]);
            }
        }

        $data['category_id'] = $categoryId;
        $data['sub_category_id'] = $subCategoryId;
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

    protected function isDuplicateSerialException(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'products_serial_no_unique')
            || str_contains($message, 'products.serial_no')
            || (str_contains($message, 'Duplicate entry') && str_contains($message, 'serial_no'));
    }

    protected function canViewHidden(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['super-admin', 'owner', 'client-admin'])
            || $user->can('products.view-hidden')
            || $user->can('products.manage');
    }

    protected function canHideProduct(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['super-admin', 'owner', 'client-admin'])
            || $user->can('products.hide')
            || $user->can('products.manage');
    }

    protected function canUnhideProduct(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['super-admin', 'owner', 'client-admin'])
            || $user->can('products.unhide')
            || $user->can('products.manage');
    }

    public function inTransitDetails(Product $product): \Illuminate\Http\JsonResponse
    {
        $summary = app(\App\Domains\Inventory\Services\InTransitStockService::class)->getAggregatedStockForProducts([$product->id]);
        $data = $summary[$product->id] ?? [
            'physical_stock' => 0.0,
            'in_transit_stock' => 0.0,
            'total_expected_stock' => 0.0,
            'next_expected_delivery_date' => null,
            'warehouses' => [],
            'invoices' => [],
        ];

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'serial_no' => $product->serial_no,
            ],
            'total_in_transit' => (float) ($data['in_transit_stock'] ?? 0),
            'summary' => $data,
        ]);
    }


    /**
     * Handle customized product data export (Excel, CSV, PDF) with selectable columns.
     */
    protected function exportProducts(Request $request, \Illuminate\Database\Eloquent\Builder $query): ?Response
    {
        if (! $request->filled('export')) {
            return null;
        }

        $format = strtolower($request->string('export')->toString());
        if (! in_array($format, ['csv', 'xlsx', 'excel', 'pdf'], true)) {
            return null;
        }

        $columnDefinitions = $this->getExportColumnDefinitions();

        // Determine requested columns
        $requestedCols = $request->input('columns');
        if (is_string($requestedCols)) {
            $requestedCols = array_filter(array_map('trim', explode(',', $requestedCols)));
        } elseif (! is_array($requestedCols)) {
            $requestedCols = [];
        }

        if (! empty($requestedCols)) {
            // Keep user-specified order, retaining only recognized valid columns (case-insensitive)
            $selectedKeys = [];
            foreach ($requestedCols as $col) {
                $colKey = strtolower(trim((string) $col));
                if (isset($columnDefinitions[$colKey]) && ! in_array($colKey, $selectedKeys, true)) {
                    $selectedKeys[] = $colKey;
                }
            }
            if (empty($selectedKeys)) {
                $selectedKeys = $this->getDefaultExportColumnKeys();
            }
        } else {
            $selectedKeys = $this->getDefaultExportColumnKeys();
        }

        // Eager load related models to prevent N+1 queries
        $query->with(['baseUom', 'alternateUom', 'brand', 'category', 'subCategory', 'productType']);

        $products = $query->get();
        $productIds = $products->pluck('id')->all();

        // Batch stock computation if stock columns are requested
        $stockSummary = [];
        if (! empty($productIds) && array_intersect($selectedKeys, ['available_stock', 'in_transit_stock', 'total_stock'])) {
            $stockSummary = app(\App\Domains\Inventory\Services\InTransitStockService::class)->getAggregatedStockForProducts($productIds);
        }

        // Batch opening stock lookup if requested
        $openingStocks = [];
        if (! empty($productIds) && in_array('opening_stock', $selectedKeys, true)) {
            $openingStocks = DB::table('stock_movements')
                ->whereIn('product_id', $productIds)
                ->where('type', 'opening')
                ->groupBy('product_id')
                ->select('product_id', DB::raw('SUM(quantity) as total'))
                ->pluck('total', 'product_id')
                ->all();
        }

        // Build header labels in requested order
        $headers = [];
        foreach ($selectedKeys as $key) {
            $headers[] = $columnDefinitions[$key]['label'];
        }

        // Build rows
        $rows = [];
        foreach ($products as $p) {
            $row = [];
            foreach ($selectedKeys as $key) {
                $row[] = $columnDefinitions[$key]['resolver']($p, $stockSummary, $openingStocks);
            }
            $rows[] = $row;
        }

        if ($format === 'csv') {
            return \App\Support\DocumentExporter::exportCsv('Products-'.now()->format('Ymd-His').'.csv', $headers, $rows);
        }

        return \App\Support\DocumentExporter::exportTabular('Products', 'Products', $headers, $rows, $format);
    }

    /**
     * Return grouped export columns schema for UI presentation.
     */
    public function getExportColumnGroups(): array
    {
        return [
            'basic' => [
                'title' => 'Basic Details',
                'fields' => [
                    'name' => ['label' => 'Product Name', 'default' => true],
                    'sku' => ['label' => 'Part Number (SKU)', 'default' => true],
                    'series' => ['label' => 'Series', 'default' => false],
                    'model' => ['label' => 'Model', 'default' => true],
                    'serial_no' => ['label' => 'Serial Number', 'default' => true],
                    'description' => ['label' => 'Description', 'default' => false],
                    'is_active' => ['label' => 'Status', 'default' => true],
                    'is_hidden' => ['label' => 'Visibility', 'default' => false],
                ],
            ],
            'classification' => [
                'title' => 'Classification',
                'fields' => [
                    'brand' => ['label' => 'Brand', 'default' => true],
                    'category' => ['label' => 'Category', 'default' => true],
                    'sub_category' => ['label' => 'Subcategory', 'default' => true],
                    'product_type' => ['label' => 'Product Type', 'default' => true],
                    'supply_type' => ['label' => 'Type of Supply', 'default' => false],
                ],
            ],
            'units' => [
                'title' => 'Units',
                'fields' => [
                    'base_uom' => ['label' => 'Base UOM', 'default' => true],
                    'alternate_uom' => ['label' => 'Alternative UOM', 'default' => false],
                    'conversion_factor' => ['label' => 'Conversion Factor', 'default' => false],
                ],
            ],
            'details' => [
                'title' => 'Product Details',
                'fields' => [
                    'hsn_code' => ['label' => 'HSN Code', 'default' => true],
                    'dimension_cm' => ['label' => 'Dimensions (CM)', 'default' => false],
                    'dimension_in' => ['label' => 'Dimensions (Inches)', 'default' => false],
                    'net_weight' => ['label' => 'Net Weight', 'default' => false],
                    'specification' => ['label' => 'Specification', 'default' => false],
                    'color_variant' => ['label' => 'Color Variant', 'default' => false],
                ],
            ],
            'pricing' => [
                'title' => 'Tax & Pricing',
                'fields' => [
                    'tax_rate' => ['label' => 'GST Rate', 'default' => true],
                    'purchase_price' => ['label' => 'Purchase Price', 'default' => false],
                    'trade_price' => ['label' => 'Trade Price', 'default' => false],
                    'selling_price' => ['label' => 'Selling Price', 'default' => true],
                    'mrp' => ['label' => 'MRP', 'default' => true],
                    'purchase_price_tax_basis' => ['label' => 'Purchase Tax Basis', 'default' => false],
                    'discount' => ['label' => 'Discount', 'default' => false],
                ],
            ],
            'inventory' => [
                'title' => 'Inventory',
                'fields' => [
                    'opening_stock' => ['label' => 'Opening Stock', 'default' => false],
                    'available_stock' => ['label' => 'Available Stock', 'default' => true],
                    'in_transit_stock' => ['label' => 'In-Transit Stock', 'default' => false],
                    'total_stock' => ['label' => 'Total Stock', 'default' => true],
                    'min_stock' => ['label' => 'Min Stock', 'default' => false],
                    'reorder_level' => ['label' => 'Reorder Level', 'default' => false],
                ],
            ],
            'additional' => [
                'title' => 'Additional Details',
                'fields' => [
                    'costing_method' => ['label' => 'Costing Method', 'default' => false],
                    'warranty' => ['label' => 'Warranty', 'default' => false],
                    'warranty_terms' => ['label' => 'Warranty Terms', 'default' => false],
                    'image' => ['label' => 'Product Image', 'default' => false],
                    'tracking_type' => ['label' => 'Tracking Type', 'default' => false],
                ],
            ],
        ];
    }

    /**
     * Return column keys, labels and data resolver callbacks.
     */
    public function getExportColumnDefinitions(): array
    {
        return [
            'name' => [
                'label' => 'Product Name',
                'resolver' => fn (Product $p) => $p->name,
            ],
            'sku' => [
                'label' => 'Part Number',
                'resolver' => fn (Product $p) => $p->sku ?: '',
            ],
            'series' => [
                'label' => 'Series',
                'resolver' => fn (Product $p) => $p->series ?: '',
            ],
            'model' => [
                'label' => 'Model',
                'resolver' => fn (Product $p) => $p->model ?: ($p->models ?: ''),
            ],
            'serial_no' => [
                'label' => 'Serial Number',
                'resolver' => fn (Product $p) => $p->serial_no ?: '',
            ],
            'description' => [
                'label' => 'Description',
                'resolver' => fn (Product $p) => $p->description ?: '',
            ],
            'is_active' => [
                'label' => 'Status',
                'resolver' => fn (Product $p) => $p->is_active ? 'Active' : 'Inactive',
            ],
            'is_hidden' => [
                'label' => 'Visibility',
                'resolver' => fn (Product $p) => $p->is_hidden ? 'Hidden' : 'Visible',
            ],
            'brand' => [
                'label' => 'Brand',
                'resolver' => fn (Product $p) => $p->brand?->name ?? '',
            ],
            'category' => [
                'label' => 'Category',
                'resolver' => fn (Product $p) => $p->category?->name ?? '',
            ],
            'sub_category' => [
                'label' => 'Subcategory',
                'resolver' => fn (Product $p) => $p->subCategory?->name ?? '',
            ],
            'product_type' => [
                'label' => 'Product Type',
                'resolver' => fn (Product $p) => $p->productType?->name ?? ($p->product_type ?? ''),
            ],
            'supply_type' => [
                'label' => 'Type of Supply',
                'resolver' => fn (Product $p) => Product::SUPPLY_TYPES[$p->supply_type] ?? ucfirst((string) ($p->supply_type ?? '')),
            ],
            'base_uom' => [
                'label' => 'Base UOM',
                'resolver' => fn (Product $p) => $p->baseUom?->name ?? '',
            ],
            'alternate_uom' => [
                'label' => 'Alternative UOM',
                'resolver' => fn (Product $p) => $p->alternateUom?->name ?? '',
            ],
            'conversion_factor' => [
                'label' => 'Conversion Factor',
                'resolver' => fn (Product $p) => $p->conversion_factor !== null ? (string) (float) $p->conversion_factor : '',
            ],
            'hsn_code' => [
                'label' => 'HSN Code',
                'resolver' => fn (Product $p) => $p->hsn_code ?: '',
            ],
            'dimension_cm' => [
                'label' => 'Dimensions (CM)',
                'resolver' => fn (Product $p) => ($p->length_cm || $p->width_cm || $p->height_cm) ? "{$p->length_cm} x {$p->width_cm} x {$p->height_cm} cm" : '',
            ],
            'dimension_in' => [
                'label' => 'Dimensions (Inches)',
                'resolver' => fn (Product $p) => ($p->length_in || $p->width_in || $p->height_in) ? "{$p->length_in} x {$p->width_in} x {$p->height_in} in" : '',
            ],
            'net_weight' => [
                'label' => 'Net Weight',
                'resolver' => fn (Product $p) => $p->net_weight !== null ? ((float) $p->net_weight . ' ' . ($p->weight_unit ?: 'kg')) : '',
            ],
            'specification' => [
                'label' => 'Specification',
                'resolver' => fn (Product $p) => $p->specification ?: '',
            ],
            'color_variant' => [
                'label' => 'Color Variant',
                'resolver' => fn (Product $p) => $p->color_variant ?: '',
            ],
            'tax_rate' => [
                'label' => 'GST Rate',
                'resolver' => fn (Product $p) => $p->tax_rate !== null ? (float) $p->tax_rate . '%' : '',
            ],
            'purchase_price' => [
                'label' => 'Purchase Price',
                'resolver' => fn (Product $p) => $p->purchase_price !== null ? number_format((float) $p->purchase_price, 2, '.', '') : '',
            ],
            'trade_price' => [
                'label' => 'Trade Price',
                'resolver' => fn (Product $p) => $p->trade_price !== null ? number_format((float) $p->trade_price, 2, '.', '') : '',
            ],
            'selling_price' => [
                'label' => 'Selling Price',
                'resolver' => fn (Product $p) => $p->selling_price !== null ? number_format((float) $p->selling_price, 2, '.', '') : '',
            ],
            'mrp' => [
                'label' => 'MRP',
                'resolver' => fn (Product $p) => ($p->calculation_mrp ?? $p->mrp) !== null ? number_format((float) ($p->calculation_mrp ?? $p->mrp), 2, '.', '') : '',
            ],
            'purchase_price_tax_basis' => [
                'label' => 'Purchase Tax Basis',
                'resolver' => fn (Product $p) => Product::PURCHASE_TAX_BASES[$p->purchase_price_tax_basis] ?? ucfirst((string) ($p->purchase_price_tax_basis ?? '')),
            ],
            'discount' => [
                'label' => 'Discount',
                'resolver' => fn (Product $p) => $p->discount_value ? ($p->discount_type === 'percent' ? "{$p->discount_value}%" : "₹ {$p->discount_value}") : ($p->discount_percent ? "{$p->discount_percent}%" : ''),
            ],
            'opening_stock' => [
                'label' => 'Opening Stock',
                'resolver' => fn (Product $p, $stockSummary, $openingStocks) => number_format((float) ($openingStocks[$p->id] ?? 0), 2, '.', ''),
            ],
            'available_stock' => [
                'label' => 'Available Stock',
                'resolver' => fn (Product $p, $stockSummary) => number_format((float) ($stockSummary[$p->id]['physical_stock'] ?? 0), 2, '.', ''),
            ],
            'in_transit_stock' => [
                'label' => 'In-Transit Stock',
                'resolver' => fn (Product $p, $stockSummary) => number_format((float) ($stockSummary[$p->id]['in_transit_stock'] ?? 0), 2, '.', ''),
            ],
            'total_stock' => [
                'label' => 'Total Stock',
                'resolver' => fn (Product $p, $stockSummary) => number_format((float) ($stockSummary[$p->id]['total_expected_stock'] ?? 0), 2, '.', ''),
            ],
            'min_stock' => [
                'label' => 'Min Stock',
                'resolver' => fn (Product $p) => $p->min_stock !== null ? (string) (float) $p->min_stock : '',
            ],
            'reorder_level' => [
                'label' => 'Reorder Level',
                'resolver' => fn (Product $p) => $p->reorder_level !== null ? (string) (float) $p->reorder_level : '',
            ],
            'costing_method' => [
                'label' => 'Costing Method',
                'resolver' => fn (Product $p) => Product::COSTING_METHODS[$p->costing_method] ?? ucfirst(str_replace('_', ' ', (string) ($p->costing_method ?? ''))),
            ],
            'warranty' => [
                'label' => 'Warranty',
                'resolver' => fn (Product $p) => $p->warranty ?: ($p->warranty_months ? "{$p->warranty_months} Months" : ''),
            ],
            'warranty_terms' => [
                'label' => 'Warranty Terms',
                'resolver' => fn (Product $p) => $p->warranty_terms ?: '',
            ],
            'image' => [
                'label' => 'Product Image',
                'resolver' => fn (Product $p) => $p->image_path ? asset('storage/' . $p->image_path) : ($p->catalog_link ?: ''),
            ],
            'tracking_type' => [
                'label' => 'Tracking Type',
                'resolver' => fn (Product $p) => ! empty($p->tracking_types) ? implode(', ', array_map('ucfirst', $p->tracking_types)) : ucfirst((string) ($p->tracking_type ?? 'None')),
            ],
        ];
    }

    /**
     * Default columns when none are explicitly selected.
     */
    public function getDefaultExportColumnKeys(): array
    {
        return [
            'name',
            'sku',
            'model',
            'brand',
            'category',
            'sub_category',
            'product_type',
            'base_uom',
            'tax_rate',
            'is_active',
            'is_hidden',
        ];
    }
}