<?php

namespace Tally\DataExchange\Import\Definitions;

use Tally\DataExchange\Import\ImportBatch;
use Tally\DataExchange\Import\ImportDefinition;
use Tally\Models\Unit;

class UnitImporter extends ImportDefinition
{
    public function key(): string
    {
        return 'units';
    }

    public function label(): string
    {
        return 'Units';
    }

    public function description(): string
    {
        return 'Creates units of measure for the current company.';
    }

    public function columns(): array
    {
        return [
            ['key' => 'name', 'required' => true, 'hint' => ''],
            ['key' => 'symbol', 'required' => true, 'hint' => 'Unique in the company'],
            ['key' => 'decimal_places', 'required' => false, 'hint' => '0 to 4'],
            ['key' => 'is_active', 'required' => false, 'hint' => 'yes or no'],
        ];
    }

    public function sample(): array
    {
        return [[
            'name' => 'Numbers',
            'symbol' => 'Nos',
            'decimal_places' => '0',
            'is_active' => 'yes',
        ]];
    }

    public function validate(ImportBatch $batch): void
    {
        $this->parsed = [];
        $company = $batch->company;

        if (! $company) {
            $batch->add(0, 'Select a company before importing units.');

            return;
        }

        foreach ($batch->rows as $index => $row) {
            $line = $index + 2;
            $name = $this->required($batch, $line, $row, 'name', 'Name');
            $symbol = $batch->cell($row, 'symbol');
            $active = $this->active($batch, $line, $row);
            $places = $batch->cell($row, 'decimal_places');
            $decimals = 0;

            if ($symbol === '') {
                $batch->add($line, 'Symbol is required.');
                $symbol = null;
            } elseif (mb_strlen($symbol) > 16) {
                $batch->add($line, 'Symbol must be 16 characters or fewer.');
                $symbol = null;
            }

            if ($places === '') {
                $decimals = 0;
            } elseif (! preg_match('/^\d+$/', $places) || (int) $places > 4) {
                $batch->add($line, 'Decimal places must be a whole number from 0 to 4.');
            } else {
                $decimals = (int) $places;
            }

            if ($name) {
                $batch->claim($line, 'unit name', $name);

                if ($this->taken('units', $company->id, 'name', $name)) {
                    $batch->add($line, 'Unit "'.$name.'" already exists in this company.');
                }
            }

            if ($symbol) {
                $batch->claim($line, 'unit symbol', $symbol);

                if ($this->taken('units', $company->id, 'symbol', $symbol)) {
                    $batch->add($line, 'Unit symbol '.$symbol.' already exists in this company.');
                }
            }

            if ($name === null || $symbol === null || $active === null) {
                continue;
            }

            $this->parsed[] = [
                'company_id' => $company->id,
                'name' => $name,
                'symbol' => $symbol,
                'decimal_places' => $decimals,
                'is_active' => $active,
            ];
        }
    }

    public function persist(ImportBatch $batch): int
    {
        foreach ($this->parsed as $row) {
            Unit::query()->create($row);
        }

        return count($this->parsed);
    }
}
