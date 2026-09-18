<?php

namespace App\Http\Controllers\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Small JSON endpoint used by "Quick add product" modals in the purchase / sales
 * flows so users don't need to leave the document they're building.
 * Returns the created product summary that the calling screen appends to its list.
 */
class QuickAddProductController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'hsn_code' => 'nullable|string|max:20',
            'base_uom_id' => 'required|exists:uoms,id',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'calculation_mrp' => 'nullable|numeric|min:0',
            'color_variant' => 'nullable|string|max:60',
        ]);

        $product = DB::transaction(function () use ($data) {
            $companyId = auth()->user()?->company_id ?? Company::query()->value('id');

            $product = Product::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'sku' => $data['sku'] ?? CodeGenerator::forProduct($companyId),
                'serial_no' => CodeGenerator::forProductSerial(),
                'hsn_code' => $data['hsn_code'] ?? null,
                'base_uom_id' => (int) $data['base_uom_id'],
                'brand_id' => $data['brand_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'tax_rate' => (float) ($data['tax_rate'] ?? 0),
                'purchase_price' => (float) ($data['purchase_price'] ?? 0),
                'selling_price' => (float) ($data['selling_price'] ?? 0),
                'trade_price' => (float) ($data['selling_price'] ?? 0),
                'calculation_mrp' => (float) ($data['calculation_mrp'] ?? $data['selling_price'] ?? 0),
                'color_variant' => $data['color_variant'] ?? null,
                'tracking_type' => 'none',
                'is_active' => true,
                'discount_type' => 'percent',
                'discount_value' => 0,
                'selling_discount_type' => 'percent',
                'selling_discount_value' => 0,
            ]);

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
                ]
            );

            return $product;
        });

        return response()->json([
            'ok' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'serial_no' => $product->serial_no,
                'base_uom_id' => $product->base_uom_id,
                'tax_rate' => (float) $product->tax_rate,
                'purchase_price' => (float) $product->purchase_price,
                'selling_price' => (float) $product->selling_price,
                'calculation_mrp' => (float) $product->calculation_mrp,
                'color_variant' => $product->color_variant,
            ],
        ]);
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }
}
