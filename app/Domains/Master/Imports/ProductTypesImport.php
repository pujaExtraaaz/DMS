<?php

namespace App\Domains\Master\Imports;

use App\Domains\Master\Models\ProductType;
use App\Domains\Organization\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductTypesImport implements ToCollection, WithHeadingRow
{
    use BulkImportHelpers, Importable;

    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function __construct(protected ?int $companyId = null) {}

    public function collection(Collection $rows): void
    {
        $companies = Company::query()->get()->keyBy(fn ($c) => strtolower((string) $c->code));
        $defaultCompanyId = $this->companyId ?? Company::query()->value('id');

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = $this->rowData($row);

            $name = $this->str($data, 'name');
            if ($name === '') {
                $this->skip($rowNo, 'name', 'Name is required');
                continue;
            }

            $companyId = $defaultCompanyId;
            $companyCode = $this->str($data, 'company_code');
            if ($companyCode !== '') {
                $company = $companies[strtolower($companyCode)] ?? null;
                if (! $company) {
                    $this->skip($rowNo, 'company_code', "Company '{$companyCode}' not found");
                    continue;
                }
                $companyId = $company->id;
            }

            if (! $companyId) {
                $this->skip($rowNo, 'company_code', 'Company is required');
                continue;
            }

            $code = $this->str($data, 'code');
            if ($code === '') {
                $code = strtoupper(Str::slug($name, '_'));
            }

            $trackSerial = array_key_exists('track_serial', $data) && $data['track_serial'] !== null && $data['track_serial'] !== ''
                ? $this->isTruthy($data['track_serial'])
                : false;

            $payload = [
                'company_id' => $companyId,
                'name' => $name,
                'code' => $code,
                'description' => $this->nullableStr($data, 'description'),
                'track_serial' => $trackSerial,
                'is_active' => $this->boolOrDefault($data, 'is_active', true),
            ];

            $existing = ProductType::query()
                ->where('company_id', $companyId)
                ->where(function ($q) use ($name, $code) {
                    $q->whereRaw('LOWER(name) = ?', [strtolower($name)])
                        ->orWhereRaw('UPPER(code) = ?', [strtoupper($code)]);
                })
                ->first();

            if ($existing) {
                $existing->fill($payload);
                $existing->track_serial = $trackSerial;
                $existing->save();
                $this->updated++;
            } else {
                $type = new ProductType($payload);
                $type->track_serial = $trackSerial;
                $type->save();
                $this->created++;
            }
        }
    }
}
