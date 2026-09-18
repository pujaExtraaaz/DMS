<?php

namespace App\Domains\Master\Imports;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Support\CodeGenerator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bulk import Products from CSV/XLSX. Accepts these headings (case-insensitive):
 *   name, sku, hsn_code, uom_code, brand, category, color_variant,
 *   purchase_price, selling_price, trade_price, mrp, tax_rate,
 *   sender_warranty_months, customer_warranty_months, discount_percent
 *
 * Missing SKU → auto-generated. Missing UOM → skipped with error.
 * Update rule: match on `sku` (case-insensitive), otherwise create.
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
        $uomsByCode = Uom::query()->get()->keyBy(fn($u) => strtoupper($u->code));
        $brands = Brand::query()->get()->keyBy(fn($b) => strtolower($b->name));
        $categories = Category::query()->get()->keyBy(fn($c) => strtolower($c->name));
        $companyId = $this->companyId ?? Company::query()->value('id');

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2; // +1 for 0-index, +1 for header row.
            $data = collect($row)->mapWithKeys(fn($v, $k) => [strtolower((string) $k) => $v])->toArray();

            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                $this->skipped++;
                $this->errors[] = ['row' => $rowNo, 'field' => 'name', 'message' => 'Name is required'];
                continue;
            }

            $uomCode = strtoupper((string) ($data['uom_code'] ?? $data['uom'] ?? ''));
            $uom = $uomsByCode[$uomCode] ?? null;
            if (! $uom) {
                $this->skipped++;
                $this->errors[] = ['row' => $rowNo, 'field' => 'uom_code', 'message' => "Unknown UOM code '{$uomCode}'"];
                continue;
            }

            $sku = trim((string) ($data['sku'] ?? '')) ?: CodeGenerator::forProduct($companyId);

            $brand = filled($data['brand'] ?? null)
                ? ($brands[strtolower((string) $data['brand'])] ?? Brand::create(['company_id' => $companyId, 'name' => trim((string) $data['brand']), 'code' => CodeGenerator::forBrand($companyId), 'is_active' => true]))
                : null;
            if ($brand && ! $brands->has(strtolower($brand->name))) $brands->put(strtolower($brand->name), $brand);

            $category = filled($data['category'] ?? null)
                ? ($categories[strtolower((string) $data['category'])] ?? Category::create(['company_id' => $companyId, 'brand_id' => $brand?->id, 'name' => trim((string) $data['category']), 'code' => strtoupper('CAT-'.substr(md5($data['category']), 0, 6)), 'is_active' => true]))
                : null;
            if ($category && ! $categories->has(strtolower($category->name))) $categories->put(strtolower($category->name), $category);

            $existing = Product::query()->whereRaw('LOWER(sku) = ?', [strtolower($sku)])->first();

            $payload = [
                'company_id' => $companyId,
                'name' => $name,
                'sku' => $sku,
                'hsn_code' => trim((string) ($data['hsn_code'] ?? '')) ?: null,
                'base_uom_id' => $uom->id,
                'brand_id' => $brand?->id,
                'category_id' => $category?->id,
                'tax_rate' => (float) ($data['tax_rate'] ?? 0),
                'purchase_price' => (float) ($data['purchase_price'] ?? 0),
                'selling_price' => (float) ($data['selling_price'] ?? 0),
                'trade_price' => (float) ($data['trade_price'] ?? $data['selling_price'] ?? 0),
                'calculation_mrp' => (float) ($data['mrp'] ?? 0),
                'color_variant' => trim((string) ($data['color_variant'] ?? '')) ?: null,
                'sender_warranty_months' => filled($data['sender_warranty_months'] ?? null) ? (int) $data['sender_warranty_months'] : null,
                'customer_warranty_months' => filled($data['customer_warranty_months'] ?? null) ? (int) $data['customer_warranty_months'] : null,
                'discount_percent' => (float) ($data['discount_percent'] ?? 0),
                'discount_type' => 'percent',
                'discount_value' => (float) ($data['discount_percent'] ?? 0),
                'selling_discount_type' => 'percent',
                'selling_discount_value' => 0,
                'tracking_type' => 'none',
                'is_active' => true,
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
        }
    }
}
