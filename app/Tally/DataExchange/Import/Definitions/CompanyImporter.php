<?php

namespace Tally\DataExchange\Import\Definitions;

use Tally\DataExchange\Import\ImportBatch;
use Tally\DataExchange\Import\ImportDefinition;
use Tally\Models\Company;
use Tally\Models\FinancialYear;

class CompanyImporter extends ImportDefinition
{
    public function key(): string
    {
        return 'companies';
    }

    public function label(): string
    {
        return 'Companies';
    }

    public function description(): string
    {
        return 'Creates companies and their first financial year. Each new company receives the standard account groups.';
    }

    public function requiresCompany(): bool
    {
        return false;
    }

    public function columns(): array
    {
        return [
            ['key' => 'name', 'required' => true, 'hint' => 'Display name'],
            ['key' => 'legal_name', 'required' => false, 'hint' => 'Registered name'],
            ['key' => 'address', 'required' => false, 'hint' => ''],
            ['key' => 'city', 'required' => false, 'hint' => ''],
            ['key' => 'state', 'required' => false, 'hint' => ''],
            ['key' => 'country', 'required' => true, 'hint' => 'India'],
            ['key' => 'pincode', 'required' => false, 'hint' => ''],
            ['key' => 'phone', 'required' => false, 'hint' => ''],
            ['key' => 'email', 'required' => false, 'hint' => ''],
            ['key' => 'gst_registration_type', 'required' => false, 'hint' => 'regular, composition, unregistered, consumer'],
            ['key' => 'gstin', 'required' => false, 'hint' => '15 characters'],
            ['key' => 'pan', 'required' => false, 'hint' => '10 characters'],
            ['key' => 'financial_year_start', 'required' => true, 'hint' => 'YYYY-MM-DD'],
            ['key' => 'financial_year_end', 'required' => true, 'hint' => 'YYYY-MM-DD'],
            ['key' => 'is_active', 'required' => false, 'hint' => 'yes or no'],
        ];
    }

    public function sample(): array
    {
        return [[
            'name' => 'Acme Traders',
            'legal_name' => 'Acme Traders Private Limited',
            'country' => 'India',
            'state' => 'Maharashtra',
            'city' => 'Pune',
            'financial_year_start' => '2026-04-01',
            'financial_year_end' => '2027-03-31',
            'is_active' => 'yes',
        ]];
    }

    public function validate(ImportBatch $batch): void
    {
        $this->parsed = [];

        foreach ($batch->rows as $index => $row) {
            $line = $index + 2;
            $name = $this->required($batch, $line, $row, 'name', 'Name');
            $country = $this->required($batch, $line, $row, 'country', 'Country');
            $start = $this->date($batch, $line, $row, 'financial_year_start', 'Financial year start');
            $end = $this->date($batch, $line, $row, 'financial_year_end', 'Financial year end');
            $active = $this->active($batch, $line, $row);
            $gstin = $this->gstin($batch, $line, $row);
            $pan = $this->pan($batch, $line, $row);
            $email = $this->email($batch, $line, $row);
            $registration = $this->registration($batch, $line, $row);

            if ($name) {
                $batch->claim($line, 'company name', $name);

                if (Company::query()->whereRaw('lower(name) = ?', [mb_strtolower($name)])->exists()) {
                    $batch->add($line, 'A company named "'.$name.'" already exists.');
                }
            }

            if ($gstin) {
                $batch->claim($line, 'GSTIN', $gstin);

                if (Company::query()->where('gstin', $gstin)->exists()) {
                    $batch->add($line, 'GSTIN '.$gstin.' is already used.');
                }
            }

            if ($start && $end && $end <= $start) {
                $batch->add($line, 'The financial year end must be after the start date.');
            }

            if ($name === null || $country === null || $start === null || $end === null || $active === null) {
                continue;
            }

            $this->parsed[] = [
                'name' => $name,
                'legal_name' => $this->limited($batch, $line, $this->optional($batch, $row, 'legal_name', 255), 'Legal name', 255),
                'address' => $this->limited($batch, $line, $this->optional($batch, $row, 'address', 500), 'Address', 500),
                'city' => $this->limited($batch, $line, $this->optional($batch, $row, 'city', 255), 'City', 255),
                'state' => $this->limited($batch, $line, $this->optional($batch, $row, 'state', 255), 'State', 255),
                'country' => $country,
                'pincode' => $this->limited($batch, $line, $this->optional($batch, $row, 'pincode', 12), 'Pincode', 12),
                'phone' => $this->limited($batch, $line, $this->optional($batch, $row, 'phone', 30), 'Phone', 30),
                'email' => $email,
                'gst_registration_type' => $registration,
                'gstin' => $gstin,
                'pan' => $pan,
                'financial_year_start' => $start,
                'financial_year_end' => $end,
                'is_active' => $active,
            ];
        }
    }

    public function persist(ImportBatch $batch): int
    {
        foreach ($this->parsed as $row) {
            $company = Company::query()->create($row);
            $company->financialYears()->create([
                'name' => FinancialYear::labelForPeriod($company->financial_year_start, $company->financial_year_end),
                'start_date' => $company->financial_year_start,
                'end_date' => $company->financial_year_end,
                'is_active' => true,
            ]);
        }

        return count($this->parsed);
    }
}
