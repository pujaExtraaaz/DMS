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
    use HandlesBulkImportRows, Importable;

    public function collection(Collection $rows): void
    {
        $companies = Company::query()->get()->keyBy(fn ($c) => strtoupper((string) $c->code));

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = $this->rowData($row);

            $companyCode = strtoupper(trim((string) ($data['company_code'] ?? '')));
            $company = $companies[$companyCode] ?? null;
            if (! $company) {
                $this->skip($rowNo, 'company_code', "Company '{$companyCode}' not found");
                continue;
            }

            try {
                $startsOn = Carbon::parse((string) ($data['starts_on'] ?? ''))->toDateString();
                $endsOn = Carbon::parse((string) ($data['ends_on'] ?? ''))->toDateString();
            } catch (\Throwable) {
                $this->skip($rowNo, 'starts_on', 'Valid starts_on and ends_on dates are required (YYYY-MM-DD)');
                continue;
            }

            $name = $this->str($data, 'name');
            if (! $name) {
                $name = Carbon::parse($startsOn)->format('Y').'-'.Carbon::parse($endsOn)->format('Y');
            }

            $isCurrent = $this->boolOrDefault($data, 'is_current', false);
            $payload = [
                'company_id' => $company->id,
                'name' => $name,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'is_current' => $isCurrent,
                'is_closed' => $this->boolOrDefault($data, 'is_closed', false),
            ];

            if ($isCurrent) {
                FinancialYear::query()->where('company_id', $company->id)->update(['is_current' => false]);
            }

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
