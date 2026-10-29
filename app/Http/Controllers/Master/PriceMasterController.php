<?php

namespace App\Http\Controllers\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Traits\ExportsListing;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PriceMasterController extends Controller
{
    use ExportsListing, SortableAndSearchable;

    public function index(Request $request): View|Response
    {
        $query = PriceMaster::query()
            ->with(['customerType', 'brand', 'product', 'uom'])
            ->when($request->filled('type'), function ($q) use ($request) {
                if ($request->type === PriceMaster::TYPE_CUSTOMER_TYPE) {
                    $q->customerTypeWise();
                } elseif ($request->type === PriceMaster::TYPE_BRAND) {
                    $q->brandWise();
                }
            })
            ->when($request->filled('brand_id'), fn ($q) => $q->where('price_masters.brand_id', $request->brand_id))
            ->when($request->filled('customer_type_id'), fn ($q) => $q->where('price_masters.customer_type_id', $request->customer_type_id))
            ->when($request->filled('product_id'), fn ($q) => $q->where('price_masters.product_id', $request->product_id));

        $this->applySearch($query, $request->input('search'), ['price_masters.name'], [
            'product' => ['name', 'sku'],
            'customerType' => ['name'],
            'brand' => ['name'],
        ]);

        $allowedSorts = [
            'type' => 'type',
            'rate' => 'rate',
            'product' => function ($q, $dir) {
                $q->join('products', 'price_masters.product_id', '=', 'products.id')
                    ->orderBy('products.name', $dir)
                    ->select('price_masters.*');
            },
            'customer_type' => function ($q, $dir) {
                $q->leftJoin('customer_types', 'price_masters.customer_type_id', '=', 'customer_types.id')
                    ->orderBy('customer_types.name', $dir)
                    ->select('price_masters.*');
            },
            'brand' => function ($q, $dir) {
                $q->leftJoin('brands', 'price_masters.brand_id', '=', 'brands.id')
                    ->orderBy('brands.name', $dir)
                    ->select('price_masters.*');
            },
            'created_at' => 'price_masters.created_at',
        ];

        if ($export = $this->exportIfRequested(
            $request,
            $query,
            'Price-Master',
            'Price Master',
            ['Price List Type', 'Target (Customer Type / Brand)', 'Price List Name', 'Product', 'Part Number', 'Unit', 'Dealer Price (Entered)', 'Dealer GST Mode', 'Retail Price (Entered)', 'Retail GST Mode', 'Rate (General)', 'GST Mode', 'Min Qty'],
            fn ($item) => [
                $item->isBrandWise() ? 'Brand Wise' : 'Customer Type Wise',
                $item->isBrandWise() ? ($item->brand?->name ?? '') : ($item->customerType?->name ?? ''),
                $item->name ?? '',
                $item->product?->name ?? '',
                $item->product?->sku ?? '',
                $item->uom?->code ?? $item->uom?->name ?? '',
                $item->dealer_price_entered !== null ? (string) $item->dealer_price_entered : ($item->dealer_price !== null ? (string) $item->dealer_price : ''),
                $item->dealer_price_gst_mode ? ucfirst($item->dealer_price_gst_mode) : '',
                $item->retail_price_entered !== null ? (string) $item->retail_price_entered : ($item->retail_price !== null ? (string) $item->retail_price : ''),
                $item->retail_price_gst_mode ? ucfirst($item->retail_price_gst_mode) : '',
                (string) $item->rate,
                $item->rate_includes_gst ? 'Including GST' : 'Excluding GST',
                (string) ($item->min_qty ?? ''),
            ]
        )) {
            return $export;
        }

        $sortData = $this->applySorting($query, $request, $allowedSorts, defaultSort: 'product', defaultDirection: 'asc');

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 15;
        }

        $items = $query->paginate($perPage)->withQueryString();

        return view('masters.price-masters.index', [
            'items' => $items,
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
            'search' => $request->string('search'),
        ]);
    }

    public function create(Request $request): View
    {
        $item = new PriceMaster();
        $item->type = PriceMaster::TYPE_CUSTOMER_TYPE;

        return view('masters.price-masters.form', array_merge(['item' => $item], $this->formData($request->user() ?? auth()->user())));
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type', PriceMaster::TYPE_CUSTOMER_TYPE);
        if (! in_array($type, [PriceMaster::TYPE_CUSTOMER_TYPE, PriceMaster::TYPE_BRAND], true)) {
            $type = PriceMaster::TYPE_CUSTOMER_TYPE;
        }

        if ($type === PriceMaster::TYPE_BRAND) {
            $validated = $request->validate([
                'type' => 'required|in:customer_type,brand',
                'brand_id' => 'required|exists:brands,id',
                'name' => 'nullable|string|max:255',
                'lines' => 'required|array|min:1',
                'lines.*.product_id' => 'nullable|exists:products,id',
                'lines.*.uom_id' => 'nullable|exists:uoms,id',
                'lines.*.dealer_price' => 'nullable|numeric|min:0',
                'lines.*.dealer_price_gst_mode' => 'nullable|in:inclusive,exclusive,Inclusive,Exclusive',
                'lines.*.retail_price' => 'nullable|numeric|min:0',
                'lines.*.retail_price_gst_mode' => 'nullable|in:inclusive,exclusive,Inclusive,Exclusive',
                'lines.*.rate' => 'nullable|numeric|min:0',
                'lines.*.rate_includes_gst' => 'nullable|boolean',
                'lines.*.min_qty' => 'nullable|numeric|min:0',
            ]);
        } else {
            $validated = $request->validate([
                'type' => 'nullable|in:customer_type,brand',
                'customer_type_id' => 'required|exists:customer_types,id',
                'name' => 'nullable|string|max:255',
                'lines' => 'required|array|min:1',
                'lines.*.product_id' => 'nullable|exists:products,id',
                'lines.*.uom_id' => 'nullable|exists:uoms,id',
                'lines.*.dealer_price' => 'nullable|numeric|min:0',
                'lines.*.dealer_price_gst_mode' => 'nullable|in:inclusive,exclusive,Inclusive,Exclusive',
                'lines.*.retail_price' => 'nullable|numeric|min:0',
                'lines.*.retail_price_gst_mode' => 'nullable|in:inclusive,exclusive,Inclusive,Exclusive',
                'lines.*.rate' => 'nullable|numeric|min:0',
                'lines.*.rate_includes_gst' => 'nullable|boolean',
                'lines.*.min_qty' => 'nullable|numeric|min:0',
            ]);
        }

        $lines = collect($validated['lines'] ?? [])
            ->filter(fn ($row) => filled($row['product_id'] ?? null)
                || filled($row['uom_id'] ?? null)
                || filled($row['dealer_price'] ?? null)
                || filled($row['retail_price'] ?? null)
                || filled($row['rate'] ?? null)
                || filled($row['min_qty'] ?? null))
            ->values();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'Add at least one product with UOM and pricing.',
            ]);
        }

        $errors = [];
        foreach ($lines as $index => $row) {
            if (! filled($row['product_id'] ?? null)) {
                $errors["lines.{$index}.product_id"] = 'Product is required.';
            }
            if (! filled($row['uom_id'] ?? null)) {
                $errors["lines.{$index}.uom_id"] = 'UOM is required.';
            }
            $hasDealer = filled($row['dealer_price'] ?? null);
            $hasRetail = filled($row['retail_price'] ?? null);
            $hasRate = filled($row['rate'] ?? null);
            if (! $hasDealer && ! $hasRetail && ! $hasRate) {
                $errors["lines.{$index}.dealer_price"] = 'Please enter Dealer Price or Retail Price.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        // Fetch products to verify brand and calculate tax
        $products = Product::query()
            ->whereIn('id', $lines->pluck('product_id')->filter()->unique()->all())
            ->get()
            ->keyBy('id');

        // Brand-specific checks
        if ($type === PriceMaster::TYPE_BRAND) {
            foreach ($lines as $index => $row) {
                $product = $products->get($row['product_id']);
                if (! $product || (int) $product->brand_id !== (int) $validated['brand_id']) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.product_id" => "Selected product ({$product?->name}) does not belong to the selected brand.",
                    ]);
                }
            }
        }

        // Detect duplicates within the submitted form
        $seen = [];
        foreach ($lines as $index => $row) {
            $minQty = $row['min_qty'] ?? null;
            $key = implode('|', [
                (string) $type,
                (string) ($type === PriceMaster::TYPE_BRAND ? $validated['brand_id'] : $validated['customer_type_id']),
                (string) $row['product_id'],
                (string) $row['uom_id'],
                $minQty === null || $minQty === '' ? '' : (string) $minQty,
            ]);
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    "lines.{$index}.product_id" => 'Duplicate product / UOM / min qty combination in this form.',
                ]);
            }
            $seen[$key] = true;
        }

        // Check if matching prices already exist in DB
        foreach ($lines as $index => $row) {
            $minQty = filled($row['min_qty'] ?? null) ? $row['min_qty'] : null;
            $existsQuery = PriceMaster::query()
                ->where('product_id', $row['product_id'])
                ->where('uom_id', $row['uom_id'])
                ->when($minQty !== null, fn ($q) => $q->where('min_qty', $minQty), fn ($q) => $q->whereNull('min_qty'));

            if ($type === PriceMaster::TYPE_BRAND) {
                $existsQuery->where('type', PriceMaster::TYPE_BRAND)
                    ->where('brand_id', $validated['brand_id']);
            } else {
                $existsQuery->customerTypeWise()
                    ->where('customer_type_id', $validated['customer_type_id']);
            }

            if ($existsQuery->exists()) {
                $prodName = $products->get($row['product_id'])?->name ?? 'Product';
                $targetLabel = $type === PriceMaster::TYPE_BRAND ? 'brand' : 'customer type';
                throw ValidationException::withMessages([
                    "lines.{$index}.product_id" => "A price already exists for {$prodName} with this {$targetLabel}, UOM, and min quantity.",
                ]);
            }
        }

        try {
            DB::transaction(function () use ($validated, $lines, $products, $type) {
                foreach ($lines as $row) {
                    $product = $products->get($row['product_id']);
                    PriceMaster::create(array_merge(
                        $this->pricedAttributes($row, (float) ($product->tax_rate ?? 0)),
                        [
                            'type' => $type,
                            'name' => filled($validated['name'] ?? null) ? $validated['name'] : null,
                            'customer_type_id' => $type === PriceMaster::TYPE_CUSTOMER_TYPE ? $validated['customer_type_id'] : null,
                            'brand_id' => $type === PriceMaster::TYPE_BRAND ? $validated['brand_id'] : null,
                            'product_id' => $row['product_id'],
                            'uom_id' => $row['uom_id'],
                            'min_qty' => filled($row['min_qty'] ?? null) ? $row['min_qty'] : null,
                        ]
                    ));
                }
            });
        } catch (QueryException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                throw ValidationException::withMessages([
                    'lines' => 'One or more prices already exist for this ' . ($type === PriceMaster::TYPE_BRAND ? 'brand' : 'customer type') . ', product, UOM, and min qty.',
                ]);
            }
            throw $e;
        }

        $count = $lines->count();
        $label = $type === PriceMaster::TYPE_BRAND ? 'Brand-wise price' : 'Price';
        $successMsg = $count === 1 ? "{$label} created successfully." : "{$count} prices created successfully.";

        return $this->flashSuccess($successMsg, 'masters.price-masters.index')
            ->with('success', $successMsg);
    }

    public function show(Request $request, PriceMaster $priceMaster): View|JsonResponse
    {
        $user = $request->user() ?? auth()->user();

        // Enforce authorization
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('masters.view') &&
            ! $user->can('masters.manage') &&
            ! $user->can('price-master.view') &&
            ! $user->can('price-master.manage') &&
            ! $user->can('price-master.edit')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to view price list records.',
                ], 403);
            }

            abort(403, 'Unauthorized: You do not have permission to view price list records.');
        }

        // Multi-tenancy check (company boundary)
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && $priceMaster->product?->company_id && (int) $priceMaster->product->company_id !== (int) $user->company_id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This price record belongs to another company.',
                ], 403);
            }

            abort(403, 'Unauthorized: This price record belongs to another company.');
        }

        $priceMaster->loadMissing([
            'customerType',
            'brand',
            'product.brand',
            'product.category',
            'product.subCategory',
            'product.productType',
            'product.baseUom',
            'product.alternateUom',
            'product.productUoms.uom',
            'product.company',
            'uom',
        ]);

        $product = $priceMaster->product;
        $mrp = 0.0;
        if ($product) {
            if ((float) $product->calculation_mrp > 0) {
                $mrp = (float) $product->calculation_mrp;
            } elseif ($product->relationLoaded('productUoms') && ($baseUomPu = $product->productUoms->firstWhere('is_base', true)) && (float) $baseUomPu->mrp > 0) {
                $mrp = (float) $baseUomPu->mrp;
            } elseif ((float) ($product->selling_price ?? 0) > 0) {
                $mrp = (float) $product->selling_price;
            }
        }

        $dealerPricing = $priceMaster->dealerPricingSummary();
        $retailPricing = $priceMaster->retailPricingSummary();
        $generalPricing = $priceMaster->pricingSummary();

        if ($request->wantsJson() || $request->ajax()) {
            $validUoms = collect();
            if ($product) {
                if ($product->baseUom) {
                    $validUoms->push([
                        'id' => (string) $product->baseUom->id,
                        'code' => $product->baseUom->code,
                        'name' => $product->baseUom->name,
                        'is_base' => true,
                        'label' => 'Base UOM',
                    ]);
                }
                if ($product->alternateUom && (int) $product->alternate_uom_id !== (int) $product->base_uom_id) {
                    $validUoms->push([
                        'id' => (string) $product->alternateUom->id,
                        'code' => $product->alternateUom->code,
                        'name' => $product->alternateUom->name,
                        'is_base' => false,
                        'label' => 'Alternate UOM',
                    ]);
                }
                if ($product->relationLoaded('productUoms')) {
                    foreach ($product->productUoms as $pu) {
                        if ($pu->uom && ! $validUoms->contains('id', (string) $pu->uom->id)) {
                            $validUoms->push([
                                'id' => (string) $pu->uom->id,
                                'code' => $pu->uom->code,
                                'name' => $pu->uom->name,
                                'is_base' => (bool) $pu->is_base,
                                'label' => $pu->label ?: ($pu->is_base ? 'Base UOM' : 'Unit'),
                            ]);
                        }
                    }
                }
            }

            $productTypeName = $product?->productType?->name ?: ($product?->product_type ?: '');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $priceMaster->id,
                    'type' => $priceMaster->type,
                    'is_brand_wise' => $priceMaster->isBrandWise(),
                    'type_label' => $priceMaster->isBrandWise() ? 'Brand Wise' : 'Customer Type Wise',
                    'name' => $priceMaster->name,
                    'brand_id' => $priceMaster->brand_id,
                    'brand_name' => $priceMaster->brand?->name,
                    'customer_type_id' => $priceMaster->customer_type_id,
                    'customer_type_name' => $priceMaster->customerType?->name,
                    'target_name' => $priceMaster->isBrandWise() ? ($priceMaster->brand?->name ?? '—') : ($priceMaster->customerType?->name ?? '—'),
                    'product_id' => $priceMaster->product_id,
                    'uom_id' => $priceMaster->uom_id,
                    'uom_code' => $priceMaster->uom?->code ?: ($priceMaster->uom?->name ?: '—'),
                    'uom_name' => $priceMaster->uom?->name ?: '',
                    'min_qty' => $priceMaster->min_qty,
                    'rate' => (float) $priceMaster->rate,
                    'rate_entered' => $priceMaster->rate_entered !== null ? (float) $priceMaster->rate_entered : (float) $priceMaster->rate,
                    'rate_includes_gst' => (bool) $priceMaster->rate_includes_gst,
                    'dealer_price' => $priceMaster->dealer_price !== null ? (float) $priceMaster->dealer_price : null,
                    'dealer_price_entered' => $priceMaster->dealer_price_entered !== null ? (float) $priceMaster->dealer_price_entered : ($priceMaster->dealer_price !== null ? (float) $priceMaster->dealer_price : null),
                    'dealer_price_gst_mode' => $priceMaster->dealer_price_gst_mode ?: 'exclusive',
                    'retail_price' => $priceMaster->retail_price !== null ? (float) $priceMaster->retail_price : null,
                    'retail_price_entered' => $priceMaster->retail_price_entered !== null ? (float) $priceMaster->retail_price_entered : ($priceMaster->retail_price !== null ? (float) $priceMaster->retail_price : null),
                    'retail_price_gst_mode' => $priceMaster->retail_price_gst_mode ?: 'exclusive',
                    'dealer_pricing' => $dealerPricing,
                    'retail_pricing' => $retailPricing,
                    'general_pricing' => $generalPricing,
                    'created_at' => $priceMaster->created_at?->format('d M Y, h:i A'),
                    'updated_at' => $priceMaster->updated_at?->format('d M Y, h:i A'),
                    'product' => $product ? [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku ?: '',
                        'part_number' => $product->sku ?: '',
                        'model' => $product->model ?: '',
                        'series' => $product->series ?: '',
                        'brand_id' => $product->brand_id,
                        'brand_name' => $product->brand?->name ?: '',
                        'category_name' => $product->category?->name ?: '',
                        'sub_category_name' => $product->subCategory?->name ?: '',
                        'product_type_name' => $productTypeName,
                        'hsn_code' => $product->hsn_code ?: '',
                        'tax_rate' => $product->tax_rate !== null ? (float) $product->tax_rate : 0.0,
                        'mrp' => $mrp > 0 ? $mrp : null,
                        'selling_price' => $product->selling_price !== null ? (float) $product->selling_price : null,
                        'base_uom_name' => $product->baseUom?->name ?: ($product->baseUom?->code ?: ''),
                        'uoms' => $validUoms->values()->all(),
                    ] : null,
                ],
            ]);
        }

        // Query all associated products under the same price list / brand / customer type
        $associatedProductsQuery = PriceMaster::query()
            ->with(['product.brand', 'product.category', 'product.subCategory', 'uom']);

        if ($priceMaster->isBrandWise()) {
            $associatedProductsQuery->where('type', PriceMaster::TYPE_BRAND)
                ->where('brand_id', $priceMaster->brand_id);
            if (filled($priceMaster->name)) {
                $associatedProductsQuery->where('name', $priceMaster->name);
            }
        } else {
            $associatedProductsQuery->customerTypeWise()
                ->where('customer_type_id', $priceMaster->customer_type_id);
            if (filled($priceMaster->name)) {
                $associatedProductsQuery->where('name', $priceMaster->name);
            }
        }

        $associatedProducts = $associatedProductsQuery->orderBy('id')->get();
        $company = $priceMaster->product?->company ?? ($user?->company ?? null);

        return view('masters.price-masters.show', [
            'item' => $priceMaster,
            'priceMaster' => $priceMaster,
            'product' => $product,
            'mrp' => $mrp,
            'company' => $company,
            'dealerPricing' => $dealerPricing,
            'retailPricing' => $retailPricing,
            'generalPricing' => $generalPricing,
            'associatedProducts' => $associatedProducts,
        ]);
    }

    public function productDetails(Request $request, Product $product): JsonResponse
    {
        $user = $request->user() ?? auth()->user();

        // Multi-tenancy check (company boundary)
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && $product->company_id && (int) $product->company_id !== (int) $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Product belongs to another company.',
            ], 403);
        }

        $product->loadMissing([
            'brand:id,name',
            'category:id,name',
            'subCategory:id,name',
            'productType:id,name',
            'baseUom:id,name,code',
            'alternateUom:id,name,code',
            'productUoms.uom:id,name,code',
        ]);

        $mrp = 0.0;
        if ((float) $product->calculation_mrp > 0) {
            $mrp = (float) $product->calculation_mrp;
        } elseif ($product->relationLoaded('productUoms') && ($baseUomPu = $product->productUoms->firstWhere('is_base', true)) && (float) $baseUomPu->mrp > 0) {
            $mrp = (float) $baseUomPu->mrp;
        } elseif ((float) ($product->selling_price ?? 0) > 0) {
            $mrp = (float) $product->selling_price;
        }

        $productTypeName = $product->productType?->name ?: ($product->product_type ?: null);

        $validUoms = collect();
        if ($product->baseUom) {
            $validUoms->push([
                'id' => (string) $product->baseUom->id,
                'code' => $product->baseUom->code,
                'name' => $product->baseUom->name,
                'is_base' => true,
                'label' => 'Base UOM',
            ]);
        }
        if ($product->alternateUom && (int) $product->alternate_uom_id !== (int) $product->base_uom_id) {
            $validUoms->push([
                'id' => (string) $product->alternateUom->id,
                'code' => $product->alternateUom->code,
                'name' => $product->alternateUom->name,
                'is_base' => false,
                'label' => 'Alternate UOM',
            ]);
        }
        if ($product->relationLoaded('productUoms')) {
            foreach ($product->productUoms as $pu) {
                if ($pu->uom && ! $validUoms->contains('id', (string) $pu->uom->id)) {
                    $validUoms->push([
                        'id' => (string) $pu->uom->id,
                        'code' => $pu->uom->code,
                        'name' => $pu->uom->name,
                        'is_base' => (bool) $pu->is_base,
                        'label' => $pu->label ?: ($pu->is_base ? 'Base UOM' : 'Unit'),
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku ?: '',
                'part_number' => $product->sku ?: '',
                'model' => $product->model ?: '',
                'series' => $product->series ?: '',
                'brand_id' => $product->brand_id ? (string) $product->brand_id : '',
                'brand_name' => $product->brand?->name ?: '',
                'category_id' => $product->category_id ? (string) $product->category_id : '',
                'category_name' => $product->category?->name ?: '',
                'sub_category_id' => $product->sub_category_id ? (string) $product->sub_category_id : '',
                'sub_category_name' => $product->subCategory?->name ?: '',
                'product_type_id' => $product->product_type_id ? (string) $product->product_type_id : '',
                'product_type' => $productTypeName ?: '',
                'product_type_name' => $productTypeName ?: '',
                'hsn_code' => $product->hsn_code ?: '',
                'tax_rate' => $product->tax_rate !== null ? (float) $product->tax_rate : 0.0,
                'gst_rate' => $product->tax_rate !== null ? (float) $product->tax_rate : 0.0,
                'mrp' => $mrp > 0 ? $mrp : null,
                'selling_price' => $product->selling_price !== null ? (float) $product->selling_price : null,
                'base_uom_id' => $product->base_uom_id ? (string) $product->base_uom_id : '',
                'base_uom_code' => $product->baseUom?->code ?: '',
                'base_uom_name' => $product->baseUom?->name ?: '',
                'uoms' => $validUoms->values()->all(),
            ],
        ]);
    }

    public function brandProducts(Request $request, Brand $brand): JsonResponse
    {
        $user = $request->user() ?? auth()->user();

        // Multi-tenancy check
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && $brand->company_id && (int) $brand->company_id !== (int) $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Brand belongs to another company.',
            ], 403);
        }

        $products = Product::query()
            ->where('is_active', true)
            ->where('brand_id', $brand->id)
            ->when($user?->company_id && ! $user->hasAnyRole(['super-admin', 'client-admin']), function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->whereNull('company_id')->orWhere('company_id', $user->company_id);
                });
            })
            ->with([
                'brand:id,name',
                'category:id,name',
                'subCategory:id,name',
                'productType:id,name',
                'baseUom:id,name,code',
                'alternateUom:id,name,code',
                'productUoms.uom:id,name,code',
            ])
            ->orderBy('name')
            ->get();

        $data = $products->map(function ($product) {
            $mrp = 0.0;
            if ((float) $product->calculation_mrp > 0) {
                $mrp = (float) $product->calculation_mrp;
            } elseif ($product->relationLoaded('productUoms') && ($baseUomPu = $product->productUoms->firstWhere('is_base', true)) && (float) $baseUomPu->mrp > 0) {
                $mrp = (float) $baseUomPu->mrp;
            } elseif ((float) ($product->selling_price ?? 0) > 0) {
                $mrp = (float) $product->selling_price;
            }

            $productTypeName = $product->productType?->name ?: ($product->product_type ?: null);

            $validUoms = collect();
            if ($product->baseUom) {
                $validUoms->push([
                    'id' => (string) $product->baseUom->id,
                    'code' => $product->baseUom->code,
                    'name' => $product->baseUom->name,
                    'is_base' => true,
                    'label' => 'Base UOM',
                ]);
            }
            if ($product->alternateUom && (int) $product->alternate_uom_id !== (int) $product->base_uom_id) {
                $validUoms->push([
                    'id' => (string) $product->alternateUom->id,
                    'code' => $product->alternateUom->code,
                    'name' => $product->alternateUom->name,
                    'is_base' => false,
                    'label' => 'Alternate UOM',
                ]);
            }
            if ($product->relationLoaded('productUoms')) {
                foreach ($product->productUoms as $pu) {
                    if ($pu->uom && ! $validUoms->contains('id', (string) $pu->uom->id)) {
                        $validUoms->push([
                            'id' => (string) $pu->uom->id,
                            'code' => $pu->uom->code,
                            'name' => $pu->uom->name,
                            'is_base' => (bool) $pu->is_base,
                            'label' => $pu->label ?: ($pu->is_base ? 'Base UOM' : 'Unit'),
                        ]);
                    }
                }
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku ?: '',
                'part_number' => $product->sku ?: '',
                'model' => $product->model ?: '',
                'series' => $product->series ?: '',
                'serial_no' => $product->serial_no ?: '',
                'brand_id' => $product->brand_id ? (string) $product->brand_id : '',
                'brand_name' => $product->brand?->name ?: '',
                'category_id' => $product->category_id ? (string) $product->category_id : '',
                'category_name' => $product->category?->name ?: '',
                'sub_category_id' => $product->sub_category_id ? (string) $product->sub_category_id : '',
                'sub_category_name' => $product->subCategory?->name ?: '',
                'product_type_id' => $product->product_type_id ? (string) $product->product_type_id : '',
                'product_type' => $productTypeName ?: '',
                'product_type_name' => $productTypeName ?: '',
                'hsn_code' => $product->hsn_code ?: '',
                'tax_rate' => $product->tax_rate !== null ? (float) $product->tax_rate : 0.0,
                'gst_rate' => $product->tax_rate !== null ? (float) $product->tax_rate : 0.0,
                'mrp' => $mrp > 0 ? $mrp : null,
                'selling_price' => $product->selling_price !== null ? (float) $product->selling_price : null,
                'base_uom_id' => $product->base_uom_id ? (string) $product->base_uom_id : '',
                'base_uom_code' => $product->baseUom?->code ?: '',
                'base_uom_name' => $product->baseUom?->name ?: '',
                'uoms' => $validUoms->values()->all(),
            ];
        });

        return response()->json([
            'success' => true,
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
            ],
            'count' => $data->count(),
            'data' => $data->values()->all(),
        ]);
    }

    public function edit(Request $request, PriceMaster $priceMaster): View
    {
        return view('masters.price-masters.form', array_merge(['item' => $priceMaster], $this->formData($request->user() ?? auth()->user())));
    }

    public function update(Request $request, PriceMaster $priceMaster): RedirectResponse|JsonResponse
    {
        $user = $request->user() ?? auth()->user();

        // Enforce authorization
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('masters.manage') &&
            ! $user->can('masters.edit') &&
            ! $user->can('price-master.manage') &&
            ! $user->can('price-master.edit')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to edit price list records.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: You do not have permission to edit price list records.');
        }

        // Multi-tenancy check (company boundary)
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && $priceMaster->product?->company_id && (int) $priceMaster->product->company_id !== (int) $user->company_id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This price record belongs to another company.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: This price record belongs to another company.');
        }

        if (! $request->filled('type') && $priceMaster->type) {
            $request->merge(['type' => $priceMaster->type]);
        }
        if (! $request->filled('brand_id') && $priceMaster->brand_id) {
            $request->merge(['brand_id' => $priceMaster->brand_id]);
        }
        if (! $request->filled('customer_type_id') && $priceMaster->customer_type_id) {
            $request->merge(['customer_type_id' => $priceMaster->customer_type_id]);
        }
        if (! $request->filled('product_id') && $priceMaster->product_id) {
            $request->merge(['product_id' => $priceMaster->product_id]);
        }
        if (! $request->filled('uom_id') && $priceMaster->uom_id) {
            $request->merge(['uom_id' => $priceMaster->uom_id]);
        }

        $type = $request->input('type', $priceMaster->type ?? PriceMaster::TYPE_CUSTOMER_TYPE);
        if (! in_array($type, [PriceMaster::TYPE_CUSTOMER_TYPE, PriceMaster::TYPE_BRAND], true)) {
            $type = PriceMaster::TYPE_CUSTOMER_TYPE;
        }

        if ($type === PriceMaster::TYPE_BRAND) {
            $validated = $request->validate([
                'type' => 'nullable|in:customer_type,brand',
                'brand_id' => 'required|exists:brands,id',
                'name' => 'nullable|string|max:255',
                'product_id' => 'required|exists:products,id',
                'uom_id' => 'required|exists:uoms,id',
                'dealer_price' => 'nullable|numeric|min:0',
                'dealer_price_gst_mode' => 'nullable|in:inclusive,exclusive,Inclusive,Exclusive',
                'retail_price' => 'nullable|numeric|min:0',
                'retail_price_gst_mode' => 'nullable|in:inclusive,exclusive,Inclusive,Exclusive',
                'rate' => 'nullable|numeric|min:0',
                'rate_includes_gst' => 'nullable|boolean',
                'min_qty' => 'nullable|numeric|min:0',
            ]);
        } else {
            $validated = $request->validate([
                'type' => 'nullable|in:customer_type,brand',
                'customer_type_id' => 'required|exists:customer_types,id',
                'name' => 'nullable|string|max:255',
                'product_id' => 'required|exists:products,id',
                'uom_id' => 'required|exists:uoms,id',
                'dealer_price' => 'nullable|numeric|min:0',
                'dealer_price_gst_mode' => 'nullable|in:inclusive,exclusive,Inclusive,Exclusive',
                'retail_price' => 'nullable|numeric|min:0',
                'retail_price_gst_mode' => 'nullable|in:inclusive,exclusive,Inclusive,Exclusive',
                'rate' => 'nullable|numeric|min:0',
                'rate_includes_gst' => 'nullable|boolean',
                'min_qty' => 'nullable|numeric|min:0',
            ]);
        }

        $hasDealer = filled($validated['dealer_price'] ?? null);
        $hasRetail = filled($validated['retail_price'] ?? null);
        $hasRate = filled($validated['rate'] ?? null);
        if (! $hasDealer && ! $hasRetail && ! $hasRate) {
            throw ValidationException::withMessages([
                'dealer_price' => 'Please enter Dealer Price or Retail Price.',
            ]);
        }

        $product = Product::query()->findOrFail($validated['product_id']);

        if ($type === PriceMaster::TYPE_BRAND && (int) $product->brand_id !== (int) $validated['brand_id']) {
            throw ValidationException::withMessages([
                'product_id' => "Selected product ({$product->name}) does not belong to the selected brand.",
            ]);
        }

        // Check for duplicates excluding this record
        $minQty = filled($validated['min_qty'] ?? null) ? $validated['min_qty'] : null;
        $existsQuery = PriceMaster::query()
            ->where('id', '!=', $priceMaster->id)
            ->where('product_id', $validated['product_id'])
            ->where('uom_id', $validated['uom_id'])
            ->when($minQty !== null, fn ($q) => $q->where('min_qty', $minQty), fn ($q) => $q->whereNull('min_qty'));

        if ($type === PriceMaster::TYPE_BRAND) {
            $existsQuery->where('type', PriceMaster::TYPE_BRAND)->where('brand_id', $validated['brand_id']);
        } else {
            $existsQuery->customerTypeWise()->where('customer_type_id', $validated['customer_type_id']);
        }

        if ($existsQuery->exists()) {
            $targetLabel = $type === PriceMaster::TYPE_BRAND ? 'brand' : 'customer type';
            throw ValidationException::withMessages([
                'product_id' => "A price already exists for this {$targetLabel}, product, UOM, and min qty.",
            ]);
        }

        try {
            $priceMaster->update(array_merge(
                [
                    'type' => $type,
                    'name' => filled($validated['name'] ?? null) ? $validated['name'] : null,
                    'customer_type_id' => $type === PriceMaster::TYPE_CUSTOMER_TYPE ? $validated['customer_type_id'] : null,
                    'brand_id' => $type === PriceMaster::TYPE_BRAND ? $validated['brand_id'] : null,
                    'product_id' => $validated['product_id'],
                    'uom_id' => $validated['uom_id'],
                    'min_qty' => $minQty,
                ],
                $this->pricedAttributes($validated, (float) ($product->tax_rate ?? 0))
            ));
        } catch (QueryException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                throw ValidationException::withMessages([
                    'product_id' => 'A price already exists for this ' . ($type === PriceMaster::TYPE_BRAND ? 'brand' : 'customer type') . ', product, UOM, and min qty.',
                ]);
            }
            throw $e;
        }

        $successMsg = 'Price updated successfully.';

        if ($request->wantsJson() || $request->ajax()) {
            $priceMaster->refresh()->loadMissing(['customerType', 'brand', 'product', 'uom']);

            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'data' => $priceMaster,
            ]);
        }

        return $this->flashSuccess($successMsg, 'masters.price-masters.index')
            ->with('success', $successMsg);
    }

    public function destroy(Request $request, PriceMaster $priceMaster): RedirectResponse|JsonResponse
    {
        $user = $request->user() ?? auth()->user();

        // Enforce authorization
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('masters.manage') &&
            ! $user->can('masters.edit') &&
            ! $user->can('masters.delete') &&
            ! $user->can('price-master.manage') &&
            ! $user->can('price-master.edit') &&
            ! $user->can('price-master.delete')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to delete price list records.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: You do not have permission to delete price list records.');
        }

        $priceMaster->loadMissing(['customerType', 'brand', 'product', 'uom']);

        // Multi-tenancy check (company boundary)
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && $priceMaster->product?->company_id && (int) $priceMaster->product->company_id !== (int) $user->company_id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This price record belongs to another company.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: This price record belongs to another company.');
        }

        $productName = $priceMaster->product?->name ?? 'Product';
        $targetName = $priceMaster->isBrandWise()
            ? ($priceMaster->brand?->name ?? 'Brand')
            : ($priceMaster->customerType?->name ?? 'Customer Type');
        $rateFormatted = '₹' . number_format((float) $priceMaster->rate, 2);

        try {
            DB::transaction(function () use ($priceMaster) {
                $priceMaster->delete();
            });

            $message = "Price record for {$productName} ({$targetName} - {$rateFormatted}) deleted successfully.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return $this->flashSuccess($message, 'masters.price-masters.index')
                ->with('success', $message);
        } catch (\Throwable $e) {
            report($e);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete price record: ' . $e->getMessage(),
                ], 500);
            }

            return $this->flashError('Failed to delete price record: ' . $e->getMessage(), 'masters.price-masters.index')
                ->with('error', 'Failed to delete price record: ' . $e->getMessage());
        }
    }

    /**
     * Compute dealer, retail, and general pricing attributes.
     * Base rates stored for billing remain exclusive of GST.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function pricedAttributes(array $input, float $taxPercent): array
    {
        $taxPercent = max(0, $taxPercent);

        // 1. Dealer Pricing
        $hasDealer = filled($input['dealer_price'] ?? null);
        $dealerGstMode = ($input['dealer_price_gst_mode'] ?? null) === 'inclusive' ? 'inclusive' : 'exclusive';
        $dealerEntered = $hasDealer ? (float) $input['dealer_price'] : null;
        $dealerSplit = PriceMaster::calculateTaxBreakdown($dealerEntered, $dealerGstMode, $taxPercent);

        // 2. Retail Pricing
        $hasRetail = filled($input['retail_price'] ?? null);
        $retailGstMode = ($input['retail_price_gst_mode'] ?? null) === 'inclusive' ? 'inclusive' : 'exclusive';
        $retailEntered = $hasRetail ? (float) $input['retail_price'] : null;
        $retailSplit = PriceMaster::calculateTaxBreakdown($retailEntered, $retailGstMode, $taxPercent);

        // 3. General / Legacy Rate
        if (filled($input['rate'] ?? null)) {
            $legacyIncludesGst = filter_var($input['rate_includes_gst'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $legacySplit = PriceMaster::splitRate((float) $input['rate'], $legacyIncludesGst, $taxPercent);
            $rate = $legacySplit['exclusive'];
            $enteredRate = $legacySplit['entered'];
            $rateIncludesGst = $legacyIncludesGst;
        } else {
            $rate = $dealerSplit['exclusive'] ?? $retailSplit['exclusive'] ?? 0.0;
            $enteredRate = $dealerSplit['entered'] ?? $retailSplit['entered'] ?? $rate;
            $rateIncludesGst = ($dealerSplit['gst_mode'] ?? $retailSplit['gst_mode'] ?? 'exclusive') === 'inclusive';
        }

        // Default dealer price to rate if not specifically provided
        if ($dealerSplit['exclusive'] === null && filled($input['rate'] ?? null)) {
            $dealerSplit['exclusive'] = $rate;
            $dealerSplit['entered'] = $enteredRate;
            $dealerGstMode = $rateIncludesGst ? 'inclusive' : 'exclusive';
        }

        return [
            'dealer_price' => $dealerSplit['exclusive'],
            'dealer_price_entered' => $dealerSplit['entered'],
            'dealer_price_gst_mode' => $dealerGstMode,
            'retail_price' => $retailSplit['exclusive'],
            'retail_price_entered' => $retailSplit['entered'],
            'retail_price_gst_mode' => $retailGstMode,
            'rate' => $rate,
            'entered_rate' => $enteredRate,
            'rate_includes_gst' => $rateIncludesGst,
        ];
    }

    protected function formData(?User $user = null): array
    {
        $user = $user ?? auth()->user();

        return [
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::where('is_active', true)
                ->with([
                    'brand:id,name',
                    'category:id,name',
                    'subCategory:id,name',
                    'productType:id,name',
                    'baseUom:id,name,code',
                    'alternateUom:id,name,code',
                    'productUoms.uom:id,name,code',
                ])
                ->orderBy('name')
                ->get([
                    'id',
                    'company_id',
                    'name',
                    'sku',
                    'model',
                    'series',
                    'brand_id',
                    'category_id',
                    'sub_category_id',
                    'product_type_id',
                    'product_type',
                    'base_uom_id',
                    'alternate_uom_id',
                    'hsn_code',
                    'tax_rate',
                    'calculation_mrp',
                    'selling_price',
                ]),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    public function copyOptions(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth()->user();

        $customerTypesQuery = CustomerType::where('is_active', true);
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id) {
            $customerTypesQuery->where(function ($q) use ($user) {
                $q->whereNull('company_id')->orWhere('company_id', $user->company_id);
            });
        }
        $customerTypes = $customerTypesQuery->whereHas('priceMasters', function ($q) {
            $q->customerTypeWise();
        })->withCount(['priceMasters' => function ($q) {
            $q->customerTypeWise();
        }])
        ->orderBy('name')
        ->get(['id', 'name'])
        ->map(fn ($ct) => [
            'id' => $ct->id,
            'name' => $ct->name,
            'count' => $ct->price_masters_count,
            'label' => "{$ct->name} ({$ct->price_masters_count} products configured)",
        ]);

        $brandsQuery = Brand::where('is_active', true);
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id) {
            $brandsQuery->where(function ($q) use ($user) {
                $q->whereNull('company_id')->orWhere('company_id', $user->company_id);
            });
        }
        $brands = $brandsQuery->whereHas('priceMasters', function ($q) {
            $q->brandWise();
        })->withCount(['priceMasters' => function ($q) {
            $q->brandWise();
        }])
        ->orderBy('name')
        ->get(['id', 'name'])
        ->map(fn ($b) => [
            'id' => $b->id,
            'name' => $b->name,
            'count' => $b->price_masters_count,
            'label' => "{$b->name} ({$b->price_masters_count} products configured)",
        ]);

        return response()->json([
            'success' => true,
            'customer_types' => $customerTypes,
            'brands' => $brands,
        ]);
    }

    public function copySource(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth()->user();
        $type = $request->input('type');
        $sourceId = $request->input('source_id');

        if (! in_array($type, [PriceMaster::TYPE_CUSTOMER_TYPE, PriceMaster::TYPE_BRAND], true) || ! $sourceId) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid source type and ID.',
            ], 422);
        }

        $query = PriceMaster::query()
            ->with([
                'product.brand:id,name',
                'product.category:id,name',
                'product.subCategory:id,name',
                'product.productType:id,name',
                'product.baseUom:id,name,code',
                'product.productUoms.uom:id,name,code',
                'uom:id,name,code',
            ]);

        if ($type === PriceMaster::TYPE_BRAND) {
            $query->brandWise()->where('brand_id', $sourceId);
        } else {
            $query->customerTypeWise()->where('customer_type_id', $sourceId);
        }

        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id) {
            $query->whereHas('product', function ($pq) use ($user) {
                $pq->whereNull('company_id')->orWhere('company_id', $user->company_id);
            });
        }

        $items = $query->orderBy('id')->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No price records found for the selected source.',
                'items' => [],
            ], 404);
        }

        $data = $items->map(function ($item) {
            $product = $item->product;
            $mrp = 0.0;
            if ($product) {
                if ((float) $product->calculation_mrp > 0) {
                    $mrp = (float) $product->calculation_mrp;
                } elseif ($product->relationLoaded('productUoms') && ($basePu = $product->productUoms->firstWhere('is_base', true)) && (float) $basePu->mrp > 0) {
                    $mrp = (float) $basePu->mrp;
                } elseif ((float) ($product->selling_price ?? 0) > 0) {
                    $mrp = (float) $product->selling_price;
                }
            }

            $uomCode = $item->uom?->code ?: ($item->uom?->name ?: ($product?->baseUom?->code ?: '—'));
            $uomName = $item->uom?->name ?: ($product?->baseUom?->name ?: '');

            return [
                'price_master_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $product?->name ?? 'Product #' . $item->product_id,
                'sku' => $product?->sku ?? '',
                'part_number' => $product?->sku ?? '',
                'brand_id' => $product?->brand_id,
                'brand_name' => $product?->brand?->name ?? '—',
                'category_name' => $product?->category?->name ?? '—',
                'sub_category_name' => $product?->subCategory?->name ?? '—',
                'product_type_name' => $product?->productType?->name ?? ($product?->product_type ?? '—'),
                'hsn_code' => $product?->hsn_code ?? '—',
                'tax_rate' => $product?->tax_rate !== null ? (float) $product->tax_rate : 0.0,
                'gst_rate' => $product?->tax_rate !== null ? (float) $product->tax_rate : 0.0,
                'mrp' => $mrp > 0 ? $mrp : null,
                'uom_id' => $item->uom_id ?? $product?->base_uom_id,
                'uom_code' => $uomCode,
                'uom_name' => $uomName,
                'min_qty' => $item->min_qty,
                'rate' => $item->entered_rate !== null ? (float) $item->entered_rate : ($item->rate !== null ? (float) $item->rate : null),
                'rate_includes_gst' => (bool) $item->rate_includes_gst,
                'dealer_price' => $item->dealer_price_entered !== null ? (float) $item->dealer_price_entered : ($item->dealer_price !== null ? (float) $item->dealer_price : null),
                'dealer_price_gst_mode' => strtolower($item->dealer_price_gst_mode ?: 'exclusive'),
                'retail_price' => $item->retail_price_entered !== null ? (float) $item->retail_price_entered : ($item->retail_price !== null ? (float) $item->retail_price : null),
                'retail_price_gst_mode' => strtolower($item->retail_price_gst_mode ?: 'exclusive'),
            ];
        });

        return response()->json([
            'success' => true,
            'source_type' => $type,
            'source_id' => $sourceId,
            'count' => $data->count(),
            'items' => $data->values()->all(),
        ]);
    }

    protected function isUniqueConstraintViolation(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'price_master_unique')
            || str_contains($message, 'price_master_brand_unique')
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'UNIQUE constraint failed')
            || (string) $e->getCode() === '23000';
    }
}
