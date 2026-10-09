<?php

namespace Tally\DataExchange\Import\Definitions;

use Tally\DataExchange\Import\ImportBatch;
use Tally\DataExchange\Import\ImportDefinition;
use Tally\DataExchange\Import\ParentLinks;
use Tally\Models\ProductGroup;

class ProductGroupImporter extends ImportDefinition
{
    public function key(): string
    {
        return 'product-groups';
    }

    public function label(): string
    {
        return 'Product Groups';
    }

    public function description(): string
    {
        return 'Creates product groups for the current company. A parent can be an existing group or another row in the file.';
    }

    public function columns(): array
    {
        return [
            ['key' => 'name', 'required' => true, 'hint' => ''],
            ['key' => 'code', 'required' => false, 'hint' => ''],
            ['key' => 'parent_code', 'required' => false, 'hint' => ''],
            ['key' => 'parent_name', 'required' => false, 'hint' => ''],
            ['key' => 'is_active', 'required' => false, 'hint' => 'yes or no'],
        ];
    }

    public function sample(): array
    {
        return [[
            'name' => 'Finished Goods',
            'code' => 'FG',
            'is_active' => 'yes',
        ]];
    }

    public function validate(ImportBatch $batch): void
    {
        $this->parsed = [];
        $company = $batch->company;

        if (! $company) {
            $batch->add(0, 'Select a company before importing product groups.');

            return;
        }

        $existing = [];
        ProductGroup::query()->where('company_id', $company->id)->get(['id', 'name', 'code'])->each(function (ProductGroup $group) use (&$existing) {
            $existing[mb_strtolower($group->name)] = $group->id;

            if ($group->code) {
                $existing[strtolower($group->code)] = $group->id;
            }
        });

        $nodes = [];

        foreach ($batch->rows as $index => $row) {
            $line = $index + 2;
            $name = $this->required($batch, $line, $row, 'name', 'Name');
            $code = $this->code($batch, $line, $batch->cell($row, 'code'), 32, false);
            $active = $this->active($batch, $line, $row);
            $parent = $batch->cell($row, 'parent_code') !== ''
                ? strtolower((string) $this->code($batch, $line, $batch->cell($row, 'parent_code'), 32, false))
                : mb_strtolower($batch->cell($row, 'parent_name'));
            $parent = $parent === '' ? null : $parent;

            if ($name) {
                $batch->claim($line, 'product group name', $name);

                if ($this->taken('product_groups', $company->id, 'name', $name)) {
                    $batch->add($line, 'Product group "'.$name.'" already exists.');
                }
            }

            if ($code) {
                $batch->claim($line, 'product group code', $code);

                if ($this->taken('product_groups', $company->id, 'code', $code)) {
                    $batch->add($line, 'Product group code '.$code.' already exists.');
                }
            }

            if ($name === null || $active === null) {
                continue;
            }

            $tokens = array_values(array_filter([$code ? strtolower($code) : null, mb_strtolower($name)]));
            $this->parsed[] = [
                'row' => $line,
                'name' => $name,
                'code' => $code,
                'is_active' => $active,
                'tokens' => $tokens,
                'parent' => $parent,
            ];
            $nodes[] = ['row' => $line, 'tokens' => $tokens, 'parent' => $parent];
        }

        if ($this->parsed === []) {
            return;
        }

        $resolved = ParentLinks::resolve($nodes, $existing);

        foreach ($resolved['errors'] as $error) {
            $batch->add($error['row'], $error['message']);
        }

        foreach ($this->parsed as $index => $item) {
            $link = $resolved['links'][$index];
            $this->parsed[$index]['parent_mode'] = $link['mode'];
            $this->parsed[$index]['parent_id'] = $link['id'];
            $this->parsed[$index]['parent_token'] = $link['token'];
        }
    }

    public function persist(ImportBatch $batch): int
    {
        $company = $batch->company;
        $ids = [];

        foreach (ParentLinks::order($this->parsed) as $item) {
            $parentId = $item['parent_mode'] === 'file'
                ? ($ids[$item['parent_token']] ?? null)
                : $item['parent_id'];

            $group = $company->productGroups()->create([
                'parent_id' => $parentId,
                'name' => $item['name'],
                'code' => $item['code'],
                'is_active' => $item['is_active'],
            ]);

            foreach ($item['tokens'] as $token) {
                $ids[$token] = $group->id;
            }
        }

        return count($this->parsed);
    }
}
