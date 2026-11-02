<?php

namespace App\Domains\Master\Imports;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SubCategoriesImport implements ToCollection, WithHeadingRow
{
    use BulkImportHelpers, Importable;

    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function __construct(protected ?int $companyId = null) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = $this->rowData($row);

            $name = $this->str($data, 'name');
            if ($name === '') {
                $this->skip($rowNo, 'name', 'Name is required');
                continue;
            }

            $brandName = $this->str($data, 'brand_name');
            $categoryName = $this->str($data, 'category_name');
            if ($brandName === '' || $categoryName === '') {
                $this->skip($rowNo, 'category_name', 'brand_name and category_name are required');
                continue;
            }

            $brand = Brand::query()
                ->when($this->companyId, fn ($q) => $q->where('company_id', $this->companyId))
                ->whereRaw('LOWER(name) = ?', [strtolower($brandName)])
                ->first();

            if (! $brand) {
                $this->skip($rowNo, 'brand_name', "Brand '{$brandName}' not found — import Brands first");
                continue;
            }

            $category = Category::query()
                ->where('brand_id', $brand->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($categoryName)])
                ->first();

            if (! $category) {
                $this->skip($rowNo, 'category_name', "Category '{$categoryName}' not found under brand '{$brandName}'");
                continue;
            }

            $payload = [
                'category_id' => $category->id,
                'name' => $name,
                'is_active' => $this->boolOrDefault($data, 'is_active', true),
            ];

            $existing = SubCategory::query()
                ->where('category_id', $category->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->first();

            if ($existing) {
                $existing->update($payload);
                $this->updated++;
            } else {
                SubCategory::create($payload);
                $this->created++;
            }
        }
    }
}
