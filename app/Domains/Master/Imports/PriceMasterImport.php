<?php

namespace App\Domains\Master\Imports;

use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Services\UomLookupService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk import Price Master rows.
 *
 * Canonical columns: see BulkImportCatalog::get('price-master')
 *   customer_type, part_number, uom_code, rate, min_qty, rate_includes_gst
 *
 * Aliases: sku / part_no / partnumber → part_number;
 * customertype / customer_type_name / customer_type_code / type / classification → customer_type.
 * customer_type defaults to Retail when blank. rate must be > 0.
 */
class PriceMasterImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public array $errors = [];
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;

    public function collection(Collection $rows): void
    {
        $products = Product::query()->get()->keyBy(fn($p) => strtolower($p->sku));
        $uoms = Uom::query()->get()->keyBy(fn($u) => strtoupper($u->code));
        $allTypes = CustomerType::query()->get();
        $typesByName = $allTypes->keyBy(fn($t) => strtolower(trim($t->name)));
        $typesByCode = $allTypes->keyBy(fn($t) => strtolower(trim($t->code)));

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            try {
                $data = collect($row)->mapWithKeys(fn($v, $k) => [strtolower(trim((string) $k)) => $v])->toArray();

                $sku = trim((string) ($data['part_number'] ?? $data['part_no'] ?? $data['partnumber'] ?? $data['sku'] ?? ''));
                $product = $products[strtolower($sku)] ?? null;
                if (! $product) {
                    $this->skipped++;
                    $this->errors[] = ['row' => $rowNo, 'field' => 'part_number', 'message' => "Product with Part Number '{$sku}' not found"];
                    continue;
                }

                $rawUom = trim((string) ($data['uom_code'] ?? ''));
                $uom = UomLookupService::resolve($rawUom);
                if (! $uom) {
                    $this->skipped++;
                    $this->errors[] = ['row' => $rowNo, 'field' => 'uom_code', 'message' => "Unknown UOM '{$rawUom}'"];
                    continue;
                }

                $rawType = trim((string) ($data['customer_type'] ?? $data['customertype'] ?? $data['customer_type_name'] ?? $data['customer_type_code'] ?? $data['type'] ?? $data['classification'] ?? ''));
                if ($rawType === '') {
                    $rawType = 'Retail';
                }

                $lookupKey = strtolower($rawType);
                $type = $typesByName->get($lookupKey) ?? $typesByCode->get($lookupKey);

                if (! $type) {
                    $typeName = \App\Support\NameCase::format($rawType);
                    $type = $typesByName->get(strtolower($typeName));
                    if (! $type) {
                        $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', Str::slug($typeName, '_')), 0, 15)) ?: 'TYPE';
                        $code = $baseCode;
                        $counter = 1;
                        while (CustomerType::where('code', $code)->exists()) {
                            $suffix = (string) $counter;
                            $code = substr($baseCode, 0, 20 - strlen($suffix)) . $suffix;
                            $counter++;
                        }

                        $type = CustomerType::firstOrCreate(
                            ['name' => $typeName],
                            [
                                'code' => $code,
                                'is_active' => true,
                            ]
                        );
                        $typesByName->put(strtolower($type->name), $type);
                        $typesByCode->put(strtolower($type->code), $type);
                    }
                }

                $rate = (float) ($data['rate'] ?? 0);
                if ($rate <= 0) {
                    $this->skipped++;
                    $this->errors[] = ['row' => $rowNo, 'field' => 'rate', 'message' => 'Rate must be > 0'];
                    continue;
                }

                $includesGst = in_array(strtolower(trim((string) ($data['rate_includes_gst'] ?? '0'))), ['1', 'true', 'yes', 'y', 'on'], true);

                $exists = PriceMaster::query()
                    ->where('customer_type_id', $type->id)
                    ->where('product_id', $product->id)
                    ->where('uom_id', $uom->id)
                    ->where('min_qty', (float) ($data['min_qty'] ?? 0))
                    ->first();

                if ($exists) {
                    $exists->update([
                        'rate' => $rate,
                        'entered_rate' => $rate,
                        'rate_includes_gst' => $includesGst,
                    ]);
                    $this->updated++;
                } else {
                    PriceMaster::create([
                        'customer_type_id' => $type->id,
                        'product_id' => $product->id,
                        'uom_id' => $uom->id,
                        'rate' => $rate,
                        'entered_rate' => $rate,
                        'rate_includes_gst' => $includesGst,
                        'min_qty' => (float) ($data['min_qty'] ?? 0),
                    ]);
                    $this->created++;
                }
            } catch (\Throwable $e) {
                $this->skipped++;
                $this->errors[] = ['row' => $rowNo, 'field' => 'row', 'message' => $e->getMessage()];
            }
        }
    }
}
