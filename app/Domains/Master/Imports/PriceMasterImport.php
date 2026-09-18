<?php

namespace App\Domains\Master\Imports;

use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk import Price Master rows.
 * Columns: customer_type (name), sku, uom_code, rate, min_qty
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
        $types = CustomerType::query()->get()->keyBy(fn($t) => strtolower($t->name));

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = collect($row)->mapWithKeys(fn($v, $k) => [strtolower((string) $k) => $v])->toArray();

            $sku = trim((string) ($data['sku'] ?? ''));
            $product = $products[strtolower($sku)] ?? null;
            if (! $product) {
                $this->skipped++;
                $this->errors[] = ['row' => $rowNo, 'field' => 'sku', 'message' => "Product with SKU '{$sku}' not found"];
                continue;
            }

            $uomCode = strtoupper((string) ($data['uom_code'] ?? ''));
            $uom = $uoms[$uomCode] ?? null;
            if (! $uom) {
                $this->skipped++;
                $this->errors[] = ['row' => $rowNo, 'field' => 'uom_code', 'message' => "UOM '{$uomCode}' not found"];
                continue;
            }

            $typeName = trim((string) ($data['customer_type'] ?? 'Retail'));
            $type = $types[strtolower($typeName)] ?? CustomerType::firstOrCreate(['name' => $typeName], ['is_active' => true]);
            if (! $types->has(strtolower($type->name))) $types->put(strtolower($type->name), $type);

            $rate = (float) ($data['rate'] ?? 0);
            if ($rate <= 0) {
                $this->skipped++;
                $this->errors[] = ['row' => $rowNo, 'field' => 'rate', 'message' => 'Rate must be > 0'];
                continue;
            }

            $exists = PriceMaster::query()
                ->where('customer_type_id', $type->id)
                ->where('product_id', $product->id)
                ->where('uom_id', $uom->id)
                ->where('min_qty', (float) ($data['min_qty'] ?? 0))
                ->first();

            if ($exists) {
                $exists->update(['rate' => $rate]);
                $this->updated++;
            } else {
                PriceMaster::create([
                    'customer_type_id' => $type->id,
                    'product_id' => $product->id,
                    'uom_id' => $uom->id,
                    'rate' => $rate,
                    'min_qty' => (float) ($data['min_qty'] ?? 0),
                ]);
                $this->created++;
            }
        }
    }
}
