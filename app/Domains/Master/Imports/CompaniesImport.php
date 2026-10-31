<?php

namespace App\Domains\Master\Imports;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Support\CodeGenerator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CompaniesImport implements ToCollection, WithHeadingRow
{
    use HandlesBulkImportRows, Importable;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = $this->rowData($row);

            $name = \App\Support\NameCase::format((string) ($data['name'] ?? ''));
            if ($name === '') {
                $this->skip($rowNo, 'name', 'Name is required');
                continue;
            }

            $code = strtoupper(trim((string) ($data['code'] ?? '')));
            if ($code === '') {
                $code = CodeGenerator::forCompany();
            }

            $businessGroupId = null;
            $groupCode = $this->str($data, 'business_group_code');
            if ($groupCode) {
                $group = BusinessGroup::query()->whereRaw('UPPER(code) = ?', [strtoupper($groupCode)])->first();
                if (! $group) {
                    $this->skip($rowNo, 'business_group_code', "Business group '{$groupCode}' not found");
                    continue;
                }
                $businessGroupId = $group->id;
            }

            $payload = [
                'name' => $name,
                'code' => $code,
                'legal_name' => $this->str($data, 'legal_name'),
                'company_type' => $this->str($data, 'company_type'),
                'business_group_id' => $businessGroupId,
                'gstin' => $this->str($data, 'gstin'),
                'pan' => $this->str($data, 'pan'),
                'address' => $this->str($data, 'address'),
                'city' => $this->str($data, 'city'),
                'state' => $this->str($data, 'state'),
                'pincode' => $this->str($data, 'pincode'),
                'phone' => $this->str($data, 'phone'),
                'email' => $this->str($data, 'email'),
                'website' => $this->str($data, 'website'),
                'is_active' => $this->boolOrDefault($data, 'is_active', true),
                'is_default' => $this->boolOrDefault($data, 'is_default', false),
            ];

            $existing = Company::query()->whereRaw('UPPER(code) = ?', [$code])->first();
            if ($existing) {
                $existing->update($payload);
                $this->updated++;
            } else {
                Company::create($payload);
                $this->created++;
            }
        }
    }
}
