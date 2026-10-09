<?php

namespace Tally\DataExchange\Import\Definitions;

use Tally\DataExchange\Import\ImportBatch;
use Tally\DataExchange\Import\ImportDefinition;

class BranchImporter extends ImportDefinition
{
    public function key(): string
    {
        return 'branches';
    }

    public function label(): string
    {
        return 'Branches';
    }

    public function description(): string
    {
        return 'Creates branches for the current company. Codes must be unique inside that company.';
    }

    public function columns(): array
    {
        return [
            ['key' => 'name', 'required' => true, 'hint' => ''],
            ['key' => 'code', 'required' => true, 'hint' => 'Unique in the company'],
            ['key' => 'address', 'required' => false, 'hint' => ''],
            ['key' => 'city', 'required' => false, 'hint' => ''],
            ['key' => 'state', 'required' => false, 'hint' => ''],
            ['key' => 'country', 'required' => true, 'hint' => 'India'],
            ['key' => 'pincode', 'required' => false, 'hint' => ''],
            ['key' => 'phone', 'required' => false, 'hint' => ''],
            ['key' => 'email', 'required' => false, 'hint' => ''],
            ['key' => 'is_active', 'required' => false, 'hint' => 'yes or no'],
        ];
    }

    public function sample(): array
    {
        return [[
            'name' => 'Head Office',
            'code' => 'HO',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'country' => 'India',
            'is_active' => 'yes',
        ]];
    }

    public function validate(ImportBatch $batch): void
    {
        $this->parsed = [];
        $company = $batch->company;

        if (! $company) {
            $batch->add(0, 'Select a company before importing branches.');

            return;
        }

        foreach ($batch->rows as $index => $row) {
            $line = $index + 2;
            $name = $this->required($batch, $line, $row, 'name', 'Name');
            $code = $this->code($batch, $line, $batch->cell($row, 'code'), 20, true);
            $country = $this->required($batch, $line, $row, 'country', 'Country');
            $active = $this->active($batch, $line, $row);
            $email = $this->email($batch, $line, $row);

            if ($name) {
                $batch->claim($line, 'branch name', $name);

                if ($this->taken('branches', $company->id, 'name', $name)) {
                    $batch->add($line, 'Branch "'.$name.'" already exists in this company.');
                }
            }

            if ($code) {
                $batch->claim($line, 'branch code', $code);

                if ($this->taken('branches', $company->id, 'code', $code)) {
                    $batch->add($line, 'Branch code '.$code.' already exists in this company.');
                }
            }

            if ($name === null || $code === null || $country === null || $active === null) {
                continue;
            }

            $this->parsed[] = [
                'company_id' => $company->id,
                'name' => $name,
                'code' => $code,
                'address' => $this->limited($batch, $line, $this->optional($batch, $row, 'address', 500), 'Address', 500),
                'city' => $this->limited($batch, $line, $this->optional($batch, $row, 'city', 255), 'City', 255),
                'state' => $this->limited($batch, $line, $this->optional($batch, $row, 'state', 255), 'State', 255),
                'country' => $country,
                'pincode' => $this->limited($batch, $line, $this->optional($batch, $row, 'pincode', 12), 'Pincode', 12),
                'phone' => $this->limited($batch, $line, $this->optional($batch, $row, 'phone', 30), 'Phone', 30),
                'email' => $email,
                'is_active' => $active,
            ];
        }
    }

    public function persist(ImportBatch $batch): int
    {
        foreach ($this->parsed as $row) {
            $batch->company?->branches()->create($row);
        }

        return count($this->parsed);
    }
}
