<?php

namespace Tally\DataExchange\Import\Definitions;

use Tally\DataExchange\Import\ImportBatch;
use Tally\Models\AccountGroup;

class LedgerImporter extends AbstractLedgerImporter
{
    public function key(): string
    {
        return 'ledgers';
    }

    public function label(): string
    {
        return 'Ledgers';
    }

    public function description(): string
    {
        return 'Creates ledgers in the current company. The account group must already exist.';
    }

    public function columns(): array
    {
        return [
            ['key' => 'name', 'required' => true, 'hint' => ''],
            ['key' => 'code', 'required' => false, 'hint' => ''],
            ['key' => 'group_code', 'required' => false, 'hint' => 'Group code or group name is required'],
            ['key' => 'group_name', 'required' => false, 'hint' => ''],
            ['key' => 'opening_balance', 'required' => false, 'hint' => '0.00'],
            ['key' => 'opening_balance_type', 'required' => false, 'hint' => 'debit or credit'],
            ['key' => 'address', 'required' => false, 'hint' => ''],
            ['key' => 'state', 'required' => false, 'hint' => ''],
            ['key' => 'phone', 'required' => false, 'hint' => ''],
            ['key' => 'email', 'required' => false, 'hint' => ''],
            ['key' => 'gstin', 'required' => false, 'hint' => ''],
            ['key' => 'gst_registration_type', 'required' => false, 'hint' => ''],
            ['key' => 'pan', 'required' => false, 'hint' => ''],
            ['key' => 'credit_limit', 'required' => false, 'hint' => ''],
            ['key' => 'credit_days', 'required' => false, 'hint' => ''],
            ['key' => 'is_active', 'required' => false, 'hint' => 'yes or no'],
        ];
    }

    public function sample(): array
    {
        return [[
            'name' => 'Cash',
            'code' => 'CASH-HO',
            'group_code' => 'CASH',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => 'yes',
        ]];
    }

    protected function resolveGroup(ImportBatch $batch, int $line, array $row): ?AccountGroup
    {
        $token = $batch->cell($row, 'group_code') !== ''
            ? $batch->cell($row, 'group_code')
            : $batch->cell($row, 'group_name');

        if ($token === '') {
            $batch->add($line, 'Group code or group name is required.');

            return null;
        }

        return $this->groupByToken($batch, $line, $token);
    }
}
