<?php

namespace Tally\DataExchange\Import\Definitions;

use Tally\DataExchange\Import\ImportBatch;
use Tally\DataExchange\Import\ImportDefinition;
use Tally\Models\Product;
use Tally\Models\ProductGroup;
use Tally\Models\Unit;

class ProductImporter extends ImportDefinition
{
    public function key(): string
    {
        return 'products';
    }

    public function label(): string
    {
        return 'Products';
    }

    public function description(): string
    {
        return 'Creates products in the current company. The product group and primary unit must already exist.';
    }

    public function columns(): array
    {
        return [
            ['key' => 'name', 'required' => true, 'hint' => ''],
            ['key' => 'code', 'required' => true, 'hint' => ''],
            ['key' => 'group_code', 'required' => false, 'hint' => 'Group code or name'],
            ['key' => 'group_name', 'required' => false, 'hint' => ''],
            ['key' => 'unit_symbol', 'required' => false, 'hint' => 'Primary unit symbol or name'],
            ['key' => 'unit_name', 'required' => false, 'hint' => ''],
            ['key' => 'alternate_unit', 'required' => false, 'hint' => 'Symbol or name'],
            ['key' => 'conversion_factor', 'required' => false, 'hint' => 'Required with an alternate unit'],
            ['key' => 'barcode', 'required' => false, 'hint' => ''],
            ['key' => 'purchase_rate', 'required' => false, 'hint' => '0.00'],
            ['key' => 'sales_rate', 'required' => false, 'hint' => '0.00'],
            ['key' => 'opening_quantity', 'required' => false, 'hint' => '0'],
            ['key' => 'opening_rate', 'required' => false, 'hint' => '0.00'],
            ['key' => 'minimum_stock', 'required' => false, 'hint' => '0'],
            ['key' => 'reorder_level', 'required' => false, 'hint' => '0'],
            ['key' => 'is_active', 'required' => false, 'hint' => 'yes or no'],
        ];
    }

    public function sample(): array
    {
        return [[
            'name' => 'Notebook',
            'code' => 'NB-01',
            'group_code' => 'FG',
            'unit_symbol' => 'Nos',
            'purchase_rate' => '20.00',
            'sales_rate' => '35.00',
            'opening_quantity' => '10',
            'opening_rate' => '20.00',
            'is_active' => 'yes',
        ]];
    }

    public function validate(ImportBatch $batch): void
    {
        $this->parsed = [];
        $company = $batch->company;

        if (! $company) {
            $batch->add(0, 'Select a company before importing products.');

            return;
        }

        foreach ($batch->rows as $index => $row) {
            $line = $index + 2;
            $name = $this->required($batch, $line, $row, 'name', 'Name');
            $code = $this->code($batch, $line, $batch->cell($row, 'code'), 32, true);
            $active = $this->active($batch, $line, $row);
            $groupToken = $batch->cell($row, 'group_code') !== '' ? $batch->cell($row, 'group_code') : $batch->cell($row, 'group_name');
            $unitToken = $batch->cell($row, 'unit_symbol') !== '' ? $batch->cell($row, 'unit_symbol') : $batch->cell($row, 'unit_name');
            $alternateToken = $batch->cell($row, 'alternate_unit');
            $barcode = $batch->cell($row, 'barcode');
            $purchase = $this->amount($batch, $line, $row, 'purchase_rate', 'Purchase rate');
            $sales = $this->amount($batch, $line, $row, 'sales_rate', 'Sales rate');
            $quantity = $this->amount($batch, $line, $row, 'opening_quantity', 'Opening quantity', false, 4);
            $rate = $this->amount($batch, $line, $row, 'opening_rate', 'Opening rate');
            $minimum = $this->amount($batch, $line, $row, 'minimum_stock', 'Minimum stock', false, 4);
            $reorder = $this->amount($batch, $line, $row, 'reorder_level', 'Reorder level', false, 4);
            $conversion = $batch->cell($row, 'conversion_factor');

            if ($groupToken === '') {
                $batch->add($line, 'Product group code or name is required.');
            }

            if ($unitToken === '') {
                $batch->add($line, 'Primary unit symbol or name is required.');
            }

            $group = $groupToken === '' ? null : $this->findGroup($batch, $line, $groupToken);
            $unit = $unitToken === '' ? null : $this->findUnit($batch, $line, $unitToken);
            $alternate = null;

            if ($alternateToken !== '') {
                $alternate = $this->findUnit($batch, $line, $alternateToken);

                if ($unit && $alternate && $unit->id === $alternate->id) {
                    $batch->add($line, 'The alternate unit must be different from the primary unit.');
                }

                if ($conversion === '' || ! preg_match('/^\d+(\.\d{1,6})?$/', $conversion) || (float) $conversion <= 0) {
                    $batch->add($line, 'Conversion factor is required when an alternate unit is set.');
                    $conversion = null;
                }
            } else {
                $conversion = null;
            }

            if ($barcode !== '') {
                if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/', $barcode)) {
                    $batch->add($line, 'Barcode contains characters that are not allowed.');
                    $barcode = '';
                } else {
                    $batch->claim($line, 'barcode', $barcode);

                    if ($this->taken('products', $company->id, 'barcode', $barcode)) {
                        $batch->add($line, 'Barcode '.$barcode.' already exists in this company.');
                    }
                }
            }

            if ($name) {
                $batch->claim($line, 'product name', $name);

                if ($this->taken('products', $company->id, 'name', $name)) {
                    $batch->add($line, 'Product "'.$name.'" already exists in this company.');
                }
            }

            if ($code) {
                $batch->claim($line, 'product code', $code);

                if ($this->taken('products', $company->id, 'code', $code)) {
                    $batch->add($line, 'Product code '.$code.' already exists in this company.');
                }
            }

            if ($unit) {
                $places = (int) $unit->decimal_places;

                foreach ([
                    'Opening quantity' => 'quantity',
                    'Minimum stock' => 'minimum',
                    'Reorder level' => 'reorder',
                ] as $label => $variable) {
                    $candidate = $$variable;

                    if ($candidate !== null && ! \Tally\Inventory\Quantity::accepts((string) $candidate, $places)) {
                        $batch->add($line, $label.' allows up to '.$places.' decimal places for this unit.');
                        $$variable = null;
                    }
                }
            }

            if ($name === null || $code === null || $group === null || $unit === null || $active === null || $purchase === null || $sales === null || $quantity === null || $rate === null || $minimum === null || $reorder === null) {
                continue;
            }

            $this->parsed[] = [
                'company_id' => $company->id,
                'product_group_id' => $group->id,
                'primary_unit_id' => $unit->id,
                'alternate_unit_id' => $alternate?->id,
                'name' => $name,
                'code' => $code,
                'barcode' => $barcode === '' ? null : $barcode,
                'conversion_factor' => $conversion,
                'purchase_rate' => $purchase,
                'sales_rate' => $sales,
                'opening_quantity' => $quantity,
                'opening_rate' => $rate,
                'opening_value' => Product::openingValue($quantity, $rate),
                'minimum_stock' => $minimum,
                'reorder_level' => $reorder,
                'is_active' => $active,
            ];
        }
    }

    public function persist(ImportBatch $batch): int
    {
        foreach ($this->parsed as $row) {
            Product::query()->create($row);
        }

        return count($this->parsed);
    }

    private function findGroup(ImportBatch $batch, int $line, string $token): ?ProductGroup
    {
        $group = ProductGroup::query()
            ->where('company_id', $batch->company->id)
            ->where('is_active', true)
            ->where(function ($query) use ($token) {
                $query->whereRaw('lower(code) = ?', [strtolower($token)])
                    ->orWhereRaw('lower(name) = ?', [mb_strtolower($token)]);
            })
            ->first();

        if (! $group) {
            $batch->add($line, 'Product group "'.$token.'" was not found in this company.');
        }

        return $group;
    }

    private function findUnit(ImportBatch $batch, int $line, string $token): ?Unit
    {
        $unit = Unit::query()
            ->where('company_id', $batch->company->id)
            ->where('is_active', true)
            ->where(function ($query) use ($token) {
                $query->whereRaw('lower(symbol) = ?', [mb_strtolower($token)])
                    ->orWhereRaw('lower(name) = ?', [mb_strtolower($token)]);
            })
            ->first();

        if (! $unit) {
            $batch->add($line, 'Unit "'.$token.'" was not found in this company.');
        }

        return $unit;
    }
}
