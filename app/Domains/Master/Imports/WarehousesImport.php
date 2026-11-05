<?php

namespace App\Domains\Master\Imports;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Support\CodeGenerator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WarehousesImport implements ToCollection, WithHeadingRow
{
    use BulkImportHelpers, Importable;

    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function collection(Collection $rows): void
    {
        $companies = Company::query()->get()->keyBy(fn ($c) => strtolower((string) $c->code));
        $branches = Branch::query()->get()->keyBy(
            fn ($b) => strtolower($b->company_id.'|'.(string) $b->code)
        );

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = $this->rowData($row);

            $name = $this->str($data, 'name');
            if ($name === '') {
                $this->skip($rowNo, 'name', 'Name is required');
                continue;
            }

            $companyCode = $this->str($data, 'company_code');
            $company = $companies[strtolower($companyCode)] ?? null;
            if (! $company) {
                $this->skip($rowNo, 'company_code', "Company '{$companyCode}' not found");
                continue;
            }

            $branchCode = $this->str($data, 'branch_code');
            $branch = $branches[strtolower($company->id.'|'.$branchCode)] ?? null;
            if (! $branch) {
                $this->skip($rowNo, 'branch_code', "Branch '{$branchCode}' not found for company '{$companyCode}'");
                continue;
            }

            $code = $this->str($data, 'code');
            if ($code === '') {
                $code = CodeGenerator::forWarehouse($company->id);
            }

            $isDefault = $this->boolOrDefault($data, 'is_default', false);
            if ($isDefault) {
                Warehouse::query()
                    ->where('company_id', $company->id)
                    ->where('branch_id', $branch->id)
                    ->update(['is_default' => false]);
            }

            $payload = [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => $name,
                'code' => $code,
                'address' => $this->nullableStr($data, 'address'),
                'is_default' => $isDefault,
                'is_active' => $this->boolOrDefault($data, 'is_active', true),
            ];

            $existing = Warehouse::query()
                ->where('company_id', $company->id)
                ->whereRaw('LOWER(code) = ?', [strtolower($code)])
                ->first();

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
