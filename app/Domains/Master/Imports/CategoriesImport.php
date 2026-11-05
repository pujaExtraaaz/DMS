<?php

namespace App\Domains\Master\Imports;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CategoriesImport implements ToCollection, WithHeadingRow
{
    use BulkImportHelpers, Importable;

    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function __construct(protected ?int $companyId = null) {}

    public function collection(Collection $rows): void
    {
        $brandsQuery = Brand::query();
        if ($this->companyId) {
            $brandsQuery->where('company_id', $this->companyId);
        }
        $brands = $brandsQuery->get()->keyBy(fn ($b) => strtolower((string) $b->name));

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = $this->rowData($row);

            $name = $this->str($data, 'name');
            if ($name === '') {
                $this->skip($rowNo, 'name', 'Name is required');
                continue;
            }

            $brandName = $this->str($data, 'brand_name');
            if ($brandName === '') {
                $this->skip($rowNo, 'brand_name', 'Brand name is required');
                continue;
            }

            $brand = $brands[strtolower($brandName)] ?? Brand::query()
                ->when($this->companyId, fn ($q) => $q->where('company_id', $this->companyId))
                ->whereRaw('LOWER(name) = ?', [strtolower($brandName)])
                ->first();

            if (! $brand) {
                $this->skip($rowNo, 'brand_name', "Brand '{$brandName}' not found — import Brands first");
                continue;
            }
            $brands->put(strtolower($brand->name), $brand);

            $payload = [
                'brand_id' => $brand->id,
                'name' => $name,
                'is_active' => $this->boolOrDefault($data, 'is_active', true),
            ];

            $existing = Category::query()
                ->where('brand_id', $brand->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->first();

            if ($existing) {
                $existing->update($payload);
                $this->updated++;
            } else {
                Category::create($payload);
                $this->created++;
            }
        }
    }
}
