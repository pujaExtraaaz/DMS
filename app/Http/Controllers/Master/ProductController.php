<?php

namespace App\Http\Controllers\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductPriceHistory;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\CodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->input('sort', 'created_at');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        $allowedSorts = [
            'serial_no',
            'name',
            'sku',
            'created_at',
        ];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $items = Product::query()
            ->with(['baseUom', 'brand', 'category'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->input('search'));

                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%')
                        ->orWhere('serial_no', 'like', '%'.$search.'%');
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return view('masters.products.index', compact('items'));
    }

    public function create(): View
    {
        return view('masters.products.form', $this->formData(new Product));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);
            $product->update([
                'serial_no' => CodeGenerator::forProductSerial(),
            ]);
            $this->syncBaseUom($product);
            $this->syncAlternateUoms($request, $product);
        });

        return $this->flashSuccess('Product created successfully.', 'masters.products.index');
    }

    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('masters.products.edit', $product);
    }

    public function edit(Product $product): View
    {
        $product->load(['productUoms.uom', 'priceHistories.uom']);
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

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return $this->flashSuccess('Product deleted successfully.', 'masters.products.index');
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
            'priceHistory' => $item->exists
                ? ProductPriceHistory::query()->where('product_id', $item->id)->orderByDesc('effective_from')->limit(30)->with('uom', 'creator')->get()
                : collect(),
        ];
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:products,sku'.($product ? ','.$product->id : ''),
            'hsn_code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'specification' => 'nullable|string',
            'base_uom_id' => 'required|exists:uoms,id',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'selling_price' => 'nullable|numeric|min:0',
            'trade_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'calculation_mrp' => 'nullable|numeric|min:0',
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
            'color_variant' => 'nullable|string|max:60',
            'catalog_link' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:4096',
            'remove_image' => 'nullable|boolean',
            'tracking_type' => 'nullable|in:none,serial,batch',
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
            'product_uoms.*.conversion_factor' => 'nullable|numeric|min:0',
            'product_uoms.*.selling_price' => 'nullable|numeric|min:0',
            'product_uoms.*.trade_price' => 'nullable|numeric|min:0',
            'product_uoms.*.purchase_price' => 'nullable|numeric|min:0',
            'product_uoms.*.mrp' => 'nullable|numeric|min:0',
            'product_uoms.*.is_default_sales' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['apply_discount_on_payable'] = $request->boolean('apply_discount_on_payable');
        $data['tax_rate'] = $data['tax_rate'] ?? 0;
        $data['selling_price'] = $data['selling_price'] ?? 0;
        $data['trade_price'] = $data['trade_price'] ?? 0;
        $data['purchase_price'] = $data['purchase_price'] ?? 0;
        $data['calculation_mrp'] = $data['calculation_mrp'] ?? 0;
        $data['discount_percent'] = $data['discount_percent'] ?? 0;
        $data['discount_type'] = $data['discount_type'] ?? 'percent';
        $data['discount_value'] = $data['discount_value'] ?? 0;
        $data['selling_discount_type'] = $data['selling_discount_type'] ?? 'percent';
        $data['selling_discount_value'] = $data['selling_discount_value'] ?? 0;
        $data['tracking_type'] = $data['tracking_type'] ?? 'none';
        $data['min_stock'] = $data['min_stock'] ?? 0;
        $data['reorder_level'] = $data['reorder_level'] ?? 0;
        $data['company_id'] = auth()->user()?->company_id ?? Company::query()->value('id');

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

    /**
     * Ensure the product has a `product_uoms` row for its base UOM, mirroring master pricing.
     */
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

    /**
     * Replace all non-base product_uoms with what came from the form.
     * Rows without a uom_id are ignored.
     */
    protected function syncAlternateUoms(Request $request, Product $product): void
    {
        $rows = collect($request->input('product_uoms', []))
            ->filter(fn ($r) => filled($r['uom_id'] ?? null) && (int) $r['uom_id'] !== (int) $product->base_uom_id)
            ->values();

        // Wipe existing non-base rows, then re-insert. Safe because we always keep the base row via syncBaseUom().
        ProductUom::query()
            ->where('product_id', $product->id)
            ->where('is_base', false)
            ->delete();

        foreach ($rows as $r) {
            ProductUom::create([
                'product_id' => $product->id,
                'uom_id' => (int) $r['uom_id'],
                'conversion_factor' => (float) ($r['conversion_factor'] ?? 1),
                'is_base' => false,
                'label' => $r['label'] ?? null,
                'selling_price' => $r['selling_price'] ?? null,
                'trade_price' => $r['trade_price'] ?? null,
                'purchase_price' => $r['purchase_price'] ?? null,
                'mrp' => $r['mrp'] ?? null,
                'is_default_sales' => (bool) ($r['is_default_sales'] ?? false),
                'is_active' => true,
            ]);
        }
    }
}
