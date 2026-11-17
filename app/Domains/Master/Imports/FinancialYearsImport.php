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

            $name = $this->str($data, 'name');
            if ($name === '') {
                $this->skip($rowNo, 'name', 'Financial year name is required (e.g. FY 2026-27)');
                continue;
            }

            try {
                $startsOn = Carbon::parse($this->str($data, 'starts_on'))->toDateString();
                $endsOn = Carbon::parse($this->str($data, 'ends_on'))->toDateString();
            } catch (\Throwable) {
                $this->skip($rowNo, 'starts_on', 'starts_on and ends_on must be valid dates (YYYY-MM-DD)');
                continue;
            }

            if ($endsOn <= $startsOn) {
                $this->skip($rowNo, 'ends_on', 'End date must be later than start date');
                continue;
            }

            $existing = FinancialYear::query()
                ->where('company_id', $company->id)
                ->whereDate('starts_on', $startsOn)
                ->whereDate('ends_on', $endsOn)
                ->first();

            $overlap = FinancialYear::query()
                ->overlapping($company->id, $startsOn, $endsOn, $existing?->id)
                ->exists();
            if ($overlap) {
                $this->skip($rowNo, 'starts_on', 'This financial year overlaps another year for the same company');
                continue;
            }

            $isClosed = $this->boolOrDefault($data, 'is_closed', false);
            $isCurrent = $this->boolOrDefault($data, 'is_current', false);

            if ($isClosed && $isCurrent) {
                $this->skip($rowNo, 'is_current', 'A closed financial year cannot be marked as current');
                continue;
            }

            // First open FY for company becomes current when none exists.
            $hasCurrent = FinancialYear::query()
                ->where('company_id', $company->id)
                ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                ->where('is_current', true)
                ->exists();

            if (! $hasCurrent && ! $isClosed) {
                $isCurrent = true;
            }

            if ($isCurrent) {
                FinancialYear::query()
                    ->where('company_id', $company->id)
                    ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                    ->update(['is_current' => false]);
            }

            $payload = [
                'company_id' => $company->id,
                'name' => $name,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'is_current' => $isCurrent,
                'is_closed' => $isClosed,
                'closed_at' => $isClosed ? ($existing?->closed_at ?: now()) : null,
                'closed_by' => $isClosed ? ($existing?->closed_by ?: null) : null,
            ];

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
