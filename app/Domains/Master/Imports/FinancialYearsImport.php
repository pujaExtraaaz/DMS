<?php

namespace App\Domains\Master\Imports;

use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FinancialYearsImport implements ToCollection, WithHeadingRow
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

            $companyCode = $this->str($data, 'company_code');
            $company = $companies[strtolower($companyCode)] ?? null;
            if (! $company) {
                $this->skip($rowNo, 'company_code', "Company '{$companyCode}' not found");
                continue;
            }

            try {
                $startsOn = Carbon::parse($this->str($data, 'starts_on'))->toDateString();
                $endsOn = Carbon::parse($this->str($data, 'ends_on'))->toDateString();
            } catch (\Throwable) {
                $this->skip($rowNo, 'starts_on', 'starts_on and ends_on must be valid dates');
                continue;
            }

            $name = $this->str($data, 'name');
            if ($name === '') {
                $name = Carbon::parse($startsOn)->format('Y').'-'.Carbon::parse($endsOn)->format('Y');
            }

            $isCurrent = $this->boolOrDefault($data, 'is_current', false);
            if ($isCurrent) {
                FinancialYear::query()
                    ->where('company_id', $company->id)
                    ->update(['is_current' => false]);
            }

            $payload = [
                'company_id' => $company->id,
                'name' => $name,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'is_current' => $isCurrent,
                'is_closed' => $this->boolOrDefault($data, 'is_closed', false),
            ];

            $existing = FinancialYear::query()
                ->where('company_id', $company->id)
                ->whereDate('starts_on', $startsOn)
                ->whereDate('ends_on', $endsOn)
                ->first();

            if ($existing) {
                $existing->update($payload);
                $this->updated++;
            } else {
                FinancialYear::create($payload);
                $this->created++;
            }
        }
    }
}
