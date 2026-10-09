<?php

namespace Tally\DataExchange\Import\Definitions;

use Tally\Accounting\AccountNature;
use Tally\DataExchange\Import\ImportBatch;
use Tally\DataExchange\Import\ImportDefinition;
use Tally\DataExchange\Import\ParentLinks;
use Tally\Models\AccountGroup;

class AccountGroupImporter extends ImportDefinition
{
    public function key(): string
    {
        return 'account-groups';
    }

    public function label(): string
    {
        return 'Account Groups';
    }

    public function description(): string
    {
        return 'Adds groups under the current company. Existing groups, including the standard chart, are left unchanged.';
    }

    public function columns(): array
    {
        return [
            ['key' => 'name', 'required' => true, 'hint' => ''],
            ['key' => 'code', 'required' => false, 'hint' => 'Unique in the company'],
            ['key' => 'parent_code', 'required' => false, 'hint' => 'Existing or same-file code'],
            ['key' => 'parent_name', 'required' => false, 'hint' => 'Used when parent code is blank'],
            ['key' => 'nature', 'required' => false, 'hint' => 'Required when there is no parent'],
            ['key' => 'is_active', 'required' => false, 'hint' => 'yes or no'],
        ];
    }

    public function sample(): array
    {
        return [[
            'name' => 'Deposits',
            'code' => 'DEPOSITS',
            'parent_code' => 'CURRENT_ASSETS',
            'nature' => 'asset',
            'is_active' => 'yes',
        ]];
    }

    public function validate(ImportBatch $batch): void
    {
        $this->parsed = [];
        $company = $batch->company;

        if (! $company) {
            $batch->add(0, 'Select a company before importing account groups.');

            return;
        }

        $existing = $this->existing($company->id);
        $nodes = [];

        foreach ($batch->rows as $index => $row) {
            $line = $index + 2;
            $name = $this->required($batch, $line, $row, 'name', 'Name');
            $code = $this->code($batch, $line, $batch->cell($row, 'code'), 32, false);
            $active = $this->active($batch, $line, $row);
            $parent = $batch->cell($row, 'parent_code') !== ''
                ? strtolower($this->code($batch, $line, $batch->cell($row, 'parent_code'), 32, false) ?? '')
                : mb_strtolower($batch->cell($row, 'parent_name'));
            $parent = $parent === '' ? null : $parent;

            if ($name) {
                $batch->claim($line, 'group name', $name);

                if ($this->taken('account_groups', $company->id, 'name', $name)) {
                    $batch->add($line, 'Account group "'.$name.'" already exists.');
                }
            }

            if ($code) {
                $batch->claim($line, 'group code', $code);

                if ($this->taken('account_groups', $company->id, 'code', $code)) {
                    $batch->add($line, 'Account group code '.$code.' already exists.');
                }
            }

            $nature = null;

            if ($parent === null) {
                $raw = $batch->cell($row, 'nature');

                if ($raw === '') {
                    $batch->add($line, 'Nature is required when the group has no parent.');
                } else {
                    $nature = $this->nature($batch, $line, $raw);
                }
            } elseif ($batch->cell($row, 'nature') !== '') {
                $nature = $this->nature($batch, $line, $batch->cell($row, 'nature'));
            }

            if ($name === null || $active === null) {
                continue;
            }

            $tokens = array_values(array_filter([
                $code ? strtolower($code) : null,
                mb_strtolower($name),
            ]));

            $this->parsed[] = [
                'row' => $line,
                'name' => $name,
                'code' => $code,
                'nature' => $nature,
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

            if ($link['mode'] === 'database' && $link['id']) {
                $parentNature = AccountGroup::query()->whereKey($link['id'])->first()?->nature;
                $parentValue = $parentNature instanceof \BackedEnum ? $parentNature->value : $parentNature;

                if ($item['nature'] && $parentValue && $item['nature'] !== $parentValue) {
                    $batch->add($item['row'], 'Nature must match the parent group.');
                }

                $this->parsed[$index]['nature'] = $parentValue;
            }
        }
    }

    public function persist(ImportBatch $batch): int
    {
        $company = $batch->company;
        $order = (int) $company->accountGroups()->max('sort_order');
        $ids = [];

        foreach (ParentLinks::order($this->parsed) as $item) {
            $order++;
            $parentId = $item['parent_id'];

            if ($item['parent_mode'] === 'file') {
                $parentId = $ids[$item['parent_token']] ?? null;
                $parentNature = AccountGroup::query()->whereKey($parentId)->first()?->nature;
                $item['nature'] = $parentNature instanceof \BackedEnum ? $parentNature->value : $parentNature;
            }

            $group = $company->accountGroups()->create([
                'parent_id' => $parentId,
                'name' => $item['name'],
                'code' => $item['code'],
                'nature' => $item['nature'] ?? AccountNature::Asset->value,
                'sort_order' => $order,
                'is_system' => false,
                'is_active' => $item['is_active'],
            ]);

            foreach ($item['tokens'] as $token) {
                $ids[$token] = $group->id;
            }
        }

        return count($this->parsed);
    }

    /**
     * @return array<string, int>
     */
    private function existing(int $companyId): array
    {
        $map = [];

        AccountGroup::query()->where('company_id', $companyId)->get(['id', 'name', 'code'])->each(function (AccountGroup $group) use (&$map) {
            $map[mb_strtolower($group->name)] = $group->id;

            if ($group->code) {
                $map[strtolower($group->code)] = $group->id;
            }
        });

        return $map;
    }
}
