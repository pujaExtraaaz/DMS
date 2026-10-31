<?php

namespace App\Domains\Master\Imports;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BranchesImport implements ToCollection, WithHeadingRow
{
    use HandlesBulkImportRows, Importable;

    public function collection(Collection $rows): void
    {
        $companies = Company::query()->get()->keyBy(fn ($c) => strtoupper((string) $c->code));

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

            $code = strtoupper(trim((string) ($data['code'] ?? '')));
            if ($code === '') {
                $code = strtoupper(Str::slug($name, '_'));
            }

            $payload = [
                'company_id' => $company->id,
                'name' => $name,
                'code' => $code,
                'gstin' => $this->str($data, 'gstin'),
                'phone' => $this->str($data, 'phone'),
                'address' => $this->str($data, 'address'),
                'state' => $this->str($data, 'state'),
                'pincode' => $this->str($data, 'pincode'),
                'is_active' => $this->boolOrDefault($data, 'is_active', true),
            ];

            $existing = Branch::query()
                ->where('company_id', $company->id)
                ->whereRaw('UPPER(code) = ?', [$code])
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
