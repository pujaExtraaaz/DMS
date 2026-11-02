<?php

namespace App\Domains\Master\Imports;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Support\CodeGenerator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BranchesImport implements ToCollection, WithHeadingRow
{
    use BulkImportHelpers, Importable;

    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function collection(Collection $rows): void
    {
        $companies = Company::query()->get()->keyBy(fn ($c) => strtolower((string) $c->code));

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

            $code = $this->str($data, 'code');
            if ($code === '') {
                $code = CodeGenerator::forBranch($company->id);
            }

            $payload = [
                'company_id' => $company->id,
                'name' => $name,
                'code' => $code,
                'gstin' => $this->nullableStr($data, 'gstin'),
                'phone' => $this->nullableStr($data, 'phone'),
                'state' => $this->nullableStr($data, 'state'),
                'pincode' => $this->nullableStr($data, 'pincode'),
                'address' => $this->nullableStr($data, 'address'),
                'is_active' => $this->boolOrDefault($data, 'is_active', true),
            ];

            $existing = Branch::query()
                ->where('company_id', $company->id)
                ->whereRaw('LOWER(code) = ?', [strtolower($code)])
                ->first();

            if ($existing) {
                $existing->update($payload);
                $this->updated++;
            } else {
                Branch::create($payload);
                $this->created++;
            }
        }
    }
}
