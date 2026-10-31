<?php

namespace App\Domains\Master\Imports;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Support\CodeGenerator;
use App\Support\WarrantyPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk import Products from CSV/XLSX.
 *
 * Template columns (case-insensitive):
 *   name, part_number, model, product_type, brand, category, sub_category,
 *   uom_code, alternate_uom_code, conversion_factor, supply_type, hsn_code, tax_rate,
 *   track_serial, track_batch,
 *   height_cm, length_cm, width_cm, net_weight, weight_unit,
 *   purchase_price, trade_price, selling_price, mrp, purchase_price_tax_basis,
 *   costing_method, market_valuation_method,
 *   min_stock, reorder_level, specification, warranty, catalog_link, is_active
 *
 * Aliases: sku / part_no / partnumber → part_number; uom → uom_code.
 * Match on SKU (part_number). Missing UOM code → skip. Brand / category /
 * sub_category / product_type auto-created by name when missing.
 */
class ProductsImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function __construct(protected ?int $companyId = null) {}

    public function collection(Collection $rows): void
    {
        $uomsByCode = Uom::query()->get()->keyBy(fn ($u) => strtoupper((string) $u->code));
        $brands = Brand::query()->get()->keyBy(fn ($b) => strtolower((string) $b->name));
        $categories = Category::query()->get()->keyBy(
            fn ($c) => strtolower(($c->brand_id ?? '0').'|'.$c->name)
        );
        $subCategories = SubCategory::query()->get()->keyBy(
            fn ($s) => strtolower(($s->category_id ?? '0').'|'.$s->name)
        );
        $productTypes = ProductType::query()
            ->when($this->companyId, fn ($q) => $q->where('company_id', $this->companyId))
            ->get()
            ->keyBy(fn ($t) => strtolower((string) $t->name));
        $companyId = $this->companyId ?? Company::query()->value('id');

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = collect($row)->mapWithKeys(fn ($v, $k) => [strtolower((string) $k) => $v])->toArray();

            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                $this->skipped++;
                $this->errors[] = ['row' => $rowNo, 'field' => 'name', 'message' => 'Name is required'];
                continue;
            }
            $name = \App\Support\NameCase::format($name);

            $uomCode = strtoupper(trim((string) ($data['uom_code'] ?? $data['uom'] ?? '')));
            $uom = $uomsByCode[$uomCode] ?? null;
            if (! $uom) {
                $this->skipped++;
                $this->errors[] = ['row' => $rowNo, 'field' => 'uom_code', 'message' => "Unknown UOM code '{$uomCode}'"];
                continue;
            }

            $sku = trim((string) ($data['part_number'] ?? $data['part_no'] ?? $data['partnumber'] ?? $data['sku'] ?? ''));
            if ($sku === '') {
                $sku = CodeGenerator::forProduct($companyId);
            }

            $brand = null;
            if (filled($data['brand'] ?? null)) {
                $brandName = trim((string) $data['brand']);
                $brandKey = strtolower($brandName);
                $brand = $brands[$brandKey] ?? Brand::create([
                    'company_id' => $companyId,
                    'name' => $brandName,
                    'is_active' => true,
                ]);
                $brands->put(strtolower($brand->name), $brand);
            }

            $category = null;
            if (filled($data['category'] ?? null)) {
                $categoryName = trim((string) $data['category']);
                $catKey = strtolower(($brand?->id ?? '0').'|'.$categoryName);
                $category = $categories[$catKey] ?? null;
                if (! $category) {
                    // Fall back to same name under any brand, then create under current brand.
                    $category = Category::query()
                        ->when($brand, fn ($q) => $q->where('brand_id', $brand->id))
                        ->whereRaw('LOWER(name) = ?', [strtolower($categoryName)])
                        ->first();
                }
                if (! $category) {
                    $category = Category::create([
                        'brand_id' => $brand?->id,
                        'name' => $categoryName,
                        'is_active' => true,
                    ]);
                }
                $categories->put(strtolower(($category->brand_id ?? '0').'|'.$category->name), $category);
            }

            $subCategory = null;
            if (filled($data['sub_category'] ?? null) && $category) {
                $subName = trim((string) $data['sub_category']);
                $subKey = strtolower($category->id.'|'.$subName);
                $subCategory = $subCategories[$subKey] ?? SubCategory::firstOrCreate(
                    ['category_id' => $category->id, 'name' => $subName],
                    ['is_active' => true]
                );
                $subCategories->put(strtolower($subCategory->category_id.'|'.$subCategory->name), $subCategory);
            }

            $productType = null;
            if (filled($data['product_type'] ?? null)) {
                $typeName = trim((string) $data['product_type']);
                $typeKey = strtolower($typeName);
                $productType = $productTypes[$typeKey] ?? ProductType::firstOrCreate(
                    ['company_id' => $companyId, 'name' => $typeName],
                    [
                        'code' => strtoupper(Str::slug($typeName, '_')),
                        'is_active' => true,
                        'track_serial' => false,
                    ]
                );
                $productTypes->put(strtolower($productType->name), $productType);
            }

            $altUom = null;
            $altCode = strtoupper(trim((string) ($data['alternate_uom_code'] ?? '')));
            if ($altCode !== '') {
                $altUom = $uomsByCode[$altCode] ?? null;
                if (! $altUom) {
                    $this->skipped++;
                    $this->errors[] = ['row' => $rowNo, 'field' => 'alternate_uom_code', 'message' => "Unknown alternate UOM code '{$altCode}'"];
                    continue;
                }
            }

            $conversion = filled($data['conversion_factor'] ?? null)
                ? (float) $data['conversion_factor']
                : ($altUom ? 1.0 : 1.0);

            $tracks = [];
            if ($this->isTruthy($data['track_serial'] ?? null)) {
                $tracks[] = 'serial';
            }
            if ($this->isTruthy($data['track_batch'] ?? null)) {
                $tracks[] = 'batch';
            }
            if ($productType?->track_serial && ! in_array('serial', $tracks, true)) {
                $tracks[] = 'serial';
            }
            $trackingType = empty($tracks) ? 'none' : implode(',', $tracks);

            $warrantyText = trim((string) ($data['warranty'] ?? '')) ?: null;
            $warrantyMonths = $warrantyText !== null ? WarrantyPeriod::months($warrantyText) : null;

            $supplyType = strtolower(trim((string) ($data['supply_type'] ?? 'goods')));
            if (! in_array($supplyType, ['capital_goods', 'goods', 'services'], true)) {
                $supplyType = 'goods';
            }

            $taxBasis = strtolower(trim((string) ($data['purchase_price_tax_basis'] ?? 'exclusive')));
            if (! in_array($taxBasis, ['exclusive', 'inclusive'], true)) {
                $taxBasis = 'exclusive';
            }

            $costing = strtolower(trim((string) ($data['costing_method'] ?? 'avg_cost')));
            if (! array_key_exists($costing, Product::COSTING_METHODS)) {
                $costing = 'avg_cost';
            }

            $market = strtolower(trim((string) ($data['market_valuation_method'] ?? 'avg_price')));
            if (! array_key_exists($market, Product::MARKET_VALUATION_METHODS)) {
                $market = 'avg_price';
            }

            $weightUnit = strtoupper(trim((string) ($data['weight_unit'] ?? 'KG')));
            if (! in_array($weightUnit, ['KG', 'GRAMS'], true)) {
                $weightUnit = 'KG';
            }

            $isActive = array_key_exists('is_active', $data) && $data['is_active'] !== null && $data['is_active'] !== ''
                ? $this->isTruthy($data['is_active'])
                : true;

            $existing = Product::query()->whereRaw('LOWER(sku) = ?', [strtolower($sku)])->first();

            $payload = [
                'company_id' => $companyId,
                'name' => $name,
                'sku' => $sku,
                'model' => trim((string) ($data['model'] ?? '')) ?: null,
                'product_type_id' => $productType?->id,
                'product_type' => $productType?->name,
                'hsn_code' => trim((string) ($data['hsn_code'] ?? '')) ?: null,
                'base_uom_id' => $uom->id,
                'alternate_uom_id' => $altUom?->id,
                'conversion_factor' => $altUom ? max(0.0001, $conversion) : 1,
                'brand_id' => $brand?->id,
                'category_id' => $category?->id,
                'sub_category_id' => $subCategory?->id,
                'supply_type' => $supplyType,
                'tax_rate' => (float) ($data['tax_rate'] ?? 0),
                'purchase_price' => (float) ($data['purchase_price'] ?? 0),
                'selling_price' => (float) ($data['selling_price'] ?? 0),
                'trade_price' => (float) ($data['trade_price'] ?? $data['selling_price'] ?? 0),
                'calculation_mrp' => (float) ($data['mrp'] ?? 0),
                'purchase_price_tax_basis' => $taxBasis,
                'costing_method' => $costing,
                'market_valuation_method' => $market,
                'height_cm' => filled($data['height_cm'] ?? null) ? (float) $data['height_cm'] : null,
                'length_cm' => filled($data['length_cm'] ?? null) ? (float) $data['length_cm'] : null,
                'width_cm' => filled($data['width_cm'] ?? null) ? (float) $data['width_cm'] : null,
                'net_weight' => filled($data['net_weight'] ?? null) ? (float) $data['net_weight'] : null,
                'weight_unit' => filled($data['net_weight'] ?? null) || filled($data['weight_unit'] ?? null) ? $weightUnit : null,
                'min_stock' => filled($data['min_stock'] ?? null) ? (float) $data['min_stock'] : 0,
                'reorder_level' => filled($data['reorder_level'] ?? null) ? (float) $data['reorder_level'] : 0,
                'specification' => trim((string) ($data['specification'] ?? '')) ?: null,
                'warranty' => $warrantyText,
                'warranty_months' => $warrantyMonths,
                'catalog_link' => trim((string) ($data['catalog_link'] ?? '')) ?: null,
                'series' => trim((string) ($data['series'] ?? '')) ?: null,
                'color_variant' => trim((string) ($data['color_variant'] ?? '')) ?: null,
                'sender_warranty_months' => filled($data['sender_warranty_months'] ?? null) ? (int) $data['sender_warranty_months'] : null,
                'customer_warranty_months' => filled($data['customer_warranty_months'] ?? null) ? (int) $data['customer_warranty_months'] : null,
                'discount_percent' => (float) ($data['discount_percent'] ?? 0),
                'discount_type' => 'percent',
                'discount_value' => (float) ($data['discount_percent'] ?? 0),
                'selling_discount_type' => 'percent',
                'selling_discount_value' => 0,
                'tracking_type' => $trackingType,
                'is_active' => $isActive,
            ];

            if ($existing) {
                $existing->update($payload);
                $product = $existing;
                $this->updated++;
            } else {
                $payload['serial_no'] = CodeGenerator::forProductSerial();
                $product = Product::create($payload);
                $this->created++;
            }

            ProductUom::updateOrCreate(
                ['product_id' => $product->id, 'uom_id' => $uom->id],
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

            if ($altUom) {
                ProductUom::updateOrCreate(
                    ['product_id' => $product->id, 'uom_id' => $altUom->id],
                    [
                        'conversion_factor' => max(0.0001, $conversion),
                        'is_base' => false,
                        'label' => 'Alternate',
                        'selling_price' => (float) ($data['alt_selling_price'] ?? $product->selling_price),
                        'trade_price' => (float) ($data['alt_trade_price'] ?? $product->trade_price),
                        'purchase_price' => (float) ($data['alt_purchase_price'] ?? $product->purchase_price),
                        'mrp' => (float) ($data['alt_mrp'] ?? $product->calculation_mrp),
                        'is_default_sales' => false,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    protected function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true);
    }
}
