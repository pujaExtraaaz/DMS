<?php

namespace App\Domains\Master\Imports;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Organization\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BrandsImport implements ToCollection, WithHeadingRow
{
    use HandlesBulkImportRows, Importable;

    public function __construct(protected ?int $companyId = null) {}

    public function collection(Collection $rows): void
    {
        $companies = Company::query()->get()->keyBy(fn ($c) => strtoupper((string) $c->code));
        $defaultCompanyId = $this->companyId ?? Company::query()->value('id');

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = $this->rowData($row);

            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                $this->skip($rowNo, 'name', 'Name is required');
                continue;
            }

            $companyId = $defaultCompanyId;
            $companyCode = strtoupper(trim((string) ($data['company_code'] ?? '')));
            if ($companyCode !== '') {
                $company = $companies[$companyCode] ?? null;
                if (! $company) {
                    $this->skip($rowNo, 'company_code', "Company '{$companyCode}' not found");
                    continue;
                }
                $companyId = $company->id;
            }

            $payload = [
                'company_id' => $companyId,
                'name' => $name,
                'detail' => $this->str($data, 'detail'),
                'is_active' => $this->boolOrDefault($data, 'is_active', true),
            ];

            $existing = Brand::query()
                ->where('company_id', $companyId)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->first();

            if ($existing) {
                $existing->update($payload);
                $this->updated++;
            } else {
                Brand::create($payload);
                $this->created++;
            }
        }
    }
}
