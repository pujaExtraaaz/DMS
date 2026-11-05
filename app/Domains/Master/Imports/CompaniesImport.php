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
    use BulkImportHelpers, Importable;

    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function collection(Collection $rows): void
    {
        $groups = BusinessGroup::query()->get()->keyBy(fn ($g) => strtolower((string) $g->code));

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = $this->rowData($row);

            $name = $this->str($data, 'name');
            if ($name === '') {
                $this->skip($rowNo, 'name', 'Name is required');
                continue;
            }

            $code = $this->str($data, 'code');
            if ($code === '') {
                $code = CodeGenerator::forCompany();
            }

            $groupId = null;
            $groupCode = $this->str($data, 'business_group_code');
            if ($groupCode !== '') {
                $group = $groups[strtolower($groupCode)] ?? null;
                if (! $group) {
                    $this->skip($rowNo, 'business_group_code', "Business group '{$groupCode}' not found");
                    continue;
                }
                $groupId = $group->id;
            }

            $payload = [
                'business_group_id' => $groupId,
                'name' => $name,
                'code' => $code,
                'legal_name' => $this->nullableStr($data, 'legal_name'),
                'company_type' => $this->nullableStr($data, 'company_type'),
                'gstin' => $this->nullableStr($data, 'gstin'),
                'pan' => $this->nullableStr($data, 'pan'),
                'address' => $this->nullableStr($data, 'address'),
                'city' => $this->nullableStr($data, 'city'),
                'state' => $this->nullableStr($data, 'state'),
                'pincode' => $this->nullableStr($data, 'pincode'),
                'phone' => $this->nullableStr($data, 'phone'),
                'email' => $this->nullableStr($data, 'email'),
                'website' => $this->nullableStr($data, 'website'),
                'is_active' => $this->boolOrDefault($data, 'is_active', true),
                'is_default' => $this->boolOrDefault($data, 'is_default', false),
            ];

            $existing = Company::query()->whereRaw('LOWER(code) = ?', [strtolower($code)])->first();
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
