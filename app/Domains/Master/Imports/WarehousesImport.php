<?php

namespace App\Domains\Master\Imports;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WarehousesImport implements ToCollection, WithHeadingRow
{
    use HandlesBulkImportRows, Importable;

    public function collection(Collection $rows): void
    {
        $companies = Company::query()->get()->keyBy(fn ($c) => strtoupper((string) $c->code));
        $branches = Branch::query()->get()->groupBy('company_id');

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = $this->rowData($row);

            $name = \App\Support\NameCase::format((string) ($data['name'] ?? ''));
            if ($name === '') {
                $this->skip($rowNo, 'name', 'Name is required');
                continue;
            }

            $companyCode = strtoupper(trim((string) ($data['company_code'] ?? '')));
            $company = $companies[$companyCode] ?? null;
            if (! $company) {
                $this->skip($rowNo, 'company_code', "Company '{$companyCode}' not found");
                continue;
            }

            $branchCode = strtoupper(trim((string) ($data['branch_code'] ?? '')));
            $branch = ($branches[$company->id] ?? collect())
                ->first(fn ($b) => strtoupper((string) $b->code) === $branchCode);

            if (! $branch) {
                $this->skip($rowNo, 'branch_code', "Branch '{$branchCode}' not found for company '{$companyCode}'");
                continue;
            }

            $code = strtoupper(trim((string) ($data['code'] ?? ''))) ?: null;

            $payload = [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => $name,
                'address' => $this->str($data, 'address'),
                'is_default' => $this->boolOrDefault($data, 'is_default', false),
                'is_active' => $this->boolOrDefault($data, 'is_active', true),
            ];
            if ($code) {
                $payload['code'] = $code;
            }

            $existing = $code
                ? Warehouse::query()->where('company_id', $company->id)->whereRaw('UPPER(code) = ?', [$code])->first()
                : Warehouse::query()->where('company_id', $company->id)->where('branch_id', $branch->id)->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();

            if ($existing) {
                $existing->update($payload);
                $this->updated++;
            } else {
                Warehouse::create($payload);
                $this->created++;
            }
        }
    }
}
