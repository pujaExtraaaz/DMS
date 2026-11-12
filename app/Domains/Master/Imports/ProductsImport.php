<?php

namespace App\Domains\Master\Imports;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Models\UomMapping;
use App\Domains\Master\Services\UomLookupService;
use App\Domains\Organization\Models\Company;
use App\Support\CodeGenerator;
use App\Support\NameCase;
use App\Support\WarrantyPeriod;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
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
 * Match on part number. An existing product is updated only when a value
 * changed; an identical row is skipped. Missing UOM code → failed with clear error.
 * Brand, category, sub-category and product type are linked only when they
 * already exist. Unknown names are left blank and are not created.
 */
class ProductsImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $failed = 0;

    public array $missingUoms = [];

    public function __construct(protected ?int $companyId = null) {}

    public function collection(Collection $rows): void
    {
        $uoms = Uom::query()->get();
        $mappings = UomMapping::with('uom')->get();
        $brands = Brand::query()->get();
        $categories = Category::query()->get();
        $subCategories = SubCategory::query()->get();
        $productTypes = ProductType::query()->get();
        $companyId = $this->companyId ?? Company::query()->value('id');

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = collect($row)->mapWithKeys(fn ($v, $k) => [strtolower((string) $k) => $v])->toArray();

            $sku = trim((string) ($data['part_number'] ?? $data['part_no'] ?? $data['partnumber'] ?? $data['sku'] ?? ''));
            $rawUom = trim((string) ($data['uom_code'] ?? $data['uom'] ?? ''));

            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                $this->recordFailure($rowNo, 'name', 'Name is required', $sku ?: 'N/A', $rawUom ?: 'N/A');
                continue;
            }
            $name = \App\Support\NameCase::format($name);

            if ($rawUom === '') {
                $this->recordMissingUom($rowNo, '', 'uom_code', $sku ?: $name);
                continue;
            }

            $uom = UomLookupService::resolve($rawUom, $uoms, $mappings);
            if (! $uom) {
                $this->recordMissingUom($rowNo, $rawUom, 'uom_code', $sku ?: $name);
                continue;
            }

            if ($sku === '') {
                $sku = CodeGenerator::forProduct($companyId);
            }

            $brand = $this->matchBrand($brands, (string) ($data['brand'] ?? ''));
            $category = $this->matchCategory($categories, $brand, (string) ($data['category'] ?? ''));
            $subCategory = $this->matchSubCategory($subCategories, $category, (string) ($data['sub_category'] ?? ''));
            $productType = $this->matchProductType($productTypes, (string) ($data['product_type'] ?? ''));

            $altUom = null;
            $rawAltUom = trim((string) ($data['alternate_uom_code'] ?? ''));
            if ($rawAltUom !== '') {
                $altUom = UomLookupService::resolve($rawAltUom, $uoms, $mappings);
                if (! $altUom) {
                    $this->recordMissingUom($rowNo, $rawAltUom, 'alternate_uom_code', $sku);
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
                'alt_purchase_price' => filled($data['alt_purchase_price'] ?? null) ? (float) $data['alt_purchase_price'] : null,
                'alt_trade_price' => filled($data['alt_trade_price'] ?? null) ? (float) $data['alt_trade_price'] : null,
                'alt_selling_price' => filled($data['alt_selling_price'] ?? null) ? (float) $data['alt_selling_price'] : null,
                'alt_calculation_mrp' => filled($data['alt_mrp'] ?? null) ? (float) $data['alt_mrp'] : null,
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

            try {
                if ($existing) {
                    if (! $this->payloadChanged($existing, $payload)) {
                        $this->skipped++;

                        continue;
                    }
                    $existing->update($payload);
                    $product = $existing;
                    $this->updated++;
                } else {
                    $payload['serial_no'] = CodeGenerator::forProductSerial();
                    $product = Product::create($payload);
                    $this->created++;
                }
            } catch (QueryException $e) {
                $this->recordFailure(
                    $rowNo,
                    'part_number',
                    'Could not save this product because it conflicts with an existing record.',
                    $sku,
                    $rawUom
                );

                continue;
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

    public function getMissingUoms(): array
    {
        return $this->missingUoms;
    }

    protected function recordFailure(int $rowNo, string $field, string $message, ?string $identifier = null, ?string $importedUom = null): void
    {
        $this->failed++;
        $this->errors[] = [
            'row' => $rowNo,
            'product_identifier' => $identifier ?: 'N/A',
            'imported_uom' => $importedUom ?: 'N/A',
            'field' => $field,
            'message' => $message,
        ];
    }

    protected function recordMissingUom(int $rowNo, string $rawUom, string $field, ?string $identifier = null): void
    {
        $rawTrimmed = trim($rawUom);
        if ($rawTrimmed !== '') {
            if (! isset($this->missingUoms[$rawTrimmed])) {
                $this->missingUoms[$rawTrimmed] = [
                    'imported_uom' => $rawTrimmed,
                    'count' => 0,
                    'sample_rows' => [],
                    'suggested_code' => UomLookupService::suggestCode($rawTrimmed),
                    'suggested_name' => UomLookupService::suggestName($rawTrimmed),
                ];
            }
            $this->missingUoms[$rawTrimmed]['count']++;
            if (count($this->missingUoms[$rawTrimmed]['sample_rows']) < 5) {
                $this->missingUoms[$rawTrimmed]['sample_rows'][] = $rowNo;
            }
        }

        $message = $rawTrimmed === ''
            ? ($field === 'alternate_uom_code' ? 'Alternate UOM code is invalid' : 'UOM is required')
            : "Unknown UOM '{$rawTrimmed}'. Please map this UOM to an existing record or add it to UOM Master.";

        $this->recordFailure($rowNo, $field, $message, $identifier, $rawTrimmed ?: 'BLANK');
    }

    protected function matchBrand(Collection $brands, string $label): ?Brand
    {
        $label = trim($label);
        if ($label === '') {
            return null;
        }

        $found = $brands->first(fn (Brand $brand) => $this->namesEqual($this->rawName($brand), $label));
        if ($found) {
            return $found;
        }

        $found = Brand::query()->where('name', NameCase::format($label))->first()
            ?? Brand::query()->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($label)])->first();
        if ($found) {
            $brands->push($found);
        }

        return $found;
    }

    protected function matchCategory(Collection $categories, ?Brand $brand, string $label): ?Category
    {
        $label = trim($label);
        if ($label === '') {
            return null;
        }

        $named = $categories->filter(fn (Category $category) => $this->namesEqual($this->rawName($category), $label))->values();
        if ($brand) {
            $underBrand = $named->first(fn (Category $category) => (int) $category->brand_id === (int) $brand->id);
            if ($underBrand) {
                return $underBrand;
            }

            $unbranded = $named->filter(fn (Category $category) => $category->brand_id === null)->values();

            return $unbranded->count() === 1 ? $unbranded->first() : null;
        }

        return $named->count() === 1 ? $named->first() : null;
    }

    protected function matchSubCategory(Collection $subCategories, ?Category $category, string $label): ?SubCategory
    {
        $label = trim($label);
        if ($label === '' || ! $category) {
            return null;
        }

        return $subCategories->first(function (SubCategory $subCategory) use ($category, $label) {
            return (int) $subCategory->category_id === (int) $category->id
                && $this->namesEqual($this->rawName($subCategory), $label);
        });
    }

    protected function matchProductType(Collection $productTypes, string $label): ?ProductType
    {
        $label = trim($label);
        if ($label === '') {
            return null;
        }

        $found = $productTypes->first(fn (ProductType $type) => $this->namesEqual($this->rawName($type), $label));
        if ($found) {
            return $found;
        }

        $found = ProductType::query()->where('name', NameCase::format($label))->first()
            ?? ProductType::query()->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($label)])->first();
        if ($found) {
            $productTypes->push($found);
        }

        return $found;
    }

    protected function rawName(object $model): string
    {
        if (method_exists($model, 'getRawOriginal')) {
            return trim((string) ($model->getRawOriginal('name') ?? ''));
        }

        return trim((string) ($model->name ?? ''));
    }

    protected function namesEqual(string $stored, string $incoming): bool
    {
        $stored = trim($stored);
        $incoming = trim($incoming);
        if ($stored === '' || $incoming === '') {
            return false;
        }

        return strcasecmp($stored, $incoming) === 0
            || strcasecmp((string) NameCase::format($stored), (string) NameCase::format($incoming)) === 0;
    }

    protected function payloadChanged(Product $existing, array $payload): bool
    {
        foreach ($payload as $key => $incoming) {
            if ($this->canonical($key, $existing->getAttribute($key)) !== $this->canonical($key, $incoming)) {
                return true;
            }
        }

        return false;
    }

    protected function canonical(string $key, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($this->isNumericField($key) && is_numeric($value)) {
            return number_format((float) $value, 4, '.', '');
        }

        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        if (NameCase::isApplicableField($key)) {
            return NameCase::format($string);
        }

        return $string;
    }

    protected function isNumericField(string $key): bool
    {
        return str_ends_with($key, '_id')
            || in_array($key, [
                'conversion_factor',
                'tax_rate',
                'purchase_price',
                'selling_price',
                'trade_price',
                'calculation_mrp',
                'alt_purchase_price',
                'alt_trade_price',
                'alt_selling_price',
                'alt_calculation_mrp',
                'height_cm',
                'length_cm',
                'width_cm',
                'net_weight',
                'min_stock',
                'reorder_level',
                'discount_percent',
                'discount_value',
                'selling_discount_value',
                'warranty_months',
                'sender_warranty_months',
                'customer_warranty_months',
            ], true);
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
